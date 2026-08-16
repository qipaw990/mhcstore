<?php
/**
 * Role Specific Middlewares & API Auth
 */

namespace App\Middleware;

use App\Core\Database;

class AdminMiddleware
{
    public function handle(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            $config = require APP_PATH . '/config/app.php';
            header('Location: ' . $config['public_url'] . '/login?redirect=admin');
            exit;
        }

        return true;
    }
}

class VendorMiddleware
{
    public function handle(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'vendor') {
            $config = require APP_PATH . '/config/app.php';
            header('Location: ' . $config['public_url'] . '/login?redirect=vendor');
            exit;
        }

        return true;
    }
}

class DeliveryMiddleware
{
    public function handle(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'delivery_man') {
            $config = require APP_PATH . '/config/app.php';
            header('Location: ' . $config['public_url'] . '/login?redirect=delivery');
            exit;
        }

        return true;
    }
}

class ApiAuthMiddleware
{
    public function handle(): bool
    {
        $token = get_bearer_token();

        if (!$token) {
            api_error('Unauthorized. API Bearer Token is required.', null, 401);
            return false;
        }

        $user = Database::fetchOne("SELECT * FROM `users` WHERE `api_token` = ? AND `is_active` = 1 LIMIT 1", [$token]);

        if (!$user) {
            api_error('Invalid or expired API token.', null, 401);
            return false;
        }

        // Attach user to global/session
        $_SESSION['api_user'] = $user;
        $_SESSION['user'] = $user;

        return true;
    }
}
