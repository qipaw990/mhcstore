<?php
namespace App\Services;

use App\Core\Database;
use App\Models\User;
use App\Models\Wallet;
use App\Models\BusinessSetting;
use Exception;

class AuthService
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function login(string $emailOrPhone, string $password): array
    {
        $sql = "SELECT * FROM `users` WHERE (`email` = ? OR `phone` = ?) AND `is_active` = 1 LIMIT 1";
        $user = Database::fetchOne($sql, [$emailOrPhone, $emailOrPhone]);

        if (!$user || !password_verify($password, $user['password'])) {
            throw new Exception("Email/No. HP atau password salah.");
        }

        $role = $user['role'] ?? 'customer';

        // Auto-detect vendor role if user owns a store in stores table
        if ($role !== 'vendor' && $role !== 'admin' && $role !== 'delivery_man') {
            $storeCheck = Database::fetchOne("SELECT id, name, status FROM `stores` WHERE `vendor_id` = ? LIMIT 1", [$user['id']]);
            if ($storeCheck) {
                $role = 'vendor';
                $user['role'] = 'vendor';
                Database::update('users', ['role' => 'vendor'], 'id = ?', [$user['id']]);
            }
        }

        // Auto-detect delivery_man role if profile exists
        if ($role !== 'delivery_man' && $role !== 'admin' && $role !== 'vendor') {
            $dmCheck = Database::fetchOne("SELECT id FROM `delivery_men` WHERE `user_id` = ? LIMIT 1", [$user['id']]);
            if ($dmCheck) {
                $role = 'delivery_man';
                $user['role'] = 'delivery_man';
                Database::update('users', ['role' => 'delivery_man'], 'id = ?', [$user['id']]);
            }
        }

        // If vendor, check store approval status
        if ($role === 'vendor') {
            $store = Database::fetchOne("SELECT id, name, status FROM `stores` WHERE `vendor_id` = ? LIMIT 1", [$user['id']]);
            if ($store && $store['status'] === 'pending') {
                throw new Exception("Pendaftaran Toko '{$store['name']}' sedang dalam proses review oleh Tim Admin CicalengkaGO. Akun akan aktif setelah disetujui.");
            }
            if ($store && $store['status'] === 'suspended') {
                throw new Exception("Akun Toko '{$store['name']}' saat ini ditangguhkan oleh Admin. Silakan hubungi CS CicalengkaGO (0851-5839-7756).");
            }
        }

        // Determine if OTP is required based on role settings
        $isAdminRole    = in_array($role, ['admin', 'vendor', 'delivery_man']);
        $isCustomer     = ($role === 'customer');

        $requireAdminOtp    = BusinessSetting::get('require_login_otp', '0') === '1';
        $requireCustomerOtp = BusinessSetting::get('require_customer_otp', '0') === '1';

        $needsOtp = ($isAdminRole && $requireAdminOtp) || ($isCustomer && $requireCustomerOtp);

        if (!$needsOtp) {
            // Direct login — no OTP required
            $_SESSION['user'] = $user;
            unset($_SESSION['pending_otp']);
            return [
                'user'      => $user,
                'needs_otp' => false,
            ];
        }

        $otpMode = BusinessSetting::get('otp_mode', 'real');
        $isDemo = ($otpMode === 'demo');

        // Fetch driver phone if missing in users table
        $phone = trim($user['phone'] ?? '');
        if (empty($phone) && $role === 'delivery_man') {
            $dm = Database::fetchOne("SELECT phone FROM `delivery_men` WHERE `user_id` = ? LIMIT 1", [$user['id']]);
            if (!empty($dm['phone'])) {
                $phone = trim($dm['phone']);
                $user['phone'] = $phone;
            }
        }

        // Generate 6-digit OTP code for verification
        $otp = $isDemo ? '123456' : sprintf("%06d", rand(100000, 999999));

        $_SESSION['pending_otp'] = [
            'user_id'    => $user['id'],
            'name'       => $user['name'],
            'email'      => $user['email'],
            'phone'      => $phone,
            'role'       => $user['role'],
            'otp'        => $otp,
            'expires_at' => time() + 600, // 10 minutes
        ];
        $_SESSION['otp_last_sent'] = time();

        // Always set WhatsApp channel details for UI consistency
        $otpChannelConfig = BusinessSetting::get('otp_verification_channel', 'whatsapp_only');
        if ($otpChannelConfig !== 'email_only') {
            $_SESSION['otp_channel'] = 'whatsapp';
            $_SESSION['otp_phone_masked'] = !empty($phone) 
                ? preg_replace('/(?<=.{4}).(?=.{4})/', '*', $phone) 
                : (!empty($user['email']) ? $user['email'] : 'WhatsApp Terdaftar');
        } else {
            $_SESSION['otp_channel'] = 'email';
        }

        // Send OTP: dispatch via WhatsApp
        if (!$isDemo) {
            $this->dispatchOtp($phone, $user['email'], $user['name'], $otp);
        }

        return [
            'user'         => $user,
            'needs_otp'    => true,
            'phone'        => $phone,
            'phone_masked' => $_SESSION['otp_phone_masked'] ?? $phone,
            'channel'      => $_SESSION['otp_channel'] ?? 'whatsapp',
        ];
    }

    public function verifyOtp(string $inputOtp): array
    {
        $otpMode = BusinessSetting::get('otp_mode', 'real');
        $isDemo = ($otpMode === 'demo');

        // Handle pending profile email update verification
        if (!empty($_SESSION['pending_profile_update'])) {
            $pending = $_SESSION['pending_profile_update'];

            if (time() > $pending['expires_at']) {
                throw new Exception("Kode OTP telah kedaluwarsa. Silakan minta kode baru.");
            }

            $isValid = (trim($inputOtp) === (string)$pending['otp']) || ($isDemo && trim($inputOtp) === '123456');
            if (!$isValid) {
                throw new Exception("Kode OTP yang Anda masukkan tidak cocok.");
            }

            $userId = $pending['user_id'];
            $apiToken = bin2hex(random_bytes(32));

            $updateData = [
                'name'              => $pending['name'],
                'email'             => $pending['new_email'],
                'phone'             => $pending['phone'],
                'email_verified_at' => date('Y-m-d H:i:s'),
                'api_token'         => $apiToken
            ];
            if (!empty($pending['password'])) {
                $updateData['password'] = $pending['password'];
            }

            $this->userModel->update($userId, $updateData);

            // Synchronize phone to related driver profile if applicable
            $user = $this->userModel->find($userId);
            if (($user['role'] ?? '') === 'delivery_man') {
                Database::execute("UPDATE `delivery_men` SET `phone` = ? WHERE `user_id` = ?", [$pending['phone'], $userId]);
            }

            unset($_SESSION['pending_profile_update'], $_SESSION['pending_otp'], $_SESSION['otp_channel'], $_SESSION['otp_phone_masked']);
            $_SESSION['user'] = $user;

            return $user;
        }

        if (empty($_SESSION['pending_otp'])) {
            throw new Exception("Sesi verifikasi tidak ditemukan atau telah kedaluwarsa. Silakan masuk kembali.");
        }

        $pending = $_SESSION['pending_otp'];

        if (time() > $pending['expires_at']) {
            throw new Exception("Kode OTP telah kedaluwarsa. Silakan klik 'Kirim Ulang OTP'.");
        }

        $isValid = (trim($inputOtp) === (string)$pending['otp']) || ($isDemo && trim($inputOtp) === '123456');
        if (!$isValid) {
            throw new Exception("Kode OTP yang Anda masukkan tidak cocok.");
        }

        // Fetch fresh user data
        $user = $this->userModel->find($pending['user_id']);
        if (!$user) {
            throw new Exception("Pengguna tidak ditemukan.");
        }

        // Mark email as verified & generate fresh API token
        $apiToken = bin2hex(random_bytes(32));
        $this->userModel->update($user['id'], [
            'email_verified_at' => date('Y-m-d H:i:s'),
            'api_token'         => $apiToken
        ]);

        $user['email_verified_at'] = date('Y-m-d H:i:s');
        $user['api_token'] = $apiToken;

        // Clear OTP state & set active session
        unset($_SESSION['pending_otp']);
        $_SESSION['user'] = $user;

        return $user;
    }

    public function resendOtp(): string
    {
        // Rate limiting flood protection (60 detik cooldown)
        if (!empty($_SESSION['otp_last_sent']) && (time() - $_SESSION['otp_last_sent']) < 60) {
            $remaining = 60 - (time() - $_SESSION['otp_last_sent']);
            throw new Exception("Harap tunggu {$remaining} detik sebelum meminta kirim ulang kode OTP.");
        }

        $otpMode = BusinessSetting::get('otp_mode', 'real');
        $isDemo = ($otpMode === 'demo');

        if (!empty($_SESSION['pending_profile_update'])) {
            $newOtp = $isDemo ? '123456' : sprintf("%06d", rand(100000, 999999));
            $_SESSION['pending_profile_update']['otp'] = $newOtp;
            $_SESSION['pending_profile_update']['expires_at'] = time() + 600;
            if (isset($_SESSION['pending_otp'])) {
                $_SESSION['pending_otp']['otp'] = $newOtp;
            }

            $_SESSION['otp_last_sent'] = time();

            if (!$isDemo) {
                $this->dispatchOtp(
                    $_SESSION['pending_profile_update']['phone'] ?? '',
                    $_SESSION['pending_profile_update']['new_email'],
                    $_SESSION['pending_profile_update']['name'],
                    $newOtp
                );
            }

            return $newOtp;
        }

        if (empty($_SESSION['pending_otp'])) {
            throw new Exception("Sesi verifikasi tidak ditemukan. Silakan masuk kembali.");
        }

        $newOtp = $isDemo ? '123456' : sprintf("%06d", rand(100000, 999999));
        $_SESSION['pending_otp']['otp'] = $newOtp;
        $_SESSION['pending_otp']['expires_at'] = time() + 600;

        $_SESSION['otp_last_sent'] = time();

        if (!$isDemo) {
            $this->dispatchOtp(
                $_SESSION['pending_otp']['phone'] ?? '',
                $_SESSION['pending_otp']['email'],
                $_SESSION['pending_otp']['name'],
                $newOtp
            );
        }

        return $newOtp;
    }

    public function registerCustomer(array $data): array
    {
        $existing = Database::fetchOne("SELECT id FROM users WHERE email = ? OR phone = ? LIMIT 1", [$data['email'], $data['phone']]);
        if ($existing) {
            throw new Exception("Email atau Nomor HP sudah terdaftar.");
        }

        $apiToken = bin2hex(random_bytes(32));
        $userId = $this->userModel->create([
            'role'      => 'customer',
            'name'      => sanitize($data['name']),
            'email'     => sanitize($data['email']),
            'phone'     => sanitize($data['phone']),
            'password'  => password_hash($data['password'], PASSWORD_BCRYPT),
            'avatar'    => 'assets/images/users/default.png',
            'is_active' => 1,
            'api_token' => $apiToken
        ]);

        // Init wallet with 0 initial balance
        (new Wallet())->create([
            'user_id'          => $userId,
            'user_type'        => 'customer',
            'balance'          => 0.00,
            'total_earned'     => 0.00,
            'total_withdrawn'  => 0.00
        ]);

        $user = $this->userModel->find($userId);

        // Initiate OTP for email verification
        $otpMode = BusinessSetting::get('otp_mode', 'real');
        $isDemo = ($otpMode === 'demo');

        $otp = $isDemo ? '123456' : sprintf("%06d", rand(100000, 999999));
        $_SESSION['pending_otp'] = [
            'user_id'    => $user['id'],
            'name'       => $user['name'],
            'email'      => $user['email'],
            'phone'      => $user['phone'] ?? '',
            'role'       => $user['role'],
            'otp'        => $otp,
            'expires_at' => time() + 600
        ];
        $_SESSION['otp_last_sent'] = time();

        if (!$isDemo) {
            $this->dispatchOtp($user['phone'] ?? '', $user['email'], $user['name'], $otp);
        }

        return $user;
    }

    public function registerVendor(array $data): array
    {
        $existing = Database::fetchOne("SELECT id FROM users WHERE email = ? OR phone = ? LIMIT 1", [$data['email'], $data['phone']]);
        if ($existing) {
            throw new Exception("Email atau Nomor WhatsApp pemilik sudah terdaftar pada akun lain.");
        }

        $apiToken = bin2hex(random_bytes(32));
        $userId = $this->userModel->create([
            'role'      => 'vendor',
            'name'      => sanitize($data['name']),
            'email'     => sanitize($data['email']),
            'phone'     => sanitize($data['phone']),
            'password'  => password_hash($data['password'], PASSWORD_BCRYPT),
            'avatar'    => 'assets/images/users/default.png',
            'is_active' => 1,
            'api_token' => $apiToken
        ]);

        // Init vendor wallet
        (new Wallet())->create([
            'user_id'         => $userId,
            'user_type'       => 'vendor',
            'balance'         => 0.00,
            'total_earned'    => 0.00,
            'total_withdrawn' => 0.00
        ]);

        // Create store with status 'pending'
        $storeName = sanitize($data['store_name'] ?? $data['name'] . ' Store');
        $storePhone = sanitize($data['store_phone'] ?? $data['phone']);
        $storeAddress = sanitize($data['store_address'] ?? 'Kecamatan Cicalengka, Kab. Bandung');
        $moduleId = !empty($data['module_id']) ? (int)$data['module_id'] : 1;
        $zoneId = !empty($data['zone_id']) ? (int)$data['zone_id'] : 1;
        $lat = !empty($data['latitude']) ? (float)$data['latitude'] : -6.9840;
        $lng = !empty($data['longitude']) ? (float)$data['longitude'] : 107.8340;

        // Handle Photo Uploads (KTP, Logo, Foto Toko)
        $logoPath = $data['logo'] ?? 'assets/images/stores/default.jpg';
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $up = upload_image($_FILES['logo'], 'stores');
            if ($up) $logoPath = $up;
        }

        $coverPath = $data['cover_photo'] ?? 'assets/images/stores/default_cover.jpg';
        if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] === UPLOAD_ERR_OK) {
            $up = upload_image($_FILES['cover_photo'], 'stores');
            if ($up) $coverPath = $up;
        }

        $ktpPath = $data['identity_image'] ?? null;
        if (isset($_FILES['identity_image']) && $_FILES['identity_image']['error'] === UPLOAD_ERR_OK) {
            $up = upload_image($_FILES['identity_image'], 'ktp');
            if ($up) $ktpPath = $up;
        }

        $storeId = (new \App\Models\Store())->create([
            'vendor_id'      => $userId,
            'module_id'      => $moduleId,
            'zone_id'        => $zoneId,
            'name'           => $storeName,
            'phone'          => $storePhone,
            'email'          => sanitize($data['email']),
            'logo'           => $logoPath,
            'cover_photo'    => $coverPath,
            'identity_image' => $ktpPath,
            'address'        => $storeAddress,
            'latitude'       => $lat,
            'longitude'      => $lng,
            'minimum_order'  => 10000.00,
            'delivery_time'  => '20-30 Menit',
            'is_open'        => 0,
            'status'         => 'pending', // Under admin review
            'rating'         => 5.0,
            'reviews_count'  => 0,
            'order_count'    => 0
        ]);

        $store = (new \App\Models\Store())->find($storeId);
        $user = $this->userModel->find($userId);

        return [
            'user'    => $user,
            'store'   => $store,
            'status'  => 'pending',
            'message' => 'Pendaftaran berhasil! Akun toko Anda sedang dalam peninjauan oleh Tim Admin CicalengkaGO.'
        ];
    }

    /**
     * Dispatch Kode OTP sesuai preferensi channel verifikasi yang diatur di Admin:
     * - whatsapp_primary : Kirim WA utama -> Fallback ke Email
     * - email_primary    : Kirim Email utama -> Fallback ke WA
     * - whatsapp_only   : Kirim via WA saja
     * - email_only      : Kirim via Email saja
     */
    private function dispatchOtp(string $phone, string $email, string $name, string $otp): void
    {
        $channel  = BusinessSetting::get('otp_verification_channel', 'whatsapp_only');
        $hasPhone = !empty(trim($phone));
        $hasEmail = !empty(trim($email));
        $cleanPhone = trim($phone);

        switch ($channel) {
            case 'email_only':
                if ($hasEmail) {
                    $_SESSION['otp_channel'] = 'email';
                    EmailService::sendOtpEmail($email, $name, $otp);
                    error_log("[AuthService] OTP sent via Email-Only to {$email}");
                }
                break;

            case 'whatsapp_only':
                $_SESSION['otp_channel'] = 'whatsapp';
                $_SESSION['otp_phone_masked'] = $hasPhone ? preg_replace('/(?<=.{4}).(?=.{4})/', '*', $cleanPhone) : 'WhatsApp Terdaftar';
                if ($hasPhone) {
                    try {
                        $wa = new WhatsAppService();
                        if ($wa->sendOtp($cleanPhone, $name, $otp)) {
                            error_log("[AuthService] OTP sent via WhatsApp-Only to {$cleanPhone}");
                        } else {
                            error_log("[AuthService] WhatsApp-Only OTP failed for {$cleanPhone}");
                        }
                    } catch (\Throwable $e) {
                        error_log("[AuthService] WhatsApp-Only error: " . $e->getMessage());
                    }
                }
                break;

            case 'email_primary':
                $sentEmail = false;
                if ($hasEmail) {
                    try {
                        EmailService::sendOtpEmail($email, $name, $otp);
                        $_SESSION['otp_channel'] = 'email';
                        $sentEmail = true;
                        error_log("[AuthService] OTP sent via Email (Primary) to {$email}");
                    } catch (\Throwable $e) {
                        error_log("[AuthService] Email Primary failed: " . $e->getMessage());
                    }
                }
                if (!$sentEmail && $hasPhone) {
                    try {
                        $wa = new WhatsAppService();
                        if ($wa->isReady() && $wa->sendOtp($cleanPhone, $name, $otp)) {
                            $_SESSION['otp_channel'] = 'whatsapp';
                            $_SESSION['otp_phone_masked'] = preg_replace('/(?<=.{4}).(?=.{4})/', '*', $cleanPhone);
                            error_log("[AuthService] OTP sent via WhatsApp (Fallback) to {$cleanPhone}");
                        }
                    } catch (\Throwable $e) {
                        error_log("[AuthService] WhatsApp Fallback error: " . $e->getMessage());
                    }
                }
                break;

            case 'whatsapp_primary':
            default:
                $sentWa = false;
                if ($hasPhone) {
                    try {
                        $wa = new WhatsAppService();
                        if ($wa->isReady()) {
                            if ($wa->sendOtp($cleanPhone, $name, $otp)) {
                                $_SESSION['otp_channel'] = 'whatsapp';
                                $_SESSION['otp_phone_masked'] = preg_replace('/(?<=.{4}).(?=.{4})/', '*', $cleanPhone);
                                $sentWa = true;
                                error_log("[AuthService] OTP sent via WhatsApp (Primary) to {$cleanPhone}");
                            }
                        }
                    } catch (\Throwable $e) {
                        error_log("[AuthService] WhatsApp Primary error: " . $e->getMessage());
                    }
                }
                if (!$sentWa && $hasEmail) {
                    $_SESSION['otp_channel'] = 'email';
                    EmailService::sendOtpEmail($email, $name, $otp);
                    error_log("[AuthService] OTP sent via Email (Fallback) to {$email}");
                }
                break;
        }
    }

    public function requestPasswordReset(string $emailOrPhone): void
    {
        $sql = "SELECT * FROM `users` WHERE (`email` = ? OR `phone` = ?) AND `is_active` = 1 LIMIT 1";
        $user = Database::fetchOne($sql, [$emailOrPhone, $emailOrPhone]);

        if (!$user) {
            throw new Exception("Nomor WhatsApp atau Email tidak terdaftar dalam sistem.");
        }

        $otpMode = BusinessSetting::get('otp_mode', 'real');
        $isDemo = ($otpMode === 'demo');
        $otp = $isDemo ? '123456' : sprintf("%06d", rand(100000, 999999));

        $_SESSION['pending_reset_otp'] = [
            'user_id'    => $user['id'],
            'name'       => $user['name'],
            'email'      => $user['email'],
            'phone'      => $user['phone'] ?? '',
            'otp'        => $otp,
            'expires_at' => time() + 600,
        ];
        $_SESSION['otp_last_sent'] = time();

        $this->dispatchOtp($user['phone'] ?? '', $user['email'] ?? '', $user['name'], $otp);
    }

    public function verifyResetOtp(string $otp): void
    {
        $pending = $_SESSION['pending_reset_otp'] ?? null;
        if (!$pending) {
            throw new Exception("Sesi permintaan reset password telah kedaluwarsa. Silakan coba lagi.");
        }

        if (time() > $pending['expires_at']) {
            unset($_SESSION['pending_reset_otp']);
            throw new Exception("Kode OTP telah kedaluwarsa. Silakan minta kode baru.");
        }

        if ($otp !== $pending['otp']) {
            throw new Exception("Kode OTP yang Anda masukkan salah.");
        }

        $_SESSION['reset_password_verified'] = true;
    }

    public function executePasswordReset(string $newPassword, string $confirmPassword): void
    {
        $pending = $_SESSION['pending_reset_otp'] ?? null;
        $verified = $_SESSION['reset_password_verified'] ?? false;

        if (!$pending || !$verified) {
            throw new Exception("Sesi reset password tidak valid. Silakan ulangi langkah pertama.");
        }

        if (strlen($newPassword) < 6) {
            throw new Exception("Kata sandi minimal 6 karakter.");
        }

        if ($newPassword !== $confirmPassword) {
            throw new Exception("Konfirmasi kata sandi tidak cocok.");
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $sql = "UPDATE `users` SET `password` = ?, `updated_at` = NOW() WHERE `id` = ?";
        Database::query($sql, [$hash, $pending['user_id']]);

        unset($_SESSION['pending_reset_otp'], $_SESSION['reset_password_verified']);
    }
}
