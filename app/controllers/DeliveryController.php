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
        }
        
        // Fallback: look up last assigned order from delivery_men or orders table
        if (empty($activeOrder)) {
            if (!empty($dm['current_order_id'])) {
                $candidate = $this->orderModel->find($dm['current_order_id']);
                if ($candidate && !in_array($candidate['order_status'], ['delivered', 'canceled'])) {
                    $activeOrder = $candidate;
                }
            }
        }
        if (empty($activeOrder)) {
            $candidate = Database::fetchOne(
                "SELECT * FROM `orders` WHERE `delivery_man_id` = ? AND `order_status` NOT IN ('delivered', 'canceled') ORDER BY id DESC LIMIT 1",
                [$dm['id']]
            );
            if ($candidate) {
                $activeOrder = $this->orderModel->findByCode($candidate['order_code']) ?: $candidate;
                Database::update('delivery_men', ['current_order_id' => $candidate['id']], 'id = ?', [$dm['id']]);
            }
        }

        if (!empty($activeOrder)) {
            if (empty($activeOrder['customer_name']) && !empty($activeOrder['customer_id'])) {
                $customer = Database::fetchOne("SELECT name, phone, avatar FROM users WHERE id = ? LIMIT 1", [$activeOrder['customer_id']]);
                if ($customer) {
                    $activeOrder['customer_name'] = $customer['name'];
                    $activeOrder['customer_phone'] = $customer['phone'];
                }
            }
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

        // Calculate REAL delivered orders count accurately from orders table, wallet transactions, and driver profile
        $realDeliveredCount = (int)Database::fetchColumn(
            "SELECT COUNT(DISTINCT id) FROM `orders` WHERE (`delivery_man_id` = ? OR `delivery_man_id` = ?) AND `order_status` = 'delivered'",
            [(int)$dm['id'], (int)$dm['user_id']]
        );
        if ($realDeliveredCount === 0) {
            $walletOrdersCount = (int)Database::fetchColumn(
                "SELECT COUNT(DISTINCT reference_id) FROM `wallet_transactions` WHERE `wallet_id` = ? AND `category` = 'order_earning'",
                [(int)$wallet['id']]
            );
            $reviewsCount = (int)Database::fetchColumn(
                "SELECT COUNT(*) FROM `reviews` WHERE `delivery_man_id` = ?",
                [(int)$dm['id']]
            );
            $realDeliveredCount = max($walletOrdersCount, $reviewsCount, (int)($dm['total_orders'] ?? 0));
        }
        Database::update('delivery_men', ['total_orders' => $realDeliveredCount], 'id = ?', [$dm['id']]);
        $dm['total_orders'] = $realDeliveredCount;

        // Recalculate driver rating & fetch driver reviews
        $reviewModel = new \App\Models\Review();
        $reviewModel->recalculateDmRating((int)$dm['id']);
        $dm = $this->dmModel->find($dm['id']);
        $dm['total_orders'] = $realDeliveredCount;
        $reviews = $reviewModel->getDmReviews((int)$dm['id'], 20);

        if ($this->isJsonRequest()) {
            $this->successResponse('Dashboard driver berhasil diambil', [
                'driver'            => $dm,
                'is_online'         => (int)($dm['is_online'] ?? 1),
                'active_order'      => $activeOrder,
                'active_trip'       => $activeOrder,
                'active_batch'      => $activeBatch,
                'available_orders'  => $availableOrders,
                'wallet'            => $wallet,
                'wallet_balance'    => (float)($wallet['balance'] ?? 0),
                'total_orders'      => $realDeliveredCount,
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

        // Recalculate REAL delivered count accurately
        $realDeliveredCount = (int)Database::fetchColumn(
            "SELECT COUNT(DISTINCT id) FROM `orders` WHERE (`delivery_man_id` = ? OR `delivery_man_id` = ?) AND `order_status` = 'delivered'",
            [(int)$dm['id'], (int)$dm['user_id']]
        );
        if ($realDeliveredCount === 0) {
            $walletOrdersCount = (int)Database::fetchColumn(
                "SELECT COUNT(DISTINCT reference_id) FROM `wallet_transactions` WHERE `wallet_id` = ? AND `category` = 'order_earning'",
                [(int)$wallet['id']]
            );
            $reviewsCount = (int)Database::fetchColumn(
                "SELECT COUNT(*) FROM `reviews` WHERE `delivery_man_id` = ?",
                [(int)$dm['id']]
            );
            $realDeliveredCount = max($walletOrdersCount, $reviewsCount, (int)($dm['total_orders'] ?? 0));
        }
        Database::update('delivery_men', ['total_orders' => $realDeliveredCount], 'id = ?', [$dm['id']]);
        $dm['total_orders'] = $realDeliveredCount;

        // Unread chats
        $unreadChats = 0;
        if ($activeOrder) {
            $chatCount = Database::query(
                "SELECT COUNT(*) as unread FROM order_chats WHERE order_id = ? AND sender_id != ? AND is_read = 0",
                [$activeOrder['id'], $userId]
            );
            $unreadChats = (int)($chatCount[0]['unread'] ?? 0);
        }

        $user = auth_user();
        if ($user) {
            $dm['name'] = $user['name'] ?? ($dm['name'] ?? '');
            $dm['email'] = $user['email'] ?? '';
            $dm['phone'] = $user['phone'] ?? ($dm['phone'] ?? '');
            $dm['avatar'] = $user['avatar'] ?? '';
            $dm['image'] = !empty($user['avatar']) ? $user['avatar'] : ($dm['image'] ?? '');
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
            'user'             => $user,
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

            // Fetch full order data for instant mobile transition
            $order = $this->orderModel->findByCode($result['order_code'] ?? '') ?: $this->orderModel->find($orderId);
            if ($order) {
                if (empty($order['customer_name']) && !empty($order['customer_id'])) {
                    $customer = Database::fetchOne("SELECT name, phone, avatar FROM users WHERE id = ? LIMIT 1", [$order['customer_id']]);
                    if ($customer) {
                        $order['customer_name'] = $customer['name'];
                        $order['customer_phone'] = $customer['phone'];
                    }
                }
                if (!empty($order['delivery_address_json']) && empty($order['delivery_address'])) {
                    $order['delivery_address'] = json_decode($order['delivery_address_json'], true);
                }
                if (!empty($order['store_id'])) {
                    $store = (new \App\Models\Store())->find($order['store_id']);
                    if ($store) {
                        $order['store_name'] = $store['name'] ?? 'Toko Mitra';
                        $order['store_address'] = $store['address'] ?? 'Cicalengka';
                        $order['store_lat'] = $store['latitude'] ?? -6.9835;
                        $order['store_lng'] = $store['longitude'] ?? 107.8345;
                        $order['store_phone'] = $store['phone'] ?? '';
                    }
                }
                $this->orderModel->attachMultiStoreDetails($order);
                $order['items'] = $this->orderModel->getItems((int)$order['id']);
            }

            $this->successResponse(
                $orderCount > 1
                    ? "Pesanan ke-{$result['sequence']} berhasil ditambahkan ke trip Anda!"
                    : 'Pesanan berhasil diterima! Segera menuju ke lokasi penjemputan.',
                [
                    'order_code'  => $result['order_code'],
                    'batch_id'    => $result['batch_id'],
                    'order_count' => $orderCount,
                    'sequence'    => $result['sequence'],
                    'active_trip' => $order,
                    'active_order'=> $order,
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
            $dm = $this->dmModel->findByUserId($userId);
            $wallet = null;
            if ($dm) {
                $wallet = $this->ensureDriverDeliveredOrdersCredited($dm);
            }
            $this->successResponse('Status pengantaran berhasil diperbarui.', [
                'wallet'         => $wallet,
                'wallet_balance' => (float)($wallet['balance'] ?? 0),
            ]);
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
            $this->errorResponse('Silakan login terlebih dahulu', null, 401);
            return;
        }

        $dm = $this->dmModel->findByUserId($userId);
        if (!$dm) {
            $this->errorResponse('Driver tidak ditemukan');
            return;
        }

        // Auto-credit any pending delivered orders
        $wallet = $this->ensureDriverDeliveredOrdersCredited($dm);

        // Recalculate REAL delivered count accurately
        $realDeliveredCount = (int)Database::fetchColumn(
            "SELECT COUNT(DISTINCT id) FROM `orders` WHERE (`delivery_man_id` = ? OR `delivery_man_id` = ?) AND `order_status` = 'delivered'",
            [(int)$dm['id'], (int)$dm['user_id']]
        );
        if ($realDeliveredCount === 0) {
            $walletOrdersCount = (int)Database::fetchColumn(
                "SELECT COUNT(DISTINCT reference_id) FROM `wallet_transactions` WHERE `wallet_id` = ? AND `category` = 'order_earning'",
                [(int)$wallet['id']]
            );
            $reviewsCount = (int)Database::fetchColumn(
                "SELECT COUNT(*) FROM `reviews` WHERE `delivery_man_id` = ?",
                [(int)$dm['id']]
            );
            $realDeliveredCount = max($walletOrdersCount, $reviewsCount, (int)($dm['total_orders'] ?? 0));
        }

        Database::update('delivery_men', ['total_orders' => $realDeliveredCount], 'id = ?', [$dm['id']]);
        $dm['total_orders'] = $realDeliveredCount;
        $wallet['total_orders'] = $realDeliveredCount;

        $reviewModel = new \App\Models\Review();
        $reviews = $reviewModel->getDmReviews((int)$dm['id'], 20);

        // Fetch detailed delivered orders history for driver
        $deliveredOrders = Database::query(
            "SELECT o.id, o.order_code, o.order_status, o.delivery_charge, o.delivered_at, o.created_at,
                    s.name as store_name, u.name as customer_name
             FROM `orders` o
             LEFT JOIN `stores` s ON o.store_id = s.id
             LEFT JOIN `users` u ON o.customer_id = u.id
             WHERE (o.delivery_man_id = ? OR o.delivery_man_id = ?) AND o.order_status = 'delivered'
             ORDER BY o.delivered_at DESC, o.id DESC LIMIT 50",
            [(int)$dm['id'], (int)$dm['user_id']]
        );

        // Fetch recent transactions for delivery_man
        $transactions = $this->walletModel->getTransactions($userId, 50, 'delivery_man');

        // Safe in-memory enrichment without collation conflicts
        if (!empty($transactions)) {
            $refIds = array_filter(array_column($transactions, 'reference_id'));
            $numericIds = array_values(array_filter($refIds, fn($val) => is_numeric($val) && (int)$val > 0));
            if (!empty($numericIds)) {
                $inPlaceholders = implode(',', array_fill(0, count($numericIds), '?'));
                $orderRows = Database::query(
                    "SELECT o.id, o.order_code, s.name as store_name, u.name as customer_name
                     FROM `orders` o
                     LEFT JOIN `stores` s ON o.store_id = s.id
                     LEFT JOIN `users` u ON o.customer_id = u.id
                     WHERE o.id IN ({$inPlaceholders})",
                    $numericIds
                );
                $ordersMap = [];
                foreach ($orderRows as $or) {
                    $ordersMap[(string)$or['id']] = $or;
                }
                foreach ($transactions as &$tx) {
                    $ref = (string)($tx['reference_id'] ?? '');
                    if (isset($ordersMap[$ref])) {
                        $tx['order_code'] = $ordersMap[$ref]['order_code'];
                        $tx['store_name'] = $ordersMap[$ref]['store_name'];
                        $tx['customer_name'] = $ordersMap[$ref]['customer_name'];
                    }
                }
            }
        }

        $withdrawRequests = $this->withdrawModel->getByUser($userId, 'delivery_man', 50);
        $totalWithdrawn = $this->withdrawModel->getTotalWithdrawn($userId, 'delivery_man');
        $pendingWithdrawn = $this->withdrawModel->getPendingWithdrawn($userId, 'delivery_man');

        if ($this->isJsonRequest()) {
            $this->successResponse('Data pendapatan driver berhasil diambil', [
                'wallet'            => $wallet,
                'wallet_balance'    => (float)($wallet['balance'] ?? 0),
                'total_orders'      => $realDeliveredCount,
                'driver'            => $dm,
                'delivered_orders'  => $deliveredOrders,
                'reviews'           => $reviews,
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
            'reviews'           => $reviews,
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
        } catch (\Throwable $e) {
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
            if ($this->isJsonRequest()) {
                $this->errorResponse('Unauthorized', null, 401);
                return;
            }
            $this->redirect('login');
            return;
        }

        $data = $this->getPost();
        $currentPassword = $data['current_password'] ?? '';
        $newPassword     = $data['new_password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        // If no password change is provided
        if (empty($newPassword) && empty($currentPassword)) {
            if ($this->isJsonRequest()) {
                $this->successResponse('Data identitas & kendaraan profil driver terkunci resmi oleh Admin. Hanya kata sandi yang dapat diubah.');
                return;
            }
            $_SESSION['info'] = 'Data profil driver diverifikasi resmi oleh Admin. Hanya kata sandi yang dapat diubah.';
            $this->redirect('delivery/profile');
            return;
        }

        // Validate password change
        $userModel = new \App\Models\User();
        $dbUser = $userModel->find($userId);

        if (empty($currentPassword)) {
            if ($this->isJsonRequest()) {
                $this->errorResponse('Harap masukkan Kata Sandi Saat Ini.');
                return;
            }
            $_SESSION['error'] = 'Harap masukkan Kata Sandi Saat Ini untuk memverifikasi perubahan kata sandi.';
            $this->redirect('delivery/profile');
            return;
        }

        if (!password_verify($currentPassword, $dbUser['password'] ?? '')) {
            if ($this->isJsonRequest()) {
                $this->errorResponse('Kata Sandi Saat Ini yang Anda masukkan salah.');
                return;
            }
            $_SESSION['error'] = 'Kata Sandi Saat Ini yang Anda masukkan salah.';
            $this->redirect('delivery/profile');
            return;
        }

        if (strlen($newPassword) < 6) {
            if ($this->isJsonRequest()) {
                $this->errorResponse('Kata Sandi Baru harus memiliki minimal 6 karakter.');
                return;
            }
            $_SESSION['error'] = 'Kata Sandi Baru harus memiliki minimal 6 karakter.';
            $this->redirect('delivery/profile');
            return;
        }

        if ($newPassword !== $confirmPassword) {
            if ($this->isJsonRequest()) {
                $this->errorResponse('Konfirmasi Kata Sandi Baru tidak cocok.');
                return;
            }
            $_SESSION['error'] = 'Konfirmasi Kata Sandi Baru tidak cocok.';
            $this->redirect('delivery/profile');
            return;
        }

        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $userModel->update($userId, ['password' => $passwordHash]);

        if ($this->isJsonRequest()) {
            $this->successResponse('Kata sandi driver berhasil diperbarui!');
            return;
        }

        $_SESSION['success'] = 'Kata sandi berhasil diperbarui!';
        $this->redirect('delivery/profile');
    }

    public function ensureDriverDeliveredOrdersCredited(array $dm): array
    {
        $userId = (int)$dm['user_id'];
        $driverWallet = $this->walletModel->getOrCreate($userId, 'delivery_man');
        $deliveredDriverOrders = Database::query(
            "SELECT id, order_code, delivery_charge, distance_km, delivery_batch_id, zone_id 
             FROM `orders` 
             WHERE (`delivery_man_id` = ? OR `delivery_man_id` = ?) AND `order_status` = 'delivered'",
            [(int)$dm['id'], (int)$dm['user_id']]
        );

        foreach ($deliveredDriverOrders as $dOrder) {
            $batchId = $dOrder['delivery_batch_id'] ?? '';
            $alreadyCredited = Database::fetchOne(
                "SELECT id FROM `wallet_transactions` 
                 WHERE `wallet_id` = ? AND `category` = 'order_earning' 
                   AND (`reference_id` = ? OR `reference_id` = ?" . (!empty($batchId) ? " OR `reference_id` = ?" : "") . ") LIMIT 1",
                !empty($batchId)
                    ? [$driverWallet['id'], (string)$dOrder['id'], (string)$dOrder['order_code'], (string)$batchId]
                    : [$driverWallet['id'], (string)$dOrder['id'], (string)$dOrder['order_code']]
            );

            if (!$alreadyCredited) {
                $charge = (float)($dOrder['delivery_charge'] ?? 0);
                if ($charge <= 0) {
                    $km = (float)($dOrder['distance_km'] ?? 0);
                    $tariff = \App\Models\Zone::getZoneTariff((int)($dOrder['zone_id'] ?? 1));
                    $charge = calculate_delivery_fee($km, $tariff['min_delivery_charge'], $tariff['per_km_delivery_charge']);
                }
                $driverEarning = max(5000.0, round($charge, 0));
                $this->walletModel->credit(
                    $userId,
                    $driverEarning,
                    'order_earning',
                    "Komisi pengantaran pesanan #{$dOrder['order_code']}",
                    (string)$dOrder['id'],
                    'delivery_man'
                );
            }
        }

        return Database::fetchOne("SELECT * FROM `wallets` WHERE `id` = ?", [$driverWallet['id']]) ?: $driverWallet;
    }

    public function ordersHistory(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Silakan login terlebih dahulu.', null, 401);
            return;
        }
        $dm = $this->dmModel->findByUserId($userId);
        if (!$dm) {
            $this->errorResponse('Driver profile tidak ditemukan.', null, 404);
            return;
        }

        // Auto-credit any pending delivered orders immediately
        $this->ensureDriverDeliveredOrdersCredited($dm);

        $status = $_GET['status'] ?? 'all';
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = max(10, min(100, (int)($_GET['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        $dmId   = (int)$dm['id'];
        $userId = (int)$dm['user_id'];

        $whereClause = "(`o`.`delivery_man_id` = {$dmId} OR `o`.`delivery_man_id` = {$userId})";
        if ($status === 'completed' || $status === 'delivered') {
            $whereClause .= " AND `o`.`order_status` = 'delivered'";
        } elseif ($status === 'canceled') {
            $whereClause .= " AND `o`.`order_status` = 'canceled'";
        } elseif ($status === 'active') {
            $whereClause .= " AND `o`.`order_status` IN ('accepted', 'confirmed', 'processing', 'handover', 'picked_up', 'on_the_way')";
        }

        $sql = "SELECT `o`.*, 
                       `u`.`name` as `customer_name`, 
                       `u`.`phone` as `customer_phone`, 
                       `s`.`name` as `store_name`, 
                       `s`.`address` as `store_address`,
                       `s`.`phone` as `store_phone`,
                       `s`.`latitude` as `store_lat`,
                       `s`.`longitude` as `store_lng`
                FROM `orders` `o`
                LEFT JOIN `users` `u` ON `o`.`customer_id` = `u`.`id`
                LEFT JOIN `stores` `s` ON `o`.`store_id` = `s`.`id`
                WHERE {$whereClause}
                ORDER BY `o`.`id` DESC
                LIMIT {$limit} OFFSET {$offset}";

        $orders = Database::query($sql) ?: [];

        foreach ($orders as &$order) {
            if (is_string($order['delivery_address_json'] ?? null)) {
                $order['delivery_address'] = json_decode($order['delivery_address_json'], true);
            } elseif (is_string($order['delivery_address'] ?? null)) {
                $decoded = json_decode($order['delivery_address'], true);
                if (is_array($decoded)) {
                    $order['delivery_address'] = $decoded;
                }
            }

            $order['items'] = $this->orderModel->getItems((int)$order['id']);
            $order['items_count'] = count($order['items']);

            if (!empty($order['delivery_batch_id'])) {
                $this->orderModel->attachMultiStoreDetails($order);
            }

            // Komisi driver per order (selaras 100% dengan ongkir yang dibayar pelanggan)
            $orderDeliveryCharge = (float)($order['delivery_charge'] ?? 0);
            if ($orderDeliveryCharge <= 0) {
                $tariff = \App\Models\Zone::getZoneTariff((int)($order['zone_id'] ?? 1));
                $orderDeliveryCharge = calculate_delivery_fee(
                    (float)($order['distance_km'] ?? 0),
                    $tariff['min_delivery_charge'],
                    $tariff['per_km_delivery_charge']
                );
            }
            $order['driver_earning'] = $orderDeliveryCharge;
            $order['delivery_charge'] = $orderDeliveryCharge;

            // Pastikan distance_km float
            $order['distance_km'] = round((float)($order['distance_km'] ?? 0), 2);
        }
        unset($order);

        $totalDelivered = (int)Database::fetchColumn(
            "SELECT COUNT(DISTINCT id) FROM `orders` WHERE (`delivery_man_id` = ? OR `delivery_man_id` = ?) AND `order_status` = 'delivered'",
            [$dmId, $userId]
        );
        $totalCanceled = (int)Database::fetchColumn(
            "SELECT COUNT(DISTINCT id) FROM `orders` WHERE (`delivery_man_id` = ? OR `delivery_man_id` = ?) AND `order_status` = 'canceled'",
            [$dmId, $userId]
        );
        $totalEarnings = (float)Database::fetchColumn(
            "SELECT COALESCE(SUM(amount), 0) FROM `wallet_transactions` wt 
             JOIN `wallets` w ON wt.wallet_id = w.id 
             WHERE w.user_id = ? AND wt.type = 'credit' AND wt.category = 'order_earning'",
            [$userId]
        );
        $sumDeliveredCharges = (float)Database::fetchColumn(
            "SELECT COALESCE(SUM(delivery_charge), 0) FROM `orders`
             WHERE (`delivery_man_id` = ? OR `delivery_man_id` = ?) AND `order_status` = 'delivered'",
            [$dmId, $userId]
        );
        $totalEarnings = max($totalEarnings, $sumDeliveredCharges);

        $totalKm = (float)Database::fetchColumn(
            "SELECT COALESCE(SUM(distance_km), 0) FROM `orders`
             WHERE (`delivery_man_id` = ? OR `delivery_man_id` = ?) AND `order_status` = 'delivered'",
            [$dmId, $userId]
        );

        $this->successResponse('Riwayat pesanan driver', [
            'orders'          => $orders,
            'total_delivered' => $totalDelivered,
            'total_canceled'  => $totalCanceled,
            'total_earnings'  => $totalEarnings,
            'total_km'        => round($totalKm, 2),
            'page'            => $page,
            'limit'           => $limit,
        ]);
    }

    public function orderDetail(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Silakan login terlebih dahulu.', null, 401);
            return;
        }
        $dm = $this->dmModel->findByUserId($userId);
        if (!$dm) {
            $this->errorResponse('Driver profile tidak ditemukan.', null, 404);
            return;
        }

        $orderId = (int)($_GET['id'] ?? 0);
        $orderCode = $_GET['order_code'] ?? '';

        if ($orderId <= 0 && empty($orderCode)) {
            $this->errorResponse('Order ID atau Order Code dibutuhkan.', 400);
            return;
        }

        $condition = $orderId > 0 ? "`o`.`id` = {$orderId}" : "`o`.`order_code` = " . Database::quote($orderCode);
        $sql = "SELECT `o`.*, 
                       `u`.`name` as `customer_name`, 
                       `u`.`phone` as `customer_phone`, 
                       `s`.`name` as `store_name`, 
                       `s`.`address` as `store_address`,
                       `s`.`phone` as `store_phone`,
                       `s`.`latitude` as `store_lat`,
                       `s`.`longitude` as `store_lng`
                FROM `orders` `o`
                LEFT JOIN `users` `u` ON `o`.`customer_id` = `u`.`id`
                LEFT JOIN `stores` `s` ON `o`.`store_id` = `s`.`id`
                WHERE {$condition} LIMIT 1";

        $order = Database::fetchOne($sql);
        if (!$order) {
            $this->errorResponse('Pesanan tidak ditemukan.', 404);
            return;
        }

        if (is_string($order['delivery_address_json'] ?? null)) {
            $order['delivery_address'] = json_decode($order['delivery_address_json'], true);
        } elseif (is_string($order['delivery_address'] ?? null)) {
            $decoded = json_decode($order['delivery_address'], true);
            if (is_array($decoded)) {
                $order['delivery_address'] = $decoded;
            }
        }

        $order['items'] = $this->orderModel->getItems((int)$order['id']);
        if (!empty($order['delivery_batch_id'])) {
            $this->orderModel->attachMultiStoreDetails($order);
        }

        $orderDeliveryCharge = (float)($order['delivery_charge'] ?? 0);
        if ($orderDeliveryCharge <= 0) {
            $tariff = \App\Models\Zone::getZoneTariff((int)($order['zone_id'] ?? 1));
            $orderDeliveryCharge = calculate_delivery_fee(
                (float)($order['distance_km'] ?? 0),
                $tariff['min_delivery_charge'],
                $tariff['per_km_delivery_charge']
            );
        }
        $order['driver_earning'] = $orderDeliveryCharge;
        $order['delivery_charge'] = $orderDeliveryCharge;

        $review = Database::fetchOne(
            "SELECT * FROM `reviews` WHERE (`order_id` = ? OR (`delivery_man_id` = ? AND `order_id` = ?)) LIMIT 1",
            [$order['id'], $dm['id'], $order['id']]
        );
        $order['review'] = $review ?: null;

        $this->successResponse('Detail pesanan driver', [
            'order' => $order,
        ]);
    }
}
