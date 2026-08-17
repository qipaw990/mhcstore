<?php
require_once __DIR__ . '/../app/config/constants.php';
require_once __DIR__ . '/../app/core/Database.php';

use App\Core\Database;

try {
    echo "Clearing lingering driver assignments for canceled and unclaimed orders...\n";
    
    // 1. Clear delivery_man_id for canceled orders
    $rowsCanceled = Database::execute("UPDATE `orders` SET `delivery_man_id` = NULL WHERE `order_status` = 'canceled'");
    
    // 2. Clear delivery_man_id for pending or confirmed orders where driver hasn't accepted yet
    $rowsUnclaimed = Database::execute("UPDATE `orders` SET `delivery_man_id` = NULL WHERE `order_status` IN ('pending', 'confirmed')");

    // 3. Clear current_order_id on delivery_men where order is canceled or delivered
    Database::execute("UPDATE `delivery_men` dm LEFT JOIN `orders` o ON dm.current_order_id = o.id SET dm.current_order_id = NULL WHERE o.id IS NULL OR o.order_status IN ('delivered', 'canceled')");

    echo "Successfully updated order driver assignments.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
