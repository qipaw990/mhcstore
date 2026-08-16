<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Chat extends Model
{
    protected string $table = 'chats';
    protected array $fillable = ['order_id', 'sender_id', 'receiver_id', 'message', 'file', 'is_read'];

    /**
     * Get chat messages for an order, optionally filtering newer than $sinceId
     */
    public function getOrderMessages(int $orderId, int $sinceId = 0): array
    {
        $params = [$orderId];
        $sinceClause = '';
        if ($sinceId > 0) {
            $sinceClause = ' AND c.id > ? ';
            $params[] = $sinceId;
        }

        $sql = "SELECT c.*, 
                       u.name as sender_name, 
                       u.avatar as sender_avatar, 
                       u.role as sender_role,
                       DATE_FORMAT(c.created_at, '%H:%i') as time_formatted
                FROM `chats` c
                JOIN `users` u ON c.sender_id = u.id
                WHERE c.order_id = ? {$sinceClause}
                ORDER BY c.id ASC";

        return Database::query($sql, $params);
    }

    /**
     * Save a new message
     */
    public function saveMessage(int $orderId, int $senderId, int $receiverId, string $message, ?string $file = null): int
    {
        return Database::insert($this->table, [
            'order_id'    => $orderId,
            'sender_id'   => $senderId,
            'receiver_id' => $receiverId,
            'message'     => trim($message),
            'file'        => $file,
            'is_read'     => 0
        ]);
    }

    /**
     * Mark messages in an order as read for the receiver
     */
    public function markAsRead(int $orderId, int $receiverId): bool
    {
        return Database::update(
            $this->table,
            ['is_read' => 1],
            'order_id = ? AND receiver_id = ? AND is_read = 0',
            [$orderId, $receiverId]
        );
    }

    /**
     * Get unread message count for a specific order and receiver
     */
    public function getUnreadCountForOrder(int $orderId, int $receiverId): int
    {
        $res = Database::fetchOne(
            "SELECT COUNT(*) as unread FROM `chats` WHERE `order_id` = ? AND `receiver_id` = ? AND `is_read` = 0",
            [$orderId, $receiverId]
        );
        return (int)($res['unread'] ?? 0);
    }

    /**
     * Get order details with both customer and driver user identities
     */
    public function getOrderChatDetails(string $orderCode): ?array
    {
        $sql = "SELECT o.id as order_id, 
                       o.order_code, 
                       o.order_status, 
                       o.payment_status,
                       o.order_type,
                       o.customer_id as cust_user_id,
                       c.name as customer_name,
                       c.phone as customer_phone,
                       c.avatar as customer_avatar,
                       dm.id as dm_id,
                       dm.user_id as dm_user_id,
                       dmu.name as dm_name,
                       dmu.phone as dm_phone,
                       dmu.avatar as dm_avatar,
                       dm.vehicle_type,
                       dm.vehicle_number
                FROM `orders` o
                JOIN `users` c ON o.customer_id = c.id
                LEFT JOIN `delivery_men` dm ON o.delivery_man_id = dm.id
                LEFT JOIN `users` dmu ON dm.user_id = dmu.id
                WHERE o.order_code = ? OR o.id = ?
                LIMIT 1";

        $res = Database::fetchOne($sql, [$orderCode, is_numeric($orderCode) ? (int)$orderCode : 0]);
        return $res ?: null;
    }
}
