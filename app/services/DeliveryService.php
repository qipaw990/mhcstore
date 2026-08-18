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

    const MAX_BATCH_ORDERS = 3; // Max orders a driver can carry in one trip

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
        if (!empty($order['delivery_batch_id'])) {
            throw new Exception("Pesanan ini sudah masuk batch pengiriman.");
        }

        // Load current active batch orders
        $activeBatchId  = $dm['active_batch_id'] ?? null;
        $activeOrderIds = !empty($dm['active_order_ids']) ? json_decode($dm['active_order_ids'], true) : [];

        // Validate batch size
        if (!empty($activeOrderIds) && count($activeOrderIds) >= self::MAX_BATCH_ORDERS) {
            throw new Exception("Trip Anda sudah penuh (" . self::MAX_BATCH_ORDERS . " pesanan). Selesaikan pengantaran sebelum mengambil pesanan baru.");
        }

        // Validate existing active orders are still in valid pickup states
        if (!empty($activeOrderIds)) {
            foreach ($activeOrderIds as $existingId) {
                $existing = $this->orderModel->find($existingId);
                if ($existing && in_array($existing['order_status'], ['on_the_way', 'delivered', 'canceled'])) {
                    // This order is already picked up or done; allow adding more
                    continue;
                }
            }
        }

        return Database::transaction(function () use ($dm, $order, $orderId, $activeBatchId, $activeOrderIds) {
            // Create new batch ID if this is the first order
            if (empty($activeBatchId)) {
                $activeBatchId = 'BATCH-' . strtoupper(substr(uniqid(), -6));
            }

            $activeOrderIds[] = $orderId;
            $sequence = count($activeOrderIds);

            // Update the order
            Database::update('orders', [
                'delivery_man_id'   => $dm['id'],
                'order_status'      => 'processing',
                'processing_at'     => date('Y-m-d H:i:s'),
                'delivery_batch_id' => $activeBatchId,
                'pickup_sequence'   => $sequence,
            ], 'id = ?', [$orderId]);

            // Update driver batch state
            Database::update('delivery_men', [
                'current_order_id' => $orderId,         // latest accepted
                'active_batch_id'  => $activeBatchId,
                'active_order_ids' => json_encode($activeOrderIds),
            ], 'id = ?', [$dm['id']]);

            return [
                'batch_id'    => $activeBatchId,
                'order_count' => count($activeOrderIds),
                'sequence'    => $sequence,
                'order_code'  => $order['order_code'],
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
                // Verify OTP
                if (empty($otp) || trim($otp) !== trim($order['otp'])) {
                    throw new Exception("Kode OTP pengantaran salah!");
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

                // Check if ALL batch orders are now delivered
                $batchId = $order['delivery_batch_id'];
                if (!empty($batchId)) {
                    // Mark this one delivered first
                    Database::update('orders', $updateData, 'id = ?', [$orderId]);
                    $updateData = []; // already applied

                    // Count remaining non-delivered in batch
                    $remaining = (int)Database::fetchColumn(
                        "SELECT COUNT(*) FROM `orders`
                         WHERE `delivery_batch_id` = ?
                           AND `order_status` NOT IN ('delivered', 'canceled')
                           AND `id` != ?",
                        [$batchId, $orderId]
                    );

                    if ($remaining === 0) {
                        // All done — compute total km and credit batch commission
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
                    }
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

    private function creditBatchCommission(array $dm, string $batchId, array $lastOrder): void
    {
        $batchOrders = Database::query(
            "SELECT * FROM `orders` WHERE `delivery_batch_id` = ? AND `order_status` = 'delivered'",
            [$batchId]
        );

        $totalKm = $this->calcBatchTotalKm($dm, $batchOrders);
        $totalCommission = $this->calcBatchCommissionAmount($batchOrders, $totalKm);

        // Split commission proportionally per order
        foreach ($batchOrders as $ord) {
            $ratio   = count($batchOrders) > 0 ? 1 / count($batchOrders) : 1;
            $earning = round($totalCommission * $ratio, 2);

            $alreadyCredited = Database::fetchOne(
                "SELECT id FROM `wallet_transactions` WHERE `wallet_id` = (SELECT id FROM wallets WHERE user_id = ? AND type = 'delivery_man' LIMIT 1) AND `category` = 'order_earning' AND `reference_id` = ? LIMIT 1",
                [$dm['user_id'], (string)$ord['id']]
            );

            if (!$alreadyCredited) {
                $this->walletModel->credit(
                    $dm['user_id'],
                    $earning,
                    'order_earning',
                    "Komisi batch {$batchId} – #{$ord['order_code']} ({$totalKm} km total)",
                    (string)$ord['id']
                );
            }
        }
    }

    /**
     * Calculate total km of the route:
     * Driver pos → Store1 → Store2 → … → Customer destination (last order's address)
     */
    private function calcBatchTotalKm(array $dm, array $orders): float
    {
        if (empty($orders)) return 0.0;

        $driverLat = (float)($dm['current_latitude']  ?? -6.9840);
        $driverLng = (float)($dm['current_longitude'] ?? 107.8340);

        $totalKm = 0.0;
        $prevLat = $driverLat;
        $prevLng = $driverLng;

        // Sort by pickup_sequence
        usort($orders, fn($a, $b) => (int)$a['pickup_sequence'] <=> (int)$b['pickup_sequence']);

        foreach ($orders as $ord) {
            $storeLat = (float)($ord['store_lat'] ?? -6.9835);
            $storeLng = (float)($ord['store_lng'] ?? 107.8335);
            $totalKm += haversine_distance($prevLat, $prevLng, $storeLat, $storeLng);
            $prevLat  = $storeLat;
            $prevLng  = $storeLng;
        }

        // Last leg: last store → customer destination
        $lastOrder = end($orders);
        $addr      = is_string($lastOrder['delivery_address_json'] ?? null)
            ? json_decode($lastOrder['delivery_address_json'], true)
            : [];
        $destLat   = (float)($addr['lat'] ?? -6.9855);
        $destLng   = (float)($addr['lng'] ?? 107.8350);
        $totalKm  += haversine_distance($prevLat, $prevLng, $destLat, $destLng);

        return max(0.5, round($totalKm, 2));
    }

    /**
     * Commission = total_km × average_rate_per_km × 0.85
     * rate_per_km = delivery_charge / distance_km per order
     */
    private function calcBatchCommissionAmount(array $orders, float $totalKm): float
    {
        if (empty($orders)) return 0.0;

        $sumCharge   = array_sum(array_column($orders, 'delivery_charge'));
        $sumDistance = array_sum(array_map(fn($o) => max(0.5, (float)$o['distance_km']), $orders));
        $ratePerKm   = $sumDistance > 0 ? ($sumCharge / $sumDistance) : 3000.0;

        return max(500, $totalKm * $ratePerKm * 0.85);
    }
}
