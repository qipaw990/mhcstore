<?php
namespace App\Middleware;

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
