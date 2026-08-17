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

            $order['items'] = Database::query("SELECT * FROM `order_items` WHERE `order_id` = ?", [$order['id']]);
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
                $order['items'] = Database::query("SELECT * FROM `order_items` WHERE `order_id` = ?", [$order['id']]);
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
            }
            return $order;
        }

        return $this->findByCode((string)$idOrCode);
    }

    public function getCustomerOrders(int $customerId): array
    {
        $sql = "SELECT o.*, s.name as store_name, s.logo as store_logo, m.name as module_name
                FROM `orders` o
                LEFT JOIN `stores` s ON o.store_id = s.id
                LEFT JOIN `modules` m ON o.module_id = m.id
                WHERE o.customer_id = ?
                ORDER BY o.id DESC";
        $orders = Database::query($sql, [$customerId]);
        foreach ($orders as &$o) {
            $o['items'] = Database::query("SELECT * FROM `order_items` WHERE `order_id` = ?", [$o['id']]);
            $o['delivery_address'] = json_decode($o['delivery_address_json'] ?? '{}', true) ?: [];
            $o['is_reviewed'] = Database::fetchOne("SELECT id FROM `reviews` WHERE `order_id` = ? AND `user_id` = ? LIMIT 1", [$o['id'], $customerId]) ? true : false;
        }
        return $orders;
    }

    public function getStoreOrders(int $storeId): array
    {
        $sql = "SELECT o.*, u.name as customer_name, u.phone as customer_phone,
                       dmu.name as dm_name, dmu.phone as dm_phone
                FROM `orders` o
                JOIN `users` u ON o.customer_id = u.id
                LEFT JOIN `delivery_men` dm ON o.delivery_man_id = dm.id
                LEFT JOIN `users` dmu ON dm.user_id = dmu.id
                WHERE o.store_id = ?
                ORDER BY o.id DESC";
        $orders = Database::query($sql, [$storeId]);
        foreach ($orders as &$o) {
            $o['items'] = Database::query("SELECT * FROM `order_items` WHERE `order_id` = ?", [$o['id']]);
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
                       COALESCE(u.phone, '-') as customer_phone
                FROM `orders` o
                LEFT JOIN `stores` s ON o.store_id = s.id
                LEFT JOIN `users` u ON o.customer_id = u.id
                WHERE (o.delivery_man_id IS NULL OR o.delivery_man_id = 0 OR o.delivery_man_id = '')
                  AND o.order_status NOT IN ('delivered', 'canceled', 'refunded', 'failed')
                ORDER BY o.id DESC";
        $orders = Database::query($sql);
        foreach ($orders as &$o) {
            $o['items'] = Database::query("SELECT * FROM `order_items` WHERE `order_id` = ?", [$o['id']]);
            $o['delivery_address'] = json_decode($o['delivery_address_json'] ?? '{}', true) ?: [];
        }
        return $orders;
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
            Database::execute("UPDATE `orders` SET `delivery_man_id` = NULL WHERE `order_status` IN ('pending', 'confirmed') AND `delivery_man_id` IS NOT NULL");
            Database::execute("UPDATE `delivery_men` dm LEFT JOIN `orders` o ON dm.current_order_id = o.id SET dm.current_order_id = NULL WHERE dm.current_order_id IS NOT NULL AND (o.id IS NULL OR o.order_status IN ('delivered', 'canceled'))");

            // Sync rating toko dan driver jika rating bernilai 0.0
            Database::execute("UPDATE `stores` SET `rating` = 5.0 WHERE `rating` = 0.0 OR `rating` IS NULL");
            Database::execute("UPDATE `delivery_men` SET `rating` = 5.0 WHERE `rating` = 0.0 OR `rating` IS NULL");

            // Find active orders without driver (or unclaimed) where created_at is older than 60 seconds (1 minute)
            $expiredOrders = Database::query(
                "SELECT id, order_code, customer_id, delivery_man_id, payment_method, payment_status, total_amount, created_at 
                 FROM `orders` 
                 WHERE (`delivery_man_id` IS NULL OR `order_status` IN ('pending', 'confirmed'))
                   AND `order_status` NOT IN ('processing', 'handover', 'on_the_way', 'delivered', 'canceled', 'refunded', 'failed')
                   AND `created_at` <= TIMESTAMPADD(SECOND, -60, NOW())"
            );

            foreach ($expiredOrders as $ord) {
                // Clear active order link from delivery_men if any
                if (!empty($ord['delivery_man_id'])) {
                    Database::update('delivery_men', ['current_order_id' => null], 'id = ?', [$ord['delivery_man_id']]);
                }

                // Cancel order & ensure delivery_man_id is NULL
                Database::update('orders', [
                    'delivery_man_id'     => null,
                    'order_status'        => 'canceled',
                    'cancellation_reason' => 'Batal Otomatis: Tidak mendapatkan driver dalam waktu 1 menit',
                    'canceled_at'          => date('Y-m-d H:i:s')
                ], 'id = ?', [$ord['id']]);

                // ===== REFUND LOGIC PER PAYMENT METHOD =====

                if ($ord['payment_status'] === 'paid') {
                    $walletModel = new \App\Models\Wallet();

                    if ($ord['payment_method'] === 'wallet') {
                        // CicalengkaPay / Wallet → kembalikan saldo otomatis
                        $walletModel->credit(
                            (int)$ord['customer_id'],
                            (float)$ord['total_amount'],
                            'order_refund',
                            "Pengembalian saldo CicalengkaPay untuk pesanan #{$ord['order_code']} (tidak ada driver)",
                            (string)$ord['id']
                        );
                        Database::update('orders', ['payment_status' => 'refunded'], 'id = ?', [$ord['id']]);

                    } elseif (in_array($ord['payment_method'], ['midtrans', 'online', 'qris', 'va', 'credit_card'])) {
                        // Midtrans / Online → refund otomatis ke CicalengkaPay wallet
                        // Lebih cepat & mudah dibanding proses refund ke rekening bank (1-7 hari kerja)
                        $existing = Database::fetchOne(
                            "SELECT id FROM `wallet_transactions` WHERE `reference_id` = ? AND `type` = 'order_refund' LIMIT 1",
                            [(string)$ord['id']]
                        );
                        if (!$existing) {
                            $walletModel->credit(
                                (int)$ord['customer_id'],
                                (float)$ord['total_amount'],
                                'order_refund',
                                "Refund Midtrans → CicalengkaPay untuk pesanan #{$ord['order_code']} (tidak ada driver tersedia)",
                                (string)$ord['id']
                            );
                        }
                        Database::update('orders', ['payment_status' => 'refunded'], 'id = ?', [$ord['id']]);
                    }
                    // COD: tidak ada uang yang keluar, tidak perlu refund
                }

                // Send notification to customer
                $isRefunded = in_array($ord['payment_method'], ['wallet', 'midtrans', 'online', 'qris', 'va', 'credit_card']) && $ord['payment_status'] === 'paid';
                $notifMsg = $isRefunded
                    ? "Pesanan #{$ord['order_code']} dibatalkan otomatis (tidak ada driver). Dana Rp " . number_format((float)$ord['total_amount'], 0, ',', '.') . " telah dikembalikan ke CicalengkaPay Anda."
                    : "Pesanan #{$ord['order_code']} dibatalkan otomatis karena tidak ada driver dalam waktu 1 menit.";

                Database::insert('notifications', [
                    'user_id'   => (int)$ord['customer_id'],
                    'title'     => $isRefunded ? 'Pesanan Dibatalkan & Dana Dikembalikan 💚' : 'Pesanan Dibatalkan Otomatis ⚠️',
                    'message'   => $notifMsg,
                    'type'      => 'order',
                    'data_json' => json_encode(['order_code' => $ord['order_code'], 'order_id' => $ord['id']])
                ]);
            }
        } catch (\Exception $e) {
            error_log("autoCancelUnclaimedOrders Error: " . $e->getMessage());
        }
    }
}
