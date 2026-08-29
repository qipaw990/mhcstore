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

        $user = auth_user();
        if ($user) {
            $_SESSION['user'] = $user;
        }

        $userId = auth_id();
        $isDelivery = false;

        if ($userId > 0) {
            $role = $_SESSION['user']['role'] ?? auth_role();
            if ($role === 'delivery_man') {
                $isDelivery = true;
            } else {
                $dm = Database::fetchOne("SELECT id FROM delivery_men WHERE user_id = ? LIMIT 1", [$userId]);
                if ($dm) {
                    $isDelivery = true;
                    // Sync user role
                    Database::update('users', ['role' => 'delivery_man'], 'id = ?', [$userId]);
                    if (isset($_SESSION['user'])) {
                        $_SESSION['user']['role'] = 'delivery_man';
                    }
                }
            }
        }

        if (!$isDelivery) {
            $isJson = (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
                || isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                || !empty(get_bearer_token())
                || isset($_SERVER['HTTP_X_USER_ID']);

            if ($isJson) {
                if (!headers_sent()) {
                    header('Content-Type: application/json');
                    http_response_code(403);
                }
                echo json_encode([
                    'success' => false,
                    'message' => 'Akses ditolak. Peran Kurir Delivery diperlukan.',
                    'unauthenticated' => empty($userId)
                ]);
                exit;
            }

            $config = require APP_PATH . '/config/app.php';
            header('Location: ' . $config['public_url'] . '/login?redirect=delivery');
            exit;
        }

        return true;
    }
}
