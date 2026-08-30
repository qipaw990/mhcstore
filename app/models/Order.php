<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Order extends Model
{
    protected string $table = 'orders';
    protected array $fillable = [
        'order_code', 'customer_id', 'store_id', 'delivery_man_id', 'module_id', 'zone_id',
        'order_amount', 'delivery_charge', 'coupon_code', 'coupon_discount', 'tax_amount',
        'total_amount', 'payment_status', 'payment_method', 'order_status', 'order_type',
        'delivery_address_json', 'order_notes', 'otp', 'distance_km', 'parcel_details_json',
        'is_scheduled', 'scheduled_at', 'confirmed_at', 'processing_at', 'handover_at',
        'picked_up_at', 'delivered_at', 'canceled_at', 'cancellation_reason'
    ];

    public function findByCode(string $orderCode): ?array
    {
        $sql = "SELECT o.*, s.name as store_name, s.phone as store_phone, s.address as store_address,
                       s.latitude as store_lat, s.longitude as store_lng, s.logo as store_logo,
                       u.name as customer_name, u.phone as customer_phone,
                       dm.vehicle_type, dm.vehicle_number, dm.current_latitude as dm_lat, dm.current_longitude as dm_lng,
                       dmu.name as dm_name, dmu.phone as dm_phone, dmu.avatar as dm_avatar
                FROM `orders` o
                LEFT JOIN `stores` s ON o.store_id = s.id
                JOIN `users` u ON o.customer_id = u.id
                LEFT JOIN `delivery_men` dm ON o.delivery_man_id = dm.id
                LEFT JOIN `users` dmu ON dm.user_id = dmu.id
                WHERE o.order_code = ? LIMIT 1";

        $order = Database::fetchOne($sql, [$orderCode]);
        if ($order) {
            // Strict Driver Visibility Rules:
            // If order is canceled OR hasn't been accepted by a driver yet, hide driver info
            if ($order['order_status'] === 'canceled' || !in_array($order['order_status'], ['processing', 'handover', 'on_the_way', 'delivered'])) {
                $order['delivery_man_id'] = null;
                $order['dm_name'] = null;
                $order['dm_phone'] = null;
                $order['dm_avatar'] = null;
                $order['vehicle_type'] = null;
                $order['vehicle_number'] = null;
                $order['dm_lat'] = null;
                $order['dm_lng'] = null;
            }

            $order['items'] = Database::query("SELECT oi.*, COALESCE(NULLIF(oi.product_name, ''), p.name, 'Menu Kuliner') as product_name, p.image as product_image FROM `order_items` oi LEFT JOIN `products` p ON oi.product_id = p.id WHERE oi.`order_id` = ?", [$order['id']]);
            $order['delivery_address'] = json_decode($order['delivery_address_json'] ?? '{}', true) ?: [];
            $order['parcel_details'] = json_decode($order['parcel_details_json'] ?? '{}', true) ?: [];

            if ($order['order_type'] === 'parcel' && !empty($order['parcel_details'])) {
                $order['store_name'] = $order['parcel_details']['pickup_address'] ?? 'Titik Penjemputan Paket';
                $order['store_lat'] = $order['parcel_details']['pickup_lat'] ?? $order['store_lat'] ?? -6.9840;
                $order['store_lng'] = $order['parcel_details']['pickup_lng'] ?? $order['store_lng'] ?? 107.8340;
            }

            $currentUserId = auth_id() ?: (int)($order['customer_id'] ?? 0);
            if ($currentUserId) {
                $order['review_info'] = (new \App\Models\Review())->getOrderReview((int)$order['id'], $currentUserId);
            }

            $this->enrichBatchOrderDetails($order);
        }
        return $order;
    }

    public function findByIdOrCode(string|int $idOrCode): ?array
    {
        if (is_numeric($idOrCode)) {
            $sql = "SELECT o.*, s.name as store_name, s.phone as store_phone, s.address as store_address,
                           s.latitude as store_lat, s.longitude as store_lng, s.logo as store_logo,
                           u.name as customer_name, u.phone as customer_phone,
                           dm.vehicle_type, dm.vehicle_number, dm.current_latitude as dm_lat, dm.current_longitude as dm_lng,
                           dmu.name as dm_name, dmu.phone as dm_phone, dmu.avatar as dm_avatar
                    FROM `orders` o
                    LEFT JOIN `stores` s ON o.store_id = s.id
                    JOIN `users` u ON o.customer_id = u.id
                    LEFT JOIN `delivery_men` dm ON o.delivery_man_id = dm.id
                    LEFT JOIN `users` dmu ON dm.user_id = dmu.id
                    WHERE o.id = ? LIMIT 1";
            $order = Database::fetchOne($sql, [(int)$idOrCode]);
            if ($order) {
                $order['items'] = Database::query("SELECT oi.*, COALESCE(NULLIF(oi.product_name, ''), p.name, 'Menu Kuliner') as product_name, p.image as product_image FROM `order_items` oi LEFT JOIN `products` p ON oi.product_id = p.id WHERE oi.`order_id` = ?", [$order['id']]);
                $order['delivery_address'] = json_decode($order['delivery_address_json'] ?? '{}', true) ?: [];
                $order['parcel_details'] = json_decode($order['parcel_details_json'] ?? '{}', true) ?: [];

                if ($order['order_type'] === 'parcel' && !empty($order['parcel_details'])) {
                    $order['store_name'] = $order['parcel_details']['pickup_address'] ?? 'Titik Penjemputan Paket';
                    $order['store_lat'] = $order['parcel_details']['pickup_lat'] ?? $order['store_lat'] ?? -6.9840;
                    $order['store_lng'] = $order['parcel_details']['pickup_lng'] ?? $order['store_lng'] ?? 107.8340;
                }

                $currentUserId = auth_id() ?: (int)($order['customer_id'] ?? 0);
                if ($currentUserId) {
                    $order['review_info'] = (new \App\Models\Review())->getOrderReview((int)$order['id'], $currentUserId);
                }

                $this->enrichBatchOrderDetails($order);
            }
            return $order;
        }

        return $this->findByCode((string)$idOrCode);
    }

    public function getItems(int $orderId): array
    {
        return Database::query(
            "SELECT oi.*, 
                    COALESCE(NULLIF(oi.product_name, ''), p.name, 'Menu Kuliner') as product_name, 
                    COALESCE(NULLIF(oi.product_name, ''), p.name, 'Menu Kuliner') as item_name, 
                    p.image as product_image,
                    s.name as store_name,
                    s.address as store_address,
                    s.id as store_id
             FROM `order_items` oi 
             LEFT JOIN `products` p ON oi.product_id = p.id 
             LEFT JOIN `orders` o ON oi.order_id = o.id
             LEFT JOIN `stores` s ON o.store_id = s.id
             WHERE oi.`order_id` = ?",
            [$orderId]
        ) ?: [];
    }

    public function attachMultiStoreDetails(?array &$order): void
    {
        $this->enrichBatchOrderDetails($order);
    }

    public function enrichBatchOrderDetails(?array &$order): void
    {
        if (empty($order) || empty($order['delivery_batch_id'])) {
            return;
        }

        $batchOrders = Database::query(
            "SELECT o.*, s.name as store_name, s.phone as store_phone, s.address as store_address,
                    s.latitude as store_lat, s.longitude as store_lng, s.logo as store_logo
             FROM `orders` o
             LEFT JOIN `stores` s ON o.store_id = s.id
             WHERE o.delivery_batch_id = ? AND o.order_status != 'canceled'
             ORDER BY o.pickup_sequence ASC, o.id ASC",
            [$order['delivery_batch_id']]
        );

        if (count($batchOrders) > 1) {
            $order['is_multi_store_batch'] = true;
            $order['batch_sub_orders']     = [];
            $order['batch_stores']         = [];
            $order['batch_total_amount']   = 0.0;

            $storeSeen = [];

            foreach ($batchOrders as $subOrd) {
                $subOrd['items'] = Database::query("SELECT oi.*, COALESCE(NULLIF(oi.product_name, ''), p.name, 'Menu Kuliner') as product_name, p.image as product_image FROM `order_items` oi LEFT JOIN `products` p ON oi.product_id = p.id WHERE oi.`order_id` = ?", [$subOrd['id']]);
                $order['batch_sub_orders'][] = $subOrd;
                $order['batch_total_amount'] += (float)$subOrd['total_amount'];

                $sId = $subOrd['store_id'];
                if ($sId && !isset($storeSeen[$sId])) {
                    $storeSeen[$sId] = count($order['batch_stores']);
                    $order['batch_stores'][] = [
                        'store_id'   => $sId,
                        'order_id'   => $subOrd['id'],
                        'order_code' => $subOrd['order_code'] ?? '',
                        'name'       => $subOrd['store_name'] ?? 'Toko Cicalengka',
                        'address'    => $subOrd['store_address'] ?? 'Cicalengka, Bandung',
                        'phone'      => $subOrd['store_phone'] ?? '',
                        'lat'        => (float)($subOrd['store_lat'] ?? -6.9835),
                        'lng'        => (float)($subOrd['store_lng'] ?? 107.8335),
                        'items'      => $subOrd['items'] ?? []
                    ];
                } elseif ($sId && isset($storeSeen[$sId])) {
                    $existingIdx = $storeSeen[$sId];
                    if (!empty($subOrd['items'])) {
                        $order['batch_stores'][$existingIdx]['items'] = array_merge(
                            $order['batch_stores'][$existingIdx]['items'] ?? [],
                            $subOrd['items']
                        );
                    }
                }
            }
        }
    }

    public function getCustomerOrders(int $customerId): array
    {
        $sql = "SELECT o.*, s.name as store_name, s.logo as store_logo, m.name as module_name
                FROM `orders` o
                LEFT JOIN `stores` s ON o.store_id = s.id
                LEFT JOIN `modules` m ON o.module_id = m.id
                WHERE o.customer_id = ?
                ORDER BY o.id DESC";
        $rawOrders = Database::query($sql, [$customerId]);

        $grouped  = [];
        $batchMap = [];

        foreach ($rawOrders as $o) {
            $o['items'] = Database::query("SELECT oi.*, COALESCE(NULLIF(oi.product_name, ''), p.name, 'Menu Kuliner') as product_name, p.image as product_image FROM `order_items` oi LEFT JOIN `products` p ON oi.product_id = p.id WHERE oi.`order_id` = ?", [$o['id']]);
            foreach ($o['items'] as &$it) {
                $it['store_name'] = $o['store_name'] ?? 'Toko';
            }
            unset($it);

            $o['delivery_address'] = json_decode($o['delivery_address_json'] ?? '{}', true) ?: [];
            $o['is_reviewed']      = Database::fetchOne("SELECT id FROM `reviews` WHERE `order_id` = ? AND `user_id` = ? LIMIT 1", [$o['id'], $customerId]) ? true : false;

            $batchId = $o['delivery_batch_id'] ?? null;

            if ($batchId) {
                if (!isset($batchMap[$batchId])) {
                    $parent                   = $o;
                    $parent['sub_orders']     = [$o];
                    $parent['store_names']   = [$o['store_name'] ?? 'Toko'];
                    $parent['all_items']      = $o['items'];
                    $parent['total_amount']   = (float)$o['total_amount'];
                    $parent['is_multi_store'] = false;
                    $batchMap[$batchId]       = count($grouped);
                    $grouped[]                = $parent;
                } else {
                    $idx = $batchMap[$batchId];
                    $grouped[$idx]['sub_orders'][]   = $o;
                    $grouped[$idx]['store_names'][] = $o['store_name'] ?? 'Toko';
                    $grouped[$idx]['all_items']      = array_merge($grouped[$idx]['all_items'], $o['items']);
                    $grouped[$idx]['total_amount']   += (float)$o['total_amount'];
                    $grouped[$idx]['is_multi_store'] = true;
                    $grouped[$idx]['store_name']     = implode(' • ', array_unique($grouped[$idx]['store_names']));
                    $grouped[$idx]['items']          = $grouped[$idx]['all_items'];
                }
            } else {
                $o['is_multi_store'] = false;
                $grouped[] = $o;
            }
        }

        return $grouped;
    }

    public function getStoreOrders(int $storeId): array
    {
        $sql = "SELECT o.*, u.name as customer_name, u.phone as customer_phone,
                       s.name as store_name, s.address as store_address,
                       COALESCE(s.latitude, -6.9835) as store_lat,
                       COALESCE(s.longitude, 107.8335) as store_lng,
                       s.phone as store_phone,
                       dmu.name as dm_name, dmu.phone as dm_phone
                FROM `orders` o
                JOIN `stores` s ON o.store_id = s.id
                JOIN `users` u ON o.customer_id = u.id
                LEFT JOIN `delivery_men` dm ON o.delivery_man_id = dm.id
                LEFT JOIN `users` dmu ON dm.user_id = dmu.id
                WHERE o.store_id = ?
                ORDER BY o.id DESC";
        $orders = Database::query($sql, [$storeId]);
        foreach ($orders as &$o) {
            $o['items'] = Database::query("SELECT oi.*, COALESCE(NULLIF(oi.product_name, ''), p.name, 'Menu Kuliner') as product_name, p.image as product_image FROM `order_items` oi LEFT JOIN `products` p ON oi.product_id = p.id WHERE oi.`order_id` = ?", [$o['id']]);
            $o['delivery_address'] = json_decode($o['delivery_address_json'] ?? '{}', true) ?: [];
        }
        return $orders;
    }

    public function getAvailableForDelivery(int $zoneId = 0): array
    {
        $sql = "SELECT o.*, 
                       COALESCE(s.name, 'Resto / Mitra Cicalengka') as store_name, 
                       COALESCE(s.address, 'Cicalengka, Jawa Barat') as store_address, 
                       COALESCE(s.latitude, -6.9835) as store_lat, 
                       COALESCE(s.longitude, 107.8335) as store_lng,
                       COALESCE(u.name, 'Pelanggan') as customer_name, 
                       COALESCE(u.phone, '-') as customer_phone,
                       COALESCE(z.name, 'Zona Cicalengka Raya') as zone_name,
                       COALESCE(z.min_delivery_charge, 5000.00) as min_delivery_charge,
                       COALESCE(z.per_km_delivery_charge, 2500.00) as per_km_delivery_charge
                FROM `orders` o
                LEFT JOIN `stores` s ON o.store_id = s.id
                LEFT JOIN `zones` z ON s.zone_id = z.id
                LEFT JOIN `users` u ON o.customer_id = u.id
                WHERE (o.delivery_man_id IS NULL OR o.delivery_man_id = 0 OR o.delivery_man_id = '')
                  AND (o.delivery_type IS NULL OR o.delivery_type != 'merchant')
                  AND o.order_status = 'handover'
                ORDER BY o.id DESC";
        $rawOrders = Database::query($sql);

        $result   = [];
        $batchMap = [];

        foreach ($rawOrders as $o) {
            $o['items'] = Database::query("SELECT oi.*, COALESCE(NULLIF(oi.product_name, ''), p.name, 'Menu Kuliner') as product_name, p.image as product_image FROM `order_items` oi LEFT JOIN `products` p ON oi.product_id = p.id WHERE oi.`order_id` = ?", [$o['id']]);
            $o['delivery_address'] = json_decode($o['delivery_address_json'] ?? '{}', true) ?: [];

            $batchId = $o['delivery_batch_id'] ?? null;

            if ($batchId) {
                if (!isset($batchMap[$batchId])) {
                    $parent                   = $o;
                    $parent['is_multi_store'] = false;
                    $parent['stores_count']   = 1;
                    $parent['store_names']    = [$o['store_name']];
                    $parent['all_items']      = $o['items'];
                    $parent['total_amount']   = (float)$o['total_amount'];
                    $parent['delivery_charge']= (float)($o['delivery_charge'] ?? 0);
                    $parent['sub_order_ids']  = [(int)$o['id']];
                    $parent['sub_orders']     = [$o];
                    $batchMap[$batchId]       = count($result);
                    $result[]                 = $parent;
                } else {
                    $idx = $batchMap[$batchId];
                    $result[$idx]['is_multi_store']  = true;
                    $result[$idx]['sub_order_ids'][] = (int)$o['id'];
                    $result[$idx]['sub_orders'][]    = $o;
                    $result[$idx]['store_names'][]   = $o['store_name'];
                    $result[$idx]['stores_count']    = count(array_unique($result[$idx]['store_names']));
                    $result[$idx]['store_name']      = implode(' & ', array_unique($result[$idx]['store_names']));
                    $result[$idx]['all_items']       = array_merge($result[$idx]['all_items'], $o['items']);
                    $result[$idx]['items']           = $result[$idx]['all_items'];
                    $result[$idx]['total_amount']   += (float)$o['total_amount'];
                    $result[$idx]['delivery_charge']+= (float)($o['delivery_charge'] ?? 0);
                }
            } else {
                $o['is_multi_store'] = false;
                $o['stores_count']   = 1;
                $result[]            = $o;
            }
        }

        // Final pass: calculate multi-leg distance_km for batch orders
        foreach ($result as &$resOrd) {
            if (!empty($resOrd['sub_orders']) && count($resOrd['sub_orders']) > 1) {
                $subOrds = $resOrd['sub_orders'];
                usort($subOrds, fn($a, $b) => (int)($a['pickup_sequence'] ?? 1) <=> (int)($b['pickup_sequence'] ?? 1));

                $storeIds = array_filter(array_column($subOrds, 'store_id'));
                $stores = [];
                if (!empty($storeIds)) {
                    $placeholders = implode(',', array_fill(0, count($storeIds), '?'));
                    $sRows = Database::query("SELECT id, name, address, latitude, longitude FROM stores WHERE id IN ({$placeholders})", array_values($storeIds));
                    foreach ($sRows as $sr) {
                        $stores[$sr['id']] = $sr;
                    }
                }

                $totalRouteKm = 0.0;
                $st0Id   = $subOrds[0]['store_id'] ?? null;
                $prevLat = (float)($subOrds[0]['store_lat'] ?? $stores[$st0Id]['latitude'] ?? -6.9835);
                $prevLng = (float)($subOrds[0]['store_lng'] ?? $stores[$st0Id]['longitude'] ?? 107.8335);

                for ($i = 1; $i < count($subOrds); $i++) {
                    $stId = $subOrds[$i]['store_id'] ?? null;
                    $sLat = (float)($subOrds[$i]['store_lat'] ?? $stores[$stId]['latitude'] ?? -6.9835);
                    $sLng = (float)($subOrds[$i]['store_lng'] ?? $stores[$stId]['longitude'] ?? 107.8335);
                    if ($sLat != 0 && $sLng != 0 && $prevLat != 0 && $prevLng != 0) {
                        $totalRouteKm += haversine_distance($prevLat, $prevLng, $sLat, $sLng);
                        $prevLat = $sLat;
                        $prevLng = $sLng;
                    }
                }

                $lastSub  = end($subOrds);
                $custAddr = is_string($lastSub['delivery_address_json'] ?? null)
                    ? json_decode($lastSub['delivery_address_json'], true)
                    : ($lastSub['delivery_address'] ?? []);
                $destLat  = (float)($custAddr['lat'] ?? -6.9855);
                $destLng  = (float)($custAddr['lng'] ?? 107.8350);

                if ($destLat != 0 && $destLng != 0 && $prevLat != 0 && $prevLng != 0) {
                    $totalRouteKm += haversine_distance($prevLat, $prevLng, $destLat, $destLng);
                }

                if ($totalRouteKm < 0.3) {
                    $totalRouteKm = array_sum(array_map(fn($so) => max(0.5, (float)($so['distance_km'] ?? 1.5)), $subOrds));
                }

                $resOrd['distance_km'] = round($totalRouteKm, 2);
            }
        }

        return $result;
    }

    public function updateStatus(int $id, string $status, array $extraData = []): bool
    {
        $data = array_merge(['order_status' => $status], $extraData);
        $timeMap = [
            'confirmed'  => 'confirmed_at',
            'processing' => 'processing_at',
            'handover'   => 'handover_at',
            'picked_up'  => 'picked_up_at',
            'delivered'  => 'delivered_at',
            'canceled'   => 'canceled_at'
        ];

        if (isset($timeMap[$status]) && !isset($data[$timeMap[$status]])) {
            $data[$timeMap[$status]] = date('Y-m-d H:i:s');
        }

        return $this->update($id, $data);
    }

    public static function autoCancelUnclaimedOrders(): void
    {
        try {
            // Self-healing: Automatically clean up lingering driver IDs on canceled or unaccepted orders
            Database::execute("UPDATE `orders` SET `delivery_man_id` = NULL WHERE `order_status` = 'canceled' AND `delivery_man_id` IS NOT NULL");
            Database::execute("UPDATE `delivery_men` dm LEFT JOIN `orders` o ON dm.current_order_id = o.id SET dm.current_order_id = NULL WHERE dm.current_order_id IS NOT NULL AND (o.id IS NULL OR o.order_status IN ('delivered', 'canceled'))");

            // Sync rating toko dan driver jika rating bernilai 0.0
            Database::execute("UPDATE `stores` SET `rating` = 5.0 WHERE `rating` = 0.0 OR `rating` IS NULL");
            Database::execute("UPDATE `delivery_men` SET `rating` = 5.0 WHERE `rating` = 0.0 OR `rating` IS NULL");

            // Batalkan pesanan lelang driver (handover) yang tidak diambil driver setelah 5 menit (300 detik)
            $expiredOrders = Database::query(
                "SELECT id, order_code, customer_id, delivery_man_id, payment_method, payment_status, total_amount, created_at, handover_at 
                 FROM `orders` 
                 WHERE (delivery_man_id IS NULL OR delivery_man_id = 0)
                   AND order_status = 'handover'
                   AND (delivery_type IS NULL OR delivery_type != 'merchant')
                   AND (
                        (handover_at IS NOT NULL AND handover_at <= TIMESTAMPADD(SECOND, -300, NOW()))
                        OR (handover_at IS NULL AND created_at <= TIMESTAMPADD(SECOND, -300, NOW()))
                   )"
            );

            foreach ($expiredOrders as $ord) {
                if (!empty($ord['delivery_man_id'])) {
                    Database::update('delivery_men', ['current_order_id' => null], 'id = ?', [$ord['delivery_man_id']]);
                }

                Database::update('orders', [
                    'delivery_man_id'     => null,
                    'order_status'        => 'canceled',
                    'cancellation_reason' => 'Batal Otomatis: Tidak mendapatkan driver dalam waktu 5 menit lelang',
                    'canceled_at'          => date('Y-m-d H:i:s')
                ], 'id = ?', [$ord['id']]);

                self::refundOrderIfPaid($ord, 'Batal Otomatis: Tidak mendapatkan driver dalam waktu 5 menit lelang');
            }
        } catch (\Exception $e) {
            error_log("autoCancelUnclaimedOrders error: " . $e->getMessage());
        }
    }

    /**
     * Helper terpusat untuk memproses pengembalian dana pesanan ke CicalengkaPay wallet.
     */
    public static function refundOrderIfPaid(int|array $order, string $reason = ''): bool
    {
        $ord = is_array($order) ? $order : (new self())->find($order);
        if (!$ord) return false;

        $batchId = $ord['delivery_batch_id'] ?? null;
        if (!empty($batchId)) {
            return self::refundBatchIfPaid($batchId, $reason, $ord);
        }

        $orderId = (int)$ord['id'];
        $customerId = (int)$ord['customer_id'];
        $amount = (float)$ord['total_amount'];
        $orderCode = $ord['order_code'];
        $paymentStatus = strtolower($ord['payment_status'] ?? 'unpaid');
        $paymentMethod = strtolower($ord['payment_method'] ?? 'wallet');

        // Jika sudah di-refund sebelumnya, lewati
        if ($paymentStatus === 'refunded') {
            return false;
        }

        // Cek apakah pernah ada catatan refund di wallet_transactions
        $existing = Database::fetchOne(
            "SELECT id FROM `wallet_transactions` WHERE `reference_id` = ? AND `category` IN ('refund', 'order_refund') LIMIT 1",
            [(string)$orderId]
        );

        $validPaidMethods = ['wallet', 'cicalengkapay', 'cicago_pay', 'saldo', 'balance', 'midtrans', 'online', 'qris', 'va', 'credit_card'];
        $isEligible = ($paymentStatus === 'paid' || in_array($paymentMethod, $validPaidMethods));

        if (!$existing && $amount > 0 && $isEligible) {
            $walletModel = new Wallet();
            $desc = "Refund pengembalian dana untuk pesanan #{$orderCode}";
            if (!empty($reason)) {
                $desc .= " ({$reason})";
            }

            // Tambahkan saldo ke CicalengkaPay
            $walletModel->credit(
                $customerId,
                $amount,
                'refund',
                $desc,
                (string)$orderId
            );

            // Update status pembayaran pesanan menjadi refunded
            Database::update('orders', ['payment_status' => 'refunded'], 'id = ?', [$orderId]);

            // Kirim Notifikasi ke Pelanggan
            Database::insert('notifications', [
                'user_id'   => $customerId,
                'title'     => 'Pesanan Dibatalkan & Dana Dikembalikan 💚',
                'message'   => "Pesanan #{$orderCode} dibatalkan. Dana sebesar Rp " . number_format($amount, 0, ',', '.') . " telah dikembalikan ke saldo CicalengkaPay Anda.",
                'type'      => 'order',
                'data_json' => json_encode(['order_code' => $orderCode, 'order_id' => $orderId])
            ]);

            return true;
        }

        return false;
    }

    /**
     * Memproses pengembalian dana untuk 1 batch pesanan multi-toko sebagai 1 transaksi tunggal.
     */
    public static function refundBatchIfPaid(string $batchId, string $reason = '', ?array $primaryOrder = null): bool
    {
        $batchOrders = Database::query(
            "SELECT * FROM `orders` WHERE `delivery_batch_id` = ?",
            [$batchId]
        );

        if (empty($batchOrders)) return false;

        $firstOrder = $batchOrders[0];
        $customerId = (int)$firstOrder['customer_id'];

        // Cek apakah batchId atau salah satu sub-order ID sudah pernah di-refund
        $orderIds = array_map(fn($o) => (string)$o['id'], $batchOrders);
        $orderIds[] = $batchId;
        $inPlaceholders = implode(',', array_fill(0, count($orderIds), '?'));

        $alreadyRefunded = Database::fetchOne(
            "SELECT wt.id FROM `wallet_transactions` wt
             JOIN `wallets` w ON wt.wallet_id = w.id
             WHERE w.user_id = ? AND w.user_type = 'customer'
               AND wt.category IN ('refund', 'order_refund')
               AND wt.reference_id IN ({$inPlaceholders}) LIMIT 1",
            array_merge([$customerId], $orderIds)
        );

        if ($alreadyRefunded) {
            return false;
        }

        $validPaidMethods = ['wallet', 'cicalengkapay', 'cicago_pay', 'saldo', 'balance', 'midtrans', 'online', 'qris', 'va', 'credit_card'];
        $totalRefundAmount = 0.0;
        $isAnyPaid = false;

        foreach ($batchOrders as $bOrd) {
            $pStatus = strtolower($bOrd['payment_status'] ?? 'unpaid');
            $pMethod = strtolower($bOrd['payment_method'] ?? 'wallet');
            if ($pStatus === 'paid' || in_array($pMethod, $validPaidMethods)) {
                $isAnyPaid = true;
                $totalRefundAmount += (float)$bOrd['total_amount'];
            }
        }

        if (!$isAnyPaid || $totalRefundAmount <= 0) {
            return false;
        }

        $walletModel = new Wallet();
        $storeCount = count($batchOrders);
        $desc = "Refund pengembalian dana batch pesanan {$batchId} ({$storeCount} Toko)";
        if (!empty($reason)) {
            $desc .= " ({$reason})";
        }

        // Tangani 1 kredit refund tunggal di dompet pelanggan
        $walletModel->credit(
            $customerId,
            $totalRefundAmount,
            'refund',
            $desc,
            $batchId
        );

        // Update semua order dalam batch: payment_status = refunded, order_status = canceled
        foreach ($batchOrders as $bOrd) {
            $updateData = ['payment_status' => 'refunded'];
            if (in_array($bOrd['order_status'], ['pending', 'confirmed'])) {
                $updateData['order_status']        = 'canceled';
                $updateData['cancellation_reason'] = $reason ?: 'Dibatalkan';
                $updateData['canceled_at']          = date('Y-m-d H:i:s');
                $updateData['delivery_man_id']     = null;
            }
            Database::update('orders', $updateData, 'id = ?', [$bOrd['id']]);
        }

        // Kirim 1 notifikasi tunggal untuk refund batch
        Database::insert('notifications', [
            'user_id'   => $customerId,
            'title'     => 'Pesanan Multi-Toko Dibatalkan & Dana Dikembalikan 💚',
            'message'   => "Pesanan multi-toko ({$batchId}) dibatalkan. Total dana sebesar Rp " . number_format($totalRefundAmount, 0, ',', '.') . " telah dikembalikan ke saldo CicalengkaPay Anda.",
            'type'      => 'order',
            'data_json' => json_encode(['batch_id' => $batchId, 'order_code' => $firstOrder['order_code']])
        ]);

        return true;
    }

    /**
     * Auto-heal & backfill: Periksa seluruh pesanan batal milik customer yang saldonya belum ter-refund
     */
    public static function processPendingRefundsForCustomer(int $customerId): int
    {
        if (!$customerId) return 0;

        $unrefundedOrders = Database::query(
            "SELECT * FROM `orders` 
             WHERE `customer_id` = ? 
               AND `order_status` = 'canceled' 
               AND `payment_status` != 'refunded'
               AND (`payment_status` = 'paid' OR `payment_method` IN ('wallet', 'cicalengkapay', 'cicago_pay', 'saldo', 'balance', 'midtrans', 'qris', 'va', 'online', 'credit_card'))",
            [$customerId]
        );

        $refundedCount = 0;
        foreach ($unrefundedOrders as $ord) {
            $reason = !empty($ord['cancellation_reason']) ? $ord['cancellation_reason'] : 'Pesanan Dibatalkan';
            if (self::refundOrderIfPaid($ord, $reason)) {
                $refundedCount++;
            }
        }

        return $refundedCount;
    }
}
