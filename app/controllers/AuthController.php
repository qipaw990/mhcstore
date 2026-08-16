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
            $this->authService->login($emailOrPhone, $password);
            $_SESSION['info'] = 'Kode verifikasi OTP telah dikirimkan ke email Anda.';
            $this->redirect('verify-otp');
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('login');
        }
    }

    public function showVerifyOtp(): void
    {
        if ($this->isAuth()) {
            $role = $_SESSION['user']['role'];
            $this->redirectToRoleDashboard($role);
            return;
        }

        if (empty($_SESSION['pending_otp'])) {
            $_SESSION['error'] = 'Silakan masuk terlebih dahulu.';
            $this->redirect('login');
            return;
        }

        $pending = $_SESSION['pending_otp'];
        $this->view('auth.verify_otp', [
            'title'   => 'Verifikasi Email OTP - CicalengkaGO',
            'email'   => $pending['email'],
            'name'    => $pending['name'],
            'demoOtp' => $pending['otp']
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
            $user = $this->authService->verifyOtp($otp);
            $_SESSION['success'] = 'Email berhasil diverifikasi! Selamat datang di CicalengkaGO.';
            $this->redirectToRoleDashboard($user['role']);
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
        unset($_SESSION['user'], $_SESSION['pending_otp']);
        session_destroy();
        $appConfig = require APP_PATH . '/config/app.php';
        $this->redirect($appConfig['url'] . '/login');
    }

    private function redirectToRoleDashboard(string $role): void
    {
        $appConfig = require APP_PATH . '/config/app.php';
        $base = $appConfig['url'];

        match ($role) {
            'admin'        => $this->redirect($base . '/admin'),
            'vendor'       => $this->redirect($base . '/vendor'),
            'delivery_man' => $this->redirect($base . '/delivery'),
            default        => $this->redirect($base . '/')
        };
    }
}
