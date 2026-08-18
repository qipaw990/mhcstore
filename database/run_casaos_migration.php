<?php
/**
 * CLI Migration Runner untuk CasaOS / Remote Server
 * Jalankan: php database/run_casaos_migration.php [HOST] [USER] [PASS] [DBNAME] [PORT]
 * Contoh: php database/run_casaos_migration.php 192.168.1.100 root secret cicalengkago_db 3306
 */

$host   = $argv[1] ?? getenv('DB_HOST') ?: '127.0.0.1';
$user   = $argv[2] ?? getenv('DB_USERNAME') ?: 'root';
$pass   = $argv[3] ?? getenv('DB_PASSWORD') ?: '';
$dbname = $argv[4] ?? getenv('DB_DATABASE') ?: 'cicalengkago_db';
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

    echo "\n=========================================================\n";
    echo " SUCCESS: Migrasi struktur tabel ke CasaOS berhasil!\n";
    echo "=========================================================\n";

} catch (Exception $e) {
    echo "\n[X] ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
