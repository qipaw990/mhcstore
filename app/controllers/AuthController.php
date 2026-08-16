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
            $this->redirectToRoleDashboard($user['role']);
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('login');
        }
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
            $user = $this->authService->registerCustomer($data);
            $_SESSION['success'] = 'Pendaftaran berhasil! Saldo bonus selamat datang Rp 25.000 telah masuk ke dompet Anda.';
            $this->redirect('');
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
        unset($_SESSION['user']);
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
