<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

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
