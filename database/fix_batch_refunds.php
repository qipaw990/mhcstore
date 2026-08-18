<?php
/**
 * Migration Script to Consolidate Duplicate Customer Batch Order Refund Transactions into 1 Transaction
 */
require_once __DIR__ . '/../app/core/Database.php';
if (!defined('APP_PATH')) {
    define('APP_PATH', __DIR__ . '/../app');
}

use App\Core\Database;

try {
    echo "Starting Batch Refund Repair...\n";

    // Find all batch refund transactions split by order
    $splitRefunds = Database::query("
        SELECT wt.id, wt.wallet_id, wt.amount, wt.description, wt.reference_id, w.user_id
        FROM `wallet_transactions` wt
        JOIN `wallets` w ON wt.wallet_id = w.id
        WHERE wt.category IN ('refund', 'order_refund') AND wt.description LIKE 'Refund pengembalian dana batch%'
        ORDER BY wt.id ASC
    ");

    $batchGroups = [];
    foreach ($splitRefunds as $tx) {
        if (preg_match('/batch pesanan (BATCH-[A-Z0-9]+)/i', $tx['description'], $m)) {
            $bId = $m[1];
            $batchGroups[$bId][] = $tx;
        }
    }

    foreach ($batchGroups as $batchId => $txs) {
        if (count($txs) <= 1) continue;

        echo "Found " . count($txs) . " split refund entries for {$batchId}.\n";
        $totalAmount = array_sum(array_column($txs, 'amount'));
        $firstTxId = $txs[0]['id'];

        $desc = "Refund pengembalian dana batch pesanan {$batchId} (" . count($txs) . " Toko)";

        // Keep first transaction and update its amount and reference_id
        Database::update('wallet_transactions', [
            'amount'       => $totalAmount,
            'description'  => $desc,
            'reference_id' => $batchId
        ], 'id = ?', [$firstTxId]);

        // Delete remaining duplicate transactions
        $toDelete = array_slice($txs, 1);
        foreach ($toDelete as $dt) {
            Database::delete('wallet_transactions', 'id = ?', [$dt['id']]);
        }

        echo "Consolidated {$batchId} refund into single transaction ID {$firstTxId} with total Rp {$totalAmount}.\n";
    }

    echo "Batch Refund Repair Completed Successfully!\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
