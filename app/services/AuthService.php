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
     * Dispatch Kode OTP sesuai preferensi channel verifikasi yang diatur di Admin:
     * - whatsapp_primary : Kirim WA utama -> Fallback ke Email
     * - email_primary    : Kirim Email utama -> Fallback ke WA
     * - whatsapp_only   : Kirim via WA saja
     * - email_only      : Kirim via Email saja
     */
    private function dispatchOtp(string $phone, string $email, string $name, string $otp): void
    {
        $channel  = BusinessSetting::get('otp_verification_channel', 'whatsapp_primary');
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
                if ($hasPhone) {
                    try {
                        $wa = new WhatsAppService();
                        if ($wa->sendOtp($cleanPhone, $name, $otp)) {
                            $_SESSION['otp_channel'] = 'whatsapp';
                            $_SESSION['otp_phone_masked'] = preg_replace('/(?<=.{4}).(?=.{4})/', '*', $cleanPhone);
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
}
