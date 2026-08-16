<?php
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');

require_once APP_PATH . '/config/constants.php';
require_once APP_PATH . '/helpers/auth.php';
require_once APP_PATH . '/helpers/response.php';
require_once APP_PATH . '/helpers/validation.php';
require_once APP_PATH . '/helpers/upload.php';
require_once APP_PATH . '/helpers/format.php';
require_once APP_PATH . '/helpers/distance.php';
require_once APP_PATH . '/autoload.php';

use App\Core\Database;

echo "=== DIAGNOSIS ORDERS IN DATABASE ===\n";

$ordersCount = Database::fetchOne("SELECT COUNT(*) as c FROM orders")['c'] ?? 0;
echo "Total orders in orders table: " . $ordersCount . "\n";

$orders = Database::query("SELECT id, order_code, customer_id, store_id, order_type, order_status, total_amount, created_at FROM orders ORDER BY id DESC LIMIT 10");
echo "Sample orders:\n";
print_r($orders);

echo "\nExecuting AdminController Orders Query (INNER JOIN user):\n";
$innerJoinOrders = Database::query("
    SELECT o.*, s.name as store_name, s.latitude as store_lat, s.longitude as store_lng,
           u.name as customer_name, u.phone as customer_phone,
           dmu.name as dm_name, dmu.phone as dm_phone, dm.current_latitude as dm_lat, dm.current_longitude as dm_lng
    FROM `orders` o
    LEFT JOIN `stores` s ON o.store_id = s.id
    JOIN `users` u ON o.customer_id = u.id
    LEFT JOIN `delivery_men` dm ON o.delivery_man_id = dm.id
    LEFT JOIN `users` dmu ON dm.user_id = dmu.id
    ORDER BY o.id DESC
");
echo "Count with JOIN users: " . count($innerJoinOrders) . "\n";

echo "\nExecuting AdminController Orders Query (LEFT JOIN user):\n";
$leftJoinOrders = Database::query("
    SELECT o.*, s.name as store_name, s.latitude as store_lat, s.longitude as store_lng,
           u.name as customer_name, u.phone as customer_phone,
           dmu.name as dm_name, dmu.phone as dm_phone, dm.current_latitude as dm_lat, dm.current_longitude as dm_lng
    FROM `orders` o
    LEFT JOIN `stores` s ON o.store_id = s.id
    LEFT JOIN `users` u ON o.customer_id = u.id
    LEFT JOIN `delivery_men` dm ON o.delivery_man_id = dm.id
    LEFT JOIN `users` dmu ON dm.user_id = dmu.id
    ORDER BY o.id DESC
");
echo "Count with LEFT JOIN users: " . count($leftJoinOrders) . "\n";
