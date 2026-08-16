<?php
/**
 * Zero-dependency PSR-4 Autoloader with Linux Case-Sensitivity Fallback
 */

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);

    // 1. Direct PSR-4 Path (e.g. app/Core/App.php)
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }

    // 2. Lowercase directory fallback for Linux OS (e.g. App\Core\App -> app/core/App.php)
    $parts = explode('\\', $relativeClass);
    if (count($parts) > 1) {
        $className = array_pop($parts);
        $dirParts = array_map('strtolower', $parts);
        $fallbackFile = $baseDir . implode('/', $dirParts) . '/' . $className . '.php';
        if (file_exists($fallbackFile)) {
            require_once $fallbackFile;
            return;
        }
    }
});
