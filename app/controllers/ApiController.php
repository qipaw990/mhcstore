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
            
            $token = $user['api_token'] ?? null;
            if (empty($token)) {
                $token = bin2hex(random_bytes(32));
                (new \App\Models\User())->update($user['id'], ['api_token' => $token]);
            }

            $this->successResponse('Login berhasil', [
                'token' => $token,
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
        // Suppress HTML error output and clean buffer to ensure pure JSON response
        @ini_set('display_errors', '0');
        if (ob_get_length()) {
            @ob_clean();
        }

        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept");
        header("Content-Type: application/json; charset=utf-8");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        try {
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);
            if (!$data && !empty($_POST)) {
                $data = $_POST;
            }

            $storeName = trim($data['name'] ?? '');
            if (empty($storeName)) {
                $this->errorResponse('Nama toko tidak boleh kosong.');
                return;
            }

            $pdo = \App\Core\Database::getPdo();

            // 1. Create or Get Vendor
            $phone = !empty($data['phone']) ? trim($data['phone']) : ('08' . str_pad((string)rand(100000000, 999999999), 10, '0', STR_PAD_LEFT));
            $cleanName = preg_replace('/[^a-z0-9]/', '', strtolower($storeName));
            $email = 'vendor_' . ($cleanName ?: 'store_' . rand(1000, 9999)) . '@cicalengkago.id';

            $stmtV = $pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ? LIMIT 1");
            $stmtV->execute([$email, $phone]);
            $vUser = $stmtV->fetch(\PDO::FETCH_ASSOC);

            if ($vUser) {
                $vendorId = (int)$vUser['id'];
            } else {
                // Handle duplicate phone/email graceful fallback
                $phoneAlt = '08' . str_pad((string)rand(100000000, 999999999), 10, '0', STR_PAD_LEFT);
                $emailAlt = 'vendor_' . rand(10000, 99999) . '@cicalengkago.id';
                $stmtInsV = $pdo->prepare("INSERT INTO users (role, name, email, phone, password, is_active) VALUES ('vendor', ?, ?, ?, ?, 1)");
                try {
                    $stmtInsV->execute([
                        "Mitra " . $storeName,
                        $email,
                        $phone,
                        password_hash('vendor123', PASSWORD_BCRYPT)
                    ]);
                } catch (\PDOException $pe) {
                    $stmtInsV->execute([
                        "Mitra " . $storeName,
                        $emailAlt,
                        $phoneAlt,
                        password_hash('vendor123', PASSWORD_BCRYPT)
                    ]);
                }
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

            $rawLogo  = !empty($data['logo']) ? trim($data['logo']) : '';
            $rawCover = !empty($data['cover_photo']) ? trim($data['cover_photo']) : '';

            $logo  = (!empty($rawLogo) && str_starts_with($rawLogo, 'http')) ? download_and_save_image($rawLogo, 'stores') : $rawLogo;
            $cover = (!empty($rawCover) && str_starts_with($rawCover, 'http')) ? download_and_save_image($rawCover, 'stores') : $rawCover;

            $address  = !empty($data['address']) ? trim($data['address']) : 'Cicalengka, Kab. Bandung';
            $lat      = !empty($data['latitude']) ? (float)$data['latitude'] : -6.98350000;
            $lng      = !empty($data['longitude']) ? (float)$data['longitude'] : 107.83350000;
            $delTime  = !empty($data['delivery_time']) ? $data['delivery_time'] : '15-25 min';
            $rating   = !empty($data['rating']) ? (float)$data['rating'] : 4.8;
            $revCount = !empty($data['reviews_count']) ? (int)$data['reviews_count'] : rand(50, 200);

            $openTime  = !empty($data['opening_time']) ? date('H:i:s', strtotime($data['opening_time'])) : '08:00:00';
            $closeTime = !empty($data['closing_time']) ? date('H:i:s', strtotime($data['closing_time'])) : '22:00:00';
            $grabUrl   = !empty($data['grab_url']) ? trim($data['grab_url']) : (!empty($data['url']) ? trim($data['url']) : null);
            $isOpen    = isset($data['is_open']) ? ((int)$data['is_open'] === 1 ? 1 : 0) : 1;

            if ($sRow) {
                $storeId = (int)$sRow['id'];
                $stmtUpS = $pdo->prepare("UPDATE stores SET logo = ?, cover_photo = ?, address = ?, rating = ?, latitude = ?, longitude = ?, grab_url = COALESCE(?, grab_url), is_open = ? WHERE id = ?");
                $stmtUpS->execute([$logo, $cover, $address, $rating, $lat, $lng, $grabUrl, $isOpen, $storeId]);
            } else {
                $stmtInsS = $pdo->prepare("
                    INSERT INTO stores (
                        vendor_id, module_id, zone_id, name, phone, email, logo, cover_photo, address,
                        latitude, longitude, minimum_order, delivery_time, delivery_fee, grab_url, is_open, status, rating, reviews_count
                    ) VALUES (?, 1, 1, ?, ?, ?, ?, ?, ?, ?, ?, 0.00, ?, 5000.00, ?, ?, 'approved', ?, ?)
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
                    $grabUrl,
                    $isOpen,
                    $rating,
                    $revCount
                ]);
                $storeId = (int)$pdo->lastInsertId();
            }

            // Save or Update store schedules (Day 0 to 6)
            for ($day = 0; $day <= 6; $day++) {
                $stmtCheckSch = $pdo->prepare("SELECT id FROM store_schedules WHERE store_id = ? AND day_of_week = ? LIMIT 1");
                $stmtCheckSch->execute([$storeId, $day]);
                if ($stmtCheckSch->fetch()) {
                    $stmtUpSch = $pdo->prepare("UPDATE store_schedules SET opening_time = ?, closing_time = ? WHERE store_id = ? AND day_of_week = ?");
                    $stmtUpSch->execute([$openTime, $closeTime, $storeId, $day]);
                } else {
                    $stmtInsSch = $pdo->prepare("INSERT INTO store_schedules (store_id, day_of_week, opening_time, closing_time) VALUES (?, ?, ?, ?)");
                    $stmtInsSch->execute([$storeId, $day, $openTime, $closeTime]);
                }
            }

            // 4. Products Handling
            $products = is_array($data['products'] ?? null) ? $data['products'] : [];
            $itemsImported = 0;
            $categoryCache = [];

            foreach ($products as $p) {
                $pName = trim($p['name'] ?? '');
                if (empty($pName)) continue;

                // Handle product specific category
                $pCatName = !empty($p['category']) ? trim($p['category']) : $catName;
                if (!isset($categoryCache[$pCatName])) {
                    $stmtC = $pdo->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
                    $stmtC->execute([$pCatName]);
                    $cRow = $stmtC->fetch(\PDO::FETCH_ASSOC);

                    if ($cRow) {
                        $categoryCache[$pCatName] = (int)$cRow['id'];
                    } else {
                        $stmtInsC = $pdo->prepare("INSERT INTO categories (module_id, name, icon, status) VALUES (1, ?, 'bi-shop-window', 1)");
                        $stmtInsC->execute([$pCatName]);
                        $categoryCache[$pCatName] = (int)$pdo->lastInsertId();
                    }
                }
                $itemCatId = $categoryCache[$pCatName];

                $pDesc     = trim($p['description'] ?? '');
                $pPrice    = (float)($p['price'] ?? 0);
                $rawPImage = !empty($p['image']) ? trim($p['image']) : '';

                // Clean image URL: if product missing item photo, fallback to store cover/logo
                if (empty($rawPImage) || str_contains($rawPImage, 'unsplash')) {
                    $rawPImage = !empty($rawCover) ? $rawCover : $rawLogo;
                }

                $pImage = (!empty($rawPImage) && str_starts_with($rawPImage, 'http')) ? download_and_save_image($rawPImage, 'products') : ($rawPImage ?: $cover);
                $pRec      = !empty($p['is_recommended']) ? 1 : 0;
                $pDisc     = (float)($p['discount'] ?? 0);

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
                        $itemCatId,
                        $pName,
                        $pDesc,
                        $pImage,
                        $pPrice,
                        $pDisc,
                        $pRec
                    ]);
                } else {
                    $currImg = $existingP['image'] ?? '';
                    $isDuplicateStoreImg = ($currImg === $logo || $currImg === $cover || str_contains($currImg, 'unsplash') || empty($currImg));
                    $imgToUpdate = (!empty($pImage) && ($isDuplicateStoreImg || str_contains($pImage, 'uploads/products/'))) ? $pImage : $currImg;

                    $stmtUpP = $pdo->prepare("UPDATE products SET category_id = ?, description = ?, price = ?, image = ? WHERE id = ?");
                    $stmtUpP->execute([$itemCatId, $pDesc, $pPrice, $imgToUpdate, $existingP['id']]);
                }
                $itemsImported++;
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
        } catch (\Throwable $e) {
            $this->errorResponse("Gagal mengimpor toko: " . $e->getMessage());
        }
    }

    public function fillSchedules(): void
    {
        @ini_set('display_errors', '0');
        if (ob_get_length()) {
            @ob_clean();
        }

        try {
            $pdo = \App\Core\Database::getPdo();
            $stores = $pdo->query('SELECT id, name FROM stores')->fetchAll(\PDO::FETCH_ASSOC);

            $count = 0;
            foreach ($stores as $s) {
                $sid = (int)$s['id'];
                for ($d = 0; $d <= 6; $d++) {
                    $stmt = $pdo->prepare('SELECT id FROM store_schedules WHERE store_id = ? AND day_of_week = ? LIMIT 1');
                    $stmt->execute([$sid, $d]);
                    if (!$stmt->fetch()) {
                        $ins = $pdo->prepare("INSERT INTO store_schedules (store_id, day_of_week, opening_time, closing_time) VALUES (?, ?, '08:00:00', '22:00:00')");
                        $ins->execute([$sid, $d]);
                        $count++;
                    }
                }
            }

            $this->successResponse("Berhasil memperbarui jadwal toko di database! Total " . count($stores) . " toko kini memiliki jam buka (08:00) dan tutup (22:00).", [
                'total_stores'    => count($stores),
                'schedules_added' => $count
            ]);
        } catch (\Throwable $e) {
            $this->errorResponse("Gagal mengisi jadwal: " . $e->getMessage());
        }
    }

    public function fixImages(): void
    {
        @ini_set('display_errors', '0');
        if (ob_get_length()) {
            @ob_clean();
        }

        try {
            $pdo = \App\Core\Database::getPdo();
            $stores = $pdo->query("SELECT id, name, logo, cover_photo FROM stores")->fetchAll(\PDO::FETCH_ASSOC);
            $updatedStores = 0;

            foreach ($stores as $s) {
                $sid = (int)$s['id'];
                $newLogo = $s['logo'];
                $newCover = $s['cover_photo'];
                $changed = false;

                if (!empty($s['logo']) && (str_starts_with($s['logo'], 'http://') || str_starts_with($s['logo'], 'https://'))) {
                    $newLogo = download_and_save_image($s['logo'], 'stores');
                    $changed = true;
                }
                if (!empty($s['cover_photo']) && (str_starts_with($s['cover_photo'], 'http://') || str_starts_with($s['cover_photo'], 'https://'))) {
                    $newCover = download_and_save_image($s['cover_photo'], 'stores');
                    $changed = true;
                }

                if ($changed) {
                    $stmtUp = $pdo->prepare("UPDATE stores SET logo = ?, cover_photo = ? WHERE id = ?");
                    $stmtUp->execute([$newLogo, $newCover, $sid]);
                    $updatedStores++;
                }
            }

            // Cleanup old dummy non-grab products for stores that have scraped grab products
            $scrapedStores = $pdo->query("SELECT DISTINCT store_id FROM products WHERE image LIKE 'uploads/products/grab_%'")->fetchAll(\PDO::FETCH_COLUMN);
            foreach ($scrapedStores as $sid) {
                $stmtDel = $pdo->prepare("DELETE FROM products WHERE store_id = ? AND image NOT LIKE 'uploads/products/grab_%'");
                $stmtDel->execute([$sid]);
            }

            // Find duplicate images shared across multiple products (e.g. store logo used for all items)
            $imageCounts = $pdo->query("SELECT image, COUNT(*) as cnt FROM products WHERE image IS NOT NULL AND image != '' GROUP BY image HAVING cnt > 1")->fetchAll(\PDO::FETCH_KEY_PAIR);

            $products = $pdo->query("SELECT id, name, description, image FROM products")->fetchAll(\PDO::FETCH_ASSOC);
            $updatedProducts = 0;

            foreach ($products as $p) {
                $pid = (int)$p['id'];
                $img = $p['image'];
                $rawName = $p['name'];

                // Clean concatenated product name
                if (preg_match('/([a-z0-9])([A-Z])/', $rawName, $m, PREG_OFFSET_CAPTURE)) {
                    $splitPos = $m[1][1] + 1;
                    $cleanTitle = trim(substr($rawName, 0, $splitPos));
                    $cleanDesc  = trim(substr($rawName, $splitPos));

                    if (!empty($cleanTitle) && strlen($cleanTitle) > 3) {
                        $stmtClean = $pdo->prepare("UPDATE products SET name = ?, description = ? WHERE id = ?");
                        $stmtClean->execute([$cleanTitle, $cleanDesc, $pid]);
                        $rawName = $cleanTitle;
                    }
                }

                // Check small file / SVG / duplicate store image
                $isSmallFile = false;
                if (!empty($img) && str_starts_with($img, 'uploads/')) {
                    $fullPath = PUBLIC_PATH . '/' . $img;
                    if (file_exists($fullPath) && filesize($fullPath) < 15000) {
                        $isSmallFile = true;
                    }
                }

                $isSharedDuplicate = isset($imageCounts[$img]);

                $needsFix = empty($img) 
                    || $isSmallFile
                    || $isSharedDuplicate
                    || str_contains($img, 'default') 
                    || str_contains($img, 'unsplash') 
                    || str_contains($img, 'photo-1546069901-ba9599a7e63c')
                    || str_contains($img, 'logo-grabfood')
                    || str_contains($img, 'svg')
                    || str_contains($img, 'placeholder')
                    || str_starts_with($img, 'http://') 
                    || str_starts_with($img, 'https://');

                if ($needsFix) {
                    $targetUrl = $this->getFoodImageByName($rawName);
                    $newImg = download_and_save_image($targetUrl, 'products');
                    if (!empty($newImg)) {
                        $stmtUpP = $pdo->prepare("UPDATE products SET image = ? WHERE id = ?");
                        $stmtUpP->execute([$newImg, $pid]);
                        $updatedProducts++;
                    }
                }
            }

            $this->successResponse("Sukses! Berhasil merapikan nama menu dan mengunduh foto produk kuliner HD ke penyimpanan lokal server.", [
                'updated_stores'   => $updatedStores,
                'updated_products' => $updatedProducts
            ]);
        } catch (\Throwable $e) {
            $this->errorResponse("Gagal memperbaiki gambar: " . $e->getMessage());
        }
    }

    private function getFoodImageByName(string $name): string
    {
        $nameLower = strtolower($name);

        if (str_contains($nameLower, 'nasi goreng') || str_contains($nameLower, 'nasgor')) {
            return 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=500&q=80';
        }
        if (str_contains($nameLower, 'kwetiau') || str_contains($nameLower, 'kwetiew')) {
            return 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=500&q=80';
        }
        if (str_contains($nameLower, 'mie') || str_contains($nameLower, 'ramen') || str_contains($nameLower, 'noodle')) {
            return 'https://images.unsplash.com/photo-1612927601601-6638404737ce?w=500&q=80';
        }
        if (str_contains($nameLower, 'ayam') || str_contains($nameLower, 'geprek') || str_contains($nameLower, 'chicken')) {
            return 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?w=500&q=80';
        }
        if (str_contains($nameLower, 'seblak')) {
            return 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=500&q=80';
        }
        if (str_contains($nameLower, 'bakso') || str_contains($nameLower, 'baso')) {
            return 'https://images.unsplash.com/photo-1541696432-82c6da8ce7bf?w=500&q=80';
        }
        if (str_contains($nameLower, 'sate') || str_contains($nameLower, 'satay')) {
            return 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=500&q=80';
        }
        if (str_contains($nameLower, 'martabak') || str_contains($nameLower, 'cake') || str_contains($nameLower, 'cheese')) {
            return 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=500&q=80';
        }
        if (str_contains($nameLower, 'es') || str_contains($nameLower, 'kopi') || str_contains($nameLower, 'tea') || str_contains($nameLower, 'drink') || str_contains($nameLower, 'latte')) {
            return 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=500&q=80';
        }
        if (str_contains($nameLower, 'wonton') || str_contains($nameLower, 'dimsum')) {
            return 'https://images.unsplash.com/photo-1496116218417-1a781b1c416c?w=500&q=80';
        }
        if (str_contains($nameLower, 'burger')) {
            return 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=500&q=80';
        }
        if (str_contains($nameLower, 'fries') || str_contains($nameLower, 'kentang')) {
            return 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?w=500&q=80';
        }
        if (str_contains($nameLower, 'rendang') || str_contains($nameLower, 'padang') || str_contains($nameLower, 'nasi timbel') || str_contains($nameLower, 'liwet')) {
            return 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&q=80';
        }

        return 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=500&q=80';
    }

    /**
     * Clear all stores, products, schedules, and vendor accounts
     */
    public function clearStores(): void
    {
        try {
            $pdo = Database::getInstance();
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $pdo->exec("TRUNCATE TABLE `products`");
            $pdo->exec("TRUNCATE TABLE `store_schedules`");
            $pdo->exec("TRUNCATE TABLE `stores`");
            $pdo->exec("TRUNCATE TABLE `carts`");
            $pdo->exec("TRUNCATE TABLE `order_items`");
            $pdo->exec("TRUNCATE TABLE `orders`");
            $pdo->exec("DELETE FROM `users` WHERE `role` = 'vendor'");
            $pdo->exec("DELETE FROM `wallets` WHERE `user_type` = 'vendor'");
            $pdo->exec("UPDATE `modules` SET `stores_count` = 0");
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

            json_response(true, "Seluruh data mitra, produk, dan toko berhasil dibersihkan!", [
                'cleared_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            json_response(false, "Gagal mengosongkan data toko: " . $e->getMessage(), [], 500);
        }
    }
}

