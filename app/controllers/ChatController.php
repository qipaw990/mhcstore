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
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Silakan login terlebih dahulu.', null, 401);
            return;
        }

        $orderCode = sanitize($_GET['order_code'] ?? '');
        $sinceId = (int)($_GET['since_id'] ?? 0);
        $markRead = (bool)($_GET['mark_read'] ?? true);

        if (empty($orderCode)) {
            $this->errorResponse('Kode pesanan wajib diisi.');
            return;
        }

        $order = $this->chatModel->getOrderChatDetails($orderCode);
        if (!$order) {
            $this->errorResponse('Pesanan tidak ditemukan.');
            return;
        }

        $isCustomer = ((int)$order['cust_user_id'] === $userId);
        $isDriver   = (!empty($order['dm_user_id']) && (int)$order['dm_user_id'] === $userId);
        $isAdmin    = (auth_user()['role'] ?? '') === 'admin';

        if (!$isCustomer && !$isDriver && !$isAdmin) {
            $this->errorResponse('Akses percakapan ditolak.', null, 403);
            return;
        }

        // Mark incoming messages as read for this user
        if ($markRead) {
            $this->chatModel->markAsRead((int)$order['order_id'], $userId);
        }

        $messages = $this->chatModel->getOrderMessages((int)$order['order_id'], $sinceId);

        // Define partner information based on viewer role
        $partner = null;
        if ($isCustomer) {
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
                    'name'           => 'Belum ada Driver',
                    'role'           => 'driver',
                    'role_label'     => 'Menunggu Driver',
                    'avatar'         => 'assets/images/users/driver.png',
                    'phone'          => '',
                    'vehicle_info'   => 'Mencari kurir terdekat...'
                ];
            }
        } elseif ($isDriver) {
            $partner = [
                'name'         => $order['customer_name'] ?? 'Pelanggan CicalengkaGO',
                'role'         => 'customer',
                'role_label'   => 'Pelanggan',
                'avatar'       => $order['customer_avatar'] ?? 'assets/images/users/customer.png',
                'phone'        => $order['customer_phone'] ?? '',
                'vehicle_info' => 'Tujuan Pengantaran'
            ];
        }

        $unread = $this->chatModel->getUnreadCountForOrder((int)$order['order_id'], $userId);

        $this->successResponse('Pesan berhasil diambil', [
            'order_id'     => (int)$order['order_id'],
            'order_code'   => $order['order_code'],
            'order_status' => $order['order_status'],
            'user_id'      => $userId,
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
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Silakan login terlebih dahulu.', null, 401);
            return;
        }

        $data = $this->getPost();
        if (empty($data)) {
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true) ?: [];
        }

        $orderCode = sanitize($data['order_code'] ?? '');
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

        $isCustomer = ((int)$order['cust_user_id'] === $userId);
        $isDriver   = (!empty($order['dm_user_id']) && (int)$order['dm_user_id'] === $userId);
        $isAdmin    = (auth_user()['role'] ?? '') === 'admin';

        if (!$isCustomer && !$isDriver && !$isAdmin) {
            $this->errorResponse('Akses pengiriman pesan ditolak.', null, 403);
            return;
        }

        $receiverId = 0;
        if ($isCustomer) {
            if (empty($order['dm_user_id'])) {
                $this->errorResponse('Kurir belum mengambil pesanan ini. Chat akan aktif setelah kurir ditugaskan.');
                return;
            }
            $receiverId = (int)$order['dm_user_id'];
        } elseif ($isDriver) {
            $receiverId = (int)$order['cust_user_id'];
        } elseif ($isAdmin) {
            // If admin, send to driver if available or customer
            $receiverId = !empty($order['dm_user_id']) ? (int)$order['dm_user_id'] : (int)$order['cust_user_id'];
        }

        $msgId = $this->chatModel->saveMessage((int)$order['order_id'], $userId, $receiverId, $message);

        $this->successResponse('Pesan berhasil dikirim', [
            'id'             => $msgId,
            'order_id'       => (int)$order['order_id'],
            'sender_id'      => $userId,
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
