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
        $dmUserId     = (int)($order['dm_user_id'] ?? 0);
        if ($dmUserId === 0 && !empty($order['delivery_man_id'])) {
            $dmRec = Database::fetchOne("SELECT user_id FROM delivery_men WHERE id = ? LIMIT 1", [(int)$order['delivery_man_id']]);
            if ($dmRec) {
                $dmUserId = (int)($dmRec['user_id'] ?? 0);
            }
        }
        $custUserId   = (int)($order['cust_user_id'] ?? 0);

        $requestedRole = sanitize(trim($data['caller_role'] ?? $data['role'] ?? $_GET['role'] ?? ''));
        $targetRole    = sanitize(trim($data['target_role'] ?? $data['receiver_role'] ?? ''));

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

            $shouldCallVendor = in_array($targetRole, ['vendor', 'store', 'merchant'], true) || ($isMerchantDelivery && empty($targetRole));

            if ($shouldCallVendor) {
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
            $userId    = (int)($_GET['user_id'] ?? 0);
            if ($userId === 0) {
                $userId = auth_id() ?: (int)($_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? 0));
            }
            $orderCode = sanitize(trim($_GET['order_code'] ?? ''));

            if (empty($orderCode) && $userId === 0) {
                $this->successResponse('Tidak ada panggilan aktif', ['active_call' => null]);
                return;
            }

            // 1. Build WHERE conditions
            $whereAct = [];
            $paramsAct = [];

            if (!empty($orderCode) && $userId > 0) {
                $whereAct[] = "(vc.order_code = ? OR vc.receiver_id = ? OR vc.caller_id = ?)";
                $paramsAct[] = $orderCode;
                $paramsAct[] = $userId;
                $paramsAct[] = $userId;
            } elseif (!empty($orderCode)) {
                $whereAct[] = "vc.order_code = ?";
                $paramsAct[] = $orderCode;
            } elseif ($userId > 0) {
                $whereAct[] = "(vc.receiver_id = ? OR vc.caller_id = ?)";
                $paramsAct[] = $userId;
                $paramsAct[] = $userId;
            }

            $whereSql = implode(' AND ', $whereAct);

            // 2. Fetch active call ('calling' or 'connected') directly from voice_calls
            $call = Database::fetchOne(
                "SELECT * FROM voice_calls vc WHERE vc.status IN ('calling', 'connected') AND {$whereSql} ORDER BY vc.id DESC LIMIT 1",
                $paramsAct
            );

            // 3. If no active call, check if there was a recently ended/rejected call
            if (!$call) {
                $call = Database::fetchOne(
                    "SELECT * FROM voice_calls vc WHERE vc.status IN ('rejected', 'ended') AND {$whereSql} ORDER BY vc.id DESC LIMIT 1",
                    $paramsAct
                );
            }

            if (!$call) {
                $this->successResponse('Tidak ada panggilan aktif', ['active_call' => null]);
                return;
            }

            // 4. Safely enrich caller & receiver details
            $callerName   = 'Pengguna';
            $callerAvatar = '';
            $receiverName = 'Pengguna';
            $receiverAvatar = '';
            $storeName    = 'Mitra Toko';
            $storeLogo    = '';

            try {
                $uCaller = Database::fetchOne("SELECT name, avatar FROM users WHERE id = ? LIMIT 1", [(int)$call['caller_id']]);
                if ($uCaller) {
                    $callerName   = $uCaller['name'] ?? $callerName;
                    $callerAvatar = $uCaller['avatar'] ?? $callerAvatar;
                }

                $uRecv = Database::fetchOne("SELECT name, avatar FROM users WHERE id = ? LIMIT 1", [(int)$call['receiver_id']]);
                if ($uRecv) {
                    $receiverName   = $uRecv['name'] ?? $receiverName;
                    $receiverAvatar = $uRecv['avatar'] ?? $receiverAvatar;
                }

                // Check store info from order
                $ord = Database::fetchOne("SELECT s.name as store_name, s.logo as store_logo FROM orders o LEFT JOIN stores s ON o.store_id = s.id WHERE o.order_code = ? OR o.id = ? LIMIT 1", [$call['order_code'], is_numeric($call['order_code']) ? (int)$call['order_code'] : 0]);
                if ($ord) {
                    $storeName = $ord['store_name'] ?? $storeName;
                    $storeLogo = $ord['store_logo'] ?? $storeLogo;
                }

                if (in_array($call['caller_role'], ['vendor', 'store'], true) && !empty($storeName)) {
                    $callerName = $storeName;
                    $callerAvatar = $storeLogo ?: $callerAvatar;
                }
                if (in_array($call['receiver_role'], ['vendor', 'store'], true) && !empty($storeName)) {
                    $receiverName = $storeName;
                    $receiverAvatar = $storeLogo ?: $receiverAvatar;
                }
            } catch (\Throwable $enrichErr) {}

            $connectedTs = !empty($call['connected_at']) ? strtotime($call['connected_at']) : (!empty($call['updated_at']) ? strtotime($call['updated_at']) : time());
            if ($call['status'] !== 'connected') {
                $connectedTs = null;
            }

            $this->successResponse('Status panggilan', [
                'active_call' => [
                    'id'                  => (int)$call['id'],
                    'order_code'          => $call['order_code'],
                    'caller_id'           => (int)$call['caller_id'],
                    'receiver_id'         => (int)$call['receiver_id'],
                    'caller_role'         => $call['caller_role'],
                    'receiver_role'       => $call['receiver_role'],
                    'caller_name'         => $callerName,
                    'caller_avatar'       => $callerAvatar,
                    'receiver_name'       => $receiverName,
                    'receiver_avatar'     => $receiverAvatar,
                    'store_logo'          => $storeLogo,
                    'store_name'          => $storeName,
                    'status'              => $call['status'],
                    'offer'               => $call['offer'],
                    'answer'              => $call['answer'],
                    'ice_candidates'      => $call['ice_candidates'],
                    'connected_at'        => $call['connected_at'] ?? $call['updated_at'],
                    'connected_timestamp' => $connectedTs,
                    'connected_at_ms'     => $connectedTs ? ($connectedTs * 1000) : null,
                    'server_time'         => time(),
                    'server_time_ms'      => time() * 1000
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

        try {
            Database::execute("ALTER TABLE voice_calls ADD COLUMN `connected_at` timestamp NULL DEFAULT NULL");
        } catch (\Throwable $e) {}

        $nowStr = date('Y-m-d H:i:s');
        $updateData = [
            'status' => 'connected',
            'answer' => !empty($answer) ? (is_string($answer) ? $answer : json_encode($answer)) : null
        ];

        try {
            $updateData['connected_at'] = $nowStr;
            Database::update('voice_calls', $updateData, 'id = ?', [$callId]);
        } catch (\Throwable $e) {
            unset($updateData['connected_at']);
            Database::update('voice_calls', $updateData, 'id = ?', [$callId]);
        }

        $this->successResponse('Panggilan diterima', [
            'connected_at'        => $nowStr,
            'connected_timestamp' => time(),
            'connected_at_ms'     => time() * 1000
        ]);
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
