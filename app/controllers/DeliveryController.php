<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\DeliveryMan;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\WithdrawRequest;
use App\Services\DeliveryService;
use App\Core\Database;
use Exception;

class DeliveryController extends Controller
{
    private DeliveryMan $dmModel;
    private Order $orderModel;
    private Wallet $walletModel;
    private WithdrawRequest $withdrawModel;
    private DeliveryService $deliveryService;

    public function __construct()
    {
        $this->dmModel = new DeliveryMan();
        $this->orderModel = new Order();
        $this->walletModel = new Wallet();
        $this->withdrawModel = new WithdrawRequest();
        $this->deliveryService = new DeliveryService();
    }

    public function dashboard(): void
    {
        // Auto cancel any unclaimed orders older than 60 seconds
        \App\Models\Order::autoCancelUnclaimedOrders();

        $userId = auth_id();
        if (!$userId) {
            if ($this->isJsonRequest()) {
                $this->errorResponse('Silakan login terlebih dahulu.', null, 401);
                return;
            }
            $this->redirect('login');
            return;
        }

        $dm = $this->dmModel->findByUserId($userId);

        if (!$dm) {
            // Auto create delivery man record if driver user
            $dmId = $this->dmModel->create([
                'user_id'           => $userId,
                'zone_id'           => 1,
                'vehicle_type'      => 'Motor Honda Beat',
                'vehicle_number'    => 'D 1234 CCG',
                'identity_number'   => '3204000000000001',
                'is_online'         => 1,
                'is_active'         => 1,
                'current_latitude'  => -6.9840,
                'current_longitude' => 107.8340
            ]);
            $dm = $this->dmModel->find($dmId);
        }

        // Active Batch (all orders in current trip)
        $activeBatch = $this->deliveryService->getActiveBatch($userId);
        $activeOrder = null;
        if (!empty($activeBatch['orders'])) {
            // Use the first non-delivered order as the "current" order for map
            foreach ($activeBatch['orders'] as $bo) {
                if (!in_array($bo['order_status'], ['delivered', 'canceled'])) {
                    $activeOrder = $bo;
                    break;
                }
            }
        } else {
            // Fallback: look up last assigned order
            if (!empty($dm['current_order_id'])) {
                $activeOrder = $this->orderModel->find($dm['current_order_id']);
                if ($activeOrder && in_array($activeOrder['order_status'], ['delivered', 'canceled'])) {
                    $activeOrder = null;
                }
            }
            if (empty($activeOrder)) {
                $activeOrder = Database::fetchOne(
                    "SELECT * FROM `orders` WHERE `delivery_man_id` = ? AND `order_status` IN ('confirmed', 'processing', 'handover', 'picked_up', 'on_the_way') ORDER BY id DESC LIMIT 1",
                    [$dm['id']]
                );
            }
        }

        if (!empty($activeOrder)) {
            if (!empty($activeOrder['delivery_address_json']) && empty($activeOrder['delivery_address'])) {
                $activeOrder['delivery_address'] = json_decode($activeOrder['delivery_address_json'], true);
            }
            if (!empty($activeOrder['store_id'])) {
                $storeModel = new \App\Models\Store();
                $store = $storeModel->find($activeOrder['store_id']);
                if ($store) {
                    $activeOrder['store_name'] = $store['name'] ?? 'Toko Mitra';
                    $activeOrder['store_address'] = $store['address'] ?? 'Cicalengka';
                    $activeOrder['store_lat'] = $store['latitude'] ?? -6.9835;
                    $activeOrder['store_lng'] = $store['longitude'] ?? 107.8345;
                    $activeOrder['store_phone'] = $store['phone'] ?? '';
                }
            }
            // Attach multi-store batch data if part of multi-store order
            $this->orderModel->attachMultiStoreDetails($activeOrder);

            // Fetch and attach order items list for mobile driver UI
            if (empty($activeOrder['items'])) {
                $activeOrder['items'] = $this->orderModel->getItems((int)$activeOrder['id']);
            }
        }

        // Available nearby orders in driver zone
        $availableOrders = [];
        if (empty($activeOrder) || !empty($activeBatch['slots_left'])) {
            $availableOrders = $this->orderModel->getAvailableForDelivery((int)($dm['zone_id'] ?? 1));
        }

        // Auto-credit driver commission for delivered orders that haven't been credited yet
        $wallet = $this->ensureDriverDeliveredOrdersCredited($dm);

        // Calculate REAL delivered orders count strictly from orders table
        $realDeliveredCount = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM `orders` WHERE `delivery_man_id` = ? AND `order_status` = 'delivered'",
            [$dm['id']]
        );
        Database::update('delivery_men', ['total_orders' => $realDeliveredCount], 'id = ?', [$dm['id']]);

        // Recalculate driver rating & fetch driver reviews
        $reviewModel = new \App\Models\Review();
        $reviewModel->recalculateDmRating((int)$dm['id']);
        $dm = $this->dmModel->find($dm['id']);
        $reviews = $reviewModel->getDmReviews((int)$dm['id'], 20);

        if ($this->isJsonRequest()) {
            $this->successResponse('Dashboard driver berhasil diambil', [
                'driver'            => $dm,
                'active_order'      => $activeOrder,
                'active_trip'       => $activeOrder,
                'active_batch'      => $activeBatch,
                'available_orders'  => $availableOrders,
                'wallet'            => $wallet,
                'reviews'           => $reviews,
            ]);
            return;
        }

        $this->view('delivery.dashboard', [
            'title'             => 'Dashboard Driver',
            'dm'                => $dm,
            'activeOrder'       => $activeOrder,
            'activeBatch'       => $activeBatch,
            'availableOrders'   => $availableOrders,
            'wallet'            => $wallet,
            'reviews'           => $reviews,
            'active_tab'        => 'home'
        ], 'delivery_layout');
    }

    public function getLiveDashboard(): void
    {
        // Auto cancel any unclaimed orders older than 60 seconds
        \App\Models\Order::autoCancelUnclaimedOrders();

        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Silakan login terlebih dahulu', null, 401);
            return;
        }

        $dm = $this->dmModel->findByUserId($userId);

        if (!$dm) {
            $this->errorResponse('Driver tidak ditemukan');
            return;
        }

        // Recalculate rating
        (new \App\Models\Review())->recalculateDmRating((int)$dm['id']);
        $dm = $this->dmModel->find($dm['id']);

        // Active Order (Self-healing from both delivery_men and orders table)
        $activeOrder = null;
        if (!empty($dm['current_order_id'])) {
            $activeOrder = $this->orderModel->find($dm['current_order_id']);
        }
        if (!$activeOrder || in_array($activeOrder['order_status'], ['delivered', 'canceled'])) {
            $assigned = Database::fetchOne(
                "SELECT * FROM `orders` WHERE `delivery_man_id` = ? AND `order_status` NOT IN ('delivered', 'canceled') ORDER BY `id` DESC LIMIT 1",
                [$dm['id']]
            );
            if ($assigned) {
                $activeOrder = $this->orderModel->findByCode($assigned['order_code']);
                Database::update('delivery_men', ['current_order_id' => $assigned['id']], 'id = ?', [$dm['id']]);
            } else {
                $activeOrder = null;
                if (!empty($dm['current_order_id'])) {
                    Database::update('delivery_men', ['current_order_id' => null], 'id = ?', [$dm['id']]);
                }
            }
        } else {
            $activeOrder = $this->orderModel->findByCode($activeOrder['order_code']);
        }

        if (!empty($activeOrder)) {
            if (!empty($activeOrder['delivery_address_json']) && empty($activeOrder['delivery_address'])) {
                $activeOrder['delivery_address'] = json_decode($activeOrder['delivery_address_json'], true);
            }
            if (!empty($activeOrder['store_id'])) {
                $storeModel = new \App\Models\Store();
                $store = $storeModel->find($activeOrder['store_id']);
                if ($store) {
                    $activeOrder['store_name'] = $store['name'] ?? 'Toko Mitra';
                    $activeOrder['store_address'] = $store['address'] ?? 'Cicalengka';
                    $activeOrder['store_lat'] = $store['latitude'] ?? -6.9835;
                    $activeOrder['store_lng'] = $store['longitude'] ?? 107.8345;
                    $activeOrder['store_phone'] = $store['phone'] ?? '';
                }
            }
            // Attach multi-store batch data if part of multi-store order
            $this->orderModel->attachMultiStoreDetails($activeOrder);

            // Fetch and attach order items list for mobile driver UI
            if (empty($activeOrder['items'])) {
                $activeOrder['items'] = $this->orderModel->getItems((int)$activeOrder['id']);
            }
        }

        // Available nearby orders in driver radar
        $availableOrders = [];
        if (empty($activeOrder) && $dm['is_online']) {
            $availableOrders = $this->orderModel->getAvailableForDelivery((int)($dm['zone_id'] ?? 1));
        }

        // Auto-credit any pending delivered orders
        $wallet = $this->ensureDriverDeliveredOrdersCredited($dm);

        // Recalculate REAL delivered count strictly from orders table
        $realDeliveredCount = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM `orders` WHERE `delivery_man_id` = ? AND `order_status` = 'delivered'",
            [$dm['id']]
        );
        Database::update('delivery_men', ['total_orders' => $realDeliveredCount], 'id = ?', [$dm['id']]);

        // Unread chats
        $unreadChats = 0;
        if ($activeOrder) {
            $chatCount = Database::query(
                "SELECT COUNT(*) as unread FROM order_chats WHERE order_id = ? AND sender_id != ? AND is_read = 0",
                [$activeOrder['id'], $userId]
            );
            $unreadChats = (int)($chatCount[0]['unread'] ?? 0);
        }

        $latestDbOrders = Database::query(
            "SELECT id, order_code, customer_id, store_id, delivery_man_id, order_status, payment_status, payment_method, created_at FROM `orders` ORDER BY `id` DESC LIMIT 5"
        );

        $this->successResponse('Live dashboard sync', [
            'is_online'        => (int)$dm['is_online'],
            'has_active_order' => !empty($activeOrder),
            'active_order'     => $activeOrder,
            'active_trip'      => $activeOrder,
            'driver'           => $dm,
            'available_orders' => $availableOrders,
            'available_count'  => count($availableOrders),
            'wallet'           => $wallet,
            'wallet_balance'   => (float)($wallet['balance'] ?? 0),
            'total_orders'     => $realDeliveredCount,
            'rating'           => (float)($dm['rating'] ?? 5.0),
            'reviews_count'    => (int)($dm['reviews_count'] ?? 0),
            'unread_chats'     => $unreadChats,
            'debug_db_orders'  => $latestDbOrders,
            'debug_dm'         => [
                'id'               => $dm['id'],
                'user_id'          => $dm['user_id'],
                'is_online'        => $dm['is_online'],
                'current_order_id' => $dm['current_order_id']
            ]
        ]);
    }

    public function toggleOnline(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Silakan login terlebih dahulu', null, 401);
            return;
        }

        $dm = $this->dmModel->findByUserId($userId);
        if (!$dm) {
            $this->errorResponse('Driver tidak ditemukan');
            return;
        }

        $data = $this->getPost();
        if (isset($data['online'])) {
            $newStatus = ($data['online'] == '1' || $data['online'] === true || $data['online'] === 1) ? 1 : 0;
        } else {
            $newStatus = $dm['is_online'] ? 0 : 1;
        }
        $this->dmModel->update($dm['id'], ['is_online' => $newStatus]);

        $this->successResponse($newStatus ? 'Anda sekarang ONLINE dan siap menerima order.' : 'Anda sekarang OFFLINE.', [
            'is_online' => $newStatus
        ]);
    }

    public function acceptOrder(): void
    {
        $userId = auth_id();
        $data   = $this->getPost();
        $orderId = (int)($data['order_id'] ?? 0);

        try {
            $result = $this->deliveryService->acceptOrder($userId, $orderId);
            $orderCount = is_array($result['order_count'] ?? null) ? count($result['order_count']) : (int)($result['order_count'] ?? 1);
            $this->successResponse(
                $orderCount > 1
                    ? "Pesanan ke-{$result['sequence']} berhasil ditambahkan ke trip Anda!"
                    : 'Pesanan berhasil diterima! Segera menuju ke lokasi penjemputan.',
                [
                    'order_code'  => $result['order_code'],
                    'batch_id'    => $result['batch_id'],
                    'order_count' => $orderCount,
                    'sequence'    => $result['sequence'],
                ]
            );
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    public function getBatchStatus(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Unauthorized', null, 401);
            return;
        }

        $batch = $this->deliveryService->getActiveBatch($userId);
        $this->successResponse('Batch status', $batch);
    }

    public function updateDeliveryStatus(): void
    {
        $userId = auth_id();
        $data = $this->getPost();
        $orderId = (int)($data['order_id'] ?? 0);
        $status = sanitize($data['status'] ?? '');
        $otp = sanitize($data['otp'] ?? '');

        try {
            $this->deliveryService->updateOrderStatus($userId, $orderId, $status, $otp);
            $this->successResponse('Status pengantaran berhasil diperbarui.');
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    public function updateLocation(): void
    {
        $userId = auth_id();
        $data = $this->getPost();
        $lat = (float)($data['lat'] ?? 0);
        $lng = (float)($data['lng'] ?? 0);
        $accuracy = (float)($data['accuracy'] ?? 0);
        $isMocked = (int)($data['is_mocked'] ?? 0);

        $dm = $this->dmModel->findByUserId($userId);
        if (!$dm) {
            $this->errorResponse('Driver tidak ditemukan.');
            return;
        }

        // 1. Check client-side reported Mock Location flag
        if ($isMocked === 1 || ($accuracy > 0 && $accuracy < 0.05)) {
            $this->errorResponse('Aplikasi Fake GPS terdeteksi. Akses pembaruan lokasi ditolak.');
            return;
        }

        // 2. Server-side Teleportation & Speed Jump Anomaly Check
        $prevLat = (float)($dm['current_latitude'] ?? 0);
        $prevLng = (float)($dm['current_longitude'] ?? 0);
        $lastUpdate = !empty($dm['updated_at']) ? strtotime($dm['updated_at']) : 0;
        $now = time();
        $timeDeltaSec = $now - $lastUpdate;

        if ($prevLat != 0 && $prevLng != 0 && $timeDeltaSec > 0 && $timeDeltaSec < 20) {
            $distKm = haversine_distance($prevLat, $prevLng, $lat, $lng);
            $distMeters = $distKm * 1000;
            $speedKmh = $distKm / ($timeDeltaSec / 3600);

            if ($distMeters > 350 && $speedKmh > 160) {
                $this->errorResponse('Anomali lompatan lokasi tidak wajar terdeteksi (Fake GPS).');
                return;
            }
        }

        if ($lat != 0 && $lng != 0) {
            $this->dmModel->updateLocation($dm['id'], $lat, $lng, $dm['current_order_id']);
            $this->successResponse('Lokasi diperbarui.');
            return;
        }

        $this->errorResponse('Invalid coordinates.');
    }

    public function earnings(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Unauthorized', 401);
            return;
        }

        $dm = $this->dmModel->findByUserId($userId);
        if (!$dm) {
            $this->errorResponse('Driver tidak ditemukan');
            return;
        }

        // Auto-credit any pending delivered orders
        $wallet = $this->ensureDriverDeliveredOrdersCredited($dm);
        $transactions = $this->walletModel->getTransactions($userId, 50);
        $withdrawRequests = $this->withdrawModel->getByUser($userId, 'delivery_man', 50);
        $totalWithdrawn = $this->withdrawModel->getTotalWithdrawn($userId, 'delivery_man');
        $pendingWithdrawn = $this->withdrawModel->getPendingWithdrawn($userId, 'delivery_man');

        if ($this->isJsonRequest()) {
            $this->successResponse('Data pendapatan driver berhasil diambil', [
                'wallet'            => $wallet,
                'driver'            => $dm,
                'transactions'      => $transactions,
                'withdraw_requests' => $withdrawRequests,
                'total_withdrawn'   => $totalWithdrawn,
                'pending_withdrawn' => $pendingWithdrawn,
            ]);
            return;
        }

        $this->view('delivery.earnings', [
            'title'             => 'Pendapatan & Saldo Driver',
            'wallet'            => $wallet,
            'driver'            => $dm,
            'transactions'      => $transactions,
            'withdraw_requests' => $withdrawRequests,
            'total_withdrawn'   => $totalWithdrawn,
            'pending_withdrawn' => $pendingWithdrawn,
            'active_tab'        => 'earnings'
        ], 'delivery_layout');
    }

    public function requestWithdraw(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Sesi tidak valid.');
            return;
        }

        $data = $this->getPost();
        $amount = (float)($data['amount'] ?? 0);
        $bankName = sanitize($data['bank_name'] ?? '');
        $accNumber = sanitize($data['account_number'] ?? '');
        $accHolder = sanitize($data['account_holder'] ?? '');

        if ($amount < 10000) {
            $this->errorResponse('Minimal penarikan saldo adalah Rp 10.000.');
            return;
        }

        if (empty($bankName) || empty($accNumber) || empty($accHolder)) {
            $this->errorResponse('Harap lengkapi informasi tujuan pencairan, nomor rekening/e-wallet, dan nama pemilik akun.');
            return;
        }

        try {
            $req = $this->withdrawModel->requestPayout(
                $userId,
                'delivery_man',
                $amount,
                $bankName,
                $accNumber,
                $accHolder
            );

            $this->successResponse('Pengajuan penarikan dana berhasil! Dana akan segera ditransfer ke rekening Anda.', [
                'withdraw' => $req
            ]);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    public function profile(): void
    {
        $userId = auth_id();
        $user = auth_user();
        $dm = $this->dmModel->findByUserId($userId);

        $reviews = [];
        if ($dm) {
            $reviewModel = new \App\Models\Review();
            $reviewModel->recalculateDmRating((int)$dm['id']);
            $dm = $this->dmModel->find($dm['id']);
            $reviews = $reviewModel->getDmReviews((int)$dm['id'], 20);
        }

        if ($this->isJsonRequest()) {
            $this->successResponse('Profil driver berhasil diambil', [
                'user'    => $user,
                'driver'  => $dm,
                'reviews' => $reviews,
            ]);
            return;
        }

        $this->view('delivery.profile', [
            'title'      => 'Profil Mitra Driver',
            'user'       => $user,
            'driver'     => $dm,
            'reviews'    => $reviews,
            'active_tab' => 'profile'
        ], 'delivery_layout');
    }

    public function updateProfile(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->redirect('login');
            return;
        }

        $data = $this->getPost();
        $name          = sanitize($data['name'] ?? '');
        $email         = sanitize($data['email'] ?? '');
        $phone         = sanitize($data['phone'] ?? '');
        $vehicleType   = sanitize($data['vehicle_type'] ?? '');
        $vehicleNumber = sanitize($data['vehicle_number'] ?? '');

        if (empty($name) || empty($email) || empty($phone)) {
            $_SESSION['error'] = 'Nama, email, dan nomor HP driver wajib diisi.';
            $this->redirect('delivery/profile');
            return;
        }

        // Handle Driver Avatar Upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $avatarPath = upload_image($_FILES['avatar'], 'profiles');
            if ($avatarPath) {
                (new \App\Models\User())->update($userId, ['avatar' => $avatarPath]);
                $_SESSION['user']['avatar'] = $avatarPath;
            }
        }

        $dm = $this->dmModel->findByUserId($userId);
        if ($dm && (!empty($vehicleType) || !empty($vehicleNumber))) {
            $this->dmModel->update($dm['id'], [
                'vehicle_type'   => $vehicleType ?: $dm['vehicle_type'],
                'vehicle_number' => $vehicleNumber ?: $dm['vehicle_number']
            ]);
        }

        $currentPassword = $data['current_password'] ?? '';
        $newPassword     = $data['new_password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        $passwordUpdate = null;
        if (!empty($newPassword) || !empty($currentPassword)) {
            $userModel = new \App\Models\User();
            $dbUser = $userModel->find($userId);

            if (empty($currentPassword)) {
                $_SESSION['error'] = 'Harap masukkan Kata Sandi Saat Ini untuk memverifikasi perubahan kata sandi.';
                $this->redirect('delivery/profile');
                return;
            }

            if (!password_verify($currentPassword, $dbUser['password'] ?? '')) {
                $_SESSION['error'] = 'Kata Sandi Saat Ini yang Anda masukkan salah.';
                $this->redirect('delivery/profile');
                return;
            }

            if (strlen($newPassword) < 6) {
                $_SESSION['error'] = 'Kata Sandi Baru harus memiliki minimal 6 karakter.';
                $this->redirect('delivery/profile');
                return;
            }

            if ($newPassword !== $confirmPassword) {
                $_SESSION['error'] = 'Konfirmasi Kata Sandi Baru tidak cocok.';
                $this->redirect('delivery/profile');
                return;
            }

            $passwordUpdate = password_hash($newPassword, PASSWORD_BCRYPT);
        }

        $currentUser = auth_user();
        $isEmailChanged    = (strtolower($email) !== strtolower($currentUser['email'] ?? ''));
        $isPhoneChanged    = (trim($phone) !== trim($currentUser['phone'] ?? ''));
        $isPasswordChanged = !empty($passwordUpdate);

        if ($isEmailChanged || $isPhoneChanged || $isPasswordChanged) {
            if ($isEmailChanged) {
                $existing = Database::fetchOne("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1", [$email, $userId]);
                if ($existing) {
                    $_SESSION['error'] = 'Alamat email ini sudah terdaftar pada akun lain.';
                    $this->redirect('delivery/profile');
                    return;
                }
            }

            if ($isPhoneChanged) {
                $existingPhone = Database::fetchOne("SELECT id FROM users WHERE phone = ? AND id != ? LIMIT 1", [$phone, $userId]);
                if ($existingPhone) {
                    $_SESSION['error'] = 'Nomor WhatsApp ini sudah terdaftar pada akun lain.';
                    $this->redirect('delivery/profile');
                    return;
                }
            }

            $otpMode = \App\Models\BusinessSetting::get('otp_mode', 'real');
            $isDemo = ($otpMode === 'demo');
            $otp = $isDemo ? '123456' : sprintf("%06d", rand(100000, 999999));

            $_SESSION['pending_profile_update'] = [
                'user_id'    => $userId,
                'name'       => $name,
                'phone'      => $phone,
                'new_email'  => $email,
                'password'   => $passwordUpdate,
                'otp'        => $otp,
                'expires_at' => time() + 600
            ];

            $_SESSION['pending_otp'] = [
                'user_id'    => $userId,
                'name'       => $name,
                'email'      => $email,
                'phone'      => $phone,
                'role'       => $currentUser['role'],
                'otp'        => $otp,
                'expires_at' => time() + 600
            ];
            $_SESSION['otp_last_sent'] = time();

            // Set WhatsApp channel details for verify UI
            $targetPhone = $isPhoneChanged ? $phone : ($currentUser['phone'] ?? $phone);
            $_SESSION['otp_channel'] = 'whatsapp';
            $_SESSION['otp_phone_masked'] = !empty($targetPhone)
                ? preg_replace('/(?<=.{4}).(?=.{4})/', '*', $targetPhone)
                : 'Nomor WhatsApp Baru';

            if (!$isDemo && !empty($targetPhone)) {
                try {
                    $wa = new \App\Services\WhatsAppService();
                    $wa->sendOtp($targetPhone, $name, $otp);
                } catch (\Throwable $e) {
                    error_log("[DeliveryController] Profile update WA OTP failed: " . $e->getMessage());
                }
            }

            $reasons = [];
            if ($isPhoneChanged) $reasons[] = 'nomor WhatsApp';
            if ($isEmailChanged) $reasons[] = 'email';
            if ($isPasswordChanged) $reasons[] = 'kata sandi';
            $reasonStr = implode(' & ', $reasons);

            $maskedNotice = !empty($targetPhone) ? preg_replace('/(?<=.{4}).(?=.{4})/', '*', $targetPhone) : $targetPhone;
            $_SESSION['info'] = "Kode verifikasi 6-digit telah dikirimkan via WhatsApp ke ({$maskedNotice}) untuk mengonfirmasi perubahan {$reasonStr}.";
            $this->redirect('verify-otp');
            return;
        }

        // Only name / phone changed without password or email changes
        (new \App\Models\User())->update($userId, [
            'name'  => $name,
            'phone' => $phone
        ]);

        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['phone'] = $phone;

        $_SESSION['success'] = 'Profil Mitra Driver berhasil diperbarui!';
        $this->redirect('delivery/profile');
    }

    public function ensureDriverDeliveredOrdersCredited(array $dm): array
    {
        $userId = (int)$dm['user_id'];
        $driverWallet = $this->walletModel->getOrCreate($userId, 'delivery_man');
        $deliveredDriverOrders = Database::query(
            "SELECT id, order_code, delivery_charge, distance_km, delivery_batch_id FROM `orders` WHERE `delivery_man_id` = ? AND `order_status` = 'delivered'",
            [$dm['id']]
        );
        $processedBatches = [];

        foreach ($deliveredDriverOrders as $dOrder) {
            $batchId = $dOrder['delivery_batch_id'] ?? null;
            if (!empty($batchId)) {
                if (!in_array($batchId, $processedBatches)) {
                    $processedBatches[] = $batchId;
                    $this->deliveryService->creditBatchCommission($dm, $batchId);
                }
            } else {
                $alreadyCredited = Database::fetchOne(
                    "SELECT id FROM `wallet_transactions` WHERE `wallet_id` = ? AND `category` = 'order_earning' AND `reference_id` = ? LIMIT 1",
                    [$driverWallet['id'], (string)$dOrder['id']]
                );
                if (!$alreadyCredited) {
                    $charge = (float)($dOrder['delivery_charge'] ?? 0);
                    if ($charge <= 0) {
                        $km = (float)($dOrder['distance_km'] ?? 1.5);
                        $charge = max(5000.0, $km * 2500.0);
                    }
                    $driverEarning = max(3000.0, round($charge * 0.85, 0));
                    $this->walletModel->credit(
                        $userId,
                        $driverEarning,
                        'order_earning',
                        "Komisi pengantaran pesanan #{$dOrder['order_code']}",
                        (string)$dOrder['id']
                    );
                }
            }
        }

        return $this->walletModel->find($driverWallet['id']);
    }
}
