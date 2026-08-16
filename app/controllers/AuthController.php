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

        $appConfig = require APP_PATH . '/config/app.php';
        $this->view('auth.login', ['title' => 'Masuk - CicalengkaGO'], 'auth_layout');
    }

    public function handleLogin(): void
    {
        $data = $this->getPost();
        $emailOrPhone = trim($data['username'] ?? $data['email'] ?? '');
        $password = trim($data['password'] ?? '');

        if (empty($emailOrPhone) || empty($password)) {
            $_SESSION['error'] = 'Email/No HP dan password wajib diisi.';
            $this->redirect('login');
            return;
        }

        try {
            $user = $this->authService->login($emailOrPhone, $password);

            // If pending_otp is set, OTP verification is required
            if (!empty($_SESSION['pending_otp'])) {
                $_SESSION['info'] = 'Kode verifikasi OTP telah dikirimkan ke email Anda.';
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
            'title'   => 'Verifikasi Email OTP - CicalengkaGO',
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
            $isProfileUpdate = !empty($_SESSION['pending_profile_update']);
            $user = $this->authService->verifyOtp($otp);
            $_SESSION['success'] = 'Email & profil berhasil diverifikasi!';
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
            $_SESSION['success'] = 'Kode OTP baru telah dikirim ke email Anda.';
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
            $_SESSION['info'] = 'Pendaftaran berhasil! Kode verifikasi OTP telah dikirim ke email Anda.';
            $this->redirect('verify-otp');
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('register');
        }
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['user'], $_SESSION['pending_otp'], $_SESSION['pending_profile_update']);
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
