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
                   dm.user_id as dm_user_id, u_cust.name as cust_name, u_cust.phone as cust_phone,
                   u_cust.avatar as cust_avatar, u_dm.name as dm_name, u_dm.phone as dm_phone,
                   u_dm.avatar as dm_avatar
            FROM orders o
            LEFT JOIN users u_cust ON o.customer_id = u_cust.id
            LEFT JOIN delivery_men dm ON o.delivery_man_id = dm.id
            LEFT JOIN users u_dm ON dm.user_id = u_dm.id
            WHERE o.order_code = ? LIMIT 1
        ", [$orderCode]);

        if (!$order) {
            $this->errorResponse('Pesanan tidak ditemukan.');
            return;
        }

        $isDriver   = ($userRole === 'delivery_man');
        $isCustomer = ($userId > 0 && (int)$order['cust_user_id'] === $userId) || ($userId === 0 && !$isDriver);

        if (!$isDriver && !$isCustomer) {
            $this->errorResponse('Akses panggilan ditolak.', null, 403);
            return;
        }

        $callerId = $userId;
        if ($callerId === 0 && $isCustomer) {
            $callerId = (int)$order['cust_user_id'];
        }

        if ($isDriver) {
            $callerRole   = 'delivery_man';
            $receiverId   = (int)$order['cust_user_id'];
            $receiverRole = 'customer';
            $partnerName  = $order['cust_name'] ?? 'Pelanggan';
            $partnerAvatar= $order['cust_avatar'] ?? 'assets/images/users/customer.png';
            $partnerPhone = $order['cust_phone'] ?? '';
        } else {
            $callerRole   = 'customer';
            $receiverId   = (int)($order['dm_user_id'] ?? 0);
            $receiverRole = 'delivery_man';
            $partnerName  = $order['dm_name'] ?? 'Mitra Driver';
            $partnerAvatar= $order['dm_avatar'] ?? 'assets/images/users/driver.png';
            $partnerPhone = $order['dm_phone'] ?? '';
        }

        if (!$receiverId) {
            $this->errorResponse('Mitra kurir belum ditugaskan untuk pesanan ini.');
            return;
        }

        // End previous active calls for this order
        Database::execute("UPDATE voice_calls SET status = 'ended' WHERE order_code = ? AND status IN ('calling', 'connected')", [$orderCode]);

        // Insert new voice call entry
        $callId = Database::insert('voice_calls', [
            'order_code'    => $orderCode,
            'caller_id'     => $callerId,
            'receiver_id'   => $receiverId,
            'caller_role'   => $callerRole,
            'receiver_role' => $receiverRole,
            'status'        => 'calling',
            'offer'         => is_string($offer) ? $offer : json_encode($offer)
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
        $userId   = auth_id() ?: 0;
        $orderCode = sanitize(trim($_GET['order_code'] ?? ''));

        $whereSql = "vc.created_at >= NOW() - INTERVAL 5 MINUTE AND vc.status IN ('calling', 'connected')";
        $params = [];

        if (!empty($orderCode)) {
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

        // Fetch active call for order or user
        $call = Database::fetchOne("
            SELECT vc.*,
                   u_caller.name as caller_name, u_caller.avatar as caller_avatar,
                   u_recv.name as receiver_name, u_recv.avatar as receiver_avatar
            FROM voice_calls vc
            LEFT JOIN users u_caller ON vc.caller_id = u_caller.id
            LEFT JOIN users u_recv ON vc.receiver_id = u_recv.id
            WHERE {$whereSql}
            ORDER BY vc.id DESC LIMIT 1
        ", $params);

        if (!$call) {
            $this->successResponse('Tidak ada panggilan aktif', ['active_call' => null]);
            return;
        }

        $this->successResponse('Status panggilan', [
            'active_call' => [
                'id'              => (int)$call['id'],
                'order_code'      => $call['order_code'],
                'caller_id'       => (int)$call['caller_id'],
                'receiver_id'     => (int)$call['receiver_id'],
                'caller_role'     => $call['caller_role'],
                'receiver_role'   => $call['receiver_role'],
                'caller_name'     => $call['caller_name'] ?? 'Pengguna',
                'caller_avatar'   => $call['caller_avatar'] ?? 'assets/images/users/customer.png',
                'receiver_name'   => $call['receiver_name'] ?? 'Pengguna',
                'receiver_avatar' => $call['receiver_avatar'] ?? 'assets/images/users/driver.png',
                'status'          => $call['status'],
                'offer'           => $call['offer'],
                'answer'          => $call['answer'],
                'ice_candidates'  => $call['ice_candidates']
            ]
        ]);
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
            'answer' => is_string($answer) ? $answer : json_encode($answer)
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

        if (!$callId) {
            $this->errorResponse('ID panggilan tidak valid.');
            return;
        }

        Database::update('voice_calls', ['status' => 'rejected'], 'id = ?', [$callId]);
        $this->successResponse('Panggilan ditolak');
    }

    /**
     * End active call
     */
    public function end(): void
    {
        $data   = $this->getPostData();
        $callId = (int)($data['call_id'] ?? 0);

        if (!$callId) {
            $this->errorResponse('ID panggilan tidak valid.');
            return;
        }

        Database::update('voice_calls', ['status' => 'ended'], 'id = ?', [$callId]);
        $this->successResponse('Panggilan diakhiri');
    }

    /**
     * Send WebRTC ICE Candidate
     */
    public function iceCandidate(): void
    {
        $data      = $this->getPostData();
        $callId    = (int)($data['call_id'] ?? 0);
        $candidate = $data['candidate'] ?? null;

        if (!$callId || !$candidate) {
            $this->errorResponse('Parameter tidak lengkap.');
            return;
        }

        $call = Database::fetchOne("SELECT ice_candidates FROM voice_calls WHERE id = ? LIMIT 1", [$callId]);
        if ($call) {
            $currentCandidates = !empty($call['ice_candidates']) ? json_decode($call['ice_candidates'], true) : [];
            $currentCandidates[] = $candidate;

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
