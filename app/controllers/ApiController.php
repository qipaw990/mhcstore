<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Module;
use App\Models\Store;
use App\Models\Product;
use App\Models\Category;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\Notification;
use App\Models\Coupon;
use App\Services\AuthService;
use App\Services\OrderService;
use App\Services\DeliveryService;
use Exception;

class ApiController extends Controller
{
    public function login(): void
    {
        $data = $this->getPost();
        $username = trim($data['username'] ?? $data['email'] ?? '');
        $password = trim($data['password'] ?? '');

        try {
            $user = (new AuthService())->login($username, $password);
            $this->successResponse('Login berhasil', [
                'token' => $user['api_token'],
                'user'  => [
                    'id'    => $user['id'],
                    'name'  => $user['name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'role'  => $user['role']
                ]
            ]);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), null, 401);
        }
    }

    public function register(): void
    {
        $data = $this->getPost();
        $errors = validate_required($data, ['name', 'email', 'phone', 'password']);
        if (!empty($errors)) {
            $this->errorResponse('Validasi gagal', $errors, 422);
            return;
        }

        try {
            $user = (new AuthService())->registerCustomer($data);
            $this->successResponse('Registrasi berhasil', [
                'token' => $user['api_token'],
                'user'  => [
                    'id'    => $user['id'],
                    'name'  => $user['name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'role'  => $user['role']
                ]
            ]);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), null, 400);
        }
    }

    public function modules(): void
    {
        $modules = (new Module())->activeModules();
        $this->successResponse('Daftar modul berhasil diambil', $modules);
    }

    public function stores(): void
    {
        $moduleId = !empty($_GET['module_id']) ? (int)$_GET['module_id'] : 1;
        $stores = (new Store())->getByModule($moduleId);
        $this->successResponse('Daftar toko berhasil diambil', $stores);
    }

    public function storeDetail(int $id): void
    {
        $store = (new Store())->findWithDetails($id);
        if (!$store) {
            $this->errorResponse('Toko tidak ditemukan', null, 404);
            return;
        }
        $products = (new Product())->getByStore($id);
        $this->successResponse('Detail toko berhasil diambil', [
            'store'    => $store,
            'products' => $products
        ]);
    }

    public function products(): void
    {
        $query = trim($_GET['q'] ?? '');
        $moduleId = !empty($_GET['module_id']) ? (int)$_GET['module_id'] : null;

        if (!empty($query)) {
            $products = (new Product())->search($query, $moduleId);
        } else {
            $products = (new Product())->getRecommended(20);
        }

        $this->successResponse('Daftar produk berhasil diambil', $products);
    }

    public function cart(): void
    {
        $userId = auth_id();
        $cart = (new Cart())->getUserCart($userId, session_id());
        $this->successResponse('Data keranjang', $cart);
    }

    public function checkout(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Unauthorized', null, 401);
            return;
        }

        $data = $this->getPost();
        try {
            $res = (new OrderService())->createOrderFromCart($userId, $data);
            $this->successResponse('Pesanan berhasil dibuat', $res);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), null, 400);
        }
    }

    public function orders(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Unauthorized', null, 401);
            return;
        }

        $orders = (new Order())->getCustomerOrders($userId);
        $this->successResponse('Daftar pesanan', $orders);
    }

    public function orderTracking(string $code): void
    {
        $order = (new Order())->findByCode($code);
        if (!$order) {
            $this->errorResponse('Pesanan tidak ditemukan', null, 404);
            return;
        }
        $this->successResponse('Data pelacakan pesanan', $order);
    }

    public function updateDriverLocation(): void
    {
        $userId = auth_id();
        $data = $this->getPost();
        $lat = (float)($data['lat'] ?? 0);
        $lng = (float)($data['lng'] ?? 0);

        $dm = (new \App\Models\DeliveryMan())->findByUserId($userId);
        if ($dm && $lat != 0 && $lng != 0) {
            (new \App\Models\DeliveryMan())->updateLocation($dm['id'], $lat, $lng, $dm['current_order_id']);
            $this->successResponse('Lokasi driver diperbarui');
            return;
        }

        $this->errorResponse('Gagal memperbarui lokasi');
    }

    public function wallet(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Unauthorized', null, 401);
            return;
        }

        $wallet = (new Wallet())->getOrCreate($userId, 'customer');
        $transactions = (new Wallet())->getTransactions($userId, 20);

        $this->successResponse('Data dompet digital', [
            'wallet'       => $wallet,
            'transactions' => $transactions
        ]);
    }

    public function notifications(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Unauthorized', null, 401);
            return;
        }

        $notifications = (new Notification())->getUserNotifications($userId, 30);
        $this->successResponse('Daftar notifikasi', $notifications);
    }
}
