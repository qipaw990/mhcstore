<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Review extends Model
{
    protected string $table = 'reviews';
    protected array $fillable = [
        'order_id', 'user_id', 'store_id', 'product_id', 'delivery_man_id',
        'rating', 'comment', 'reply'
    ];
}

class Banner extends Model
{
    protected string $table = 'banners';
    protected array $fillable = [
        'module_id', 'zone_id', 'title', 'image', 'banner_type',
        'target_type', 'target_id', 'status', 'priority'
    ];

    public function getActiveBanners(?int $moduleId = null): array
    {
        $params = [];
        $moduleSql = '';
        if ($moduleId) {
            $moduleSql = " AND (module_id = ? OR module_id IS NULL)";
            $params[] = $moduleId;
        }

        return Database::query("SELECT * FROM `banners` WHERE `status` = 1 {$moduleSql} ORDER BY `priority` ASC", $params);
    }
}

class Notification extends Model
{
    protected string $table = 'notifications';
    protected array $fillable = ['user_id', 'title', 'message', 'type', 'is_read', 'data_json'];

    public function getUserNotifications(int $userId, int $limit = 20): array
    {
        return Database::query("SELECT * FROM `notifications` WHERE `user_id` = ? ORDER BY `id` DESC LIMIT {$limit}", [$userId]);
    }

    public function getUnreadCount(int $userId): int
    {
        $res = Database::fetchOne("SELECT COUNT(*) as unread FROM `notifications` WHERE `user_id` = ? AND `is_read` = 0", [$userId]);
        return (int)($res['unread'] ?? 0);
    }
}

class BusinessSetting extends Model
{
    protected string $table = 'business_settings';
    protected array $fillable = ['key_name', 'value_text'];

    public static function get(string $key, $default = null)
    {
        $res = Database::fetchOne("SELECT `value_text` FROM `business_settings` WHERE `key_name` = ? LIMIT 1", [$key]);
        return $res ? $res['value_text'] : $default;
    }

    public static function set(string $key, $value): bool
    {
        $exists = Database::fetchOne("SELECT id FROM `business_settings` WHERE `key_name` = ? LIMIT 1", [$key]);
        if ($exists) {
            return Database::update('business_settings', ['value_text' => $value], '`key_name` = ?', [$key]);
        }
        Database::insert('business_settings', ['key_name' => $key, 'value_text' => $value]);
        return true;
    }
}
