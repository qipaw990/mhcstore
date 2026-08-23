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

        $role = $user['role'];

        // Determine if OTP is required based on role settings
        $isAdminRole    = in_array($role, ['admin', 'vendor', 'delivery_man']);
        $isCustomer     = ($role === 'customer');

        $requireAdminOtp    = BusinessSetting::get('require_login_otp', '1') === '1';
        $requireCustomerOtp = BusinessSetting::get('require_customer_otp', '0') === '1';

        $needsOtp = ($isAdminRole && $requireAdminOtp) || ($isCustomer && $requireCustomerOtp);

        if (!$needsOtp) {
            // Direct login — no OTP required
            $_SESSION['user'] = $user;
            return $user;
        }

        $otpMode = BusinessSetting::get('otp_mode', 'real');
        $isDemo = ($otpMode === 'demo');

        // Generate 6-digit OTP code for email verification
        $otp = $isDemo ? '123456' : sprintf("%06d", rand(100000, 999999));

        $_SESSION['pending_otp'] = [
            'user_id'    => $user['id'],
            'name'       => $user['name'],
            'email'      => $user['email'],
            'phone'      => $user['phone'] ?? '',
            'role'       => $user['role'],
            'otp'        => $otp,
            'expires_at' => time() + 600, // 10 minutes
        ];
        $_SESSION['otp_last_sent'] = time();

        // Send OTP: prioritaskan WhatsApp jika tersedia, fallback ke Email
        if (!$isDemo) {
            $this->dispatchOtp($user['phone'] ?? '', $user['email'], $user['name'], $otp);
        }

        return $user;
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

            $user = $this->userModel->find($userId);

            unset($_SESSION['pending_profile_update'], $_SESSION['pending_otp']);
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

        // Init wallet with welcome bonus
        (new Wallet())->create([
            'user_id'          => $userId,
            'user_type'        => 'customer',
            'balance'          => 25000.00, // Welcome gift Rp 25.000
            'total_earned'     => 25000.00,
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

    /**
     * Kirim OTP via WhatsApp (utama) atau Email (fallback)
     * Jika user punya nomor HP dan WA gateway aktif → kirim WA
     * Jika tidak → kirim Email
     */
    private function dispatchOtp(string $phone, string $email, string $name, string $otp): void
    {
        $waEnabled = BusinessSetting::get('whatsapp_otp_enabled', '1') === '1';

        if ($waEnabled && !empty(trim($phone))) {
            try {
                $wa = new WhatsAppService();
                if ($wa->isReady()) {
                    $sent = $wa->sendOtp($phone, $name, $otp);
                    if ($sent) {
                        // WA berhasil, simpan channel yang digunakan di session
                        $_SESSION['otp_channel'] = 'whatsapp';
                        $_SESSION['otp_phone_masked'] = preg_replace('/(?<=.{4}).(?=.{4})/', '*', $phone);
                        error_log("[AuthService] OTP sent via WhatsApp to {$phone}");
                        return; // sukses via WA, tidak perlu email
                    }
                }
            } catch (\Throwable $e) {
                error_log("[AuthService] WhatsApp OTP failed: " . $e->getMessage());
            }
        }

        // Fallback: kirim via Email
        $_SESSION['otp_channel'] = 'email';
        EmailService::sendOtpEmail($email, $name, $otp);
        error_log("[AuthService] OTP sent via Email to {$email}");
    }

