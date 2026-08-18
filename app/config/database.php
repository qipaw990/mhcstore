<?php
/**
 * Database Configuration
 * CicalengkaGO Multi-Vendor Delivery Platform
 */

$getEnvVar = function($keys, $default = '') {
    foreach ((array)$keys as $k) {
        $val = getenv($k);
        if ($val !== false && $val !== '') return $val;
        if (!empty($_ENV[$k])) return $_ENV[$k];
        if (!empty($_SERVER[$k])) return $_SERVER[$k];
    }
    return $default;
};

return [
    'driver'    => 'mysql',
    'host'      => $getEnvVar(['DB_HOST'], '127.0.0.1'),
    'port'      => $getEnvVar(['DB_PORT'], '3306'),
    'database'  => $getEnvVar(['DB_DATABASE', 'DB_NAME'], 'cicalengkago_db'),
    'username'  => $getEnvVar(['DB_USERNAME', 'DB_USER'], 'root'),
    'password'  => $getEnvVar(['DB_PASSWORD', 'DB_PASS'], ''),
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options'   => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => false,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ]
];
