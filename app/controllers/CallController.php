<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use Exception;

class CallController extends Controller
{
    /**
     * Initiate a new voice call for an order
     */
    public function initiate(): void
    {
        $userId   = auth_id() ?: 0;
        $userRole = auth_role();

        $data = $this->getPostData();
        $orderCode = sanitize(trim($data['order_code'] ?? ''));
        $offer = $data['offer'] ?? null;

        if (empty($orderCode)) {
            $this->errorResponse('Kode pesanan wajib diisi.');
            return;
        }

        // Fetch order details with correct column names (customer_id & avatar)
        $order = Database::fetchOne("
            SELECT o.id as order_id, o.order_code, o.customer_id as cust_user_id, o.delivery_man_id,
                   o.store_id, o.delivery_type,
                   dm.user_id as dm_user_id, u_cust.name as cust_name, u_cust.phone as cust_phone,
                   u_cust.avatar as cust_avatar, u_dm.name as dm_name, u_dm.phone as dm_phone,
                   u_dm.avatar as dm_avatar,
                   s.name as store_name, s.logo as store_logo, s.phone as store_phone,
                   s.vendor_id as store_vendor_user_id
            FROM orders o
            LEFT JOIN users u_cust ON o.customer_id = u_cust.id
            LEFT JOIN delivery_men dm ON o.delivery_man_id = dm.id
            LEFT JOIN users u_dm ON dm.user_id = u_dm.id
            LEFT JOIN stores s ON o.store_id = s.id
            WHERE o.order_code = ? OR o.id = ? LIMIT 1
        ", [$orderCode, is_numeric($orderCode) ? (int)$orderCode : 0]);

        if (!$order) {
            $this->errorResponse('Pesanan tidak ditemukan.');
            return;
        }

        $vendorUserId = (int)($order['store_vendor_user_id'] ?? 0);
        $dmUserId     = (int)($order['dm_user_id'] ?? $order['delivery_man_id'] ?? 0);
        $custUserId   = (int)($order['cust_user_id'] ?? 0);

        $requestedRole = sanitize(trim($data['caller_role'] ?? $data['role'] ?? $_GET['role'] ?? ''));
        $isDriver   = in_array($userRole, ['delivery_man', 'driver', 'delivery'], true) 
                    || in_array($requestedRole, ['delivery_man', 'driver', 'delivery'], true)
                    || ($userId > 0 && $dmUserId === $userId);

        $isMerchant = in_array($userRole, ['vendor', 'merchant'], true)
                    || in_array($requestedRole, ['vendor', 'merchant'], true)
                    || ($userId > 0 && $vendorUserId === $userId);

        $isCustomer = ($userId > 0 && $custUserId === $userId) 
                    || ($requestedRole === 'customer')
                    || ($userId === 0 && !$isDriver && !$isMerchant);

        if (!$isDriver && !$isCustomer && !$isMerchant) {
            $this->errorResponse('Akses panggilan ditolak.', null, 403);
            return;
        }

        if ($isDriver) {
            $callerId     = ($userId > 0) ? $userId : $dmUserId;
            $callerRole   = 'delivery_man';
            $receiverId   = $custUserId;
            $receiverRole = 'customer';
            $partnerName  = $order['cust_name'] ?? 'Pelanggan';
            $partnerAvatar= $order['cust_avatar'] ?? 'assets/images/users/customer.png';
            $partnerPhone = $order['cust_phone'] ?? '';
        } elseif ($isMerchant) {
            $callerId     = ($userId > 0) ? $userId : $vendorUserId;
            $callerRole   = 'vendor';
            $receiverId   = $custUserId;
            $receiverRole = 'customer';
            $partnerName  = $order['cust_name'] ?? 'Pelanggan';
            $partnerAvatar= $order['cust_avatar'] ?? 'assets/images/users/customer.png';
            $partnerPhone = $order['cust_phone'] ?? '';
        } else {
            $callerId     = ($userId > 0) ? $userId : $custUserId;
            $callerRole   = 'customer';
            $isMerchantDelivery = ($order['delivery_type'] ?? '') === 'merchant' || empty($dmUserId);
            $storeLogo = !empty($order['store_logo']) ? $order['store_logo'] : (!empty($order['store_cover']) ? $order['store_cover'] : '');
            $storeName = !empty($order['store_name']) ? $order['store_name'] : 'Mitra Toko';

            if ($isMerchantDelivery) {
                $receiverId   = $vendorUserId;
                $receiverRole = 'vendor';
                $partnerName  = $storeName;
                $partnerAvatar= $storeLogo;
                $partnerPhone = $order['store_phone'] ?? '';
            } else {
                $receiverId   = $dmUserId;
                $receiverRole = 'delivery_man';
                $partnerName  = $order['dm_name'] ?? 'Mitra Driver';
                $partnerAvatar= $order['dm_avatar'] ?? '';
                $partnerPhone = $order['dm_phone'] ?? '';
            }
        }

        if (!$receiverId) {
            $this->errorResponse('Tujuan panggilan tidak ditemukan untuk pesanan ini.');
            return;
        }

        // End previous active calls for this order or user
        Database::execute("UPDATE voice_calls SET status = 'ended' WHERE (order_code = ? OR caller_id = ? OR receiver_id = ?) AND status IN ('calling', 'connected')", [$orderCode, $callerId, $callerId]);

        // Insert new voice call entry
        $callId = Database::insert('voice_calls', [
            'order_code'    => $orderCode,
            'caller_id'     => $callerId,
            'receiver_id'   => $receiverId,
            'caller_role'   => $callerRole,
            'receiver_role' => $receiverRole,
            'status'        => 'calling',
            'offer'         => !empty($offer) ? (is_string($offer) ? $offer : json_encode($offer)) : null
        ]);

        $this->successResponse('Panggilan dimulai', [
            'call_id'       => (int)$callId,
            'order_code'    => $orderCode,
            'caller_id'     => $callerId,
            'receiver_id'   => $receiverId,
            'partner_name'  => $partnerName,
            'partner_avatar'=> $partnerAvatar,
            'partner_phone' => $partnerPhone,
            'status'        => 'calling'
        ]);
    }

    /**
     * Poll active call status & signaling (by order_code or user_id)
     */
    public function poll(): void
    {
        try {
            $userId   = auth_id() ?: 0;
            $orderCode = sanitize(trim($_GET['order_code'] ?? ''));
            $reqUserId = (int)($_GET['user_id'] ?? 0);
            if ($reqUserId > 0 && $userId === 0) {
                $userId = $reqUserId;
            }

            $whereSql = "vc.created_at >= NOW() - INTERVAL 5 MINUTE AND vc.status IN ('calling', 'connected', 'ended', 'rejected')";
            $params = [];

            if (!empty($orderCode) && $userId > 0) {
                $whereSql .= " AND vc.order_code = ? AND (vc.receiver_id = ? OR vc.caller_id = ?)";
                $params[] = $orderCode;
                $params[] = $userId;
                $params[] = $userId;
            } elseif (!empty($orderCode)) {
                $whereSql .= " AND vc.order_code = ?";
                $params[] = $orderCode;
            } elseif ($userId > 0) {
                $whereSql .= " AND (vc.receiver_id = ? OR vc.caller_id = ?)";
                $params[] = $userId;
                $params[] = $userId;
            } else {
                $this->successResponse('Tidak ada panggilan aktif', ['active_call' => null]);
                return;
            }

            // Fetch active call for order or user with store & driver details
            $call = Database::fetchOne("
                SELECT vc.*,
                       u_caller.name as caller_name, u_caller.avatar as caller_avatar,
                       u_recv.name as receiver_name, u_recv.avatar as receiver_avatar,
                       o.store_id, o.delivery_type,
                       s.name as store_name, s.logo as store_logo, s.cover_photo as store_cover,
                       dm.id as dm_id, u_dm.name as dm_name, u_dm.avatar as dm_avatar
                FROM voice_calls vc
                LEFT JOIN orders o ON (vc.order_code = o.order_code OR vc.order_code = CAST(o.id AS CHAR))
                LEFT JOIN stores s ON o.store_id = s.id
                LEFT JOIN delivery_men dm ON o.delivery_man_id = dm.id
                LEFT JOIN users u_dm ON dm.user_id = u_dm.id
                LEFT JOIN users u_caller ON vc.caller_id = u_caller.id
                LEFT JOIN users u_recv ON vc.receiver_id = u_recv.id
                WHERE {$whereSql}
                ORDER BY vc.id DESC LIMIT 1
            ", $params);

            if (!$call) {
                $this->successResponse('Tidak ada panggilan aktif', ['active_call' => null]);
                return;
            }

            $storeLogo = !empty($call['store_logo']) ? $call['store_logo'] : (!empty($call['store_cover']) ? $call['store_cover'] : '');
            $storeName = !empty($call['store_name']) ? $call['store_name'] : 'Mitra Toko';

            $callerName   = $call['caller_name'] ?? 'Pengguna';
            $callerAvatar = $call['caller_avatar'] ?? '';
            if (in_array($call['caller_role'], ['vendor', 'store'], true)) {
                $callerName   = $storeName;
                $callerAvatar = $storeLogo ?: $callerAvatar;
            } elseif ($call['caller_role'] === 'delivery_man') {
                $callerName   = $call['dm_name'] ?? $callerName;
                $callerAvatar = $call['dm_avatar'] ?? $callerAvatar;
            }

            $receiverName   = $call['receiver_name'] ?? 'Pengguna';
            $receiverAvatar = $call['receiver_avatar'] ?? '';
            if (in_array($call['receiver_role'], ['vendor', 'store'], true)) {
                $receiverName   = $storeName;
                $receiverAvatar = $storeLogo ?: $receiverAvatar;
            } elseif ($call['receiver_role'] === 'delivery_man') {
                $receiverName   = $call['dm_name'] ?? $receiverName;
                $receiverAvatar = $call['dm_avatar'] ?? $receiverAvatar;
            }

            $this->successResponse('Status panggilan', [
                'active_call' => [
                    'id'              => (int)$call['id'],
                    'order_code'      => $call['order_code'],
                    'caller_id'       => (int)$call['caller_id'],
                    'receiver_id'     => (int)$call['receiver_id'],
                    'caller_role'     => $call['caller_role'],
                    'receiver_role'   => $call['receiver_role'],
                    'caller_name'     => $callerName,
                    'caller_avatar'   => $callerAvatar,
                    'receiver_name'   => $receiverName,
                    'receiver_avatar' => $receiverAvatar,
                    'store_logo'      => $storeLogo,
                    'store_name'      => $storeName,
                    'status'          => $call['status'],
                    'offer'           => $call['offer'],
                    'answer'          => $call['answer'],
                    'ice_candidates'  => $call['ice_candidates']
                ]
            ]);
        } catch (\Throwable $e) {
            $this->successResponse('Tidak ada panggilan aktif', ['active_call' => null]);
        }
    }

    /**
     * Answer incoming call
     */
    public function answer(): void
    {
        $data   = $this->getPostData();
        $callId = (int)($data['call_id'] ?? 0);
        $answer = $data['answer'] ?? null;

        if (!$callId) {
            $this->errorResponse('ID panggilan tidak valid.');
            return;
        }

        Database::update('voice_calls', [
            'status' => 'connected',
            'answer' => !empty($answer) ? (is_string($answer) ? $answer : json_encode($answer)) : null
        ], 'id = ?', [$callId]);

        $this->successResponse('Panggilan diterima');
    }

    /**
     * Reject incoming call
     */
    public function reject(): void
    {
        $data   = $this->getPostData();
        $callId = (int)($data['call_id'] ?? 0);
        $orderCode = sanitize(trim($data['order_code'] ?? ''));

        if ($callId) {
            Database::update('voice_calls', ['status' => 'rejected'], 'id = ?', [$callId]);
        } elseif (!empty($orderCode)) {
            Database::execute("UPDATE voice_calls SET status = 'rejected' WHERE order_code = ? AND status IN ('calling', 'connected')", [$orderCode]);
        }
        $this->successResponse('Panggilan ditolak');
    }

    /**
     * End active call
     */
    public function end(): void
    {
        $data   = $this->getPostData();
        $callId = (int)($data['call_id'] ?? 0);
        $orderCode = sanitize(trim($data['order_code'] ?? ''));

        if ($callId) {
            Database::update('voice_calls', ['status' => 'ended'], 'id = ?', [$callId]);
        } elseif (!empty($orderCode)) {
            Database::execute("UPDATE voice_calls SET status = 'ended' WHERE order_code = ? AND status IN ('calling', 'connected')", [$orderCode]);
        }
        $this->successResponse('Panggilan diakhiri');
    }

    /**
     * Send WebRTC ICE Candidate
     */
    public function iceCandidate(): void
    {
        $data      = $this->getPostData();
        $callId    = (int)($data['call_id'] ?? 0);
        $orderCode = sanitize(trim($data['order_code'] ?? ''));
        $candidate = $data['candidate'] ?? null;
        $role      = sanitize(trim($data['role'] ?? ''));

        if (!$callId && !empty($orderCode)) {
            $activeCall = Database::fetchOne("SELECT id FROM voice_calls WHERE order_code = ? AND status IN ('calling', 'connected') ORDER BY id DESC LIMIT 1", [$orderCode]);
            if ($activeCall) {
                $callId = (int)$activeCall['id'];
            }
        }

        if (!$callId || !$candidate) {
            $this->errorResponse('Parameter tidak lengkap.');
            return;
        }

        $call = Database::fetchOne("SELECT ice_candidates FROM voice_calls WHERE id = ? LIMIT 1", [$callId]);
        if ($call) {
            $currentCandidates = !empty($call['ice_candidates']) ? json_decode($call['ice_candidates'], true) : [];
            if (!is_array($currentCandidates)) {
                $currentCandidates = [];
            }

            // Handle candidate whether sent as json string or nested dict or raw object
            $parsedCand = is_string($candidate) ? (json_decode($candidate, true) ?: $candidate) : $candidate;

            if (is_array($parsedCand) && isset($parsedCand['role']) && isset($parsedCand['candidate'])) {
                $itemToSave = $parsedCand;
            } else {
                $itemToSave = [
                    'role'      => $role ?: 'unknown',
                    'candidate' => $parsedCand
                ];
            }

            $currentCandidates[] = $itemToSave;

            Database::update('voice_calls', [
                'ice_candidates' => json_encode($currentCandidates)
            ], 'id = ?', [$callId]);
        }

        $this->successResponse('ICE Candidate tersimpan');
    }

    /**
     * Helper to read POST body
     */
    private function getPostData(): array
    {
        $data = $this->getPost();
        if (empty($data)) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }
        return $data;
    }
}
