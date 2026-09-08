<?php
/**
 * Auto-Repair & Credit Script for Driver Non-COD Delivered Orders
 * Ensures that any non-COD orders delivered by drivers are credited to their delivery_man wallet.
 */

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/models/Wallet.php';
require_once __DIR__ . '/../app/models/Zone.php';
require_once __DIR__ . '/../app/helpers/distance.php';

if (!defined('APP_PATH')) {
    define('APP_PATH', __DIR__ . '/../app');
}

use App\Core\Database;
use App\Models\Wallet;
use App\Models\Zone;

try {
    echo "=======================================================\n";
    echo " 🚴 MEMERIKSA KOMISI DRIVER PESANAN NON-COD DELIVERED...\n";
    echo "=======================================================\n";

    $walletModel = new Wallet();

    // Query all delivered orders that are non-COD with a delivery_man assigned
    $deliveredOrders = Database::query("
        SELECT o.id, o.order_code, o.delivery_man_id, o.delivery_charge, o.distance_km, 
               o.zone_id, o.payment_method, o.order_amount, o.delivered_at
        FROM `orders` o
        WHERE o.order_status = 'delivered'
          AND o.delivery_man_id IS NOT NULL 
          AND o.delivery_man_id > 0
          AND LOWER(TRIM(COALESCE(o.payment_method, ''))) NOT IN ('cod', 'cash', 'tunai')
        ORDER BY o.id ASC
    ");

    echo "Ditemukan " . count($deliveredOrders) . " total pesanan non-COD berstatus delivered.\n";

    $creditedCount = 0;
    $totalAmountCredited = 0;

    foreach ($deliveredOrders as $order) {
        $dmId = (int)$order['delivery_man_id'];
        
        // Find driver profile to get driver user_id
        $dm = Database::fetchOne(
            "SELECT * FROM `delivery_men` WHERE `id` = ? OR `user_id` = ? LIMIT 1",
            [$dmId, $dmId]
        );

        if (!$dm) {
            continue;
        }

        $userId = (int)$dm['user_id'];
        $driverWallet = $walletModel->getOrCreate($userId, 'delivery_man');

        // Check if commission for this order has already been credited
        $alreadyCredited = Database::fetchOne(
            "SELECT wt.id FROM `wallet_transactions` wt
             JOIN `wallets` w ON wt.wallet_id = w.id
             WHERE w.user_id = ? AND wt.category = 'order_earning' 
               AND (wt.reference_id = ? OR wt.reference_id = ?) LIMIT 1",
            [$userId, (string)$order['id'], (string)$order['order_code']]
        );

        if ($alreadyCredited) {
            continue;
        }

        // Calculate delivery commission (guaranteed minimum Rp 5.000)
        $charge = (float)($order['delivery_charge'] ?? 0);
        if ($charge <= 0) {
            $km = (float)($order['distance_km'] ?? 0);
            $tariff = Zone::getZoneTariff((int)($order['zone_id'] ?? 1));
            $charge = calculate_delivery_fee($km, $tariff['min_delivery_charge'], $tariff['per_km_delivery_charge']);
        }
        $driverEarning = max(5000.0, round($charge, 0));

        // Credit to delivery_man wallet
        $walletModel->credit(
            $userId,
            $driverEarning,
            'order_earning',
            "Komisi pengantaran pesanan #{$order['order_code']}",
            (string)$order['id'],
            'delivery_man'
        );

        $creditedCount++;
        $totalAmountCredited += $driverEarning;

        echo "  [+] Dikreditkan Rp " . number_format($driverEarning, 0, ',', '.') . " ke Driver #{$dm['id']} (User #{$userId}) untuk #{$order['order_code']}\n";
    }

    echo "-------------------------------------------------------\n";
    echo "✅ Selesai! Berhasil mengkreditkan {$creditedCount} komisi pesanan dengan total Rp " . number_format($totalAmountCredited, 0, ',', '.') . ".\n";
    echo "=======================================================\n";

} catch (\Throwable $e) {
    echo "⚠️ Error: " . $e->getMessage() . "\n";
}
