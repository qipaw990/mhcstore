<?php
/**
 * Application Configuration
 */

// Detect correct protocol (support reverse proxy / Cloudflare Tunnel HTTPS)
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? 80) == 443)
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    || (($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on');
$protocol = $isHttps ? 'https://' : 'http://';

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// ENV override takes priority (set via docker-compose.yml)
$envAppUrl = getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? null);
$envPublicUrl = getenv('PUBLIC_URL') ?: ($_ENV['PUBLIC_URL'] ?? null);

if ($envAppUrl) {
    // Explicitly configured via environment variable
    $appUrl = rtrim($envAppUrl, '/');
    $publicUrl = $appUrl;
} else {
    // Auto-detect: Check if Apache DocumentRoot already points to /public
    // When DocumentRoot = /var/www/html/public, SCRIPT_NAME = /index.php (no /public prefix)
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $scriptDir = rtrim(dirname($scriptName), '/');

    // If DOCUMENT_ROOT ends with /public, we're already inside public/ dir
    // so baseUrl should NOT include /public
    if (str_ends_with(rtrim($docRoot, '/'), '/public') || $scriptDir === '' || $scriptDir === '.') {
        $publicUrl = rtrim($protocol . $host, '/');
    } else {
        $publicUrl = rtrim($protocol . $host . $scriptDir, '/');
    }
    $appUrl = $publicUrl;
}

return [
    'name'        => 'CicalengkaGO',
    'app_name'    => 'CicalengkaGO - Delivery On Demand',
    'version'     => '1.0.0',
    'env'         => $_ENV['APP_ENV'] ?? 'development',
    'debug'       => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'url'         => $_ENV['APP_URL'] ?? $appUrl,
    'public_url'  => $publicUrl,
    'timezone'    => 'Asia/Jakarta',
    'locale'      => 'id',
    'currency'    => 'IDR',
    'currency_symbol' => 'Rp',
];
