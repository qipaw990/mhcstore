<?php
namespace App\Services;

use App\Core\Database;
use App\Models\Order;
use App\Models\DeliveryMan;
use App\Models\Wallet;
use Exception;

class DeliveryService
{
    private Order $orderModel;
    private DeliveryMan $dmModel;
    private Wallet $walletModel;

    const MAX_BATCH_ORDERS = 10; // Max orders a driver can carry in one trip

    public function __construct()
    {
        $this->orderModel = new Order();
        $this->dmModel    = new DeliveryMan();
        $this->walletModel = new Wallet();
    }

    /**
     * Accept an order and add it to the driver's active batch.
     * A driver can hold up to MAX_BATCH_ORDERS simultaneously.
     */
    public function acceptOrder(int $dmUserId, int $orderId): array
    {
        $dm = $this->dmModel->findByUserId($dmUserId);
        if (!$dm || !$dm['is_online']) {
            throw new Exception("Status driver harus online untuk menerima pesanan.");
        }

        $order = $this->orderModel->find($orderId);
        if (!$order) {
            throw new Exception("Pesanan tidak ditemukan.");
        }
        if (!empty($order['delivery_man_id'])) {
            throw new Exception("Pesanan sudah diambil oleh driver lain.");
        }

        // Check if this order is part of an unassigned multi-store batch
        $batchIdToAccept = $order['delivery_batch_id'] ?? null;
        $ordersToAccept  = [];

        if ($batchIdToAccept) {
            $ordersToAccept = Database::query(
                "SELECT * FROM `orders` WHERE `delivery_batch_id` = ? AND (`delivery_man_id` IS NULL OR `delivery_man_id` = 0) AND `order_status` NOT IN ('canceled', 'delivered')",
                [$batchIdToAccept]
            );
        }

        if (empty($ordersToAccept)) {
            $ordersToAccept = [$order];
        }

        // Load current active batch orders
        $activeBatchId  = $dm['active_batch_id'] ?? null;
        $activeOrderIds = !empty($dm['active_order_ids']) ? json_decode($dm['active_order_ids'], true) : [];

        // Validate batch size
        if (count($activeOrderIds) + count($ordersToAccept) > self::MAX_BATCH_ORDERS) {
            throw new Exception("Jumlah pesanan melebihi batas maksimal trip driver (" . self::MAX_BATCH_ORDERS . " pesanan).");
        }

        return Database::transaction(function () use ($dm, $ordersToAccept, $batchIdToAccept, $activeBatchId, $activeOrderIds) {
            if (empty($activeBatchId)) {
                $activeBatchId = $batchIdToAccept ?: ('BATCH-' . strtoupper(substr(uniqid(), -6)));
            }

            $lastAcceptedId = null;

            foreach ($ordersToAccept as $ord) {
                $ordId = (int)$ord['id'];
                if (!in_array($ordId, $activeOrderIds)) {
                    $activeOrderIds[] = $ordId;
                }
                $sequence = count($activeOrderIds);

                Database::update('orders', [
                    'delivery_man_id'   => $dm['id'],
                    'order_status'      => 'processing',
                    'processing_at'     => date('Y-m-d H:i:s'),
                    'delivery_batch_id' => $activeBatchId,
                    'pickup_sequence'   => $sequence,
                ], 'id = ?', [$ordId]);

                $lastAcceptedId = $ordId;
            }

            Database::update('delivery_men', [
                'current_order_id' => $lastAcceptedId,
                'active_batch_id'  => $activeBatchId,
                'active_order_ids' => json_encode($activeOrderIds),
            ], 'id = ?', [$dm['id']]);

            return [
                'batch_id'    => $activeBatchId,
                'order_count' => count($activeOrderIds),
                'sequence'    => count($activeOrderIds),
                'order_code'  => $ordersToAccept[0]['order_code'],
            ];
        });
    }

    /**
     * Update status for one order in the batch.
     * When ALL batch orders are 'delivered', credit driver commission based on total km.
     */
    public function updateOrderStatus(int $dmUserId, int $orderId, string $status, ?string $otp = null): bool
    {
        $dm    = $this->dmModel->findByUserId($dmUserId);
        $order = $this->orderModel->find($orderId);

        if (!$order || $order['delivery_man_id'] != $dm['id']) {
            throw new Exception("Akses pesanan tidak valid.");
        }

        return Database::transaction(function () use ($dm, $order, $orderId, $status, $otp) {
            $updateData = ['order_status' => $status];
            $now        = date('Y-m-d H:i:s');

            if ($status === 'picked_up' || $status === 'on_the_way') {
                $updateData['picked_up_at']  = $now;
                $updateData['order_status']  = 'on_the_way';

            } elseif ($status === 'delivered') {
                // Verify OTP for single non-batch order (batch orders verify inside batch block)
                if (empty($order['delivery_batch_id'])) {
                    if (empty($otp) || trim($otp) !== trim($order['otp'])) {
                        throw new Exception("Kode OTP pengantaran salah!");
                    }
                }
                $updateData['delivered_at']     = $now;
                $updateData['payment_status']   = 'paid';

                // Credit Vendor Earnings (90% of order amount)
                if (!empty($order['store_id'])) {
                    $store = Database::fetchOne("SELECT vendor_id, name FROM stores WHERE id = ?", [$order['store_id']]);
                    if ($store) {
                        $vendorEarning = (float)$order['order_amount'] * 0.90;
                        $this->walletModel->credit(
                            $store['vendor_id'],
                            $vendorEarning,
                            'order_earning',
                            "Penjualan pesanan #{$order['order_code']}",
                            (string)$order['id']
                        );
                    }
                }

                // Check if this is a batch delivery
                $batchId = $order['delivery_batch_id'];
                if (!empty($batchId)) {
                    // Fetch all non-delivered orders in this batch
                    $remainingOrders = Database::query(
                        "SELECT * FROM `orders`
                         WHERE `delivery_batch_id` = ?
                           AND `order_status` NOT IN ('delivered', 'canceled')",
                        [$batchId]
                    );

                    // Check if OTP matches target order or any order in the batch
                    $validOtp = false;
                    if (!empty($otp)) {
                        if (trim($otp) === trim($order['otp'])) {
                            $validOtp = true;
                        } else {
                            foreach ($remainingOrders as $bCheck) {
                                if (trim($otp) === trim($bCheck['otp'])) {
                                    $validOtp = true;
                                    break;
                                }
                            }
                        }
                    }

                    if (!$validOtp) {
                        throw new Exception("Kode OTP pengantaran salah!");
                    }

                    foreach ($remainingOrders as $bOrdToDel) {
                        $subUpdateData = [
                            'order_status'   => 'delivered',
                            'delivered_at'   => $now,
                            'payment_status' => 'paid'
                        ];
                        Database::update('orders', $subUpdateData, 'id = ?', [$bOrdToDel['id']]);

                        // Credit Vendor Earnings (90% of order amount)
                        if (!empty($bOrdToDel['store_id'])) {
                            $store = Database::fetchOne("SELECT vendor_id, name FROM stores WHERE id = ?", [$bOrdToDel['store_id']]);
                            if ($store) {
                                $vendorEarning = (float)$bOrdToDel['order_amount'] * 0.90;
                                $this->walletModel->credit(
                                    $store['vendor_id'],
                                    $vendorEarning,
                                    'order_earning',
                                    "Penjualan pesanan #{$bOrdToDel['order_code']}",
                                    (string)$bOrdToDel['id']
                                );
                            }
                        }
                    }

                    $updateData = []; // applied in loop above

                    // All batch orders delivered — compute total km and credit batch commission
                    $this->creditBatchCommission($dm, $batchId, $order);
                    // Clear driver batch state
                    Database::update('delivery_men', [
                        'current_order_id' => null,
                        'active_batch_id'  => null,
                        'active_order_ids' => null,
                        'total_orders'     => Database::fetchColumn(
                            "SELECT COUNT(*) FROM `orders` WHERE `delivery_man_id` = ? AND `order_status` = 'delivered'",
                            [$dm['id']]
                        ),
                    ], 'id = ?', [$dm['id']]);
                } else {
                    // Single order delivery (no batch)
                    $dmEarning = $this->calcSingleCommission($order);
                    $this->walletModel->credit(
                        $dm['user_id'],
                        $dmEarning,
                        'order_earning',
                        "Komisi pengantaran #{$order['order_code']}",
                        (string)$order['id']
                    );
                    Database::execute(
                        "UPDATE `delivery_men` SET `total_orders` = `total_orders` + 1, `current_order_id` = NULL, `active_batch_id` = NULL, `active_order_ids` = NULL WHERE `id` = ?",
                        [$dm['id']]
                    );
                }
            }

            if (!empty($updateData)) {
                Database::update('orders', $updateData, 'id = ?', [$orderId]);
            }

            return true;
        });
    }

    /**
     * Get all orders in the driver's active batch, with full details.
     */
    public function getActiveBatch(int $dmUserId): array
    {
        $dm = $this->dmModel->findByUserId($dmUserId);
        if (!$dm || empty($dm['active_batch_id'])) {
            return ['batch_id' => null, 'orders' => [], 'total_km' => 0, 'est_commission' => 0];
        }

        $batchId = $dm['active_batch_id'];
        $orders  = Database::query(
            "SELECT o.*,
                    s.name as store_name, s.address as store_address,
                    s.latitude as store_lat, s.longitude as store_lng,
                    u.name as customer_name, u.phone as customer_phone
             FROM `orders` o
             LEFT JOIN `stores` s ON o.store_id = s.id
             LEFT JOIN `users`  u ON o.customer_id = u.id
             WHERE o.delivery_batch_id = ?
               AND o.order_status NOT IN ('canceled')
             ORDER BY o.pickup_sequence ASC",
            [$batchId]
        );

        foreach ($orders as &$ord) {
            $ord['delivery_address'] = is_string($ord['delivery_address_json'] ?? null)
                ? json_decode($ord['delivery_address_json'], true)
                : ($ord['delivery_address'] ?? []);
            $ord['items'] = Database::query(
                "SELECT oi.*, COALESCE(p.name, 'Item') as item_name 
                 FROM `order_items` oi 
                 LEFT JOIN `products` p ON oi.product_id = p.id 
                 WHERE oi.order_id = ?",
                [$ord['id']]
            );
        }

        $totalKm       = $this->calcBatchTotalKm($dm, $orders);
        $estCommission = $this->calcBatchCommissionAmount($orders, $totalKm);

        return [
            'batch_id'      => $batchId,
            'orders'        => $orders,
            'order_count'   => count($orders),
            'total_km'      => round($totalKm, 1),
            'est_commission'=> round($estCommission, 0),
            'slots_left'    => max(0, self::MAX_BATCH_ORDERS - count($orders)),
        ];
    }

    // ──────────────── Private helpers ────────────────

    private function calcSingleCommission(array $order): float
    {
        return (float)$order['delivery_charge'] * 0.85;
    }

    public function creditBatchCommission(array $dm, string $batchId, ?array $lastOrder = null): void
    {
        $batchOrders = Database::query(
            "SELECT * FROM `orders` WHERE `delivery_batch_id` = ? AND `order_status` = 'delivered'",
            [$batchId]
        );

        if (empty($batchOrders)) return;

        $totalKm = $this->calcBatchTotalKm($dm, $batchOrders);
        $totalCommission = $this->calcBatchCommissionAmount($batchOrders, $totalKm);

        // Check if ANY sub-order or the batchId was already credited to driver's wallet
        $orderIds = array_map(fn($o) => (string)$o['id'], $batchOrders);
        $orderIds[] = $batchId;
        $inPlaceholders = implode(',', array_fill(0, count($orderIds), '?'));

        $alreadyCredited = Database::fetchOne(
            "SELECT wt.id FROM `wallet_transactions` wt
             JOIN `wallets` w ON wt.wallet_id = w.id
             WHERE w.user_id = ? AND w.user_type = 'delivery_man' 
               AND wt.category = 'order_earning' 
               AND wt.reference_id IN ({$inPlaceholders}) LIMIT 1",
            array_merge([$dm['user_id']], $orderIds)
        );

        if (!$alreadyCredited) {
            $storeCount = count($batchOrders);
            $this->walletModel->credit(
                $dm['user_id'],
                $totalCommission,
                'order_earning',
                "Komisi Batch {$batchId} ({$storeCount} Toko, {$totalKm} km total)",
                $batchId
            );
        }
    }

    private function calcBatchTotalKm(array $dm, array $orders): float
    {
        if (empty($orders)) return 0.0;

        // Sort by pickup_sequence
        usort($orders, fn($a, $b) => (int)($a['pickup_sequence'] ?? 1) <=> (int)($b['pickup_sequence'] ?? 1));

        $totalKm = 0.0;
        $prevLat = (float)($orders[0]['store_lat'] ?? $orders[0]['latitude'] ?? -6.9835);
        $prevLng = (float)($orders[0]['store_lng'] ?? $orders[0]['longitude'] ?? 107.8335);

        for ($i = 1; $i < count($orders); $i++) {
            $stLat = (float)($orders[$i]['store_lat'] ?? $orders[$i]['latitude'] ?? -6.9835);
            $stLng = (float)($orders[$i]['store_lng'] ?? $orders[$i]['longitude'] ?? 107.8335);
            if ($stLat != 0 && $stLng != 0 && $prevLat != 0 && $prevLng != 0) {
                $totalKm += haversine_distance($prevLat, $prevLng, $stLat, $stLng);
                $prevLat  = $stLat;
                $prevLng  = $stLng;
            }
        }

        // Last leg: last store → customer destination
        $lastOrder = end($orders);
        $addr      = is_string($lastOrder['delivery_address_json'] ?? null)
            ? json_decode($lastOrder['delivery_address_json'], true)
            : ($lastOrder['delivery_address'] ?? []);
        $destLat   = (float)($addr['lat'] ?? -6.9855);
        $destLng   = (float)($addr['lng'] ?? 107.8350);

        if ($destLat != 0 && $destLng != 0 && $prevLat != 0 && $prevLng != 0) {
            $totalKm += haversine_distance($prevLat, $prevLng, $destLat, $destLng);
        }

        // Fallback to sum of order recorded distance_km if calculated distance is minimal
        if ($totalKm < 0.3) {
            $totalKm = array_sum(array_map(fn($o) => max(0.5, (float)($o['distance_km'] ?? 1.5)), $orders));
        }

        return max(0.5, round($totalKm, 2));
    }

    /**
     * Net Driver Commission = 85% of total delivery charges for all orders in the batch
     */
    private function calcBatchCommissionAmount(array $orders, float $totalKm): float
    {
        if (empty($orders)) return 0.0;

        $sumCharge = array_sum(array_column($orders, 'delivery_charge'));
        $netCommission = $sumCharge * 0.85;

        return max(500.0, round($netCommission, 0));
    }
}
