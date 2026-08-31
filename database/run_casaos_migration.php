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

    echo "\n=========================================================\n";
    echo " SUCCESS: Migrasi struktur tabel ke CasaOS berhasil!\n";
    echo "=========================================================\n";

} catch (Exception $e) {
    echo "\n[X] ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
