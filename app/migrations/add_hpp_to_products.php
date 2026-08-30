<?php
/**
 * Migration: Add hpp column to products table
 * Run once from browser: https://cicago.store/vendor/migrate-hpp
 * Or run via CLI: php migrate_hpp.php
 */

require_once __DIR__ . '/../../bootstrap.php';

use App\Core\Database;

try {
    // Add hpp column if it doesn't exist
    $cols = Database::query("SHOW COLUMNS FROM `products` LIKE 'hpp'");
    if (empty($cols)) {
        Database::query("ALTER TABLE `products` ADD COLUMN `hpp` DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER `price`");
        echo json_encode(['success' => true, 'message' => 'Kolom hpp berhasil ditambahkan ke tabel products!']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Kolom hpp sudah ada di tabel products.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Gagal migrasi: ' . $e->getMessage()]);
}
