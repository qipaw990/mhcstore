<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Chat extends Model
{
    protected string $table = 'chats';
    protected array $fillable = ['order_id', 'store_id', 'sender_id', 'receiver_id', 'message', 'file', 'is_read'];

    /**
     * Get all related order IDs (expands to all batch orders if part of a delivery batch)
     */
    public function getRelatedOrderIds(int $orderId, ?string $batchId = null): array
    {
        if (!empty($batchId)) {
            $rows = Database::query("SELECT id FROM `orders` WHERE `delivery_batch_id` = ?", [$batchId]);
            $ids = array_map('intval', array_column($rows, 'id'));
            if (!empty($ids)) {
                if (!in_array($orderId, $ids)) {
                    $ids[] = $orderId;
                }
                return array_unique($ids);
            }
        }
        return [$orderId];
    }

    /**
     * Get chat messages for an order (or full batch trip), optionally filtering newer than $sinceId
     */
    public function getOrderMessages(int $orderId, int $sinceId = 0, ?string $batchId = null): array
    {
        $orderIds = $this->getRelatedOrderIds($orderId, $batchId);
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $params = $orderIds;

        $sinceClause = '';
        if ($sinceId > 0) {
            $sinceClause = ' AND c.id > ? ';
            $params[] = $sinceId;
        }

        $sql = "SELECT c.*, 
                       COALESCE(u.name, 'Pengguna') as sender_name, 
                       COALESCE(u.avatar, 'assets/images/users/default.png') as sender_avatar, 
                       COALESCE(u.role, 'customer') as sender_role,
                       DATE_FORMAT(c.created_at, '%H:%i') as time_formatted
                FROM `chats` c
                LEFT JOIN `users` u ON c.sender_id = u.id
                WHERE c.order_id IN ({$placeholders}) {$sinceClause}
                ORDER BY c.id ASC";

        return Database::query($sql, $params);
    }

    /**
     * Save a new message
     */
    public function saveMessage(int $orderId, int $senderId, int $receiverId, string $message, ?string $file = null, int $storeId = 0): int
    {
        return (int)Database::insert($this->table, [
            'order_id'    => $orderId,
            'store_id'    => $storeId,
            'sender_id'   => $senderId,
            'receiver_id' => $receiverId,
            'message'     => trim($message),
            'file'        => $file,
            'is_read'     => 0
        ]);
    }

    /**
     * Mark messages in an order (or full batch trip) as read for the receiver
     */
    public function markAsRead(int $orderId, int $receiverId, ?string $batchId = null): bool
    {
        $orderIds = $this->getRelatedOrderIds($orderId, $batchId);
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $params = array_merge([$receiverId, $receiverId], $orderIds);

        return Database::execute(
            "UPDATE `{$this->table}` SET `is_read` = 1 
             WHERE (`receiver_id` = ? OR `receiver_id` = 0 OR `sender_id` != ?) 
               AND `order_id` IN ({$placeholders}) 
               AND `is_read` = 0",
            $params
        );
    }

    /**
     * Get unread message count for a specific order and receiver (expands to batch if available)
     */
    public function getUnreadCountForOrder(int $orderId, int $receiverId, ?string $batchId = null): int
    {
        $orderIds = $this->getRelatedOrderIds($orderId, $batchId);
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $params = array_merge($orderIds, [$receiverId]);

        $res = Database::fetchOne(
            "SELECT COUNT(*) as unread FROM `chats` 
             WHERE `order_id` IN ({$placeholders}) 
               AND `sender_id` != ? 
               AND `is_read` = 0",
            $params
        );
        return (int)($res['unread'] ?? 0);
    }

    /**
     * Get order details with customer, driver, and store user identities
     */
    public function getOrderChatDetails(string $orderCode): ?array
    {
        $cleanCode = trim($orderCode);
        $numericId = is_numeric($cleanCode) ? (int)$cleanCode : 0;

        $sql = "SELECT o.id as order_id, 
                       o.order_code, 
                       o.order_status, 
                       o.payment_status,
                       o.order_type,
                       o.delivery_type,
                       o.store_id,
                       o.delivery_batch_id,
                       o.delivery_man_id,
                       s.name as store_name,
                       s.logo as store_logo,
                       s.phone as store_phone,
                       s.vendor_id as store_vendor_user_id,
                       o.customer_id as cust_user_id,
                       COALESCE(c.name, 'Pelanggan') as customer_name,
                       COALESCE(c.phone, '') as customer_phone,
                       COALESCE(c.avatar, 'assets/images/users/customer.png') as customer_avatar,
                       COALESCE(dm.id, (SELECT id FROM delivery_men WHERE id = o.delivery_man_id OR user_id = o.delivery_man_id LIMIT 1)) as dm_id,
                       COALESCE(dm.user_id, (SELECT user_id FROM delivery_men WHERE id = o.delivery_man_id OR user_id = o.delivery_man_id LIMIT 1)) as dm_user_id,
                       COALESCE(dmu.name, (SELECT name FROM users WHERE id = (SELECT user_id FROM delivery_men WHERE id = o.delivery_man_id OR user_id = o.delivery_man_id LIMIT 1) LIMIT 1), 'Mitra Driver Cicalengka') as dm_name,
                       COALESCE(dmu.phone, (SELECT phone FROM users WHERE id = (SELECT user_id FROM delivery_men WHERE id = o.delivery_man_id OR user_id = o.delivery_man_id LIMIT 1) LIMIT 1), '') as dm_phone,
                       COALESCE(dmu.avatar, (SELECT avatar FROM users WHERE id = (SELECT user_id FROM delivery_men WHERE id = o.delivery_man_id OR user_id = o.delivery_man_id LIMIT 1) LIMIT 1), 'assets/images/users/driver.png') as dm_avatar,
                       COALESCE(dm.vehicle_type, 'Motor') as vehicle_type,
                       COALESCE(dm.vehicle_number, 'CCG') as vehicle_number
                FROM `orders` o
                LEFT JOIN `stores` s ON o.store_id = s.id
                LEFT JOIN `users` c ON o.customer_id = c.id
                LEFT JOIN `delivery_men` dm ON (o.delivery_man_id = dm.id OR o.delivery_man_id = dm.user_id)
                LEFT JOIN `users` dmu ON (dm.user_id = dmu.id)
                WHERE o.order_code = ? OR o.id = ?
                LIMIT 1";

        $res = Database::fetchOne($sql, [$cleanCode, $numericId]);
        return $res ?: null;
    }

    /**
     * Get chat messages between customer and a specific store
     */
    public function getStoreMessages(int $storeId, int $userId, int $sinceId = 0): array
    {
        $params = [$storeId];
        $userClause = '';
        if ($userId > 0) {
            $userClause = ' AND (c.sender_id = ? OR c.receiver_id = ?) ';
            $params[] = $userId;
            $params[] = $userId;
        }

        $sinceClause = '';
        if ($sinceId > 0) {
            $sinceClause = ' AND c.id > ? ';
            $params[] = $sinceId;
        }

        $sql = "SELECT c.*, 
                       COALESCE(u.name, 'Pengguna') as sender_name, 
                       COALESCE(u.avatar, 'assets/images/users/default.png') as sender_avatar, 
                       COALESCE(u.role, 'customer') as sender_role,
                       DATE_FORMAT(c.created_at, '%H:%i') as time_formatted
                FROM `chats` c
                LEFT JOIN `users` u ON c.sender_id = u.id
                WHERE c.store_id = ? {$userClause} {$sinceClause}
                ORDER BY c.id ASC";

        return Database::query($sql, $params);
    }

    /**
     * Save a new message for direct store chat
     */
    public function saveStoreMessage(int $storeId, int $senderId, int $receiverId, string $message, ?string $file = null): int
    {
        return (int)Database::insert($this->table, [
            'order_id'    => 0,
            'store_id'    => $storeId,
            'sender_id'   => $senderId,
            'receiver_id' => $receiverId,
            'message'     => trim($message),
            'file'        => $file,
            'is_read'     => 0
        ]);
    }

    /**
     * Mark direct store chat messages as read for receiver
     */
    public function markStoreMessagesRead(int $storeId, int $receiverId): bool
    {
        return Database::update(
            $this->table,
            ['is_read' => 1],
            'store_id = ? AND receiver_id = ? AND is_read = 0',
            [$storeId, $receiverId]
        );
    }
}
