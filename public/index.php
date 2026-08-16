<?php
/**
 * CicalengkaGO - Front Controller
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', __DIR__);

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
