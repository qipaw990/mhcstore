<?php
namespace App\Middleware;

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
