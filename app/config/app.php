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

// Support reverse proxy host header (Cloudflare / Nginx reverse proxy)
$rawHost = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? '');
if (str_contains($rawHost, ',')) {
    $rawHost = trim(explode(',', $rawHost)[0]);
}
$host = !empty($rawHost) ? $rawHost : 'market.cicago.store';

// ENV override
$envAppUrl = getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? null);
$envPublicUrl = getenv('PUBLIC_URL') ?: ($_ENV['PUBLIC_URL'] ?? null);

if (str_contains($host, 'market.cicago.store')) {
    $publicUrl = 'https://market.cicago.store';
    $appUrl    = $publicUrl;
} elseif (str_contains($host, 'cicago.store')) {
    $publicUrl = 'https://cicago.store';
    $appUrl    = $publicUrl;
} elseif ($envPublicUrl || $envAppUrl) {
    // Explicitly configured via environment variable
    $appUrl = rtrim($envPublicUrl ?: $envAppUrl, '/');
    $publicUrl = $appUrl;
} else {
    // Auto-detect for local development
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $scriptDir = rtrim(dirname($scriptName), '/');

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
    'env'         => getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'production'),
    'debug'       => filter_var(getenv('APP_DEBUG') ?: ($_ENV['APP_DEBUG'] ?? false), FILTER_VALIDATE_BOOLEAN),
    'url'         => $_ENV['APP_URL'] ?? $appUrl,
    'public_url'  => $publicUrl,
    'timezone'    => 'Asia/Jakarta',
    'locale'      => 'id',
    'currency'    => 'IDR',
    'currency_symbol' => 'Rp',
];
