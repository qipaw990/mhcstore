<?php
/**
 * Auth Middleware
 */

namespace App\Middleware;

class AuthMiddleware
{
    public function handle(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user']) || empty($_SESSION['user']['id'])) {
            $config = require APP_PATH . '/config/app.php';
            header('Location: ' . $config['public_url'] . '/login');
            exit;
        }

        return true;
    }
}
