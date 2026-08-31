<?php
define('APP_PATH', dirname(__DIR__) . '/app');
require_once dirname(__DIR__) . '/app/core/Database.php';

use App\Core\Database;

try {
    $colsStores = Database::query("SHOW COLUMNS FROM `stores`");
    $existing = array_map(function($c) { return $c['Field']; }, $colsStores);

    if (!in_array('tax', $existing)) {
        Database::execute("ALTER TABLE `stores` ADD COLUMN `tax` DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER `tax_percent`");
        echo "[+] Added `stores.tax`\n";
    }
    if (!in_array('service_charge', $existing)) {
        Database::execute("ALTER TABLE `stores` ADD COLUMN `service_charge` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `tax`");
        echo "[+] Added `stores.service_charge`\n";
    }
    if (!in_array('opening_time', $existing)) {
        Database::execute("ALTER TABLE `stores` ADD COLUMN `opening_time` TIME NOT NULL DEFAULT '08:00:00' AFTER `is_open`");
        echo "[+] Added `stores.opening_time`\n";
    }
    if (!in_array('closing_time', $existing)) {
        Database::execute("ALTER TABLE `stores` ADD COLUMN `closing_time` TIME NOT NULL DEFAULT '22:00:00' AFTER `opening_time`");
        echo "[+] Added `stores.closing_time`\n";
    }
    if (!in_array('bank_name', $existing)) {
        Database::execute("ALTER TABLE `stores` ADD COLUMN `bank_name` VARCHAR(50) NULL DEFAULT 'BCA' AFTER `closing_time`");
        echo "[+] Added `stores.bank_name`\n";
    }
    if (!in_array('bank_account_number', $existing)) {
        Database::execute("ALTER TABLE `stores` ADD COLUMN `bank_account_number` VARCHAR(50) NULL DEFAULT NULL AFTER `bank_name`");
        echo "[+] Added `stores.bank_account_number`\n";
    }
    if (!in_array('bank_account_name', $existing)) {
        Database::execute("ALTER TABLE `stores` ADD COLUMN `bank_account_name` VARCHAR(100) NULL DEFAULT NULL AFTER `bank_account_number`");
        echo "[+] Added `stores.bank_account_name`\n";
    }

    echo "[✓] All store profile columns verified & ready!\n";
} catch (\Throwable $e) {
    echo "[!] Error: " . $e->getMessage() . "\n";
}
