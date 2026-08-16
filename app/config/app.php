<?php
/**
 * Application Configuration
 */

// Base URL autodetection
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$baseUrl = rtrim($protocol . $host . $scriptName, '/');

// Fix if running from root or public
if (substr($baseUrl, -7) === '/public') {
    $publicUrl = $baseUrl;
    $appUrl = substr($baseUrl, 0, -7);
} else {
    $publicUrl = $baseUrl . '/public';
    $appUrl = $baseUrl;
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
