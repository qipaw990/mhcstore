<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Store extends Model
{
    protected string $table = 'stores';
    protected array $fillable = [
        'vendor_id', 'module_id', 'zone_id', 'name', 'phone', 'email', 'logo', 'cover_photo', 'identity_image',
        'address', 'latitude', 'longitude', 'minimum_order', 'delivery_time', 'delivery_fee',
        'tax_percent', 'is_open', 'status', 'rating', 'reviews_count', 'order_count'
    ];

    public function findWithDetails(int $id): ?array
    {
        $sql = "SELECT s.*, m.name as module_name, m.module_type, z.name as zone_name, u.name as vendor_name, u.phone as vendor_phone
                FROM `stores` s
                LEFT JOIN `modules` m ON s.module_id = m.id
                LEFT JOIN `zones` z ON s.zone_id = z.id
                LEFT JOIN `users` u ON s.vendor_id = u.id
                WHERE s.id = ? LIMIT 1";
        return Database::fetchOne($sql, [$id]);
    }

    public function getByModule(int $moduleId, ?int $zoneId = null): array
    {
        $params = [$moduleId];
        $zoneSql = '';
        if ($zoneId) {
            $zoneSql = " AND s.zone_id = ?";
            $params[] = $zoneId;
        }

        $sql = "SELECT s.*, m.name as module_name, m.module_type
                FROM `stores` s
                JOIN `modules` m ON s.module_id = m.id
                WHERE s.module_id = ? AND s.status = 'approved' {$zoneSql}
                ORDER BY s.is_open DESC, s.rating DESC";
        return Database::query($sql, $params);
    }

    public function getPopular(int $limit = 6): array
    {
        $sql = "SELECT s.*, m.name as module_name, m.module_type
                FROM `stores` s
                JOIN `modules` m ON s.module_id = m.id
                WHERE s.status = 'approved'
                ORDER BY s.is_open DESC, s.order_count DESC, s.rating DESC
                LIMIT {$limit}";
        return Database::query($sql);
    }

    public function findByVendorId(int $vendorId): ?array
    {
        $sql = "SELECT s.*, 
                       m.name as module_name, m.module_type, 
                       z.name as zone_name, 
                       u.name as vendor_name, u.phone as vendor_phone, u.email as vendor_email, u.avatar as vendor_avatar
                FROM `stores` s
                LEFT JOIN `modules` m ON s.module_id = m.id
                LEFT JOIN `zones` z ON s.zone_id = z.id
                LEFT JOIN `users` u ON s.vendor_id = u.id
                WHERE s.vendor_id = ? 
                ORDER BY s.id DESC LIMIT 1";
        $store = Database::fetchOne($sql, [$vendorId]);

        // Fallback: Check if store exists by user's phone or email
        if (!$store && $vendorId > 0) {
            $user = Database::fetchOne("SELECT id, name, phone, email, avatar FROM `users` WHERE id = ? LIMIT 1", [$vendorId]);
            if ($user) {
                $phone = trim($user['phone'] ?? '');
                $email = trim($user['email'] ?? '');
                $phoneClean = preg_replace('/[^0-9]/', '', $phone);
                
                $fallbackSql = "SELECT s.*, 
                                       m.name as module_name, m.module_type, 
                                       z.name as zone_name
                                FROM `stores` s
                                LEFT JOIN `modules` m ON s.module_id = m.id
                                LEFT JOIN `zones` z ON s.zone_id = z.id
                                WHERE (s.phone = ? AND ? != '') 
                                   OR (s.email = ? AND ? != '')
                                   OR (REPLACE(REPLACE(s.phone, '+', ''), '-', '') = ? AND ? != '')
                                ORDER BY s.id DESC LIMIT 1";
                $foundStore = Database::fetchOne($fallbackSql, [
                    $phone, $phone, 
                    $email, $email, 
                    $phoneClean, $phoneClean
                ]);

                if ($foundStore) {
                    Database::execute("UPDATE `stores` SET `vendor_id` = ? WHERE `id` = ?", [$vendorId, $foundStore['id']]);
                    $foundStore['vendor_id'] = $vendorId;
                    $foundStore['vendor_name'] = $user['name'];
                    $foundStore['vendor_phone'] = $user['phone'];
                    $foundStore['vendor_email'] = $user['email'];
                    $foundStore['vendor_avatar'] = $user['avatar'];
                    $store = $foundStore;
                }
            }
        }

        if ($store) {
            attach_store_schedule_data($store, true);
        }
        return $store;
    }

    public function search(string $query, ?int $moduleId = null): array
    {
        $params = ["%{$query}%", "%{$query}%"];
        $moduleSql = '';
        if ($moduleId) {
            $moduleSql = " AND s.module_id = ?";
            $params[] = $moduleId;
        }

        $sql = "SELECT s.*, m.name as module_name, m.module_type
                FROM `stores` s
                JOIN `modules` m ON s.module_id = m.id
                WHERE s.status = 'approved' AND (s.name LIKE ? OR s.address LIKE ?) {$moduleSql}
                ORDER BY s.is_open DESC, s.rating DESC LIMIT 20";
        return Database::query($sql, $params);
    }
}
