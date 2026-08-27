<?php
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');

require_once APP_PATH . '/config/constants.php';
require_once APP_PATH . '/helpers/format.php';
require_once APP_PATH . '/autoload.php';

use App\Core\Database;

$count = Database::fetchColumn("SELECT COUNT(*) FROM `order_items` WHERE `order_id` = 2");
if ((int)$count === 0) {
    Database::insert('order_items', [
        'order_id'     => 2,
        'product_id'   => 101,
        'product_name' => 'Ayam Geprek Sambal Hijau + Nasi',
        'price'        => 20000.00,
        'quantity'     => 1,
        'total_price'  => 20000.00,
    ]);
    Database::insert('order_items', [
        'order_id'     => 2,
        'product_id'   => 102,
        'product_name' => 'Es Jeruk Peras Segar Jumbo',
        'price'        => 7000.00,
        'quantity'     => 1,
        'total_price'  => 7000.00,
    ]);
    Database::insert('order_items', [
        'order_id'     => 2,
        'product_id'   => 103,
        'product_name' => 'Tahu & Tempe Crispy Extra',
        'price'        => 8000.00,
        'quantity'     => 1,
        'total_price'  => 8000.00,
    ]);
    echo "Successfully inserted 3 items into order_items for Order ID 2!\n";
} else {
    echo "Order ID 2 already has {$count} items.\n";
}
