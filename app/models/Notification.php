<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Notification extends Model
{
    protected string $table = 'notifications';
    protected array $fillable = ['user_id', 'title', 'message', 'type', 'is_read', 'data_json'];

    public function createNotification(int $userId, string $title, string $message, string $type = 'system', $data = null): int|bool
    {
        try {
            $dataJson = is_array($data) ? json_encode($data) : (is_string($data) ? $data : null);
            return Database::insert('notifications', [
                'user_id'    => $userId,
                'title'      => $title,
                'message'    => $message,
                'type'       => $type,
                'is_read'    => 0,
                'data_json'  => $dataJson,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $e) {
            error_log("Failed to create notification: " . $e->getMessage());
            return false;
        }
    }

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
