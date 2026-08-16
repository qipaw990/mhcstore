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
            $order['items'] = Database::query("SELECT * FROM `order_items` WHERE `order_id` = ?", [$order['id']]);
            $order['delivery_address'] = json_decode($order['delivery_address_json'] ?? '{}', true) ?: [];
            $order['parcel_details'] = json_decode($order['parcel_details_json'] ?? '{}', true) ?: [];

            if ($order['order_type'] === 'parcel' && !empty($order['parcel_details'])) {
                $order['store_name'] = $order['parcel_details']['pickup_address'] ?? 'Titik Penjemputan Paket';
                $order['store_lat'] = $order['parcel_details']['pickup_lat'] ?? $order['store_lat'] ?? -6.9840;
                $order['store_lng'] = $order['parcel_details']['pickup_lng'] ?? $order['store_lng'] ?? 107.8340;
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

    public function getAvailableForDelivery(int $zoneId): array
    {
        $sql = "SELECT o.*, s.name as store_name, s.address as store_address, s.latitude as store_lat, s.longitude as store_lng,
                       u.name as customer_name, u.phone as customer_phone
                FROM `orders` o
                LEFT JOIN `stores` s ON o.store_id = s.id
                JOIN `users` u ON o.customer_id = u.id
                WHERE o.delivery_man_id IS NULL
                  AND o.zone_id = ?
                  AND o.order_status IN ('confirmed', 'processing', 'handover')
                  AND (o.payment_method = 'cod' OR o.payment_status = 'paid')
                ORDER BY o.id ASC";
        $orders = Database::query($sql, [$zoneId]);
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
}
