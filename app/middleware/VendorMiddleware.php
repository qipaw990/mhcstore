<?php
namespace App\Middleware;

use App\Core\Database;

class VendorMiddleware
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
        $isVendor = false;

        if ($userId > 0) {
            $role = $_SESSION['user']['role'] ?? auth_role();
            if ($role === 'vendor' || $role === 'merchant') {
                $isVendor = true;
            } else {
                // If user owns a store in stores table, recognize as vendor!
                $store = Database::fetchOne("SELECT id FROM stores WHERE vendor_id = ? LIMIT 1", [$userId]);
                if ($store) {
                    $isVendor = true;
                    // Sync user role to vendor
                    Database::update('users', ['role' => 'vendor'], 'id = ?', [$userId]);
                    if (isset($_SESSION['user'])) {
                        $_SESSION['user']['role'] = 'vendor';
                    }
                }
            }
        }

        if (!$isVendor) {
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
                    'message' => 'Akses ditolak. Peran Mitra Vendor diperlukan.',
                    'unauthenticated' => empty($userId)
                ]);
                exit;
            }

            $config = require APP_PATH . '/config/app.php';
            header('Location: ' . $config['public_url'] . '/login?redirect=vendor');
            exit;
        }

        return true;
    }
}
