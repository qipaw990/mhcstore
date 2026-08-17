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

        // Available nearby orders in driver zone
        $availableOrders = $this->orderModel->getAvailableForDelivery((int)($dm['zone_id'] ?? 1));

        // Today's summary
        $wallet = $this->walletModel->getOrCreate($userId, 'delivery_man');

        // Recalculate driver rating & fetch driver reviews
        $reviewModel = new \App\Models\Review();
        $reviewModel->recalculateDmRating((int)$dm['id']);
        $dm = $this->dmModel->find($dm['id']);
        $reviews = $reviewModel->getDmReviews((int)$dm['id'], 15);

        $this->view('delivery.dashboard', [
            'title'            => 'Kurir Dashboard - CicalengkaGO',
            'driver'           => $dm,
            'active_order'     => $activeOrder,
            'available_orders' => $availableOrders,
            'wallet'           => $wallet,
            'reviews'          => $reviews,
            'active_tab'       => 'home'
        ], 'delivery_layout');
    }

    public function getLiveDashboard(): void
    {
        // Auto cancel any unclaimed orders older than 60 seconds
        \App\Models\Order::autoCancelUnclaimedOrders();

        $userId = auth_id();
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

        // Available nearby orders in driver radar
        $availableOrders = [];
        if (empty($activeOrder) && $dm['is_online']) {
            $availableOrders = $this->orderModel->getAvailableForDelivery((int)($dm['zone_id'] ?? 1));
        }

        // Today's summary
        $wallet = $this->walletModel->getOrCreate($userId, 'delivery_man');

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
            'available_orders' => $availableOrders,
            'available_count'  => count($availableOrders),
            'wallet_balance'   => (float)($wallet['balance'] ?? 0),
            'total_orders'     => (int)($dm['total_orders'] ?? 0),
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
        $dm = $this->dmModel->findByUserId($userId);
        if (!$dm) {
            $this->errorResponse('Driver tidak ditemukan');
            return;
        }

        $newStatus = $dm['is_online'] ? 0 : 1;
        $this->dmModel->update($dm['id'], ['is_online' => $newStatus]);

        $this->successResponse($newStatus ? 'Anda sekarang ONLINE dan siap menerima order.' : 'Anda sekarang OFFLINE.', [
            'is_online' => $newStatus
        ]);
    }

    public function acceptOrder(): void
    {
        $userId = auth_id();
        $data = $this->getPost();
        $orderId = (int)($data['order_id'] ?? 0);

        try {
            $this->deliveryService->acceptOrder($userId, $orderId);
            $order = $this->orderModel->find($orderId);
            $this->successResponse('Pesanan berhasil diterima! Segera menuju ke lokasi penjemputan.', [
                'order_code' => $order['order_code']
            ]);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
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
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    public function updateLocation(): void
    {
        $userId = auth_id();
        $data = $this->getPost();
        $lat = (float)($data['lat'] ?? 0);
        $lng = (float)($data['lng'] ?? 0);

        $dm = $this->dmModel->findByUserId($userId);
        if ($dm && $lat != 0 && $lng != 0) {
            $this->dmModel->updateLocation($dm['id'], $lat, $lng, $dm['current_order_id']);
            $this->successResponse('Lokasi diperbarui.');
            return;
        }

        $this->errorResponse('Invalid coordinates.');
    }

    public function earnings(): void
    {
        $userId = auth_id();
        $wallet = $this->walletModel->getOrCreate($userId, 'delivery_man');
        $transactions = $this->walletModel->getTransactions($userId, 50);
        $withdrawRequests = $this->withdrawModel->getByUser($userId, 'delivery_man', 50);
        $totalWithdrawn = $this->withdrawModel->getTotalWithdrawn($userId, 'delivery_man');
        $pendingWithdrawn = $this->withdrawModel->getPendingWithdrawn($userId, 'delivery_man');

        $this->view('delivery.earnings', [
            'title'             => 'Pendapatan & Saldo Driver',
            'wallet'            => $wallet,
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
        $isEmailChanged = (strtolower($email) !== strtolower($currentUser['email'] ?? ''));
        $isPasswordChanged = !empty($passwordUpdate);

        if ($isEmailChanged || $isPasswordChanged) {
            if ($isEmailChanged) {
                $existing = Database::fetchOne("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1", [$email, $userId]);
                if ($existing) {
                    $_SESSION['error'] = 'Alamat email ini sudah terdaftar pada akun lain.';
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
                'role'       => $currentUser['role'],
                'otp'        => $otp,
                'expires_at' => time() + 600
            ];
            $_SESSION['otp_last_sent'] = time();

            if (!$isDemo) {
                \App\Services\EmailService::sendOtpEmail($email, $name, $otp);
            }

            $reason = ($isPasswordChanged && $isEmailChanged)
                ? 'perubahan email dan kata sandi'
                : ($isPasswordChanged ? 'perubahan kata sandi akun' : 'perubahan alamat email');

            $_SESSION['info'] = "Kode verifikasi OTP telah dikirimkan ke email ({$email}). Masukkan kode 6-digit untuk mengonfirmasi {$reason}.";
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
}
