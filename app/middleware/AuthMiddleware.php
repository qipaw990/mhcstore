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

        // 1. Dual Authentication: Fallback ke Bearer Token jika SESSION kosong
        if (empty($_SESSION['user']) || empty($_SESSION['user']['id'])) {
            $token = get_bearer_token();
            if (!empty($token)) {
                $user = Database::fetchOne("SELECT * FROM `users` WHERE `api_token` = ? AND `is_active` = 1 LIMIT 1", [$token]);
                if (!empty($user)) {
                    $_SESSION['user'] = $user;
                }
            }
        }

        // 2. Jika tetap kosong, respons sesuai tipe request (JSON vs HTML Redirect)
        if (empty($_SESSION['user']) || empty($_SESSION['user']['id'])) {
            $isJson = (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
                || isset($_SERVER['HTTP_X_REQUESTED_WITH'])
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
