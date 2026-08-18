<?php
/**
 * Clean all stores, products, schedules, and vendor accounts for a fresh start
 * CicalengkaGO Multi-Vendor Delivery Platform
 */

require_once __DIR__ . '/../app/config/constants.php';
$config = require APP_PATH . '/config/database.php';

echo "=== CicalengkaGO Reset & Clean All Stores & Products ===\n";

try {
    $host = $config['host'] ?? '127.0.0.1';
    $port = $config['port'] ?? '3306';
    $db   = $config['database'] ?? 'cicalengkago_db';
    $user = $config['username'] ?? 'root';
    $pass = $config['password'] ?? '';

    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (Exception $e) {
        $dsn = "mysql:host=127.0.0.1;port=3306;dbname=cicalengkago_db;charset=utf8mb4";
        $pdo = new PDO($dsn, 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    echo "1. Membersihkan tabel products & store_schedules...\n";
    $pdo->exec("TRUNCATE TABLE `products`");
    $pdo->exec("TRUNCATE TABLE `store_schedules`");

    echo "2. Membersihkan tabel stores & relasi order/cart...\n";
    $pdo->exec("TRUNCATE TABLE `stores`");
    $pdo->exec("TRUNCATE TABLE `carts`");
    $pdo->exec("TRUNCATE TABLE `order_items`");
    $pdo->exec("TRUNCATE TABLE `orders`");

    echo "3. Membersihkan akun vendor & wallet vendor...\n";
    $pdo->exec("DELETE FROM `users` WHERE `role` = 'vendor'");
    $pdo->exec("DELETE FROM `wallets` WHERE `user_type` = 'vendor'");

    echo "4. Mereset counter pada modul...\n";
    $pdo->exec("UPDATE `modules` SET `stores_count` = 0");

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "✅ SELURUH DATA MITRA/TOKO, PRODUK, DAN AKUN VENDOR BERHASIL DI-CLEAR!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
