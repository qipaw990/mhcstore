<?php
define('APP_PATH', dirname(__DIR__) . '/app');
require_once dirname(__DIR__) . '/app/core/Database.php';

use App\Core\Database;

try {
    $settings = [
        'doku_enabled'     => '1',
        'doku_environment' => 'sandbox',
        'doku_client_id'   => '',
        'doku_secret_key'  => '',
    ];

    foreach ($settings as $key => $defaultVal) {
        $existing = Database::fetchOne(
            "SELECT id FROM `business_settings` WHERE `key_name` = ? LIMIT 1",
            [$key]
        );

        if (!$existing) {
            Database::execute(
                "INSERT INTO `business_settings` (`key_name`, `value_text`, `created_at`, `updated_at`) VALUES (?, ?, NOW(), NOW())",
                [$key, $defaultVal]
            );
            echo "[+] Added setting `{$key}` (default: '{$defaultVal}')\n";
        } else {
            echo "[=] Setting `{$key}` already exists.\n";
        }
    }

    echo "[✓] DOKU Payment Gateway settings initialized successfully!\n";
} catch (\Throwable $e) {
    echo "[!] Error: " . $e->getMessage() . "\n";
}
