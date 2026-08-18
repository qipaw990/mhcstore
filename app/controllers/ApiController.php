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

    public function importStore(): void
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true) ?: $this->getPost();

        $storeName = trim($data['name'] ?? '');
        if (empty($storeName)) {
            $this->errorResponse('Nama toko tidak boleh kosong.');
            return;
        }

        try {
            $pdo = \App\Core\Database::getPdo();

            // 1. Create or Get Vendor
            $phone = !empty($data['phone']) ? trim($data['phone']) : ('08' . rand(100000000, 999999999));
            $email = 'vendor_' . preg_replace('/[^a-z0-9]/', '', strtolower($storeName)) . '@cicalengkago.id';

            $stmtV = $pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ? LIMIT 1");
            $stmtV->execute([$email, $phone]);
            $vUser = $stmtV->fetch(\PDO::FETCH_ASSOC);

            if ($vUser) {
                $vendorId = (int)$vUser['id'];
            } else {
                $stmtInsV = $pdo->prepare("INSERT INTO users (role, name, email, phone, password, is_active) VALUES ('vendor', ?, ?, ?, ?, 1)");
                $stmtInsV->execute([
                    "Mitra " . $storeName,
                    $email,
                    $phone,
                    password_hash('vendor123', PASSWORD_BCRYPT)
                ]);
                $vendorId = (int)$pdo->lastInsertId();

                $stmtW = $pdo->prepare("INSERT INTO wallets (user_id, user_type, balance) VALUES (?, 'vendor', 0)");
                $stmtW->execute([$vendorId]);
            }

            // 2. Category Handling
            $catName = !empty($data['category']) ? trim($data['category']) : 'Kuliner & Snack';
            $stmtC = $pdo->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
            $stmtC->execute([$catName]);
            $cRow = $stmtC->fetch(\PDO::FETCH_ASSOC);

            if ($cRow) {
                $catId = (int)$cRow['id'];
            } else {
                $stmtInsC = $pdo->prepare("INSERT INTO categories (module_id, name, icon, status) VALUES (1, ?, 'bi-shop-window', 1)");
                $stmtInsC->execute([$catName]);
                $catId = (int)$pdo->lastInsertId();
            }

            // 3. Store Handling
            $stmtS = $pdo->prepare("SELECT id FROM stores WHERE name = ? LIMIT 1");
            $stmtS->execute([$storeName]);
            $sRow = $stmtS->fetch(\PDO::FETCH_ASSOC);

            $address  = !empty($data['address']) ? trim($data['address']) : 'Cicalengka, Kab. Bandung';
            $lat      = !empty($data['latitude']) ? (float)$data['latitude'] : -6.98350000;
            $lng      = !empty($data['longitude']) ? (float)$data['longitude'] : 107.83350000;
            $logo     = !empty($data['logo']) ? $data['logo'] : 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=300&q=80';
            $cover    = !empty($data['cover_photo']) ? $data['cover_photo'] : 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800&q=80';
            $delTime  = !empty($data['delivery_time']) ? $data['delivery_time'] : '15-25 min';
            $rating   = !empty($data['rating']) ? (float)$data['rating'] : 4.8;
            $revCount = !empty($data['reviews_count']) ? (int)$data['reviews_count'] : rand(50, 200);

            if ($sRow) {
                $storeId = (int)$sRow['id'];
                $stmtUpS = $pdo->prepare("UPDATE stores SET logo = ?, cover_photo = ?, address = ?, rating = ? WHERE id = ?");
                $stmtUpS->execute([$logo, $cover, $address, $rating, $storeId]);
            } else {
                $stmtInsS = $pdo->prepare("
                    INSERT INTO stores (
                        vendor_id, module_id, zone_id, name, phone, email, logo, cover_photo, address,
                        latitude, longitude, minimum_order, delivery_time, delivery_fee, is_open, status, rating, reviews_count
                    ) VALUES (?, 1, 1, ?, ?, ?, ?, ?, ?, ?, ?, 0.00, ?, 5000.00, 1, 'approved', ?, ?)
                ");
                $stmtInsS->execute([
                    $vendorId,
                    $storeName,
                    $phone,
                    $email,
                    $logo,
                    $cover,
                    $address,
                    $lat,
                    $lng,
                    $delTime,
                    $rating,
                    $revCount
                ]);
                $storeId = (int)$pdo->lastInsertId();

                for ($day = 0; $day <= 6; $day++) {
                    $stmtSch = $pdo->prepare("INSERT INTO store_schedules (store_id, day_of_week, opening_time, closing_time) VALUES (?, ?, '08:00:00', '22:00:00')");
                    $stmtSch->execute([$storeId, $day]);
                }
            }

            // 4. Products Handling
            $products = is_array($data['products'] ?? null) ? $data['products'] : [];
            $itemsImported = 0;

            foreach ($products as $p) {
                $pName = trim($p['name'] ?? '');
                if (empty($pName)) continue;

                $pDesc  = trim($p['description'] ?? '');
                $pPrice = (float)($p['price'] ?? 0);
                $pImage = !empty($p['image']) ? $p['image'] : $logo;
                $pRec   = !empty($p['is_recommended']) ? 1 : 0;
                $pDisc  = (float)($p['discount'] ?? 0);

                $stmtP = $pdo->prepare("SELECT id, image FROM products WHERE store_id = ? AND name = ? LIMIT 1");
                $stmtP->execute([$storeId, $pName]);
                $existingP = $stmtP->fetch(\PDO::FETCH_ASSOC);

                if (!$existingP) {
                    $stmtInsP = $pdo->prepare("
                        INSERT INTO products (
                            store_id, module_id, category_id, name, description, image, price, discount,
                            discount_type, unit, stock, is_veg, is_recommended, status, rating, reviews_count
                        ) VALUES (?, 1, ?, ?, ?, ?, ?, ?, 'percent', 'pcs', 100, 0, ?, 1, 5.00, 15)
                    ");
                    $stmtInsP->execute([
                        $storeId,
                        $catId,
                        $pName,
                        $pDesc,
                        $pImage,
                        $pPrice,
                        $pDisc,
                        $pRec
                    ]);
                    $itemsImported++;
                } else {
                    // Update image if it was previously empty or unsplash default
                    if (!empty($pImage) && (empty($existingP['image']) || strpos($existingP['image'], 'unsplash') !== false)) {
                        $stmtUpP = $pdo->prepare("UPDATE products SET image = ?, description = ?, price = ? WHERE id = ?");
                        $stmtUpP->execute([$pImage, $pDesc, $pPrice, $existingP['id']]);
                        $itemsImported++;
                    }
                }
            }

            // Update stores count
            $stmtCount = $pdo->query("SELECT COUNT(*) as cnt FROM stores WHERE module_id = 1");
            $cnt = (int)($stmtCount->fetch(\PDO::FETCH_ASSOC)['cnt'] ?? 0);
            $pdo->exec("UPDATE modules SET stores_count = {$cnt} WHERE id = 1");

            $this->successResponse("Toko '{$storeName}' & {$itemsImported} menu berhasil diimpor ke CicalengkaGO!", [
                'store_id'         => $storeId,
                'store_name'       => $storeName,
                'imported_items'   => $itemsImported,
                'total_food_stores'=> $cnt
            ]);
        } catch (\Exception $e) {
            $this->errorResponse("Gagal mengimpor toko: " . $e->getMessage());
        }
    }
}

