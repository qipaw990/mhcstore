<?php
/**
 * CLI Migration: Bahan Baku (Raw Materials)
 * Jalankan: php database/migrate_raw_materials.php [HOST] [USER] [PASS] [DBNAME] [PORT]
 */

$host   = $argv[1] ?? getenv('DB_HOST') ?: '127.0.0.1';
$user   = $argv[2] ?? getenv('DB_USERNAME') ?: getenv('DB_USER') ?: 'root';
$pass   = $argv[3] ?? getenv('DB_PASSWORD') ?: getenv('DB_PASS') ?: '';
$dbname = $argv[4] ?? getenv('DB_DATABASE') ?: getenv('DB_NAME') ?: 'cicalengkago_db';
$port   = $argv[5] ?? getenv('DB_PORT') ?: '3306';

echo "--------------------------------------------\n";
echo " CicalengkaGO — Migrasi Bahan Baku\n";
echo "--------------------------------------------\n";
echo " Host     : {$host}:{$port}\n";
echo " Database : {$dbname}\n";
echo "--------------------------------------------\n";

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "[✓] Koneksi MySQL berhasil!\n\n";

    // 1. Tabel raw_materials
    $tables = $pdo->query("SHOW TABLES LIKE 'raw_materials'")->fetchAll();
    if (empty($tables)) {
        $pdo->exec("CREATE TABLE `raw_materials` (
          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
          `store_id` bigint(20) unsigned NOT NULL,
          `name` varchar(150) NOT NULL,
          `unit` varchar(30) NOT NULL DEFAULT 'gr',
          `price_per_unit` decimal(14,4) NOT NULL DEFAULT 0.0000 COMMENT 'Harga per 1 satuan unit',
          `stock_qty` decimal(12,4) NOT NULL DEFAULT 0.0000,
          `description` varchar(255) DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `idx_rm_store` (`store_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        echo "[+] Tabel `raw_materials` berhasil dibuat.\n";
    } else {
        echo "[=] Tabel `raw_materials` sudah ada.\n";
    }

    // 2. Tabel product_raw_materials
    $tables2 = $pdo->query("SHOW TABLES LIKE 'product_raw_materials'")->fetchAll();
    if (empty($tables2)) {
        $pdo->exec("CREATE TABLE `product_raw_materials` (
          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
          `product_id` bigint(20) unsigned NOT NULL,
          `raw_material_id` bigint(20) unsigned NOT NULL,
          `qty_used` decimal(12,4) NOT NULL DEFAULT 1.0000 COMMENT 'Jumlah satuan dipakai per 1 produk',
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_product_material` (`product_id`, `raw_material_id`),
          KEY `idx_prm_product` (`product_id`),
          KEY `idx_prm_material` (`raw_material_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        echo "[+] Tabel `product_raw_materials` berhasil dibuat.\n";
    } else {
        echo "[=] Tabel `product_raw_materials` sudah ada.\n";
    }

    echo "\n[✓] Migrasi bahan baku selesai!\n";

} catch (PDOException $e) {
    echo "[✗] ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
