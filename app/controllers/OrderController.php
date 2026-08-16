<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Wallet;
use App\Models\Coupon;
use App\Models\CustomerAddress;
use App\Services\OrderService;
use App\Services\MidtransService;
use App\Core\Database;
use Exception;

class OrderController extends Controller
{
    private Order $orderModel;
    private Cart $cartModel;
    private Wallet $walletModel;
    private OrderService $orderService;
    private MidtransService $midtransService;

    public function __construct()
    {
        $this->orderModel = new Order();
        $this->cartModel = new Cart();
        $this->walletModel = new Wallet();
        $this->orderService = new OrderService();
        $this->midtransService = new MidtransService();
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

        $this->view('customer.checkout', [
            'title'        => 'Checkout Pesanan - CicalengkaGO',
            'cart_data'    => $cartData,
            'wallet'       => $wallet,
            'addresses'    => $addresses,
            'coupons'      => $coupons,
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

        // Build delivery address array
        $deliveryAddress = [
            'contact_name'  => sanitize($data['contact_name'] ?? $_SESSION['user']['name']),
            'contact_phone' => sanitize($data['contact_phone'] ?? $_SESSION['user']['phone']),
            'address'       => sanitize($data['address'] ?? 'Cicalengka, Bandung'),
            'lat'           => (float)($data['latitude'] ?? -6.9840),
            'lng'           => (float)($data['longitude'] ?? 107.8340),
            'road'          => sanitize($data['road'] ?? ''),
            'house'         => sanitize($data['house'] ?? '')
        ];

        try {
            $result = $this->orderService->createOrderFromCart($userId, [
                'delivery_address' => $deliveryAddress,
                'payment_method'   => $paymentMethod,
                'coupon_code'      => sanitize($data['coupon_code'] ?? ''),
                'order_notes'      => sanitize($data['order_notes'] ?? ''),
                'distance_km'      => (float)($data['distance_km'] ?? 1.5),
                'order_type'       => $data['order_type'] ?? 'delivery'
            ]);

            $responseData = [
                'order_code'     => $result['order_code'],
                'order_id'       => $result['order_id'],
                'payment_method' => $paymentMethod,
                'redirect'       => 'orders/' . $result['order_code'] . '/tracking'
            ];

            // If online payment via Midtrans Snap
            if ($paymentMethod === 'midtrans') {
                $user = auth_user();
                $snapParams = [
                    'transaction_details' => [
                        'order_id'     => $result['order_code'],
                        'gross_amount' => (int)round($result['total'])
                    ],
                    'customer_details' => [
                        'first_name' => $deliveryAddress['contact_name'] ?: ($user['name'] ?? 'Pelanggan'),
                        'email'      => $user['email'] ?? 'customer@cicalengkago.id',
                        'phone'      => $deliveryAddress['contact_phone'] ?: ($user['phone'] ?? '081234567890')
                    ],
                    'item_details' => [
                        [
                            'id'       => 'ORDER_' . $result['order_code'],
                            'price'    => (int)round($result['total']),
                            'quantity' => 1,
                            'name'     => 'Pesanan CicalengkaGO #' . $result['order_code']
                        ]
                    ]
                ];

                $snapResult = $this->midtransService->createSnapToken($snapParams);
                $responseData['snap_token']   = $snapResult['token'];
                $responseData['client_key']   = $snapResult['client_key'];
                $responseData['redirect_url'] = $snapResult['redirect_url'];
            }

            $this->successResponse('Pesanan berhasil dibuat!', $responseData);
        } catch (Exception $e) {
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
            $result = $this->orderService->createParcelOrder($userId, [
                'destination_address' => [
                    'recipient_name'  => sanitize($data['recipient_name'] ?? ''),
                    'recipient_phone' => sanitize($data['recipient_phone'] ?? ''),
                    'address'         => sanitize($data['destination_address'] ?? ''),
                    'lat'             => (float)($data['dest_lat'] ?? -6.9840),
                    'lng'             => (float)($data['dest_lng'] ?? 107.8340)
                ],
                'parcel_details' => [
                    'sender_name'     => sanitize($data['sender_name'] ?? $_SESSION['user']['name']),
                    'sender_phone'    => sanitize($data['sender_phone'] ?? $_SESSION['user']['phone']),
                    'pickup_address'  => sanitize($data['pickup_address'] ?? ''),
                    'pickup_lat'      => (float)($data['pickup_lat'] ?? -6.9840),
                    'pickup_lng'      => (float)($data['pickup_lng'] ?? 107.8340),
                    'item_category'   => sanitize($data['item_category'] ?? 'Dokumen / Barang'),
                    'weight_kg'       => (float)($data['weight_kg'] ?? 1.0)
                ],
                'payment_method' => $paymentMethod,
                'parcel_notes'   => sanitize($data['parcel_notes'] ?? 'Titip kirim paket kilat Cicalengka'),
                'distance_km'    => (float)($data['distance_km'] ?? 2.5)
            ]);

            $responseData = [
                'order_code'     => $result['order_code'],
                'order_id'       => $result['order_id'],
                'payment_method' => $paymentMethod,
                'redirect'       => 'orders/' . $result['order_code'] . '/tracking'
            ];

            // If online payment via Midtrans Snap
            if ($paymentMethod === 'midtrans') {
                $user = auth_user();
                $snapParams = [
                    'transaction_details' => [
                        'order_id'     => $result['order_code'],
                        'gross_amount' => (int)round($result['total'])
                    ],
                    'customer_details' => [
                        'first_name' => sanitize($data['sender_name'] ?? ($user['name'] ?? 'Pengirim')),
                        'email'      => $user['email'] ?? 'customer@cicalengkago.id',
                        'phone'      => sanitize($data['sender_phone'] ?? ($user['phone'] ?? '081234567890'))
                    ],
                    'item_details' => [
                        [
                            'id'       => 'PARCEL_' . $result['order_code'],
                            'price'    => (int)round($result['total']),
                            'quantity' => 1,
                            'name'     => 'Ongkir CicalengkaSend #' . $result['order_code']
                        ]
                    ]
                ];

                $snapResult = $this->midtransService->createSnapToken($snapParams);
                $responseData['snap_token']   = $snapResult['token'];
                $responseData['client_key']   = $snapResult['client_key'];
                $responseData['redirect_url'] = $snapResult['redirect_url'];
            }

            $this->successResponse('Pengiriman Parcel berhasil dipesan!', $responseData);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    public function ordersList(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->redirect('login');
            return;
        }

        $orders = $this->orderModel->getCustomerOrders($userId);

        $this->view('customer.orders', [
            'title'      => 'Pesanan Saya - CicalengkaGO',
            'orders'     => $orders,
            'active_tab' => 'orders'
        ], 'customer_layout');
    }

    public function tracking(string $code): void
    {
        $order = $this->orderModel->findByIdOrCode($code);
        if (!$order) {
            $this->redirect('orders');
            return;
        }

        $snapToken = null;
        $clientKey = $this->midtransService->getClientKey();
        $snapUrl   = $this->midtransService->getSnapUrl();

        if ($order['payment_method'] === 'midtrans' && $order['payment_status'] !== 'paid' && $order['order_status'] !== 'canceled') {
            try {
                $user = auth_user() ?: ['name' => 'Pelanggan', 'email' => 'customer@cicalengkago.id', 'phone' => '081234567890'];
                $snapParams = [
                    'transaction_details' => [
                        'order_id'     => $order['order_code'],
                        'gross_amount' => (int)round((float)$order['total_amount'])
                    ],
                    'customer_details' => [
                        'first_name' => $order['delivery_address']['contact_name'] ?? ($user['name'] ?? 'Pelanggan'),
                        'email'      => $user['email'] ?? 'customer@cicalengkago.id',
                        'phone'      => $order['delivery_address']['contact_phone'] ?? ($user['phone'] ?? '081234567890')
                    ],
                    'item_details' => [
                        [
                            'id'       => 'ORDER_' . $order['order_code'],
                            'price'    => (int)round((float)$order['total_amount']),
                            'quantity' => 1,
                            'name'     => 'Pesanan CicalengkaGO #' . $order['order_code']
                        ]
                    ]
                ];
                $snapRes = $this->midtransService->createSnapToken($snapParams);
                $snapToken = $snapRes['token'];
                $clientKey = $snapRes['client_key'] ?? $clientKey;
                $snapUrl   = $this->midtransService->getSnapUrl();
            } catch (\Exception $e) {
                // Keep snapToken null, client can request later
            }
        }

        $this->view('customer.order_tracking', [
            'title'      => "Lacak Pesanan #{$order['order_code']}",
            'order'      => $order,
            'snap_token' => $snapToken,
            'client_key' => $clientKey,
            'snap_url'   => $snapUrl,
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
            $snapParams = [
                'transaction_details' => [
                    'order_id'     => $order['order_code'],
                    'gross_amount' => (int)round((float)$order['total_amount'])
                ],
                'customer_details' => [
                    'first_name' => $order['delivery_address']['contact_name'] ?? ($user['name'] ?? 'Pelanggan'),
                    'email'      => $user['email'] ?? 'customer@cicalengkago.id',
                    'phone'      => $order['delivery_address']['contact_phone'] ?? ($user['phone'] ?? '081234567890')
                ],
                'item_details' => [
                    [
                        'id'       => 'ORDER_' . $order['order_code'],
                        'price'    => (int)round((float)$order['total_amount']),
                        'quantity' => 1,
                        'name'     => 'Pesanan CicalengkaGO #' . $order['order_code']
                    ]
                ]
            ];

            $snapResult = $this->midtransService->createSnapToken($snapParams);
            $this->successResponse('Snap token siap', [
                'snap_token'   => $snapResult['token'],
                'client_key'   => $snapResult['client_key'],
                'redirect_url' => $snapResult['redirect_url'],
                'snap_url'     => $this->midtransService->getSnapUrl()
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

        if ($order['payment_status'] === 'paid') {
            $this->errorResponse('Pesanan yang sudah dibayar tidak dapat dibatalkan otomatis.');
            return;
        }

        \App\Core\Database::update('orders', [
            'order_status' => 'canceled',
            'canceled_at'  => date('Y-m-d H:i:s')
        ], 'id = ?', [$order['id']]);

        $this->successResponse('Pesanan berhasil dibatalkan.');
    }

    public function showOrder(string $idOrCode): void
    {
        $order = $this->orderModel->findByIdOrCode($idOrCode);
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
        $order = $this->orderModel->findByIdOrCode($code);
        if (!$order) {
            $this->errorResponse('Pesanan tidak ditemukan.', null, 404);
            return;
        }

        // Driver coordinates
        $driverLat = (float)($order['dm_lat'] ?? -6.9840);
        $driverLng = (float)($order['dm_lng'] ?? 107.8340);

        // Store coordinates
        $storeLat = (float)($order['store_lat'] ?? -6.9835);
        $storeLng = (float)($order['store_lng'] ?? 107.8335);

        // Customer coordinates
        $destLat = (float)($order['delivery_address']['lat'] ?? -6.9855);
        $destLng = (float)($order['delivery_address']['lng'] ?? 107.8350);

        $this->json([
            'success' => true,
            'data'    => [
                'order_status'   => $order['order_status'],
                'payment_status' => $order['payment_status'],
                'otp'            => $order['otp'],
                'driver'         => [
                    'assigned' => !empty($order['delivery_man_id']),
                    'name'     => $order['dm_name'] ?? 'Mencari Kurir...',
                    'phone'    => $order['dm_phone'] ?? '',
                    'vehicle'  => $order['vehicle_type'] ?? 'Motor',
                    'plate'    => $order['vehicle_number'] ?? '',
                    'lat'      => $driverLat,
                    'lng'      => $driverLng
                ],
                'store'          => [
                    'name' => $order['store_name'] ?? 'Titik Penjemputan',
                    'lat'  => $storeLat,
                    'lng'  => $storeLng
                ],
                'destination'    => [
                    'address' => $order['delivery_address']['address'] ?? '',
                    'lat'     => $destLat,
                    'lng'     => $destLng
                ]
            ]
        ]);
    }
}
