<?php
/**
 * CicalengkaGO - Front Controller
 */

declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', __DIR__);

// Determine HTTPS status (supports Cloudflare Tunnel, Nginx Reverse Proxy, direct SSL)
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? 80) == 443)
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');

// Origin Validation & CORS Protection
$httpOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
$isAllowedOrigin = false;

if (!empty($httpOrigin)) {
    $parsed = parse_url($httpOrigin);
    $originHost = $parsed['host'] ?? '';
    $originScheme = $parsed['scheme'] ?? '';

    // Allow Localhost & Development Emulators
    if (in_array($originHost, ['localhost', '127.0.0.1', '10.0.2.2'], true)) {
        $isAllowedOrigin = true;
    }
    // Allow Mobile Webview Schemes (Capacitor / Ionic)
    elseif (in_array($originScheme, ['capacitor', 'ionic'], true)) {
        $isAllowedOrigin = true;
    }
    // Allow Production Domains & Subdomains
    elseif (preg_match('/^(.*\.)?(cicago\.store|cicalengkago\.com|cicalengkago\.store)$/i', $originHost)) {
        $isAllowedOrigin = true;
    }
    // Allow Same Host as Current Server
    elseif (isset($_SERVER['HTTP_HOST'])) {
        $currentHost = parse_url('http://' . $_SERVER['HTTP_HOST'], PHP_URL_HOST) ?? '';
        if (strcasecmp($originHost, $currentHost) === 0) {
            $isAllowedOrigin = true;
        }
    }
}

if (!headers_sent()) {
    if ($isAllowedOrigin) {
        header("Access-Control-Allow-Origin: $httpOrigin");
        header("Access-Control-Allow-Credentials: true");
    } elseif (empty($httpOrigin)) {
        // Direct non-CORS request (browser address bar, native API client)
        header("Access-Control-Allow-Origin: *");
    } else {
        // Untrusted cross-origin
        header("Access-Control-Allow-Origin: null");
    }

    header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE, PATCH");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, Cookie, Set-Cookie, X-CSRF-Token, X-User-ID, X-Session-ID, X-Role, Cache-Control, Pragma");
    header("Access-Control-Max-Age: 86400");

    // Standard Defensive Security Headers
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: SAMEORIGIN");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("X-XSS-Protection: 1; mode=block");

    header("Content-Security-Policy: default-src * 'unsafe-inline' 'unsafe-eval' data: blob:; script-src * 'unsafe-inline' 'unsafe-eval' blob: data: https: http:; script-src-elem * 'unsafe-inline' 'unsafe-eval' blob: data: https: http:; style-src * 'unsafe-inline' https: http:; style-src-elem * 'unsafe-inline' https: http:; img-src * data: blob: https: http:; media-src * data: blob: mediastream: https: http:; connect-src * https: http: ws: wss:; font-src * data: https: http:; frame-src *; child-src * blob:; worker-src * blob:;");
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Secure Session Initialization
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
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
