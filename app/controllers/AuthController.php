<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;
use Exception;

class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function showLogin(): void
    {
        if ($this->isAuth()) {
            $role = $_SESSION['user']['role'];
            $this->redirectToRoleDashboard($role);
            return;
        }

        if (!empty($_SESSION['pending_otp'])) {
            $this->redirect('verify-otp');
            return;
        }

        if (empty($_SESSION['login_captcha'])) {
            $_SESSION['login_captcha'] = (string) rand(1000, 9999);
        }

        $appConfig = require APP_PATH . '/config/app.php';
        $this->view('auth.login', [
            'title'   => 'Masuk - CicalengkaGO',
            'captcha' => $_SESSION['login_captcha']
        ], 'auth_layout');
    }

    public function refreshCaptcha(): void
    {
        $code = (string) rand(1000, 9999);
        $_SESSION['login_captcha'] = $code;
        $this->json(['success' => true, 'captcha' => $code]);
    }

    public function handleLogin(): void
    {
        $data = $this->getPost();
        $emailOrPhone = trim($data['username'] ?? $data['email'] ?? $data['phone'] ?? '');
        $password     = trim($data['password'] ?? '');
        $captcha      = trim($data['captcha'] ?? '');

        $isJsonRequest = (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
            || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'))
            || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if (empty($emailOrPhone) || empty($password)) {
            if ($isJsonRequest) {
                $this->json(['success' => false, 'message' => 'Email/No HP dan password wajib diisi.'], 400);
                return;
            }
            $_SESSION['error'] = 'Email/No HP dan password wajib diisi.';
            $this->redirect('login');
            return;
        }

        // Validate Captcha for Web requests only
        if (!$isJsonRequest) {
            $sessionCaptcha = $_SESSION['login_captcha'] ?? '';
            if (empty($captcha) || $captcha !== $sessionCaptcha) {
                $_SESSION['login_captcha'] = (string) rand(1000, 9999);
                $_SESSION['error'] = 'Kode Captcha tidak sesuai atau belum diisi. Silakan coba lagi.';
                $this->redirect('login');
                return;
            }
            $_SESSION['login_captcha'] = (string) rand(1000, 9999);
        }

        try {
            $authResult = $this->authService->login($emailOrPhone, $password);
            $user = $authResult['user'] ?? $authResult;
            $needsOtp = !empty($authResult['needs_otp']);

            if ($needsOtp) {
                if ($isJsonRequest) {
                    $this->json([
                        'success'      => true,
                        'require_otp'  => true,
                        'message'      => 'Kode OTP verifikasi telah dikirimkan via WhatsApp.',
                        'phone_masked' => $authResult['phone_masked'] ?? '',
                        'channel'      => $authResult['channel'] ?? 'whatsapp',
                        'data'         => [
                            'user_id'      => $user['id'],
                            'phone'        => $user['phone'] ?? '',
                            'role'         => $user['role'],
                            'phone_masked' => $authResult['phone_masked'] ?? '',
                            'channel'      => $authResult['channel'] ?? 'whatsapp',
                        ]
                    ]);
                    return;
                }

                $_SESSION['info'] = 'Kode verifikasi OTP telah dikirimkan via WhatsApp.';
                $this->redirect('verify-otp');
                return;
            }

            // Direct login (OTP disabled in Admin settings)
            $_SESSION['user'] = $user;

            $apiToken = $user['api_token'] ?? null;
            if (empty($apiToken)) {
                $apiToken = bin2hex(random_bytes(32));
                (new \App\Models\User())->update($user['id'], ['api_token' => $apiToken]);
                $user['api_token'] = $apiToken;
            }

            if ($isJsonRequest) {
                $this->json([
                    'success'     => true,
                    'require_otp' => false,
                    'message'     => 'Login berhasil',
                    'token'       => $apiToken,
                    'data'        => [
                        'token' => $apiToken,
                        'user'  => [
                            'id'    => $user['id'],
                            'name'  => $user['name'],
                            'email' => $user['email'],
                            'phone' => $user['phone'],
                            'role'  => $user['role']
                        ]
                    ],
                    'user'        => $user
                ]);
                return;
            }

            $_SESSION['success'] = 'Selamat datang kembali, ' . htmlspecialchars($user['name']) . '!';
            $this->redirectToRoleDashboard($user['role']);
        } catch (Exception $e) {
            if ($isJsonRequest) {
                $this->json(['success' => false, 'message' => $e->getMessage()], 400);
                return;
            }
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('login');
        }
    }

    public function showVerifyOtp(): void
    {
        if ($this->isAuth() && empty($_SESSION['pending_profile_update'])) {
            $role = $_SESSION['user']['role'];
            $this->redirectToRoleDashboard($role);
            return;
        }

        if (empty($_SESSION['pending_otp']) && empty($_SESSION['pending_profile_update'])) {
            $_SESSION['error'] = 'Silakan masuk terlebih dahulu.';
            $this->redirect('login');
            return;
        }

        $pending = $_SESSION['pending_profile_update'] ?? $_SESSION['pending_otp'];
        $email = $pending['new_email'] ?? $pending['email'];
        
        $otpMode = \App\Models\BusinessSetting::get('otp_mode', 'real');
        $demoOtp = ($otpMode === 'demo') ? ($pending['otp'] ?? '123456') : null;

        $this->view('auth.verify_otp', [
            'title'   => 'Verifikasi WhatsApp OTP - CicalengkaGO',
            'email'   => $email,
            'name'    => $pending['name'],
            'demoOtp' => $demoOtp,
            'otpMode' => $otpMode
        ], 'auth_layout');
    }

    public function handleVerifyOtp(): void
    {
        $data = $this->getPost();
        $otp = trim($data['otp'] ?? '');

        if (isset($data['otp_digit']) && is_array($data['otp_digit'])) {
            $otp = implode('', $data['otp_digit']);
        }

        $isJsonRequest = (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
            || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'))
            || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if (empty($otp) || strlen($otp) < 6) {
            if ($isJsonRequest) {
                $this->json(['success' => false, 'message' => 'Masukkan 6 digit kode OTP verifikasi.'], 400);
                return;
            }
            $_SESSION['error'] = 'Masukkan 6 digit kode OTP verifikasi.';
            $this->redirect('verify-otp');
            return;
        }

        try {
            if (!empty($_SESSION['is_password_reset_flow'])) {
                $this->authService->verifyResetOtp($otp);
                if ($isJsonRequest) {
                    $this->json(['success' => true, 'message' => 'Kode OTP terverifikasi. Silakan buat kata sandi baru.']);
                    return;
                }
                $_SESSION['success'] = 'Kode OTP terverifikasi! Silakan buat kata sandi baru Anda.';
                $this->redirect('reset-password');
                return;
            }

            $isProfileUpdate = !empty($_SESSION['pending_profile_update']);
            $isPasswordUpdate = !empty($_SESSION['pending_profile_update']['password']);
            $user = $this->authService->verifyOtp($otp);

            $apiToken = $user['api_token'] ?? null;
            if (empty($apiToken)) {
                $apiToken = bin2hex(random_bytes(32));
                (new \App\Models\User())->update($user['id'], ['api_token' => $apiToken]);
                $user['api_token'] = $apiToken;
            }

            if ($isJsonRequest) {
                $this->json([
                    'success' => true,
                    'message' => 'Verifikasi OTP berhasil',
                    'token'   => $apiToken,
                    'data'    => [
                        'token' => $apiToken,
                        'user'  => [
                            'id'    => $user['id'],
                            'name'  => $user['name'],
                            'email' => $user['email'],
                            'phone' => $user['phone'],
                            'role'  => $user['role']
                        ]
                    ],
                    'user'    => $user
                ]);
                return;
            }

            if ($isPasswordUpdate) {
                $_SESSION['success'] = 'Kata sandi dan profil berhasil diverifikasi & diperbarui!';
            } elseif ($isProfileUpdate) {
                $_SESSION['success'] = 'Alamat WhatsApp & profil berhasil diverifikasi!';
            } else {
                $_SESSION['success'] = 'Verifikasi OTP berhasil!';
            }

            $this->redirectToRoleDashboard($user['role'], $isProfileUpdate);
        } catch (Exception $e) {
            if ($isJsonRequest) {
                $this->json(['success' => false, 'message' => $e->getMessage()], 400);
                return;
            }
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('verify-otp');
        }
    }

    public function handleResendOtp(): void
    {
        $isJsonRequest = (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
            || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'))
            || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        try {
            $newOtp = $this->authService->resendOtp();
            if ($isJsonRequest) {
                $this->json([
                    'success' => true,
                    'message' => 'Kode OTP baru telah dikirimkan via WhatsApp.',
                    'phone_masked' => $_SESSION['otp_phone_masked'] ?? '',
                ]);
                return;
            }
            $_SESSION['success'] = 'Kode OTP baru telah dikirimkan via WhatsApp.';
        } catch (Exception $e) {
            if ($isJsonRequest) {
                $this->json(['success' => false, 'message' => $e->getMessage()], 400);
                return;
            }
            $_SESSION['error'] = $e->getMessage();
        }
        $this->redirect('verify-otp');
    }

    public function showRegister(): void
    {
        if ($this->isAuth()) {
            $this->redirect('');
            return;
        }

        $this->view('auth.register', ['title' => 'Daftar Akun Baru - CicalengkaGO'], 'auth_layout');
    }

    public function handleRegister(): void
    {
        $data = $this->getPost();
        $errors = validate_required($data, ['name', 'email', 'phone', 'password']);

        if (!empty($errors)) {
            $_SESSION['error'] = implode(' ', $errors);
            $this->redirect('register');
            return;
        }

        try {
            $this->authService->registerCustomer($data);
            $_SESSION['info'] = 'Pendaftaran berhasil! Kode verifikasi OTP telah dikirimkan via WhatsApp.';
            $this->redirect('verify-otp');
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('register');
        }
    }

    public function showForgotPassword(): void
    {
        if ($this->isAuth()) {
            $this->redirect('');
            return;
        }
        $this->view('auth.forgot_password', ['title' => 'Lupa Kata Sandi - CicalengkaGO'], 'auth_layout');
    }

    public function handleForgotPassword(): void
    {
        $data = $this->getPost();
        $emailOrPhone = trim($data['username'] ?? $data['email'] ?? '');

        if (empty($emailOrPhone)) {
            $_SESSION['error'] = 'Masukkan nomor WhatsApp atau Email terdaftar Anda.';
            $this->redirect('forgot-password');
            return;
        }

        try {
            $this->authService->requestPasswordReset($emailOrPhone);
            $_SESSION['pending_otp'] = $_SESSION['pending_reset_otp'];
            $_SESSION['is_password_reset_flow'] = true;
            $_SESSION['info'] = 'Kode OTP reset password telah dikirimkan via WhatsApp.';
            $this->redirect('verify-otp');
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('forgot-password');
        }
    }

    public function showResetPassword(): void
    {
        if (empty($_SESSION['reset_password_verified']) || empty($_SESSION['pending_reset_otp'])) {
            $_SESSION['error'] = 'Silakan lakukan verifikasi OTP terlebih dahulu.';
            $this->redirect('forgot-password');
            return;
        }

        $this->view('auth.reset_password', ['title' => 'Buat Kata Sandi Baru - CicalengkaGO'], 'auth_layout');
    }

    public function handleResetPassword(): void
    {
        $data = $this->getPost();
        $newPassword = $data['password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        try {
            $this->authService->executePasswordReset($newPassword, $confirmPassword);
            unset($_SESSION['is_password_reset_flow']);
            $_SESSION['success'] = 'Kata sandi Anda berhasil diperbarui! Silakan masuk dengan kata sandi baru.';
            $this->redirect('login');
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('reset-password');
        }
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['user'], $_SESSION['pending_otp'], $_SESSION['pending_profile_update'], $_SESSION['pending_reset_otp'], $_SESSION['reset_password_verified'], $_SESSION['is_password_reset_flow']);
        session_destroy();
        $appConfig = require APP_PATH . '/config/app.php';
        $this->redirect($appConfig['public_url'] . '/login');
    }

    private function redirectToRoleDashboard(string $role, bool $isProfileUpdate = false): void
    {
        if ($isProfileUpdate) {
            match ($role) {
                'admin'        => $this->redirect('admin/profile'),
                'vendor'       => $this->redirect('vendor/profile'),
                'delivery_man' => $this->redirect('delivery/profile'),
                default        => $this->redirect('profile')
            };
            return;
        }

        match ($role) {
            'admin'        => $this->redirect('admin'),
            'vendor'       => $this->redirect('vendor'),
            'delivery_man' => $this->redirect('delivery'),
            default        => $this->redirect('')
        };
    }
}
