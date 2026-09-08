<?php
namespace App\Middleware;

class AdminMiddleware
{
    public function handle(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user']) || empty($_SESSION['user']['id'])) {
            $user = auth_user();
            if ($user && !empty($user['id'])) {
                $_SESSION['user'] = $user;
            }
        }

        $role = $_SESSION['user']['role'] ?? auth_role();
        if (empty($_SESSION['user']) || !in_array($role, ['admin', 'super_admin'], true)) {
            $isJson = (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
                || isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                || (isset($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], '/api/'));

            if ($isJson) {
                if (!headers_sent()) {
                    header('Content-Type: application/json');
                    http_response_code(403);
                }
                echo json_encode([
                    'success' => false,
                    'message' => 'Akses ditolak. Hak akses Administrator diperlukan.',
                    'unauthenticated' => empty($_SESSION['user'])
                ]);
                exit;
            }

            $config = require APP_PATH . '/config/app.php';
            header('Location: ' . $config['public_url'] . '/login?redirect=admin');
            exit;
        }

        return true;
    }
}
