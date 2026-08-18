<?php
/**
 * CicalengkaGO - Front Controller
 */

declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', __DIR__);

// Global CORS & Security Headers for Chrome Extension & Mobile Apps
if (!headers_sent()) {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept");
    header("Content-Security-Policy: default-src * 'unsafe-inline' 'unsafe-eval' data: blob:; script-src * 'unsafe-inline' 'unsafe-eval' blob: data: https: http:; script-src-elem * 'unsafe-inline' 'unsafe-eval' blob: data: https: http:; style-src * 'unsafe-inline' https: http:; style-src-elem * 'unsafe-inline' https: http:; img-src * data: blob: https: http:; connect-src * https: http: ws: wss:; font-src * data: https: http:; frame-src *; child-src * blob:; worker-src * blob:;");
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load Application Constants & Helpers
require_once APP_PATH . '/config/constants.php';
require_once APP_PATH . '/helpers/auth.php';
require_once APP_PATH . '/helpers/response.php';
require_once APP_PATH . '/helpers/validation.php';
require_once APP_PATH . '/helpers/upload.php';
require_once APP_PATH . '/helpers/format.php';
require_once APP_PATH . '/helpers/distance.php';

// PSR-4 Autoloader
require_once APP_PATH . '/autoload.php';

// Initialize and Dispatch Application
use App\Core\App;

$app = new App();
$app->run();
