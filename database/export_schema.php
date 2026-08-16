<?php
/**
 * Export schema (CREATE TABLE) dari cicalengkago_db ke schema.sql
 */

$dbName = 'cicalengkago_db';
$outFile = __DIR__ . '/schema.sql';

// Urutan tabel (respecting FK dependencies)
$tables = [
    'users',
    'modules',
    'zones',
    'stores',
    'store_schedules',
    'categories',
    'products',
    'product_variations',
    'product_addons',
    'customer_addresses',
    'coupons',
    'delivery_men',
    'wallets',
    'wallet_transactions',
    'banners',
    'business_settings',
    'orders',
    'order_items',
    'delivery_trackings',
    'reviews',
    'notifications',
    'carts',
    'chats',
];

try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=$dbName;charset=utf8mb4", 'root', '');

    $out = [];
    $out[] = "-- ==================================================";
    $out[] = "-- CicalengkaGO Schema - Exported from Live Database";
    $out[] = "-- Generated: " . date('Y-m-d H:i:s');
    $out[] = "-- ==================================================";
    $out[] = "";
    $out[] = "SET FOREIGN_KEY_CHECKS = 0;";
    $out[] = "";

    foreach ($tables as $table) {
        $row = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
        if (!$row) continue;

        $createSql = $row[1];
        $out[] = "DROP TABLE IF EXISTS `$table`;";
        $out[] = $createSql . ";";
        $out[] = "";
    }

    $out[] = "SET FOREIGN_KEY_CHECKS = 1;";

    file_put_contents($outFile, implode(PHP_EOL, $out));
    echo "SUCCESS: Schema exported to database/schema.sql (" . count($out) . " lines)" . PHP_EOL;

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
