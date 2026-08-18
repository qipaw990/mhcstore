<?php
/**
 * Migration Script to Consolidate Duplicate Batch Delivery Commissions into 1 Transaction
 */
require_once __DIR__ . '/../app/core/Database.php';
if (!defined('APP_PATH')) {
    define('APP_PATH', __DIR__ . '/../app');
}

use App\Core\Database;

try {
    echo "Starting Batch Commission Repair...\n";

    // Find all batch IDs that have split transactions
    $splitTxns = Database::query("
        SELECT wt.id, wt.wallet_id, wt.amount, wt.description, wt.reference_id, w.user_id
        FROM `wallet_transactions` wt
        JOIN `wallets` w ON wt.wallet_id = w.id
        WHERE wt.category = 'order_earning' AND wt.description LIKE 'Komisi batch %'
        ORDER BY wt.id ASC
    ");

    $batchGroups = [];
    foreach ($splitTxns as $tx) {
        if (preg_match('/Komisi batch (BATCH-[A-Z0-9]+)/i', $tx['description'], $m)) {
            $bId = $m[1];
            $batchGroups[$bId][] = $tx;
        }
    }

    foreach ($batchGroups as $batchId => $txs) {
        if (count($txs) <= 1) continue;

        echo "Found " . count($txs) . " split entries for {$batchId}.\n";
        $totalAmount = array_sum(array_column($txs, 'amount'));
        $walletId = $txs[0]['wallet_id'];
        $firstTxId = $txs[0]['id'];

        // Extract store count / km info from description if available
        $desc = "Komisi Batch {$batchId} (" . count($txs) . " Toko)";

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

        echo "Consolidated {$batchId} into single transaction ID {$firstTxId} with amount Rp {$totalAmount}.\n";
    }

    echo "Batch Commission Repair Completed Successfully!\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
