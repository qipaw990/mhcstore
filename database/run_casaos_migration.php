<?php
/**
 * CLI Migration Runner untuk CasaOS / Remote Server
 * Jalankan: php database/run_casaos_migration.php [HOST] [USER] [PASS] [DBNAME] [PORT]
 * Contoh: php database/run_casaos_migration.php 192.168.1.100 root secret cicalengkago_db 3306
 */

$host   = $argv[1] ?? getenv('DB_HOST') ?: '127.0.0.1';
$user   = $argv[2] ?? getenv('DB_USERNAME') ?: getenv('DB_USER') ?: 'root';
$pass   = $argv[3] ?? getenv('DB_PASSWORD') ?: getenv('DB_PASS') ?: '';
$dbname = $argv[4] ?? getenv('DB_DATABASE') ?: getenv('DB_NAME') ?: 'cicalengkago_db';
$port   = $argv[5] ?? getenv('DB_PORT') ?: '3306';

echo "---------------------------------------------------------\n";
echo " CicalengkaGO — Migrasi Struktur Tabel Batch ke CasaOS\n";
echo "---------------------------------------------------------\n";
echo " Host     : {$host}:{$port}\n";
echo " Database : {$dbname}\n";
echo " User     : {$user}\n";
echo "---------------------------------------------------------\n";

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "[✓] Koneksi ke MySQL CasaOS berhasil!\n\n";

    // 1. Cek & Tambah kolom pada tabel `orders`
    $colsOrders = $pdo->query("SHOW COLUMNS FROM `orders`")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('delivery_batch_id', $colsOrders)) {
        $pdo->exec("ALTER TABLE `orders` ADD COLUMN `delivery_batch_id` VARCHAR(24) NULL DEFAULT NULL AFTER `delivery_man_id`");
        echo "[+] Added column `orders.delivery_batch_id`\n";
    } else {
        echo "[=] Column `orders.delivery_batch_id` already exists.\n";
    }

    if (!in_array('pickup_sequence', $colsOrders)) {
        $pdo->exec("ALTER TABLE `orders` ADD COLUMN `pickup_sequence` TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER `delivery_batch_id`");
        echo "[+] Added column `orders.pickup_sequence`\n";
    } else {
        echo "[=] Column `orders.pickup_sequence` already exists.\n";
    }

    // Index on delivery_batch_id
    try {
        $pdo->exec("CREATE INDEX `idx_batch` ON `orders` (`delivery_batch_id`)");
        echo "[+] Created index `idx_batch` on `orders`\n";
    } catch (Exception $e) {
        echo "[=] Index `idx_batch` already exists or skipped.\n";
    }

    // 2. Cek & Tambah kolom pada tabel `delivery_men`
    $colsDm = $pdo->query("SHOW COLUMNS FROM `delivery_men`")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('active_batch_id', $colsDm)) {
        $pdo->exec("ALTER TABLE `delivery_men` ADD COLUMN `active_batch_id` VARCHAR(24) NULL DEFAULT NULL");
        echo "[+] Added column `delivery_men.active_batch_id`\n";
    } else {
        echo "[=] Column `delivery_men.active_batch_id` already exists.\n";
    }

    if (!in_array('active_order_ids', $colsDm)) {
        $pdo->exec("ALTER TABLE `delivery_men` ADD COLUMN `active_order_ids` TEXT NULL DEFAULT NULL");
        echo "[+] Added column `delivery_men.active_order_ids`\n";
    } else {
        echo "[=] Column `delivery_men.active_order_ids` already exists.\n";
    }

    // 3. Cek & Tambah kolom pada tabel `chats`
    $colsChats = $pdo->query("SHOW COLUMNS FROM `chats`")->fetchAll(PDO::FETCH_COLUMN);

    try {
        $fks = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'chats' AND CONSTRAINT_SCHEMA = '{$dbname}' AND REFERENCED_TABLE_NAME = 'orders'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($fks as $fk) {
            $pdo->exec("ALTER TABLE `chats` DROP FOREIGN KEY `{$fk}`");
            echo "[+] Dropped old foreign key `{$fk}` on `chats`\n";
        }
        $pdo->exec("ALTER TABLE `chats` MODIFY COLUMN `order_id` bigint(20) unsigned NULL DEFAULT NULL");
        echo "[+] Modified `chats.order_id` to be NULLABLE\n";
    } catch (Exception $e) {
        echo "[=] Notice on chats.order_id: " . $e->getMessage() . "\n";
    }

    if (!in_array('store_id', $colsChats)) {
        $pdo->exec("ALTER TABLE `chats` ADD COLUMN `store_id` bigint(20) unsigned NULL DEFAULT NULL AFTER `order_id`");
        echo "[+] Added column `chats.store_id`\n";
    } else {
        echo "[=] Column `chats.store_id` already exists.\n";
    }

    try {
        $pdo->exec("CREATE INDEX `idx_chat_store` ON `chats` (`store_id`)");
        echo "[+] Created index `idx_chat_store` on `chats`\n";
    } catch (Exception $e) {
        echo "[=] Index `idx_chat_store` already exists or skipped.\n";
    }

    try {
        $pdo->exec("CREATE INDEX `idx_chat_order_store` ON `chats` (`order_id`, `store_id`)");
        echo "[+] Created index `idx_chat_order_store` on `chats`\n";
    } catch (Exception $e) {
        echo "[=] Index `idx_chat_order_store` already exists or skipped.\n";
    }

    // 4. Cek & Buat tabel `voice_calls` untuk In-App Voice Call
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `voice_calls` (
          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
          `order_code` varchar(50) NOT NULL,
          `caller_id` bigint(20) unsigned NOT NULL,
          `receiver_id` bigint(20) unsigned NOT NULL,
          `caller_role` varchar(20) NOT NULL DEFAULT 'customer',
          `receiver_role` varchar(20) NOT NULL DEFAULT 'delivery_man',
          `status` enum('calling','connected','rejected','ended','no_answer') NOT NULL DEFAULT 'calling',
          `offer` longtext DEFAULT NULL,
          `answer` longtext DEFAULT NULL,
          `ice_candidates` longtext DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `idx_vc_order` (`order_code`),
          KEY `idx_vc_receiver` (`receiver_id`),
          KEY `idx_vc_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        echo "[+] Ensured table `voice_calls` exists.\n";
    } catch (Exception $e) {
        echo "[=] Notice on voice_calls table: " . $e->getMessage() . "\n";
    }

    // 5. Cek & Tambah kolom pada tabel `stores`
    $colsStores = $pdo->query("SHOW COLUMNS FROM `stores`")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('tax', $colsStores)) {
        $pdo->exec("ALTER TABLE `stores` ADD COLUMN `tax` DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER `tax_percent`");
        echo "[+] Added `stores.tax`\n";
    }
    if (!in_array('service_charge', $colsStores)) {
        $pdo->exec("ALTER TABLE `stores` ADD COLUMN `service_charge` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `tax`");
        echo "[+] Added `stores.service_charge`\n";
    }
    if (!in_array('opening_time', $colsStores)) {
        $pdo->exec("ALTER TABLE `stores` ADD COLUMN `opening_time` TIME NOT NULL DEFAULT '08:00:00' AFTER `is_open`");
        echo "[+] Added `stores.opening_time`\n";
    }
    if (!in_array('closing_time', $colsStores)) {
        $pdo->exec("ALTER TABLE `stores` ADD COLUMN `closing_time` TIME NOT NULL DEFAULT '22:00:00' AFTER `opening_time`");
        echo "[+] Added `stores.closing_time`\n";
    }
    if (!in_array('bank_name', $colsStores)) {
        $pdo->exec("ALTER TABLE `stores` ADD COLUMN `bank_name` VARCHAR(50) NULL DEFAULT 'BCA' AFTER `closing_time`");
        echo "[+] Added `stores.bank_name`\n";
    }
    if (!in_array('bank_account_number', $colsStores)) {
        $pdo->exec("ALTER TABLE `stores` ADD COLUMN `bank_account_number` VARCHAR(50) NULL DEFAULT NULL AFTER `bank_name`");
        echo "[+] Added `stores.bank_account_number`\n";
    }
    if (!in_array('bank_account_name', $colsStores)) {
        $pdo->exec("ALTER TABLE `stores` ADD COLUMN `bank_account_name` VARCHAR(100) NULL DEFAULT NULL AFTER `bank_account_number`");
        echo "[+] Added `stores.bank_account_name`\n";
    }

    // 6. Cek & Buat tabel `raw_materials` dan `product_raw_materials`
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `raw_materials` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `store_id` bigint(20) unsigned NOT NULL,
            `name` varchar(255) NOT NULL,
            `unit` varchar(50) NOT NULL DEFAULT 'gr',
            `price_per_unit` decimal(15,2) NOT NULL DEFAULT 0.00,
            `stock_qty` decimal(15,2) NOT NULL DEFAULT 0.00,
            `description` text DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_rm_store` (`store_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        echo "[+] Ensured table `raw_materials` exists.\n";
    } catch (Exception $e) {
        echo "[=] Notice on raw_materials table: " . $e->getMessage() . "\n";
    }

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `product_raw_materials` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `product_id` bigint(20) unsigned NOT NULL,
            `raw_material_id` bigint(20) unsigned NOT NULL,
            `qty_used` decimal(15,4) NOT NULL DEFAULT 0.0000,
            `created_at` timestamp NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_prm_product` (`product_id`),
            KEY `idx_prm_material` (`raw_material_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        echo "[+] Ensured table `product_raw_materials` exists.\n";
    } catch (Exception $e) {
        echo "[=] Notice on product_raw_materials table: " . $e->getMessage() . "\n";
    }

    // Pastikan kolom hpp pada products
    $colsProducts = $pdo->query("SHOW COLUMNS FROM `products`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('hpp', $colsProducts)) {
        $pdo->exec("ALTER TABLE `products` ADD COLUMN `hpp` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `price`");
        echo "[+] Added `products.hpp`\n";
    }

    echo "\n=========================================================\n";
    echo " SUCCESS: Migrasi struktur tabel ke CasaOS berhasil!\n";
    echo "=========================================================\n";

} catch (Exception $e) {
    echo "\n[X] ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

