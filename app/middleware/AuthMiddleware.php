<?php
/**
 * Auth Middleware
 */

namespace App\Middleware;

use App\Core\Database;

class AuthMiddleware
{
    public function handle(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 1. Dual Authentication: Gunakan auth_user() yang otomatis memeriksa SESSION, Bearer Token, PAT, dan X-User-ID
        if (empty($_SESSION['user']) || empty($_SESSION['user']['id'])) {
            $user = auth_user();
            if ($user && !empty($user['id'])) {
                $_SESSION['user'] = $user;
            }
        }

        // 2. Jika tetap kosong, respons sesuai tipe request (JSON vs HTML Redirect)
        if (empty($_SESSION['user']) || empty($_SESSION['user']['id'])) {
            $isJson = (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
                || isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                || isset($_SERVER['HTTP_X_APP_CLIENT'])
                || isset($_SERVER['HTTP_X_API_TOKEN'])
                || isset($_SERVER['HTTP_AUTHORIZATION'])
                || !empty($_REQUEST['is_api'])
                || (isset($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], '/api/'));

            if ($isJson) {
                if (!headers_sent()) {
                    header('Content-Type: application/json');
                    http_response_code(401);
                }
                echo json_encode([
                    'success' => false,
                    'message' => 'Sesi telah berakhir atau tidak valid. Silakan login kembali.',
                    'unauthenticated' => true
                ]);
                exit;
            }

            $config = require APP_PATH . '/config/app.php';
            header('Location: ' . $config['public_url'] . '/login');
            exit;
        }

        return true;
    }
}
