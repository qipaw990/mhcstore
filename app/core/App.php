<?php
/**
 * Application Bootstrap & Autoloader
 */

namespace App\Core;

class App
{
    public static function run(): void
    {
        // Start secure session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Set timezone
        date_default_timezone_set('Asia/Jakarta');

        // Load helpers
        require_once APP_PATH . '/helpers/auth.php';
        require_once APP_PATH . '/helpers/response.php';
        require_once APP_PATH . '/helpers/validation.php';
        require_once APP_PATH . '/helpers/upload.php';
        require_once APP_PATH . '/helpers/format.php';
        require_once APP_PATH . '/helpers/distance.php';

        // Load route definitions
        require_once APP_PATH . '/routes/web.php';
        require_once APP_PATH . '/routes/api.php';

        // Dispatch request
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Support PUT / DELETE spoofing
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        Router::dispatch($uri, $method);
    }
}
