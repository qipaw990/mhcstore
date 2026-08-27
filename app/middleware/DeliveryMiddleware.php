<?php
namespace App\Middleware;

use App\Core\Database;

class DeliveryMiddleware
{
    public function handle(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user']) || empty($_SESSION['user']['id'])) {
            $token = get_bearer_token();
            if (!empty($token)) {
                $user = Database::fetchOne("SELECT * FROM `users` WHERE `api_token` = ? AND `is_active` = 1 LIMIT 1", [$token]);
                if (!empty($user)) {
                    $_SESSION['user'] = $user;
                }
            }
        }

        if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'delivery_man') {
            $isJson = (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
                || isset($_SERVER['HTTP_X_REQUESTED_WITH']);

            if ($isJson) {
                if (!headers_sent()) {
                    header('Content-Type: application/json');
                    http_response_code(403);
                }
                echo json_encode(['success' => false, 'message' => 'Akses ditolak. Peran Kurir Delivery diperlukan.', 'unauthenticated' => true]);
                exit;
            }

            $config = require APP_PATH . '/config/app.php';
            header('Location: ' . $config['public_url'] . '/login?redirect=delivery');
            exit;
        }

        return true;
    }
}
