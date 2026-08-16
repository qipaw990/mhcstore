<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class DeliveryMan extends Model
{
    protected string $table = 'delivery_men';
    protected array $fillable = [
        'user_id', 'zone_id', 'vehicle_type', 'vehicle_number', 'identity_type',
        'identity_number', 'identity_image', 'is_online', 'is_active',
        'current_latitude', 'current_longitude', 'current_order_id', 'rating',
        'reviews_count', 'total_orders'
    ];

    public function findByUserId(int $userId): ?array
    {
        $sql = "SELECT dm.*, u.name, u.email, u.phone, u.avatar, z.name as zone_name
                FROM `delivery_men` dm
                JOIN `users` u ON dm.user_id = u.id
                LEFT JOIN `zones` z ON dm.zone_id = z.id
                WHERE dm.user_id = ? LIMIT 1";
        return Database::fetchOne($sql, [$userId]);
    }

    public function updateLocation(int $dmId, float $lat, float $lng, ?int $orderId = null): bool
    {
        Database::update('delivery_men', [
            'current_latitude' => $lat,
            'current_longitude' => $lng
        ], 'id = ?', [$dmId]);

        if ($orderId) {
            Database::insert('delivery_trackings', [
                'delivery_man_id' => $dmId,
                'order_id' => $orderId,
                'latitude' => $lat,
                'longitude' => $lng
            ]);
        }
        return true;
    }
}
