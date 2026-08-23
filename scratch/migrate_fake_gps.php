<?php
define('APP_PATH', __DIR__ . '/../app');
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/core/Database.php';

use App\Core\Database;

try {
    $columnCheck = Database::query("SHOW COLUMNS FROM `delivery_men` LIKE 'is_mocked'");
    if (empty($columnCheck)) {
        Database::query("ALTER TABLE `delivery_men` ADD COLUMN `is_mocked` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_online`");
        echo "Successfully added `is_mocked` column to `delivery_men` table!\n";
    } else {
        echo "Column `is_mocked` already exists in `delivery_men` table.\n";
    }
} catch (\Throwable $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
}
