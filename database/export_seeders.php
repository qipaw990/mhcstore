<?php
/**
 * Export seeder data dari cicalengkago_db ke seeders.sql
 */

$dbName = 'cicalengkago_db';
$outFile = __DIR__ . '/seeders.sql';

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
    'notifications',
    'reviews',
    'carts',
    'chats',
];

try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=$dbName;charset=utf8mb4", 'root', '');
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $out = [];
    $out[] = "-- ==================================================";
    $out[] = "-- CicalengkaGO Seeders - Exported from Live Database";
    $out[] = "-- Generated: " . date('Y-m-d H:i:s');
    $out[] = "-- Password for all demo accounts: password";
    $out[] = "-- ==================================================";
    $out[] = "";
    $out[] = "SET FOREIGN_KEY_CHECKS = 0;";
    $out[] = "";

    foreach ($tables as $table) {
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll();
        $count = count($rows);

        $out[] = "-- Table: $table ($count rows)";
        $out[] = "TRUNCATE TABLE `$table`;";

        if (!empty($rows)) {
            $cols = array_keys($rows[0]);
            $colsSql = '`' . implode('`, `', $cols) . '`';

            foreach ($rows as $row) {
                $values = array_map(function($v) use ($pdo) {
                    if ($v === null) return 'NULL';
                    return $pdo->quote($v);
                }, array_values($row));
                $out[] = "INSERT INTO `$table` ($colsSql) VALUES (" . implode(', ', $values) . ");";
            }
        }
        $out[] = "";
    }

    $out[] = "SET FOREIGN_KEY_CHECKS = 1;";

    file_put_contents($outFile, implode(PHP_EOL, $out));
    echo "SUCCESS: Seeders exported to database/seeders.sql (" . count($out) . " lines)" . PHP_EOL;

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
