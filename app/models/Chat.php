<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Chat extends Model
{
    protected string $table = 'chats';
    protected array $fillable = ['order_id', 'sender_id', 'receiver_id', 'message', 'file', 'is_read'];

    public function getOrderMessages(int $orderId): array
    {
        $sql = "SELECT c.*, u.name as sender_name, u.avatar as sender_avatar, u.role as sender_role
                FROM `chats` c
                JOIN `users` u ON c.sender_id = u.id
                WHERE c.order_id = ?
                ORDER BY c.id ASC";
        return Database::query($sql, [$orderId]);
    }
}
