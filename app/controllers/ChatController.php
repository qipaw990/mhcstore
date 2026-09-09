<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Chat;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
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
     * Helper to resolve current authenticated user ID & role
     * Supports PHP session, Bearer token, X-User-ID header, and POST/GET payload (mobile/API)
     */
    private function resolveAuthUser(?array $inputData = null): array
    {
        $userId = auth_id() ?: 0;
        $userRole = auth_role() ?: '';

        if ($userId <= 0) {
            $reqUserId = (int)($inputData['user_id'] ?? $_POST['user_id'] ?? $_GET['user_id'] ?? 0);
            if ($reqUserId > 0) {
                $userId = $reqUserId;
            }
        }

        if (empty($userRole)) {
            $reqRole = sanitize($inputData['user_role'] ?? $_POST['user_role'] ?? $_GET['user_role'] ?? '');
            if (!empty($reqRole)) {
                $userRole = $reqRole;
            } else {
                $userRole = 'customer';
            }
        }

        return [$userId, $userRole];
    }

    /**
     * Get chat messages and partner info for an order
     */
    public function getMessages(): void
    {
        [$userId, $userRole] = $this->resolveAuthUser();

        $orderCode = sanitize($_GET['order_code'] ?? '');
        $sinceId   = (int)($_GET['since_id'] ?? 0);
        $markRead  = (bool)($_GET['mark_read'] ?? false);

        if (empty($orderCode)) {
            $this->errorResponse('Kode pesanan wajib diisi.');
            return;
        }

        $order = $this->chatModel->getOrderChatDetails($orderCode);
        if (!$order) {
            $this->errorResponse('Pesanan tidak ditemukan.');
            return;
        }

        $isCustomer = ($userId > 0 && (int)$order['cust_user_id'] === $userId);
        $isDriver   = ($userRole === 'delivery_man' || ($userId > 0 && (int)($order['dm_user_id'] ?? 0) === $userId));
        $isAdmin    = ($userRole === 'admin');
        $isMerchant = ($userRole === 'vendor' || $userRole === 'merchant' || ($userId > 0 && (int)($order['store_vendor_user_id'] ?? 0) === $userId));
        // Guests with the order_code can read (public tracking page)
        $isGuest    = ($userId === 0);

        if (!$isCustomer && !$isDriver && !$isAdmin && !$isGuest && !$isMerchant) {
            $this->errorResponse('Akses percakapan ditolak.', null, 403);
            return;
        }

        // Only mark as read if user identity is known
        if ($markRead) {
            $readTargetId = ($userId > 0) ? $userId : (int)$order['cust_user_id'];
            if ($readTargetId > 0) {
                $this->chatModel->markAsRead((int)$order['order_id'], $readTargetId);
            }
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
        } elseif ($isMerchant) {
            $partner = [
                'name'         => $order['customer_name'] ?? 'Pelanggan CicalengkaGO',
                'role'         => 'customer',
                'role_label'   => 'Pelanggan',
                'avatar'       => $order['customer_avatar'] ?? 'assets/images/users/customer.png',
                'phone'        => $order['customer_phone'] ?? '',
                'vehicle_info' => 'Pesanan #' . $order['order_code']
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
            // Customer or guest:
            $isMerchantDelivery = ($order['delivery_type'] ?? '') === 'merchant' || empty($order['dm_id']);
            if (!empty($order['dm_id']) && !$isMerchantDelivery) {
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
                    'name'           => $order['store_name'] ?? 'Mitra Toko / Resto',
                    'role'           => 'store',
                    'role_label'     => 'Mitra Toko CicalengkaGO',
                    'avatar'         => $order['store_logo'] ?? 'assets/images/store-default.png',
                    'phone'          => $order['store_phone'] ?? '',
                    'vehicle_info'   => 'Diantar Toko Langsung'
                ];
            }
        }

        $effectiveUserId = ($userId > 0) ? $userId : (int)$order['cust_user_id'];
        $unread = ($effectiveUserId > 0)
            ? $this->chatModel->getUnreadCountForOrder((int)$order['order_id'], $effectiveUserId)
            : 0;

        $this->successResponse('Pesan berhasil diambil', [
            'order_id'       => (int)$order['order_id'],
            'order_code'     => $order['order_code'],
            'order_status'   => $order['order_status'],
            'user_id'        => $effectiveUserId,
            'cust_user_id'   => (int)$order['cust_user_id'],
            'dm_user_id'     => (int)($order['dm_user_id'] ?? 0),
            'vendor_user_id' => (int)($order['store_vendor_user_id'] ?? 0),
            'partner'        => $partner,
            'messages'       => $messages,
            'unread_count'   => $unread
        ]);
    }

    /**
     * Send a new chat message
     */
    public function sendMessage(): void
    {
        // Accept both application/json and multipart/form-data
        $data = $this->getPost();
        if (empty($data['order_code']) && empty($data['message'])) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            if (!empty($decoded)) $data = $decoded;
        }

        [$userId, $userRole] = $this->resolveAuthUser($data);

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

        $isDriver   = ($userRole === 'delivery_man' || ($userId > 0 && (int)($order['dm_user_id'] ?? 0) === $userId));
        $isAdmin    = ($userRole === 'admin');
        $isMerchant = ($userRole === 'vendor' || $userRole === 'merchant' || ($userId > 0 && (int)($order['store_vendor_user_id'] ?? 0) === $userId));
        $isLoggedInCustomer = ($userId > 0 && (int)$order['cust_user_id'] === $userId);
        // Guest customer accessing by valid order_code (tracking their own order page)
        $isGuestCustomer    = ($userId === 0 && !$isDriver && !$isAdmin && !$isMerchant);

        if (!$isLoggedInCustomer && !$isGuestCustomer && !$isDriver && !$isAdmin && !$isMerchant) {
            $this->errorResponse('Akses pengiriman pesan ditolak.', null, 403);
            return;
        }

        $receiverId = 0;
        $senderId   = $userId;

        if ($isDriver) {
            // Driver sends to customer
            $receiverId = (int)$order['cust_user_id'];
            if ($senderId === 0) {
                $senderId = (int)($order['dm_user_id'] ?? 0);
            }
        } elseif ($isMerchant) {
            // Merchant sends to customer
            $receiverId = (int)$order['cust_user_id'];
            if ($senderId === 0) {
                $senderId = (int)($order['store_vendor_user_id'] ?? 0);
            }
        } elseif ($isAdmin) {
            $receiverId = !empty($order['dm_user_id']) ? (int)$order['dm_user_id'] : (int)$order['cust_user_id'];
        } else {
            // Customer (logged-in or guest) sends to driver or merchant
            if (!empty($order['dm_user_id']) && ($order['delivery_type'] ?? '') !== 'merchant') {
                $receiverId = (int)$order['dm_user_id'];
            } else {
                $receiverId = (int)($order['store_vendor_user_id'] ?? 0);
            }
            // For guest, use customer_id from order as sender
            if ($senderId === 0) {
                $senderId = (int)$order['cust_user_id'];
            }
        }

        $storeId = (int)($order['store_id'] ?? 0);
        $msgId = $this->chatModel->saveMessage((int)$order['order_id'], $senderId, $receiverId, $message, null, $storeId);

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
        $data = $this->getPost();
        if (empty($data)) {
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true) ?: [];
        }

        [$userId] = $this->resolveAuthUser($data);

        $orderCode = sanitize($data['order_code'] ?? '');
        if (!empty($orderCode)) {
            $order = $this->chatModel->getOrderChatDetails($orderCode);
            if ($order) {
                $targetId = ($userId > 0) ? $userId : (int)$order['cust_user_id'];
                if ($targetId > 0) {
                    $this->chatModel->markAsRead((int)$order['order_id'], $targetId);
                }
            }
        }

        $storeId = (int)($data['store_id'] ?? 0);
        if ($storeId > 0 && $userId > 0) {
            $this->chatModel->markStoreMessagesRead($storeId, $userId);
        }

        $this->successResponse('Pesan ditandai sudah dibaca');
    }

    /**
     * Get unread message count for badge polling
     */
    public function unreadCount(): void
    {
        [$userId] = $this->resolveAuthUser();

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

        // If guest customer, fallback to customer_id from order
        if ($userId <= 0) {
            $userId = (int)($order['cust_user_id'] ?? 0);
        }

        if ($userId <= 0) {
            $this->successResponse('OK', ['unread_count' => 0]);
            return;
        }

        $unread = $this->chatModel->getUnreadCountForOrder((int)$order['order_id'], $userId);
        $this->successResponse('OK', ['unread_count' => $unread]);
    }

    /**
     * Get chat messages between customer and a specific store (Direct In-App Chat)
     */
    public function getStoreMessages(): void
    {
        [$userId, $userRole] = $this->resolveAuthUser();
        $storeId  = (int)($_GET['store_id'] ?? 0);
        $sinceId  = (int)($_GET['since_id'] ?? 0);
        $markRead = (bool)($_GET['mark_read'] ?? false);

        if ($storeId <= 0) {
            $this->errorResponse('ID Toko wajib diisi.');
            return;
        }

        $storeModel = new Store();
        $store = $storeModel->findWithDetails($storeId);
        if (!$store) {
            $this->errorResponse('Toko tidak ditemukan.');
            return;
        }

        $vendorUserId = (int)($store['vendor_id'] ?? 0);
        $isMerchant   = ($userRole === 'vendor' || $userRole === 'merchant' || $userId === $vendorUserId);

        // If viewer is merchant and customer user id is passed via target_user_id or user_id
        $chatUserId = $isMerchant ? (int)($_GET['target_user_id'] ?? $_GET['user_id'] ?? 0) : $userId;

        if ($markRead && $userId > 0) {
            $this->chatModel->markStoreMessagesRead($storeId, $userId);
        }

        $messages = $this->chatModel->getStoreMessages($storeId, $chatUserId, $sinceId);

        $partner = null;
        if ($isMerchant) {
            $targetUser = $chatUserId > 0 ? (new User())->find($chatUserId) : null;
            $partner = [
                'name'         => $targetUser['name'] ?? 'Pelanggan CicalengkaGO',
                'role'         => 'customer',
                'role_label'   => 'Pelanggan',
                'avatar'       => $targetUser['avatar'] ?? 'assets/images/users/customer.png',
                'phone'        => $targetUser['phone'] ?? '',
                'vehicle_info' => 'Pelanggan Toko'
            ];
        } else {
            $partner = [
                'name'         => $store['name'] ?? 'Mitra Toko',
                'role'         => 'store',
                'role_label'   => 'Mitra Toko / Resto',
                'avatar'       => $store['logo'] ?? 'assets/images/store-default.png',
                'phone'        => $store['phone'] ?? $store['vendor_phone'] ?? '',
                'vehicle_info' => $store['address'] ?? 'Cicalengka, Kab. Bandung'
            ];
        }

        $this->successResponse('Pesan toko berhasil diambil', [
            'store_id'     => $storeId,
            'user_id'      => $userId,
            'partner'      => $partner,
            'messages'     => $messages,
            'unread_count' => 0
        ]);
    }

    /**
     * Send a new message to a specific store (Direct In-App Chat)
     */
    public function sendStoreMessage(): void
    {
        $data = $this->getPost();
        if (empty($data['store_id']) && empty($data['message'])) {
            $raw     = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            if (!empty($decoded)) $data = $decoded;
        }

        [$senderId, $role] = $this->resolveAuthUser($data);

        $storeId  = (int)($data['store_id'] ?? 0);
        $message  = trim($data['message'] ?? '');

        if ($storeId <= 0) {
            $this->errorResponse('ID Toko wajib diisi.');
            return;
        }

        if ($message === '') {
            $this->errorResponse('Pesan tidak boleh kosong.');
            return;
        }

        $storeModel = new Store();
        $store = $storeModel->findWithDetails($storeId);
        if (!$store) {
            $this->errorResponse('Toko tidak ditemukan.');
            return;
        }

        $vendorUserId = (int)($store['vendor_id'] ?? 0);
        $isMerchant   = ($role === 'vendor' || $role === 'merchant' || $senderId === $vendorUserId);

        $receiverId = 0;
        if ($isMerchant) {
            $receiverId = (int)($data['target_user_id'] ?? $data['receiver_id'] ?? 0);
            if ($senderId === 0) $senderId = $vendorUserId;
        } else {
            $receiverId = $vendorUserId;
            if ($senderId === 0) {
                $this->errorResponse('Silakan login untuk mengirim pesan ke toko.', null, 401);
                return;
            }
        }

        $msgId = $this->chatModel->saveStoreMessage($storeId, $senderId, $receiverId, $message);

        $this->successResponse('Pesan berhasil dikirim', [
            'id'             => $msgId,
            'store_id'       => $storeId,
            'sender_id'      => $senderId,
            'receiver_id'    => $receiverId,
            'message'        => $message,
            'time_formatted' => date('H:i'),
            'created_at'     => date('Y-m-d H:i:s')
        ]);
    }
}
