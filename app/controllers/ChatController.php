<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Chat;
use App\Models\Order;
use Exception;

class ChatController extends Controller
{
    private Chat $chatModel;
    private Order $orderModel;

    public function __construct()
    {
        $this->chatModel = new Chat();
        $this->orderModel = new Order();
    }

    /**
     * Get chat messages and partner info for an order
     */
    public function getMessages(): void
    {
        $userId = auth_id() ?: 0;

        $orderCode = sanitize($_GET['order_code'] ?? '');
        $sinceId = (int)($_GET['since_id'] ?? 0);
        $markRead = (bool)($_GET['mark_read'] ?? false);

        if (empty($orderCode)) {
            $this->errorResponse('Kode pesanan wajib diisi.');
            return;
        }

        $order = $this->chatModel->getOrderChatDetails($orderCode);
        if (!$order) {
            $this->errorResponse('Pesanan tidak ditemukan.');
            return;
        }

        $userRole   = auth_role();
        $isCustomer = ($userId > 0 && (int)$order['cust_user_id'] === $userId);
        $isDriver   = $userRole === 'delivery_man';
        $isAdmin    = $userRole === 'admin';
        // Guests with the order_code can read (public tracking page)
        $isGuest    = ($userId === 0);

        if (!$isCustomer && !$isDriver && !$isAdmin && !$isGuest) {
            $this->errorResponse('Akses percakapan ditolak.', null, 403);
            return;
        }

        // Only mark as read if authenticated
        if ($markRead && $userId > 0) {
            $this->chatModel->markAsRead((int)$order['order_id'], $userId);
        }

        $messages = $this->chatModel->getOrderMessages((int)$order['order_id'], $sinceId);

        // Define partner information based on viewer role
        $partner = null;
        if ($isDriver) {
            $partner = [
                'name'         => $order['customer_name'] ?? 'Pelanggan CicalengkaGO',
                'role'         => 'customer',
                'role_label'   => 'Pelanggan',
                'avatar'       => $order['customer_avatar'] ?? 'assets/images/users/customer.png',
                'phone'        => $order['customer_phone'] ?? '',
                'vehicle_info' => 'Tujuan Pengantaran'
            ];
        } elseif ($isAdmin) {
            $partner = [
                'name'         => $order['customer_name'] ?? 'Pelanggan',
                'role'         => 'admin',
                'role_label'   => 'Administrator',
                'avatar'       => 'assets/images/users/customer.png',
                'phone'        => '',
                'vehicle_info' => 'Pesanan #' . $order['order_code']
            ];
        } else {
            // Customer or guest: show driver info
            if (!empty($order['dm_id'])) {
                $partner = [
                    'name'           => $order['dm_name'] ?? 'Mitra Driver Cicalengka',
                    'role'           => 'driver',
                    'role_label'     => 'Mitra Kurir CicalengkaGO',
                    'avatar'         => $order['dm_avatar'] ?? 'assets/images/users/driver.png',
                    'phone'          => $order['dm_phone'] ?? '',
                    'vehicle_info'   => ($order['vehicle_type'] ?? 'Motor') . ' • ' . ($order['vehicle_number'] ?? 'CCG')
                ];
            } else {
                $partner = [
                    'name'           => 'Mitra Kurir CicalengkaGO',
                    'role'           => 'driver',
                    'role_label'     => 'Menunggu Konfirmasi Kurir',
                    'avatar'         => 'assets/images/users/driver.png',
                    'phone'          => '',
                    'vehicle_info'   => 'Mencari kurir terdekat...'
                ];
            }
        }

        $unread = ($userId > 0)
            ? $this->chatModel->getUnreadCountForOrder((int)$order['order_id'], $userId)
            : 0;

        $this->successResponse('Pesan berhasil diambil', [
            'order_id'     => (int)$order['order_id'],
            'order_code'   => $order['order_code'],
            'order_status' => $order['order_status'],
            'user_id'      => $userId,
            'cust_user_id' => (int)$order['cust_user_id'],
            'dm_user_id'   => (int)($order['dm_user_id'] ?? 0),
            'partner'      => $partner,
            'messages'     => $messages,
            'unread_count' => $unread
        ]);
    }

    /**
     * Send a new chat message
     */
    public function sendMessage(): void
    {
        $userId   = auth_id() ?: 0;
        $userRole = auth_role();

        // Accept both application/json and multipart/form-data
        $data = $this->getPost();
        if (empty($data['order_code']) && empty($data['message'])) {
            $raw     = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            if (!empty($decoded)) $data = $decoded;
        }

        $orderCode = sanitize(trim($data['order_code'] ?? ''));
        $message   = trim($data['message'] ?? '');

        if (empty($orderCode)) {
            $this->errorResponse('Kode pesanan wajib diisi.');
            return;
        }

        if ($message === '') {
            $this->errorResponse('Pesan tidak boleh kosong.');
            return;
        }

        $order = $this->chatModel->getOrderChatDetails($orderCode);
        if (!$order) {
            $this->errorResponse('Pesanan tidak ditemukan.');
            return;
        }

        $isDriver   = $userRole === 'delivery_man';
        $isAdmin    = $userRole === 'admin';
        // Customer: either logged-in owner OR guest accessing by order_code (order is their own page)
        $isLoggedInCustomer = ($userId > 0 && (int)$order['cust_user_id'] === $userId);
        // Guest customer identified by order_code (no session) — allow send on their own order
        $isGuestCustomer    = ($userId === 0 && !$isDriver && !$isAdmin);

        if (!$isLoggedInCustomer && !$isGuestCustomer && !$isDriver && !$isAdmin) {
            $this->errorResponse('Akses pengiriman pesan ditolak.', null, 403);
            return;
        }

        $receiverId = 0;
        $senderId   = $userId;

        if ($isDriver) {
            // Driver sends to customer
            $receiverId = (int)$order['cust_user_id'];
            if ($senderId === 0) {
                $this->errorResponse('Driver harus login untuk mengirim pesan.', null, 401);
                return;
            }
        } elseif ($isAdmin) {
            $receiverId = !empty($order['dm_user_id']) ? (int)$order['dm_user_id'] : (int)$order['cust_user_id'];
        } else {
            // Customer (logged-in or guest) sends to driver
            $receiverId = !empty($order['dm_user_id']) ? (int)$order['dm_user_id'] : 0;
            // For guest, use customer_id from order as sender
            if ($senderId === 0) {
                $senderId = (int)$order['cust_user_id'];
            }
        }

        $msgId = $this->chatModel->saveMessage((int)$order['order_id'], $senderId, $receiverId, $message);

        $this->successResponse('Pesan berhasil dikirim', [
            'id'             => $msgId,
            'order_id'       => (int)$order['order_id'],
            'sender_id'      => $senderId,
            'receiver_id'    => $receiverId,
            'message'        => $message,
            'time_formatted' => date('H:i'),
            'created_at'     => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Mark all messages as read for this order
     */
    public function markAsRead(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Unauthorized', null, 401);
            return;
        }

        $data = $this->getPost();
        if (empty($data)) {
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true) ?: [];
        }

        $orderCode = sanitize($data['order_code'] ?? '');
        $order = $this->chatModel->getOrderChatDetails($orderCode);
        if ($order) {
            $this->chatModel->markAsRead((int)$order['order_id'], $userId);
        }

        $this->successResponse('Pesan ditandai sudah dibaca');
    }

    /**
     * Get unread message count for badge polling
     */
    public function unreadCount(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->successResponse('OK', ['unread_count' => 0]);
            return;
        }

        $orderCode = sanitize($_GET['order_code'] ?? '');
        if (empty($orderCode)) {
            $this->successResponse('OK', ['unread_count' => 0]);
            return;
        }

        $order = $this->chatModel->getOrderChatDetails($orderCode);
        if (!$order) {
            $this->successResponse('OK', ['unread_count' => 0]);
            return;
        }

        $unread = $this->chatModel->getUnreadCountForOrder((int)$order['order_id'], $userId);
        $this->successResponse('OK', ['unread_count' => $unread]);
    }
}
