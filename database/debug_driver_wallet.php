<?php
/**
 * DEBUG: Driver Wallet Commission Inspector
 * Run: php database/debug_driver_wallet.php
 */
require_once __DIR__ . '/../app/autoload.php';

use App\Core\Database;

echo "=== DRIVER WALLET COMMISSION DEBUG ===\n\n";

// 1. Check all drivers
$drivers = Database::query("
    SELECT dm.id, dm.user_id, dm.is_online, dm.total_orders, dm.is_active,
           u.name, u.email
    FROM delivery_men dm
    JOIN users u ON dm.user_id = u.id
    ORDER BY dm.id DESC
    LIMIT 10
");
echo "--- DRIVERS ---\n";
foreach ($drivers as $d) {
    echo "  dm.id={$d['id']} user_id={$d['user_id']} name={$d['name']} total_orders={$d['total_orders']}\n";
}

echo "\n--- WALLETS (delivery_man type) ---\n";
$wallets = Database::query("
    SELECT w.*, u.name, u.email
    FROM wallets w
    JOIN users u ON w.user_id = u.id
    WHERE w.user_type = 'delivery_man'
    ORDER BY w.id DESC
");
foreach ($wallets as $w) {
    echo "  wallet.id={$w['id']} user_id={$w['user_id']} name={$w['name']} balance={$w['balance']} total_earned={$w['total_earned']}\n";
}

echo "\n--- ALL WALLETS (showing user_type) ---\n";
$allWallets = Database::query("
    SELECT w.id, w.user_id, w.user_type, w.balance, u.name
    FROM wallets w
    JOIN users u ON w.user_id = u.id
    ORDER BY w.id DESC
    LIMIT 20
");
foreach ($allWallets as $w) {
    echo "  wallet.id={$w['id']} user_id={$w['user_id']} type={$w['user_type']} balance={$w['balance']} name={$w['name']}\n";
}

echo "\n--- DELIVERED ORDERS (last 20) ---\n";
$deliveredOrders = Database::query("
    SELECT o.id, o.order_code, o.delivery_man_id, o.order_status, o.payment_method,
           o.payment_status, o.delivery_charge, o.order_amount, o.delivered_at,
           dm.user_id as dm_user_id, u.name as driver_name
    FROM orders o
    LEFT JOIN delivery_men dm ON o.delivery_man_id = dm.id
    LEFT JOIN users u ON dm.user_id = u.id
    WHERE o.order_status = 'delivered'
    ORDER BY o.id DESC
    LIMIT 20
");
foreach ($deliveredOrders as $o) {
    $pMethod = strtolower(trim($o['payment_method'] ?? ''));
    $isCod = in_array($pMethod, ['cod', 'cash', 'tunai']);
    echo "  order.id={$o['id']} code={$o['order_code']} dm_id={$o['delivery_man_id']} dm_user={$o['dm_user_id']} driver={$o['driver_name']}\n";
    echo "    payment_method=[{$o['payment_method']}] payment_status={$o['payment_status']} is_cod=" . ($isCod ? 'YES' : 'NO') . "\n";
    echo "    delivery_charge={$o['delivery_charge']} order_amount={$o['order_amount']}\n";

    if ($o['dm_user_id']) {
        $credited = Database::fetchOne("
            SELECT wt.id, wt.amount, wt.category, wt.reference_id, wt.created_at
            FROM wallet_transactions wt
            JOIN wallets w ON wt.wallet_id = w.id
            WHERE w.user_id = ? AND wt.category = 'order_earning'
              AND (wt.reference_id = ? OR wt.reference_id = ?)
            LIMIT 1
        ", [(int)$o['dm_user_id'], (string)$o['id'], (string)$o['order_code']]);

        if ($credited) {
            echo "    COMMISSION CREDITED: tx.id={$credited['id']} amount={$credited['amount']} at={$credited['created_at']}\n";
        } else {
            echo "    COMMISSION NOT CREDITED " . ($isCod ? "(COD - expected)" : "(NON-COD - BUG!)") . "\n";
        }
    } else {
        echo "    No driver assigned\n";
    }
    echo "\n";
}

echo "--- WALLET TRANSACTIONS (driver earnings, last 20) ---\n";
$txs = Database::query("
    SELECT wt.id, wt.wallet_id, wt.amount, wt.type, wt.category, wt.reference_id, wt.description, wt.created_at,
           w.user_id, w.user_type, u.name
    FROM wallet_transactions wt
    JOIN wallets w ON wt.wallet_id = w.id
    JOIN users u ON w.user_id = u.id
    WHERE w.user_type = 'delivery_man'
    ORDER BY wt.id DESC
    LIMIT 20
");
foreach ($txs as $tx) {
    echo "  tx.id={$tx['id']} user={$tx['name']} amount={$tx['amount']} type={$tx['type']} category={$tx['category']} ref={$tx['reference_id']}\n";
    echo "    desc={$tx['description']} at={$tx['created_at']}\n";
}

echo "\n--- ORDERS WITH NON-COD PAYMENTS AND ASSIGNED DRIVER (uncredited check) ---\n";
$nonCodDelivered = Database::query("
    SELECT o.id, o.order_code, o.payment_method, o.delivery_man_id, o.delivery_charge,
           dm.user_id as dm_user_id, u.name as driver_name
    FROM orders o
    LEFT JOIN delivery_men dm ON o.delivery_man_id = dm.id
    LEFT JOIN users u ON dm.user_id = u.id
    WHERE o.order_status = 'delivered'
      AND o.delivery_man_id IS NOT NULL
      AND LOWER(TRIM(COALESCE(o.payment_method, ''))) NOT IN ('cod', 'cash', 'tunai')
    ORDER BY o.id DESC
    LIMIT 20
");
echo "Found " . count($nonCodDelivered) . " non-COD delivered orders with driver assigned.\n\n";
foreach ($nonCodDelivered as $o) {
    $credited = Database::fetchOne("
        SELECT wt.id, wt.amount
        FROM wallet_transactions wt
        JOIN wallets w ON wt.wallet_id = w.id
        WHERE w.user_id = ? AND wt.category = 'order_earning'
          AND (wt.reference_id = ? OR wt.reference_id = ?)
        LIMIT 1
    ", [(int)$o['dm_user_id'], (string)$o['id'], (string)$o['order_code']]);

    $status = $credited ? "CREDITED (amount={$credited['amount']})" : "NOT CREDITED";
    echo "  Order #{$o['order_code']} (id={$o['id']}) driver={$o['driver_name']} dm_user_id={$o['dm_user_id']} payment={$o['payment_method']} charge={$o['delivery_charge']}: $status\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
