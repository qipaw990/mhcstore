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
        $emailOrPhone = trim($data['username'] ?? $data['email'] ?? '');
        $password     = trim($data['password'] ?? '');
        $captcha      = trim($data['captcha'] ?? '');

        if (empty($emailOrPhone) || empty($password)) {
            $_SESSION['error'] = 'Email/No HP dan password wajib diisi.';
            $this->redirect('login');
            return;
        }

        // Validate Captcha
        $sessionCaptcha = $_SESSION['login_captcha'] ?? '';
        if (empty($captcha) || $captcha !== $sessionCaptcha) {
            $_SESSION['login_captcha'] = (string) rand(1000, 9999); // Acak ulang captcha jika salah
            $_SESSION['error'] = 'Kode Captcha tidak sesuai. Silakan coba lagi.';
            $this->redirect('login');
            return;
        }

        // Reset captcha setelah validasi berhasil
        $_SESSION['login_captcha'] = (string) rand(1000, 9999);

        try {
            $user = $this->authService->login($emailOrPhone, $password);

            // If pending_otp is set, OTP verification is required
            if (!empty($_SESSION['pending_otp'])) {
                $_SESSION['info'] = 'Kode verifikasi OTP telah dikirimkan.';
                $this->redirect('verify-otp');
                return;
            }

            // OTP not required — session already set in AuthService, go straight to dashboard
            $_SESSION['success'] = 'Selamat datang kembali, ' . htmlspecialchars($user['name']) . '!';
            $this->redirectToRoleDashboard($user['role']);
        } catch (Exception $e) {
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

        if (empty($otp) || strlen($otp) < 6) {
            $_SESSION['error'] = 'Masukkan 6 digit kode OTP verifikasi.';
            $this->redirect('verify-otp');
            return;
        }

        try {
            if (!empty($_SESSION['is_password_reset_flow'])) {
                $this->authService->verifyResetOtp($otp);
                $_SESSION['success'] = 'Kode OTP terverifikasi! Silakan buat kata sandi baru Anda.';
                $this->redirect('reset-password');
                return;
            }

            $isProfileUpdate = !empty($_SESSION['pending_profile_update']);
            $isPasswordUpdate = !empty($_SESSION['pending_profile_update']['password']);
            $user = $this->authService->verifyOtp($otp);

            if ($isPasswordUpdate) {
                $_SESSION['success'] = 'Kata sandi dan profil berhasil diverifikasi & diperbarui!';
            } elseif ($isProfileUpdate) {
                $_SESSION['success'] = 'Alamat WhatsApp & profil berhasil diverifikasi!';
            } else {
                $_SESSION['success'] = 'Verifikasi OTP berhasil!';
            }

            $this->redirectToRoleDashboard($user['role'], $isProfileUpdate);
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('verify-otp');
        }
    }

    public function handleResendOtp(): void
    {
        try {
            $newOtp = $this->authService->resendOtp();
            $_SESSION['success'] = 'Kode OTP baru telah dikirimkan via WhatsApp.';
        } catch (Exception $e) {
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
