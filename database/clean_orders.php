<?php
/**
 * Clean all orders and related transaction data for fresh initial start
 */

require_once __DIR__ . '/../app/config/constants.php';
$config = require APP_PATH . '/config/database.php';

echo "=== CicalengkaGO Reset & Clean All Orders ===\n";

try {
    // Try local 127.0.0.1 first, fallback to config
    $host = '127.0.0.1';
    $port = '3306';
    $db   = 'cicalengkago_db';
    $user = 'root';
    $pass = '';

    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (Exception $e) {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // 1. Kosongkan tabel order & item
    echo "1. Mengosongkan tabel orders, order_items, delivery_trackings, reviews, carts, chats...\n";
    $pdo->exec("TRUNCATE TABLE `orders`");
    $pdo->exec("TRUNCATE TABLE `order_items`");
    $pdo->exec("TRUNCATE TABLE `delivery_trackings`");
    $pdo->exec("TRUNCATE TABLE `reviews`");
    $pdo->exec("TRUNCATE TABLE `carts`");
    $pdo->exec("TRUNCATE TABLE `chats`");

    // 2. Bersihkan notifikasi terkait order
    echo "2. Membersihkan notifikasi pesanan lama...\n";
    $pdo->exec("DELETE FROM `notifications` WHERE `type` = 'order' OR `title` LIKE '%Pesanan%'");
    
    // Pastikan ada notifikasi promo selamat datang
    $hasPromo = $pdo->query("SELECT COUNT(*) FROM `notifications` WHERE `type` = 'promo'")->fetchColumn();
    if (!$hasPromo) {
        $pdo->exec("INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `data_json`, `created_at`) 
            VALUES (1, 5, 'Selamat Datang di CicalengkaGO! 🎉', 'Nikmati kemudahan pesan antar makanan, sembako, dan kirim paket di Cicalengka. Gunakan kode promo CICAGOHEMAT.', 'promo', 0, '{\"coupon_code\":\"CICAGOHEMAT\"}', NOW())");
    }

    // 3. Reset saldo wallet & riwayat transaksi wallet terkait order
    echo "3. Mereset saldo wallet & membersihkan transaksi order...\n";
    $pdo->exec("DELETE FROM `wallet_transactions` WHERE `category` IN ('order_payment', 'order_earning')");
    
    // Reset saldo awal bersih
    $pdo->exec("UPDATE `wallets` SET `balance` = 0.00, `total_earned` = 0.00, `total_withdrawn` = 0.00 WHERE `user_type` IN ('admin', 'vendor', 'delivery_man')");
    $pdo->exec("UPDATE `wallets` SET `balance` = 250000.00, `total_earned` = 0.00, `total_withdrawn` = 0.00 WHERE `user_type` = 'customer'");

    // 4. Reset counter order pada tabel relasi
    echo "4. Mereset counter toko, produk, driver, dan voucher...\n";
    $pdo->exec("UPDATE `stores` SET `order_count` = 0, `reviews_count` = 0, `rating` = 5.00");
    $pdo->exec("UPDATE `products` SET `order_count` = 0, `reviews_count` = 0, `rating` = 5.00");
    $pdo->exec("UPDATE `delivery_men` SET `total_orders` = 0, `reviews_count` = 0, `current_order_id` = NULL, `rating` = 5.00");
    $pdo->exec("UPDATE `coupons` SET `usage_count` = 0");

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "✅ Semua data order berhasil dikosongkan!\n";

    // 5. Ekspor ulang seeders.sql yang sudah bersih
    echo "5. Mengupdate database/seeders.sql...\n";
    require_once __DIR__ . '/export_seeders.php';

    echo "\n=== Selesai! Database dan seeders.sql sekarang bersih tanpa order lama. ===\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
