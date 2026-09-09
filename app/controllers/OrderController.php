<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Wallet;
use App\Models\Coupon;
use App\Models\CustomerAddress;
use App\Models\Zone;
use App\Services\OrderService;
use App\Services\DokuService;
use App\Core\Database;
use Exception;

class OrderController extends Controller
{
    private Order $orderModel;
    private Cart $cartModel;
    private Wallet $walletModel;
    private OrderService $orderService;
    private DokuService $dokuService;

    public function __construct()
    {
        $this->orderModel = new Order();
        $this->cartModel = new Cart();
        $this->walletModel = new Wallet();
        $this->orderService = new OrderService();
        $this->dokuService = new DokuService();
    }

    public function checkout(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->redirect('login?redirect=checkout');
            return;
        }

        $cartData = $this->cartModel->getUserCart($userId);
        if (empty($cartData['items'])) {
            $this->redirect('cart');
            return;
        }

        $wallet = $this->walletModel->getOrCreate($userId, 'customer');
        $addresses = Database::query("SELECT * FROM `customer_addresses` WHERE `user_id` = ? ORDER BY `is_default` DESC", [$userId]);
        $coupons = (new Coupon())->where('status', 1);

        // Ambil detail zona (nama, tarif, polygon koordinat) dari store pertama di cart
        $primaryZoneId = 1;
        if (!empty($cartData['stores'])) {
            $firstStore = reset($cartData['stores']);
            $primaryZoneId = (int)($firstStore['zone_id'] ?? 1);
        }
        $zoneDetail = Zone::getZoneDetail($primaryZoneId);

        $this->view('customer.checkout', [
            'title'        => 'Checkout Pesanan - CicalengkaGO',
            'cart_data'    => $cartData,
            'wallet'       => $wallet,
            'addresses'    => $addresses,
            'coupons'      => $coupons,
            'zone_tariff'  => $zoneDetail,
            'zone_detail'  => $zoneDetail,
            'active_tab'   => 'cart'
        ], 'customer_layout');
    }

    public function placeOrder(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Silakan login terlebih dahulu.', null, 401);
            return;
        }

        $data = $this->getPost();
        $paymentMethod = $data['payment_method'] ?? 'cod';

        $rawLat  = $data['latitude'] ?? $data['lat'] ?? null;
        $rawLng  = $data['longitude'] ?? $data['lng'] ?? null;
        $rawAddr = $data['address'] ?? $data['delivery_address'] ?? 'Cicalengka, Bandung';

        $deliveryAddress = [
            'contact_name'  => sanitize($data['contact_name'] ?? $_SESSION['user']['name'] ?? 'Pelanggan'),
            'contact_phone' => sanitize($data['contact_phone'] ?? $_SESSION['user']['phone'] ?? ''),
            'address'       => sanitize($rawAddr),
            'lat'           => ($rawLat !== null && (float)$rawLat != 0) ? (float)$rawLat : -6.9840,
            'lng'           => ($rawLng !== null && (float)$rawLng != 0) ? (float)$rawLng : 107.8340,
            'road'          => sanitize($data['road'] ?? ''),
            'house'         => sanitize($data['house'] ?? ''),
        ];

        try {
            $cartData   = $this->cartModel->getUserCart($userId);
            $stores     = $cartData['stores'] ?? [];

            if (empty($stores)) {
                $this->errorResponse('Keranjang belanja Anda kosong.');
                return;
            }

            // Validasi apakah seluruh toko dalam keranjang masih BUKA
            $storeModel = new \App\Models\Store();
            foreach ($stores as $sg) {
                $stId = (int)($sg['store_id'] ?? 0);
                $stObj = $storeModel->findWithDetails($stId);
                if ($stObj) {
                    attach_store_schedule_data($stObj, true);
                    if (empty($stObj['is_open'])) {
                        $stName = $stObj['name'] ?? 'Toko Mitra';
                        $this->errorResponse("Maaf, {$stName} sedang TUTUP dan tidak dapat menerima pesanan saat ini. Silakan hapus produk dari toko tersebut atau pesan saat toko buka.");
                        return;
                    }
                }
            }

            if ($paymentMethod === 'wallet') {
                $walletModel = new \App\Models\Wallet();
                $wallet = $walletModel->getOrCreate($userId, 'customer');
                $currentBalance = (float)($wallet['balance'] ?? 0);

                $totalRequired = 0.0;
                foreach ($stores as $sg) {
                    $sgSubtotal = (float)($sg['subtotal'] ?? 0);
                    $distKm = (float)($data['distance_km'] ?? 1.5);
                    $sgDelivery = calculate_delivery_fee($distKm, (float)($sg['delivery_fee'] ?? 5000));
                    $totalRequired += ($sgSubtotal + $sgDelivery);
                }

                if ($currentBalance < $totalRequired) {
                    $formattedBalance = 'Rp ' . number_format($currentBalance, 0, ',', '.');
                    $formattedRequired = 'Rp ' . number_format($totalRequired, 0, ',', '.');
                    $this->errorResponse("Saldo CicalengkaPay Anda tidak mencukupi ({$formattedBalance}). Total tagihan: {$formattedRequired}. Silakan isi ulang saldo atau gunakan metode pembayaran lain.");
                    return;
                }
            }

            $allOrderCodes = [];
            $grandTotal    = 0.0;
            $batchId       = (count($stores) > 1) ? ('BATCH-' . strtoupper(substr(uniqid(), -6)) . rand(10, 99)) : null;
            $sharedOtp     = str_pad((string)rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            $seq           = 1;

            // Create one order per store atomically inside a single batch transaction
            \App\Core\Database::transaction(function () use ($stores, $userId, $deliveryAddress, $paymentMethod, $data, $batchId, $sharedOtp, &$seq, &$allOrderCodes, &$grandTotal) {
                foreach ($stores as $storeGroup) {
                    $result = $this->orderService->createOrderFromCart($userId, [
                        'delivery_address'  => $deliveryAddress,
                        'payment_method'    => $paymentMethod,
                        'coupon_code'       => sanitize($data['coupon_code'] ?? ''),
                        'order_notes'       => sanitize($data['order_notes'] ?? ''),
                        'distance_km'       => (float)($data['distance_km'] ?? 1.5),
                        'order_type'        => $data['order_type'] ?? 'delivery',
                        'delivery_type'     => sanitize($data['delivery_type'] ?? 'driver'),
                        'store_id'          => $storeGroup['store_id'],   // scoped to this store
                        'delivery_batch_id' => $batchId,
                        'shared_otp'        => $sharedOtp,
                        'pickup_sequence'   => $seq++,
                    ]);

                    $allOrderCodes[] = $result['order_code'];
                    $grandTotal     += (float)($result['total'] ?? 0);
                }
            });

            $firstCode   = $allOrderCodes[0];
            $multiOrder  = count($allOrderCodes) > 1;

            $responseData = [
                'order_code'     => $firstCode,
                'order_id'       => null,
                'order_codes'    => $allOrderCodes,
                'store_count'    => count($allOrderCodes),
                'payment_method' => $paymentMethod,
                'redirect'       => 'orders/' . $firstCode . '/tracking',
            ];

            // Online payment: DOKU Checkout URL covering grand total of all stores
            if (in_array($paymentMethod, ['doku', 'online'])) {
                $user        = auth_user();
                $appConfig   = require APP_PATH . '/config/app.php';
                $publicUrl   = rtrim($appConfig['public_url'] ?? '', '/');
                $dokuInvoice = 'ORD-' . $firstCode . '-' . rand(100, 999);

                $custName = trim($deliveryAddress['contact_name'] ?: ($user['name'] ?? 'Pelanggan'));
                if (empty($custName)) $custName = 'Pelanggan CicalengkaGO';

                $custEmail = trim($user['email'] ?? '');
                if (empty($custEmail) || !filter_var($custEmail, FILTER_VALIDATE_EMAIL)) {
                    $custEmail = 'customer@cicalengkago.id';
                }

                $rawPhone = $deliveryAddress['contact_phone'] ?: ($user['phone'] ?? '');
                $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
                if (strlen($cleanPhone) < 9 || strlen($cleanPhone) > 15) {
                    $cleanPhone = '081234567890';
                }

                $dokuParams  = [
                    'invoice_number' => $dokuInvoice,
                    'amount'         => (int)round($grandTotal),
                    'callback_url'   => $publicUrl . '/payment/doku/callback?order=' . $firstCode,
                    'customer'       => [
                        'id'    => (string)($user['id'] ?? $userId),
                        'name'  => $custName,
                        'email' => $custEmail,
                        'phone' => $cleanPhone,
                    ],
                    'line_items'     => [
                        [
                            'name'     => 'Pesanan CicalengkaGO ' . $firstCode,
                            'price'    => (int)round($grandTotal),
                            'quantity' => 1,
                        ]
                    ],
                ];

                $dokuResult = $this->dokuService->createPaymentUrl($dokuParams);
                $responseData['payment_url']  = $dokuResult['payment_url'];
                $responseData['redirect_url'] = $dokuResult['redirect_url'];
            }

            $this->successResponse(
                count($allOrderCodes) > 1
                    ? count($allOrderCodes) . ' pesanan dari toko berbeda berhasil dibuat!'
                    : 'Pesanan berhasil dibuat!',
                $responseData
            );
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    public function placeParcel(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Silakan login terlebih dahulu.', null, 401);
            return;
        }

        $data = $this->getPost();
        $paymentMethod = $data['payment_method'] ?? 'cod';

        try {
            $result = $this->orderService->createParcelOrder($userId, $data);

            $responseData = [
                'order_code'     => $result['order_code'],
                'order_id'       => $result['order_id'],
                'payment_method' => $paymentMethod,
                'redirect'       => 'orders/' . $result['order_code'] . '/tracking'
            ];

            // If online payment via DOKU
            if (in_array($paymentMethod, ['doku', 'online'])) {
                $user        = auth_user();
                $appConfig   = require APP_PATH . '/config/app.php';
                $publicUrl   = rtrim($appConfig['public_url'] ?? '', '/');
                $dokuInvoice = 'PCL-' . $result['order_code'] . '-' . rand(100, 999);

                $custName = trim(sanitize($data['sender_name'] ?? ($user['name'] ?? 'Pengirim')));
                if (empty($custName)) $custName = 'Pengirim CicalengkaGO';

                $custEmail = trim($user['email'] ?? '');
                if (empty($custEmail) || !filter_var($custEmail, FILTER_VALIDATE_EMAIL)) {
                    $custEmail = 'customer@cicalengkago.id';
                }

                $rawPhone = sanitize($data['sender_phone'] ?? ($user['phone'] ?? ''));
                $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
                if (strlen($cleanPhone) < 9 || strlen($cleanPhone) > 15) {
                    $cleanPhone = '081234567890';
                }

                $dokuParams = [
                    'invoice_number' => $dokuInvoice,
                    'amount'         => (int)round($result['total']),
                    'callback_url'   => $publicUrl . '/payment/doku/callback?order=' . $result['order_code'],
                    'customer'       => [
                        'id'    => (string)($user['id'] ?? $userId),
                        'name'  => $custName,
                        'email' => $custEmail,
                        'phone' => $cleanPhone,
                    ],
                    'line_items'     => [
                        [
                            'name'     => 'Ongkir CicalengkaSend ' . $result['order_code'],
                            'price'    => (int)round($result['total']),
                            'quantity' => 1,
                        ]
                    ],
                ];

                $dokuResult = $this->dokuService->createPaymentUrl($dokuParams);
                $responseData['payment_url']  = $dokuResult['payment_url'];
                $responseData['redirect_url'] = $dokuResult['redirect_url'];
            }

            $this->successResponse('Pengiriman Parcel berhasil dipesan!', $responseData);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    public function ordersList(): void
    {
        $userId = auth_id();
        if (!$userId) {
            if ($this->isJsonRequest()) {
                $this->errorResponse('Silakan login terlebih dahulu.', null, 401);
                return;
            }
            $this->redirect('login');
            return;
        }

        $orders = $this->orderModel->getCustomerOrders($userId);

        if ($this->isJsonRequest()) {
            $this->successResponse('Daftar pesanan berhasil diambil', $orders);
            return;
        }

        $this->view('customer.orders', [
            'title'      => 'Pesanan Saya - CicalengkaGO',
            'orders'     => $orders,
            'active_tab' => 'orders'
        ], 'customer_layout');
    }

    public function getLiveOrdersList(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Unauthorized', null, 401);
            return;
        }

        $orders = $this->orderModel->getCustomerOrders($userId);
        $this->successResponse('Live customer orders sync', [
            'count'  => count($orders),
            'orders' => $orders
        ]);
    }

    public function tracking(string $code): void
    {
        // Auto cancel any unclaimed orders older than 60 seconds
        Order::autoCancelUnclaimedOrders();

        $order = $this->orderModel->findByIdOrCode($code);
        if (!$order) {
            if ($this->isJsonRequest()) {
                $this->errorResponse("Pesanan #{$code} tidak ditemukan.", null, 404);
                return;
            }
            $this->notFound("Pesanan #{$code} tidak ditemukan.");
            return;
        }



        $dokuUrl = null;
        if (in_array($order['payment_method'], ['doku', 'midtrans', 'online']) && $order['payment_status'] !== 'paid' && $order['order_status'] !== 'canceled') {
            try {
                $user = auth_user() ?: ['name' => 'Pelanggan', 'email' => 'customer@cicalengkago.id', 'phone' => '081234567890'];
                $appConfig = require APP_PATH . '/config/app.php';
                $publicUrl = rtrim($appConfig['public_url'] ?? '', '/');
                $dokuRes = $this->dokuService->createPaymentUrl([
                    'invoice_number' => 'REPAY-' . $order['order_code'] . '-' . time(),
                    'amount'         => (int)round((float)$order['total_amount']),
                    'callback_url'   => $publicUrl . '/orders/' . $order['order_code'] . '/tracking',
                    'customer'       => [
                        'id'    => (string)($order['user_id'] ?? $order['customer_id'] ?? 'GUEST'),
                        'name'  => $order['delivery_address']['contact_name'] ?? ($user['name'] ?? 'Pelanggan'),
                        'email' => $user['email'] ?? 'customer@cicalengkago.id',
                        'phone' => $order['delivery_address']['contact_phone'] ?? ($user['phone'] ?? '081234567890'),
                    ],
                    'line_items'     => [
                        [
                            'name'     => 'Pesanan CicalengkaGO #' . $order['order_code'],
                            'price'    => (int)round((float)$order['total_amount']),
                            'quantity' => 1,
                        ]
                    ]
                ]);
                $dokuUrl = $dokuRes['payment_url'] ?? null;
            } catch (\Throwable $e) {}
        }

        if ($this->isJsonRequest()) {
            $this->successResponse('Tracking pesanan berhasil diambil', [
                'order'      => $order,
                'doku_url'   => $dokuUrl,
                'payment_url'=> $dokuUrl,
            ]);
            return;
        }

        $this->view('customer.order_tracking', [
            'title'      => "Lacak Pesanan #{$order['order_code']}",
            'order'      => $order,
            'doku_url'   => $dokuUrl,
            'active_tab' => 'orders'
        ], 'customer_layout');
    }

    public function getSnapToken(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Silakan login terlebih dahulu.', null, 401);
            return;
        }

        $data = $this->getPost();
        $orderCode = $data['order_code'] ?? '';

        $order = $this->orderModel->findByIdOrCode($orderCode);
        if (!$order) {
            $this->errorResponse('Pesanan tidak ditemukan.');
            return;
        }

        if ((int)$order['customer_id'] !== $userId) {
            $this->errorResponse('Akses ditolak.');
            return;
        }

        if ($order['payment_status'] === 'paid') {
            $this->errorResponse('Pesanan ini sudah lunas.');
            return;
        }

        try {
            $user = auth_user();
            $appConfig = require APP_PATH . '/config/app.php';
            $publicUrl = rtrim($appConfig['public_url'] ?? '', '/');

            $dokuRes = $this->dokuService->createPaymentUrl([
                'invoice_number' => 'REPAY-' . $order['order_code'] . '-' . time(),
                'amount'         => (int)round((float)$order['total_amount']),
                'callback_url'   => $publicUrl . '/orders/' . $order['order_code'] . '/tracking',
                'customer'       => [
                    'id'    => (string)$userId,
                    'name'  => $order['delivery_address']['contact_name'] ?? ($user['name'] ?? 'Pelanggan'),
                    'email' => $user['email'] ?? 'customer@cicalengkago.id',
                    'phone' => $order['delivery_address']['contact_phone'] ?? ($user['phone'] ?? '081234567890'),
                ],
                'line_items'     => [
                    [
                        'name'     => 'Pesanan CicalengkaGO #' . $order['order_code'],
                        'price'    => (int)round((float)$order['total_amount']),
                        'quantity' => 1,
                    ]
                ]
            ]);

            $this->successResponse('URL pembayaran DOKU siap', [
                'payment_url'  => $dokuRes['payment_url'] ?? '',
                'redirect_url' => $dokuRes['redirect_url'] ?? '',
                'order_id'     => $order['order_code'],
            ]);
        } catch (\Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    public function cancelUnpaid(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Silakan login terlebih dahulu.', null, 401);
            return;
        }

        $data = $this->getPost();
        $orderCode = $data['order_code'] ?? '';

        $order = $this->orderModel->findByIdOrCode($orderCode);
        if (!$order) {
            $this->errorResponse('Pesanan tidak ditemukan.');
            return;
        }

        if ((int)$order['customer_id'] !== $userId) {
            $this->errorResponse('Akses ditolak.');
            return;
        }

        if (in_array($order['order_status'], ['handover', 'on_the_way', 'delivered'])) {
            $this->errorResponse('Pesanan yang sedang diantar kurir tidak dapat dibatalkan.');
            return;
        }

        $batchId = $order['delivery_batch_id'] ?? null;
        if (!empty($batchId)) {
            \App\Core\Database::execute(
                "UPDATE `orders` SET `order_status` = 'canceled', `cancellation_reason` = 'Dibatalkan oleh Pelanggan', `canceled_at` = NOW() WHERE `delivery_batch_id` = ? AND `order_status` NOT IN ('delivered')",
                [$batchId]
            );
        } else {
            \App\Core\Database::update('orders', [
                'order_status'        => 'canceled',
                'cancellation_reason' => 'Dibatalkan oleh Pelanggan',
                'canceled_at'          => date('Y-m-d H:i:s')
            ], 'id = ?', [$order['id']]);
        }

        // Hanya proses refund jika pesanan memang sudah dibayar sebelumnya
        $refunded = Order::refundOrderIfPaid($order, 'Dibatalkan oleh pelanggan');

        if ($refunded) {
            $this->successResponse('Pesanan berhasil dibatalkan dan pengembalian dana telah dikreditkan ke CicalengkaPay.');
        } else {
            $this->successResponse('Pesanan berhasil dibatalkan.');
        }
    }

    public function showOrder(string $code): void
    {
        $order = $this->orderModel->findByIdOrCode($code);
        if (!$order) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))) {
                $this->errorResponse('Pesanan tidak ditemukan.', null, 404);
                return;
            }
            $this->redirect('orders');
            return;
        }

        // If AJAX request, return JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))) {
            $this->json(['success' => true, 'data' => $order]);
            return;
        }

        // Otherwise redirect to tracking page
        $this->redirect('orders/' . $order['order_code'] . '/tracking');
    }

    public function getLiveTracking(string $code): void
    {
        // Fetch order first, THEN run auto-cancel so we can still show 'canceled' state
        $order = $this->orderModel->findByIdOrCode($code);
        if (!$order) {
            $this->errorResponse('Pesanan tidak ditemukan.', null, 404);
            return;
        }

        // Run auto-cancel for unclaimed orders older than 60 seconds
        \App\Models\Order::autoCancelUnclaimedOrders();

        // Reload the order to get the latest status after auto-cancel
        $order = $this->orderModel->findByIdOrCode($code) ?? $order;

        // Driver coordinates
        $driverLat = (float)($order['dm_lat'] ?? -6.9840);
        $driverLng = (float)($order['dm_lng'] ?? 107.8340);

        // Store coordinates
        $storeLat = (float)($order['store_lat'] ?? -6.9835);
        $storeLng = (float)($order['store_lng'] ?? 107.8335);

        // Customer coordinates strictly locked from checkout
        $destLat = (float)($order['delivery_address']['lat'] ?? -6.9855);
        $destLng = (float)($order['delivery_address']['lng'] ?? 107.8350);

        $userId = auth_id();
        $unreadChatCount = 0;
        if ($userId) {
            $unreadChat = Database::fetchOne(
                "SELECT COUNT(*) as cnt FROM `chats` WHERE `order_id` = ? AND `sender_id` != ? AND `is_read` = 0",
                [$order['id'], $userId]
            );
            $unreadChatCount = (int)($unreadChat['cnt'] ?? 0);
        }

        $isDriverAssigned = !empty($order['delivery_man_id']) && $order['order_status'] !== 'canceled' && in_array($order['order_status'], ['processing', 'handover', 'on_the_way', 'delivered']);

        // Use MySQL server time for precision — avoids PHP/MySQL timezone mismatch
        $timingRow = Database::fetchOne(
            "SELECT UNIX_TIMESTAMP(created_at) AS created_ts, UNIX_TIMESTAMP(NOW()) AS now_ts FROM `orders` WHERE id = ?",
            [$order['id']]
        );
        $createdAtTime  = (int)($timingRow['created_ts'] ?? strtotime($order['created_at']));
        $serverNow      = (int)($timingRow['now_ts'] ?? time());
        $elapsedSeconds = max(0, $serverNow - $createdAtTime);
        $isMerchantOrder = (($order['delivery_type'] ?? 'driver') === 'merchant');

        if ($isMerchantOrder) {
            $remainingSeconds = max(0, 300 - $elapsedSeconds);
            // Immediate inline auto-cancellation if merchant order has exceeded 298 seconds (5 minutes) without response
            if ($elapsedSeconds >= 298 && in_array($order['order_status'], ['pending', 'unpaid'])) {
                Database::update('orders', [
                    'order_status'        => 'canceled',
                    'cancellation_reason' => 'Batal Otomatis: Resto / Merchant tidak merespon dalam 5 menit',
                    'canceled_at'          => date('Y-m-d H:i:s')
                ], 'id = ?', [$order['id']]);

                \App\Models\Order::refundOrderIfPaid($order, 'Batal Otomatis: Resto tidak merespon dalam 5 menit');
                $order = $this->orderModel->findByIdOrCode($code) ?? $order;
                $remainingSeconds = 0;
            }
        } else {
            $remainingSeconds = max(0, 60 - $elapsedSeconds);
            // Immediate inline auto-cancellation if order has exceeded 58 seconds without driver (only for driver delivery)
            if ($elapsedSeconds >= 58 && empty($order['delivery_man_id']) && !in_array($order['order_status'], ['processing', 'handover', 'on_the_way', 'delivered', 'canceled', 'refunded', 'failed'])) {
                Database::update('orders', [
                    'delivery_man_id'     => null,
                    'order_status'        => 'canceled',
                    'cancellation_reason' => 'Batal Otomatis: Tidak mendapatkan driver dalam waktu 1 menit',
                    'canceled_at'          => date('Y-m-d H:i:s')
                ], 'id = ?', [$order['id']]);

                \App\Models\Order::refundOrderIfPaid($order, 'Batal Otomatis: Tidak mendapatkan driver dalam waktu 1 menit');
                $order = $this->orderModel->findByIdOrCode($code) ?? $order;
                $remainingSeconds = 0;
            }
        }
        $batchInfo = null;
        if (!empty($order['delivery_batch_id'])) {
            $totalInBatch = (int)Database::fetchColumn(
                "SELECT COUNT(*) FROM `orders` WHERE `delivery_batch_id` = ? AND `order_status` != 'canceled'",
                [$order['delivery_batch_id']]
            );
            $batchInfo = [
                'batch_id'        => $order['delivery_batch_id'],
                'pickup_sequence' => (int)($order['pickup_sequence'] ?? 1),
                'total_orders'    => $totalInBatch,
                'is_multi_pickup' => $totalInBatch > 1,
                'stores'          => $order['batch_stores'] ?? [],
                'sub_orders'      => $order['batch_sub_orders'] ?? [],
                'total_amount'    => $order['batch_total_amount'] ?? (float)$order['total_amount'],
            ];
        }

        $liveItems = $order['items'] ?? [];
        if (!empty($order['batch_sub_orders'])) {
            $liveItems = [];
            foreach ($order['batch_sub_orders'] as $subOrd) {
                if (!empty($subOrd['items'])) {
                    foreach ($subOrd['items'] as $subIt) {
                        $subIt['store_name'] = $subOrd['store_name'] ?? 'Toko';
                        $liveItems[] = $subIt;
                    }
                }
            }
        }

        $this->json([
            'success' => true,
            'data'    => [
                'order_code'          => $order['order_code'],
                'order_status'        => $order['order_status'],
                'delivery_type'       => $order['delivery_type'] ?? 'driver',
                'distance_km'         => (float)($order['distance_km'] ?? 0),
                'cancellation_reason' => $order['cancellation_reason'] ?? '',
                'payment_status'      => $order['payment_status'],
                'payment_method'      => $order['payment_method'],
                'created_at_time'     => $createdAtTime,
                'server_time'         => $serverNow,
                'remaining_seconds'   => $remainingSeconds,
                'otp'                 => $order['otp'],
                'unread_chats'        => $unreadChatCount,
                'batch_info'          => $batchInfo,
                'total_amount'        => (float)($order['batch_total_amount'] ?? $order['total_amount'] ?? $order['order_amount'] ?? 0),
                'order_amount'        => (float)($order['order_amount'] ?? $order['total_amount'] ?? 0),
                'delivery_charge'     => (float)($order['delivery_charge'] ?? 0),
                'coupon_discount'     => (float)($order['coupon_discount'] ?? 0),
                'tax_amount'          => (float)($order['tax_amount'] ?? 0),
                'items'               => $liveItems,
                'order_type'          => $order['order_type'] ?? 'delivery',
                'order_notes'         => $order['order_notes'] ?? '',
                'parcel_details'      => $order['parcel_details'] ?? null,
                'driver'         => [
                    'assigned' => $isDriverAssigned,
                    'name'     => $isDriverAssigned ? ($order['dm_name'] ?? 'Mitra Kurir Cicalengka') : 'Mencari Kurir...',
                    'phone'    => $isDriverAssigned ? ($order['dm_phone'] ?? '') : '',
                    'avatar'   => $isDriverAssigned ? ($order['dm_avatar'] ?? 'assets/images/users/driver.png') : 'assets/images/users/driver.png',
                    'vehicle'  => $isDriverAssigned ? ($order['vehicle_type'] ?? 'Motor') : 'Motor',
                    'plate'    => $isDriverAssigned ? ($order['vehicle_number'] ?? '') : '',
                    'lat'      => ($isDriverAssigned && !in_array($order['order_status'], ['delivered', 'canceled', 'refunded', 'failed'])) ? $driverLat : null,
                    'lng'      => ($isDriverAssigned && !in_array($order['order_status'], ['delivered', 'canceled', 'refunded', 'failed'])) ? $driverLng : null
                ],
                'store'          => [
                    'name'    => $order['store_name'] ?? 'Titik Penjemputan',
                    'address' => $order['store_address'] ?? 'Cicalengka, Bandung',
                    'logo'    => $order['store_logo'] ?? $order['logo'] ?? '',
                    'lat'     => $storeLat,
                    'lng'     => $storeLng
                ],
                'destination'    => [
                    'address' => $order['delivery_address']['address'] ?? '',
                    'lat'     => $destLat,
                    'lng'     => $destLng
                ],
                'timestamps'     => [
                    'created_at'   => $order['created_at'] ?? null,
                    'confirmed_at' => $order['confirmed_at'] ?? null,
                    'delivered_at' => $order['delivered_at'] ?? null
                ],
                'review_info'    => $order['review_info'] ?? null
            ]
        ]);
    }

    public function submitReview(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Silakan login terlebih dahulu.', null, 401);
            return;
        }

        $data = $this->getPost();
        $orderId = (int)($data['order_id'] ?? 0);
        $orderCode = trim($data['order_code'] ?? '');

        if (!$orderId && !empty($orderCode)) {
            $ord = $this->orderModel->findByCode($orderCode);
            if ($ord) $orderId = (int)$ord['id'];
        }

        if (!$orderId) {
            $this->errorResponse('ID atau kode pesanan wajib disertakan.');
            return;
        }

        $storeRating  = (int)($data['store_rating'] ?? $data['rating'] ?? 5);
        $storeComment = sanitize($data['store_comment'] ?? $data['comment'] ?? '');
        $dmRating     = (isset($data['dm_rating']) && $data['dm_rating'] !== '') ? (int)$data['dm_rating'] : null;
        $dmComment    = sanitize($data['dm_comment'] ?? '');

        $multiStoreReviews = [];
        if (!empty($data['multi_store_reviews'])) {
            $decoded = is_array($data['multi_store_reviews']) ? $data['multi_store_reviews'] : json_decode($data['multi_store_reviews'], true);
            if (is_array($decoded)) {
                $multiStoreReviews = $decoded;
            }
        }

        try {
            $reviewModel = new \App\Models\Review();
            $res = $reviewModel->submitReview(
                $orderId,
                $userId,
                $storeRating,
                $storeComment,
                $dmRating,
                $dmComment,
                $multiStoreReviews
            );

            $reviewInfo = $reviewModel->getOrderReview($orderId, $userId);

            $this->successResponse('Terima kasih! Ulasan dan rating bintang berhasil dikirimkan.', [
                'review_result' => $res,
                'review_info'   => $reviewInfo
            ]);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }
}
