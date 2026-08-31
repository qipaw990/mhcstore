<?php
/**
 * CicalengkaGO Database Performance Optimization Migration
 * Safely adds indexes to high-traffic tables (orders, voice_calls, chats, items, stores, driver_locations)
 */

require_once __DIR__ . '/../config/database.php';

echo "🚀 [CicalengkaGO] Running Database Performance Optimization Index Migration...\n\n";

$indexesToEnsure = [
    'orders' => [
        'idx_orders_code' => ['order_code'],
        'idx_orders_cust_status' => ['customer_id', 'order_status'],
        'idx_orders_store_status' => ['store_id', 'order_status'],
        'idx_orders_dm_status' => ['delivery_man_id', 'order_status'],
        'idx_orders_status_created' => ['order_status', 'created_at'],
    ],
    'order_items' => [
        'idx_oi_order' => ['order_id'],
        'idx_oi_item' => ['item_id'],
    ],
    'chats' => [
        'idx_chats_order' => ['order_code'],
        'idx_chats_sender_rec' => ['sender_id', 'receiver_id'],
        'idx_chats_created' => ['created_at'],
    ],
    'voice_calls' => [
        'idx_vc_order_status' => ['order_code', 'status'],
        'idx_vc_caller' => ['caller_id', 'status'],
        'idx_vc_receiver' => ['receiver_id', 'status'],
        'idx_vc_status_updated' => ['status', 'updated_at'],
    ],
    'items' => [
        'idx_items_store_status' => ['store_id', 'status'],
        'idx_items_cat_status' => ['category_id', 'status'],
    ],
    'stores' => [
        'idx_stores_vendor' => ['vendor_id'],
        'idx_stores_open_status' => ['is_open', 'status'],
        'idx_stores_module_zone' => ['module_id', 'zone_id'],
    ],
    'driver_locations' => [
        'idx_dl_dm_updated' => ['delivery_man_id', 'updated_at'],
    ],
    'wallet_transactions' => [
        'idx_wt_wallet_created' => ['wallet_id', 'created_at'],
    ]
];

$pdo = Database::getInstance();

foreach ($indexesToEnsure as $table => $indexes) {
    // Check if table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$table]);
    if (!$stmt->fetch()) {
        echo "⚠️ Table `$table` does not exist. Skipping.\n";
        continue;
    }

    // Get existing indexes
    $existingIndexes = [];
    $stmt = $pdo->query("SHOW INDEX FROM `$table`");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingIndexes[$row['Key_name']] = true;
    }

    foreach ($indexes as $indexName => $columns) {
        if (isset($existingIndexes[$indexName])) {
            echo "   ✓ Index `$indexName` on `$table` already exists.\n";
            continue;
        }

        // Check if all columns exist in the table before adding index
        $validColumns = [];
        foreach ($columns as $col) {
            $checkCol = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
            $checkCol->execute([$col]);
            if ($checkCol->fetch()) {
                $validColumns[] = "`$col`";
            }
        }

        if (count($validColumns) === count($columns)) {
            $colsSql = implode(', ', $validColumns);
            try {
                $pdo->exec("CREATE INDEX `$indexName` ON `$table` ($colsSql)");
                echo "   ➕ Created index `$indexName` on `$table` ($colsSql).\n";
            } catch (\Exception $e) {
                echo "   ⚠️ Failed to create index `$indexName` on `$table`: " . $e->getMessage() . "\n";
            }
        }
    }
}

echo "\n✨ [CicalengkaGO] All Database Performance Indexes Successfully Optimized!\n";
