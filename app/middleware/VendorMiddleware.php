<?php
namespace App\Middleware;

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
