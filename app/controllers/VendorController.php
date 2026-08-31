<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Store;
use App\Models\Order;
use App\Models\Product;
use App\Models\Category;
use App\Models\Wallet;
use App\Models\WithdrawRequest;
use App\Core\Database;
use Exception;

class VendorController extends Controller
{
    private Store $storeModel;
    private Order $orderModel;
    private Product $productModel;
    private Wallet $walletModel;
    private WithdrawRequest $withdrawModel;

    public function __construct()
    {
        $this->storeModel = new Store();
        $this->orderModel = new Order();
        $this->productModel = new Product();
        $this->walletModel = new Wallet();
        $this->withdrawModel = new WithdrawRequest();
    }

    public function dashboard(): void
    {
        $userId = auth_id();
        $store = $this->storeModel->findByVendorId($userId);

        if (!$store) {
            if ($this->isJsonRequest()) {
                $this->successResponse('Toko belum terdaftar', [
                    'store'          => null,
                    'orders'         => [],
                    'total_orders'   => 0,
                    'stats'          => [
                        'total_orders'    => 0,
                        'total_revenue'   => 0,
                        'gross_sales'     => 0,
                        'pending_count'   => 0,
                        'processing_count'=> 0,
                        'on_the_way_count'=> 0,
                        'delivered_count' => 0,
                        'canceled_count'  => 0,
                        'today_orders'    => 0,
                        'today_revenue'   => 0,
                    ],
                    'reviews'        => [],
                    'products_count' => 0,
                    'wallet'         => $this->walletModel->getOrCreate($userId, 'vendor'),
                ]);
                return;
            }
            $this->view('vendor.setup_store', ['title' => 'Daftarkan Toko Anda'], 'vendor_layout');
            return;
        }

        $storeId = (int)$store['id'];

        // Pre-fetch vendor wallet so we can use wallet_id for duplicate check
        $vendorWallet = $this->walletModel->getOrCreate($userId, 'vendor');

        // Auto-heal vendor wallet credit for any non-COD delivered orders that haven't been credited yet (COD is received in cash directly)
        $deliveredOrders = Database::query(
            "SELECT id, order_code, order_amount, payment_method FROM `orders` WHERE `store_id` = ? AND `order_status` = 'delivered' AND `payment_method` != 'cod'",
            [$storeId]
        );
        foreach ($deliveredOrders as $dOrder) {
            $alreadyCredited = Database::fetchOne(
                "SELECT id FROM `wallet_transactions` WHERE `wallet_id` = ? AND `category` = 'order_earning' AND `reference_id` = ? LIMIT 1",
                [$vendorWallet['id'], (string)$dOrder['id']]
            );
            if (!$alreadyCredited) {
                $vendorEarning = (float)$dOrder['order_amount'] * 0.90;
                $this->walletModel->credit(
                    $userId,
                    $vendorEarning,
                    'order_earning',
                    "Penjualan pesanan #{$dOrder['order_code']}",
                    (string)$dOrder['id']
                );
            }
        }

        $wallet = $this->walletModel->getOrCreate($userId, 'vendor');

        $this->ensureHppColumn(); // Pastikan kolom hpp ada

        // Detailed Statistics strictly from orders table for this store
        $stats = Database::fetchOne(
            "SELECT 
                COUNT(*) as total_orders,
                COALESCE(SUM(CASE WHEN order_status = 'delivered' THEN order_amount * 0.90 ELSE 0 END), 0) as total_revenue,
                COALESCE(SUM(CASE WHEN order_status = 'delivered' THEN order_amount ELSE 0 END), 0) as gross_sales,
                COUNT(CASE WHEN order_status IN ('pending', 'confirmed') THEN 1 END) as pending_count,
                COUNT(CASE WHEN order_status = 'processing' THEN 1 END) as processing_count,
                COUNT(CASE WHEN order_status IN ('handover', 'picked_up', 'on_the_way') THEN 1 END) as on_the_way_count,
                COUNT(CASE WHEN order_status = 'delivered' THEN 1 END) as delivered_count,
                COUNT(CASE WHEN order_status = 'canceled' THEN 1 END) as canceled_count,
                COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_orders,
                COALESCE(SUM(CASE WHEN order_status = 'delivered' AND DATE(created_at) = CURDATE() THEN order_amount * 0.90 ELSE 0 END), 0) as today_revenue
             FROM `orders`
             WHERE `store_id` = ?",
            [$storeId]
        );

        // Profit hari ini & bulan ini — dari detail riwayat pesanan (hpp_snapshot historis)
        $profitStats = Database::fetchOne(
            "SELECT
                COALESCE(SUM(CASE WHEN DATE(o.created_at) = CURDATE()
                    THEN oi.quantity * (oi.price - COALESCE(oi.hpp_snapshot, p.hpp, 0)) ELSE 0 END), 0) as today_profit,
                COALESCE(SUM(CASE WHEN MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())
                    THEN oi.quantity * (oi.price - COALESCE(oi.hpp_snapshot, p.hpp, 0)) ELSE 0 END), 0) as month_profit,
                COALESCE(SUM(oi.quantity * (oi.price - COALESCE(oi.hpp_snapshot, p.hpp, 0))), 0) as total_profit,
                CASE WHEN SUM(oi.quantity * oi.price) > 0
                    THEN ROUND(SUM(oi.quantity * (oi.price - COALESCE(oi.hpp_snapshot, p.hpp, 0))) / SUM(oi.quantity * oi.price) * 100, 2)
                    ELSE 0 END as avg_margin_pct
             FROM order_items oi
             JOIN orders o ON oi.order_id = o.id
             LEFT JOIN products p ON oi.product_id = p.id
             WHERE o.store_id = ? AND o.order_status = 'delivered'",
            [$storeId]
        );

        if ($stats && $profitStats) {
            $stats['today_profit']   = (float)($profitStats['today_profit'] ?? 0);
            $stats['month_profit']   = (float)($profitStats['month_profit'] ?? 0);
            $stats['total_profit']   = (float)($profitStats['total_profit'] ?? 0);
            $stats['avg_margin_pct'] = (float)($profitStats['avg_margin_pct'] ?? 0);
        }

        $orders = $this->orderModel->getStoreOrders($storeId);
        $productsCount = $this->productModel->count('store_id = ?', [$storeId]);
        
        // Recalculate & fetch reviews
        $reviewModel = new \App\Models\Review();
        $reviewModel->recalculateStoreRating($storeId);
        $store = $this->storeModel->find($storeId);
        $reviews = $reviewModel->getStoreReviews($storeId, 10);

        if ($this->isJsonRequest()) {
            $this->successResponse('Dashboard merchant berhasil diambil', [
                'store'          => $store,
                'orders'         => array_slice($orders, 0, 10),
                'total_orders'   => (int)($stats['total_orders'] ?? count($orders)),
                'stats'          => $stats,
                'reviews'        => $reviews,
                'products_count' => $productsCount,
                'wallet'         => $wallet,
            ]);
            return;
        }

        $this->view('vendor.dashboard', [
            'title'          => 'Dashboard Mitra Toko - ' . $store['name'],
            'store'          => $store,
            'orders'         => array_slice($orders, 0, 10),
            'total_orders'   => (int)($stats['total_orders'] ?? count($orders)),
            'stats'          => $stats,
            'reviews'        => $reviews,
            'products_count' => $productsCount,
            'wallet'         => $wallet,
            'active_tab'     => 'dashboard'
        ], 'vendor_layout');
    }

    public function toggleStoreStatus(): void
    {
        $userId = auth_id();
        $store = $this->storeModel->findByVendorId($userId);
        if (!$store) {
            $this->errorResponse('Toko tidak ditemukan');
            return;
        }

        $newStatus = $store['is_open'] ? 0 : 1;
        $this->storeModel->update($store['id'], ['is_open' => $newStatus]);

        $this->successResponse($newStatus ? 'Toko Anda sekarang BUKA untuk menerima pesanan.' : 'Toko Anda sekarang TUTUP.', [
            'is_open' => $newStatus
        ]);
    }

    public function orders(): void
    {
        $userId = auth_id();
        $store = $this->storeModel->findByVendorId($userId);
        $orders = $store ? $this->orderModel->getStoreOrders($store['id']) : [];

        if ($this->isJsonRequest()) {
            $this->successResponse('Daftar pesanan toko berhasil diambil', [
                'store'  => $store,
                'orders' => $orders,
            ]);
            return;
        }

        $this->view('vendor.orders', [
            'title'      => 'Manajemen Pesanan - CicalengkaGO Vendor',
            'store'      => $store,
            'orders'     => $orders,
            'active_tab' => 'orders'
        ], 'vendor_layout');
    }

    public function updateOrderStatus(): void
    {
        $data = $this->getPost();
        $orderId = (int)($data['order_id'] ?? 0);
        $status = sanitize($data['status'] ?? '');
        $deliveryType = sanitize($data['delivery_type'] ?? '');

        $order = $this->orderModel->find($orderId);
        if (!$order) {
            $this->errorResponse('Pesanan tidak ditemukan.');
            return;
        }

        $updateData = ['order_status' => $status];
        if (!empty($deliveryType)) {
            $updateData['delivery_type'] = $deliveryType;
        }

        if ($status === 'processing') {
            $updateData['processing_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'handover') {
            // Ditugaskan untuk dijemput / dilelang ke mitra driver
            $updateData['handover_at'] = date('Y-m-d H:i:s');
            $updateData['delivery_type'] = 'driver';
        } elseif ($status === 'on_the_way') {
            // Toko memilih mengantar sendiri (< 300m atau kurir internal)
            $updateData['picked_up_at'] = date('Y-m-d H:i:s');
            $updateData['delivery_type'] = 'merchant';
        } elseif ($status === 'delivered') {
            // Pesanan selesai diantar oleh toko
            $updateData['delivered_at'] = date('Y-m-d H:i:s');
            $updateData['payment_status'] = 'paid';

            // Auto-credit pendapatan toko (90%) HANYA untuk pesanan non-COD (Wallet / Online / Midtrans)
            // Untuk COD, pembayaran diterima langsung tunai di tangan oleh merchant/driver (tidak masuk saldo digital).
            $pMethod = strtolower($order['payment_method'] ?? 'cod');
            if ($pMethod !== 'cod') {
                $userId = auth_id();
                $vendorWallet = $this->walletModel->getOrCreate($userId, 'vendor');
                $alreadyCredited = Database::fetchOne(
                    "SELECT id FROM `wallet_transactions` WHERE `wallet_id` = ? AND `category` = 'order_earning' AND `reference_id` = ? LIMIT 1",
                    [$vendorWallet['id'], (string)$order['id']]
                );
                if (!$alreadyCredited) {
                    $vendorEarning = (float)$order['order_amount'] * 0.90;
                    $this->walletModel->credit(
                        $userId,
                        $vendorEarning,
                        'order_earning',
                        "Penjualan pesanan #{$order['order_code']}",
                        (string)$order['id']
                    );
                }
            }
        } elseif ($status === 'canceled') {
            $updateData['cancellation_reason'] = 'Dibatalkan oleh Mitra Toko';
            $updateData['canceled_at'] = date('Y-m-d H:i:s');
            Order::refundOrderIfPaid($order, 'Dibatalkan oleh Mitra Toko');
        }

        Database::update('orders', $updateData, 'id = ?', [$orderId]);
        $this->successResponse("Status pesanan #{$order['order_code']} berhasil diperbarui menjadi {$status}.", [
            'order_id'      => $orderId,
            'order_status'  => $status,
            'delivery_type' => $updateData['delivery_type'] ?? ($order['delivery_type'] ?? 'driver')
        ]);
    }

    public function products(): void
    {
        $userId = auth_id();
        $store = $this->storeModel->findByVendorId($userId);
        $products = $store ? $this->productModel->getByStore($store['id']) : [];

        if ($this->isJsonRequest()) {
            $this->successResponse('Daftar produk toko berhasil diambil', [
                'store'    => $store,
                'products' => $products,
            ]);
            return;
        }

        $this->view('vendor.products', [
            'title'      => 'Katalog Produk & Menu - ' . ($store['name'] ?? ''),
            'store'      => $store,
            'products'   => $products,
            'active_tab' => 'products'
        ], 'vendor_layout');
    }

    public function productForm(?int $id = null): void
    {
        $userId = auth_id();
        $store = $this->storeModel->findByVendorId($userId);
        if (!$store) {
            $this->redirect('vendor');
            return;
        }

        $product = $id ? $this->productModel->findWithDetails($id) : null;
        $categories = (new Category())->getByModule($store['module_id']);

        $this->view('vendor.product_form', [
            'title'      => $id ? 'Edit Menu / Produk' : 'Tambah Menu / Produk Baru',
            'store'      => $store,
            'product'    => $product,
            'categories' => $categories,
            'active_tab' => 'products'
        ], 'vendor_layout');
    }

    public function saveProduct(): void
    {
        $this->ensureHppColumn(); // Auto-add hpp column jika belum ada

        $userId = auth_id();
        $store = $this->storeModel->findByVendorId($userId);
        if (!$store) {
            $this->errorResponse('Toko tidak ditemukan.');
            return;
        }

        $data = $this->getPost();
        $id = !empty($data['id']) ? (int)$data['id'] : null;

        // Ambil data produk eksisting jika update
        $existingProduct = $id ? $this->productModel->find($id) : null;

        // Tentukan foto produk: upload baru -> foto eksisting -> string existing_image -> default
        $imagePath = $existingProduct['image'] ?? ($data['existing_image'] ?? 'assets/images/products/default.jpg');
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploaded = upload_image($_FILES['image'], 'products');
            if ($uploaded) {
                $imagePath = $uploaded;
            }
        }

        $productData = [
            'store_id'       => $store['id'],
            'module_id'      => $store['module_id'],
            'category_id'    => !empty($data['category_id']) ? (int)$data['category_id'] : ($existingProduct ? (int)$existingProduct['category_id'] : 1),
            'name'           => sanitize($data['name'] ?? ($existingProduct['name'] ?? '')),
            'barcode'        => isset($data['barcode']) ? sanitize($data['barcode']) : ($existingProduct['barcode'] ?? null),
            'description'    => sanitize($data['description'] ?? ($existingProduct['description'] ?? '')),
            'image'          => $imagePath,
            'price'          => isset($data['price']) ? (float)$data['price'] : (float)($existingProduct['price'] ?? 0),
            'hpp'            => isset($data['hpp']) ? (float)$data['hpp'] : (float)($existingProduct['hpp'] ?? 0),
            'discount'       => isset($data['discount']) ? (float)$data['discount'] : (float)($existingProduct['discount'] ?? 0),
            'discount_type'  => $data['discount_type'] ?? ($existingProduct['discount_type'] ?? 'percent'),
            'unit'           => sanitize($data['unit'] ?? ($existingProduct['unit'] ?? 'porsi')),
            'stock'          => isset($data['stock']) ? (int)$data['stock'] : ($existingProduct ? (int)$existingProduct['stock'] : 100),
            'is_veg'         => isset($data['is_veg']) ? (!empty($data['is_veg']) ? 1 : 0) : ($existingProduct['is_veg'] ?? 0),
            'is_recommended' => isset($data['is_recommended']) ? (!empty($data['is_recommended']) ? 1 : 0) : ($existingProduct['is_recommended'] ?? 0),
            'status'         => 1
        ];

        if ($id) {
            $this->productModel->update($id, $productData);
        } else {
            $id = $this->productModel->create($productData);
        }

        // Handle Variations (Ukuran / Level / Porsi)
        $variationsData = $data['variations'] ?? ($data['variations_json'] ?? null);
        if (is_string($variationsData)) {
            $variationsData = json_decode($variationsData, true);
        }
        if (is_array($variationsData)) {
            \App\Core\Database::query("DELETE FROM `product_variations` WHERE `product_id` = ?", [$id]);
            foreach ($variationsData as $var) {
                $vName = trim($var['name'] ?? '');
                if (!empty($vName)) {
                    $vPrice = (float)($var['price'] ?? 0);
                    $vStock = (int)($var['stock'] ?? 100);
                    \App\Core\Database::query(
                        "INSERT INTO `product_variations` (`product_id`, `name`, `price`, `stock`) VALUES (?, ?, ?, ?)",
                        [$id, $vName, $vPrice, $vStock]
                    );
                }
            }
        }

        // Handle Addons (Toppings)
        $addonsData = $data['addons'] ?? ($data['addons_json'] ?? null);
        if (is_string($addonsData)) {
            $addonsData = json_decode($addonsData, true);
        }
        if (is_array($addonsData)) {
            foreach ($addonsData as $addon) {
                $aName = trim($addon['name'] ?? '');
                if (!empty($aName)) {
                    $aPrice = (float)($addon['price'] ?? 0);
                    $exist = \App\Core\Database::fetchOne("SELECT id FROM `product_addons` WHERE `store_id` = ? AND `name` = ? LIMIT 1", [$store['id'], $aName]);
                    if (!$exist) {
                        \App\Core\Database::query(
                            "INSERT INTO `product_addons` (`store_id`, `name`, `price`, `status`) VALUES (?, ?, ?, 1)",
                            [$store['id'], $aName, $aPrice]
                        );
                    }
                }
            }
        }

        if ($this->isJsonRequest()) {
            $this->successResponse('Menu produk berhasil disimpan!', [
                'product_id' => $id,
                'product'    => $this->productModel->findWithDetails($id)
            ]);
            return;
        }

        $this->redirect('vendor/products');
    }

    public function deleteProduct(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        $this->productModel->delete($id);
        $this->successResponse('Produk berhasil dihapus.');
    }

    public function toggleProductStatus(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        $field = sanitize($data['field'] ?? 'status'); // 'status' or 'stock'

        $product = $this->productModel->find($id);
        if (!$product) {
            $this->errorResponse('Produk tidak ditemukan.');
            return;
        }

        if ($field === 'stock') {
            $newVal = ((int)$product['stock'] > 0) ? 0 : 50;
            $this->productModel->update($id, ['stock' => $newVal]);
            $this->successResponse($newVal > 0 ? 'Stok produk diaktifkan kembali.' : 'Stok produk ditandai habis.', [
                'stock' => $newVal
            ]);
        } else {
            $newVal = $product['status'] ? 0 : 1;
            $this->productModel->update($id, ['status' => $newVal]);
            $this->successResponse($newVal ? 'Menu produk diaktifkan di etalase.' : 'Menu produk dinonaktifkan dari etalase.', [
                'status' => $newVal
            ]);
        }
    }

    /**
     * Pastikan kolom hpp ada di tabel products — auto-migrate jika belum
     */
    private function ensureHppColumn(): void
    {
        try {
            // 1. Kolom hpp di products
            $cols = Database::query("SHOW COLUMNS FROM `products` LIKE 'hpp'");
            if (empty($cols)) {
                Database::query("ALTER TABLE `products` ADD COLUMN `hpp` DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Harga Pokok Penjualan per unit' AFTER `price`");
                error_log('[VendorController] Auto-migrated: kolom hpp ditambahkan ke products.');
            }

            // 2. Kolom hpp_snapshot di order_items (snapshot HPP saat transaksi, untuk akurasi historis)
            $hppSnap = Database::query("SHOW COLUMNS FROM `order_items` LIKE 'hpp_snapshot'");
            if (empty($hppSnap)) {
                Database::query("ALTER TABLE `order_items` ADD COLUMN `hpp_snapshot` DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'HPP saat order dibuat (snapshot)' AFTER `total_price`");
                error_log('[VendorController] Auto-migrated: kolom hpp_snapshot ditambahkan ke order_items.');

                // Backfill: isi hpp_snapshot dari products.hpp saat ini untuk order lama
                Database::query("UPDATE `order_items` oi LEFT JOIN `products` p ON oi.product_id = p.id SET oi.hpp_snapshot = COALESCE(p.hpp, 0) WHERE oi.hpp_snapshot = 0");
                error_log('[VendorController] Backfill hpp_snapshot selesai untuk order lama.');
            }

            // 3. Kolom product_image_snapshot di order_items (snapshot foto saat transaksi)
            $imgSnap = Database::query("SHOW COLUMNS FROM `order_items` LIKE 'product_image_snapshot'");
            if (empty($imgSnap)) {
                Database::query("ALTER TABLE `order_items` ADD COLUMN `product_image_snapshot` VARCHAR(512) NULL DEFAULT NULL COMMENT 'Foto produk saat order dibuat (snapshot)' AFTER `hpp_snapshot`");
                error_log('[VendorController] Auto-migrated: kolom product_image_snapshot ditambahkan ke order_items.');

                // Backfill: isi dari products.image untuk order lama
                Database::query("UPDATE `order_items` oi LEFT JOIN `products` p ON oi.product_id = p.id SET oi.product_image_snapshot = p.image WHERE oi.product_image_snapshot IS NULL AND p.image IS NOT NULL");
                error_log('[VendorController] Backfill product_image_snapshot selesai untuk order lama.');
            }
        } catch (\Throwable $e) {
            error_log('[VendorController] ensureHppColumn failed: ' . $e->getMessage());
        }
    }

    /**
     * Stock-In: Tambah stok produk + update HPP + recalculate harga jual
     * Markup % dipertahankan: price_baru = hpp_baru * (1 + markup%)
     * POST /vendor/products/stock-in
     * Body: { product_id, qty_in, new_hpp }
     */
    public function stockIn(): void
    {
        $this->ensureHppColumn(); // Auto-add hpp column jika belum ada

        $userId = auth_id();
        $store  = $this->storeModel->findByVendorId($userId);
        if (!$store) {
            $this->errorResponse('Toko tidak ditemukan.');
            return;
        }

        $data      = $this->getPost();
        $productId = (int)($data['product_id'] ?? 0);
        $qtyIn     = max(0, (int)($data['qty_in'] ?? 0));
        $newHpp    = max(0, (float)($data['new_hpp'] ?? 0));

        if ($productId <= 0) {
            $this->errorResponse('ID produk tidak valid.');
            return;
        }

        $product = $this->productModel->find($productId);
        if (!$product || (int)$product['store_id'] !== (int)$store['id']) {
            $this->errorResponse('Produk tidak ditemukan di toko Anda.');
            return;
        }

        $currentPrice = (float)($product['price'] ?? 0);
        $currentHpp   = (float)($product['hpp'] ?? 0);

        // Hitung markup saat ini (dari hpp lama)
        // markup = (price - hpp) / hpp  — jika hpp = 0, markup = 0 (profit = 0)
        $markupRate = ($currentHpp > 0)
            ? (($currentPrice - $currentHpp) / $currentHpp)
            : 0.0;

        // Harga jual baru = hpp_baru * (1 + markup)
        $newPrice = ($newHpp > 0)
            ? round($newHpp * (1 + $markupRate))
            : $currentPrice; // kalau hpp tidak diisi, jangan ubah harga

        // Stok baru = stok lama + qty_in
        $newStock = max(0, (int)($product['stock'] ?? 0) + $qtyIn);

        $updateData = [
            'stock' => $newStock,
        ];
        if ($newHpp > 0) {
            $updateData['hpp']   = $newHpp;
            $updateData['price'] = $newPrice;
        }

        $this->productModel->update($productId, $updateData);

        $updatedProduct = $this->productModel->find($productId);

        $this->successResponse('Stok & HPP produk berhasil diperbarui!', [
            'product_id'   => $productId,
            'product_name' => $product['name'],
            'qty_added'    => $qtyIn,
            'new_stock'    => $newStock,
            'old_hpp'      => $currentHpp,
            'new_hpp'      => $newHpp > 0 ? $newHpp : $currentHpp,
            'markup_pct'   => round($markupRate * 100, 2),
            'old_price'    => $currentPrice,
            'new_price'    => $newHpp > 0 ? $newPrice : $currentPrice,
            'product'      => $updatedProduct,
        ]);
    }

    /**
     * Cari produk berdasarkan barcode — untuk modal Stock-In saat scan
     * GET /vendor/products/find-by-barcode?barcode=xxxx
     */
    public function findProductByBarcode(): void
    {
        $userId  = auth_id();
        $store   = $this->storeModel->findByVendorId($userId);
        if (!$store) {
            $this->errorResponse('Toko tidak ditemukan.');
            return;
        }

        $barcode = trim($_GET['barcode'] ?? '');
        if (empty($barcode)) {
            $this->errorResponse('Barcode tidak boleh kosong.');
            return;
        }

        $product = Database::fetchOne(
            "SELECT * FROM `products` WHERE `store_id` = ? AND `barcode` = ? LIMIT 1",
            [$store['id'], $barcode]
        );

        if (!$product) {
            // Coba cari berdasarkan nama jika barcode tidak ketemu
            $this->successResponse('Produk tidak ditemukan dengan barcode tersebut.', [
                'found'   => false,
                'product' => null,
            ]);
            return;
        }

        $product['hpp']   = (float)($product['hpp'] ?? 0);
        $product['price'] = (float)($product['price'] ?? 0);
        $product['stock'] = (int)($product['stock'] ?? 0);

        // Hitung markup pct saat ini
        $markupPct = ($product['hpp'] > 0)
            ? round((($product['price'] - $product['hpp']) / $product['hpp']) * 100, 2)
            : 0.0;
        $product['markup_pct'] = $markupPct;

        $this->successResponse('Produk ditemukan!', [
            'found'   => true,
            'product' => $product,
        ]);
    }

    /**
     * One-time migration: Add hpp column to products table
     * GET /vendor/migrate-hpp
     */
    public function migrateHpp(): void
    {
        try {
            $cols = Database::query("SHOW COLUMNS FROM `products` LIKE 'hpp'");
            if (empty($cols)) {
                Database::query("ALTER TABLE `products` ADD COLUMN `hpp` DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Harga Pokok Penjualan per unit' AFTER `price`");
                $this->successResponse('Kolom hpp berhasil ditambahkan ke tabel products!');
            } else {
                $this->successResponse('Kolom hpp sudah ada, tidak perlu migrasi ulang.');
            }
        } catch (\Exception $e) {
            $this->errorResponse('Migrasi gagal: ' . $e->getMessage());
        }
    }

    public function checkNewOrders(): void
    {
        $userId = auth_id();
        $store = $this->storeModel->findByVendorId($userId);
        if (!$store) {
            $this->errorResponse('Toko tidak ditemukan.');
            return;
        }

        $res = Database::fetchOne(
            "SELECT COUNT(*) as pending_count FROM `orders` WHERE `store_id` = ? AND `order_status` IN ('pending', 'confirmed')",
            [$store['id']]
        );

        $this->successResponse('Order check success', [
            'pending_count' => (int)($res['pending_count'] ?? 0)
        ]);
    }

    public function wallet(): void
    {
        $userId = auth_id();
        $wallet = $this->walletModel->getOrCreate($userId, 'vendor');
        $transactions = $this->walletModel->getTransactions($userId, 50);
        $withdrawRequests = $this->withdrawModel->getByUser($userId, 'vendor', 50);
        $totalWithdrawn = $this->withdrawModel->getTotalWithdrawn($userId, 'vendor');
        $pendingWithdrawn = $this->withdrawModel->getPendingWithdrawn($userId, 'vendor');

        if ($this->isJsonRequest()) {
            $this->successResponse('Data dompet toko berhasil diambil', [
                'wallet'            => $wallet,
                'transactions'      => $transactions,
                'withdraw_requests' => $withdrawRequests,
                'total_withdrawn'   => $totalWithdrawn,
                'pending_withdrawn' => $pendingWithdrawn,
            ]);
            return;
        }

        $this->view('vendor.wallet', [
            'title'             => 'Dompet & Pendapatan Toko',
            'wallet'            => $wallet,
            'transactions'      => $transactions,
            'withdraw_requests' => $withdrawRequests,
            'total_withdrawn'   => $totalWithdrawn,
            'pending_withdrawn' => $pendingWithdrawn,
            'active_tab'        => 'wallet'
        ], 'vendor_layout');
    }

    public function analytics(): void
    {
        $userId = auth_id();
        $store = $this->storeModel->findByVendorId($userId);
        if (!$store) {
            $this->errorResponse('Toko belum terdaftar.');
            return;
        }

        $storeId = (int)$store['id'];
        $this->ensureHppColumn(); // Pastikan kolom hpp ada

        // 1. KPI Summary — termasuk profit dari HPP
        $kpi = Database::fetchOne(
            "SELECT
                COUNT(*) as total_orders,
                COUNT(CASE WHEN order_status = 'delivered' THEN 1 END) as delivered_count,
                COUNT(CASE WHEN order_status = 'canceled' THEN 1 END) as canceled_count,
                COALESCE(SUM(CASE WHEN order_status = 'delivered' THEN order_amount ELSE 0 END), 0) as total_gross_sales,
                COALESCE(SUM(CASE WHEN order_status = 'delivered' THEN order_amount * 0.90 ELSE 0 END), 0) as total_net_revenue,
                COALESCE(AVG(CASE WHEN order_status = 'delivered' THEN order_amount ELSE NULL END), 0) as avg_order_value,

                -- Hari ini
                COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_orders,
                COALESCE(SUM(CASE WHEN order_status = 'delivered' AND DATE(created_at) = CURDATE() THEN order_amount * 0.90 ELSE 0 END), 0) as today_revenue,

                -- 7 Hari
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_orders,
                COALESCE(SUM(CASE WHEN order_status = 'delivered' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN order_amount * 0.90 ELSE 0 END), 0) as week_revenue,

                -- Bulan Ini
                COUNT(CASE WHEN MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) THEN 1 END) as month_orders,
                COALESCE(SUM(CASE WHEN order_status = 'delivered' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) THEN order_amount * 0.90 ELSE 0 END), 0) as month_revenue
             FROM `orders`
             WHERE `store_id` = ?",
            [$storeId]
        );

        // 1b. Profit dari detail pesanan historis (pakai hpp_snapshot & oi.price)
        $profitKpi = Database::fetchOne(
            "SELECT
                COALESCE(SUM(oi.quantity * (oi.price - COALESCE(oi.hpp_snapshot, p.hpp, 0))), 0) as total_gross_profit,
                COALESCE(SUM(CASE WHEN MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())
                    THEN oi.quantity * (oi.price - COALESCE(oi.hpp_snapshot, p.hpp, 0)) ELSE 0 END), 0) as month_profit,
                COALESCE(SUM(CASE WHEN o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    THEN oi.quantity * (oi.price - COALESCE(oi.hpp_snapshot, p.hpp, 0)) ELSE 0 END), 0) as week_profit,
                COALESCE(SUM(CASE WHEN DATE(o.created_at) = CURDATE()
                    THEN oi.quantity * (oi.price - COALESCE(oi.hpp_snapshot, p.hpp, 0)) ELSE 0 END), 0) as today_profit,
                COALESCE(SUM(oi.quantity * COALESCE(oi.hpp_snapshot, p.hpp, 0)), 0) as total_cogs,
                CASE WHEN SUM(oi.quantity * oi.price) > 0
                    THEN ROUND(SUM(oi.quantity * (oi.price - COALESCE(oi.hpp_snapshot, p.hpp, 0))) / SUM(oi.quantity * oi.price) * 100, 2)
                    ELSE 0 END as avg_margin_pct
             FROM `order_items` oi
             JOIN `orders` o ON oi.order_id = o.id
             LEFT JOIN `products` p ON oi.product_id = p.id
             WHERE o.store_id = ? AND o.order_status = 'delivered'",
            [$storeId]
        );

        // Merge profit ke kpi
        if ($kpi && $profitKpi) {
            $kpi['total_gross_profit'] = (float)($profitKpi['total_gross_profit'] ?? 0);
            $kpi['month_profit']       = (float)($profitKpi['month_profit'] ?? 0);
            $kpi['week_profit']        = (float)($profitKpi['week_profit'] ?? 0);
            $kpi['today_profit']       = (float)($profitKpi['today_profit'] ?? 0);
            $kpi['total_cogs']         = (float)($profitKpi['total_cogs'] ?? 0);
            $kpi['avg_margin_pct']     = (float)($profitKpi['avg_margin_pct'] ?? 0);
        }

        // 2. Trend 7 Hari — Revenue + Profit
        $dailyTrends = [];
        for ($i = 6; $i >= 0; $i--) {
            $date    = date('Y-m-d', strtotime("-$i days"));
            $dayName = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'][date('w', strtotime($date))];

            $dayRow = Database::fetchOne(
                "SELECT
                    COUNT(CASE WHEN o.order_status = 'delivered' THEN 1 END) as delivered_orders,
                    COUNT(*) as total_orders,
                    COALESCE(SUM(CASE WHEN o.order_status = 'delivered' THEN o.order_amount * 0.90 ELSE 0 END), 0) as net_revenue,
                    COALESCE(SUM(CASE WHEN o.order_status = 'delivered' THEN o.order_amount ELSE 0 END), 0) as gross_revenue,
                    COALESCE((
                        SELECT SUM(oi2.quantity * (oi2.price - COALESCE(oi2.hpp_snapshot, p2.hpp, 0)))
                        FROM order_items oi2
                        JOIN orders o2 ON oi2.order_id = o2.id
                        LEFT JOIN products p2 ON oi2.product_id = p2.id
                        WHERE o2.store_id = ? AND DATE(o2.created_at) = ? AND o2.order_status = 'delivered'
                    ), 0) as profit
                 FROM `orders` o
                 WHERE o.`store_id` = ? AND DATE(o.created_at) = ?",
                [$storeId, $date, $storeId, $date]
            );

            $dailyTrends[] = [
                'date'             => $date,
                'day_name'         => $dayName,
                'formatted_date'   => date('d M', strtotime($date)),
                'delivered_orders' => (int)($dayRow['delivered_orders'] ?? 0),
                'total_orders'     => (int)($dayRow['total_orders'] ?? 0),
                'revenue'          => (float)($dayRow['net_revenue'] ?? 0),
                'gross_revenue'    => (float)($dayRow['gross_revenue'] ?? 0),
                'profit'           => (float)($dayRow['profit'] ?? 0),
            ];
        }

        // 3. Menu Terlaris + Profit per item (pakai hpp_snapshot untuk akurasi historis)
        $topProducts = Database::query(
            "SELECT
                COALESCE(NULLIF(oi.product_name, ''), p.name, 'Menu Kuliner') as product_name,
                COALESCE(oi.product_image_snapshot, p.image) as product_image,
                COALESCE(p.price, oi.price) as product_price,
                COALESCE(oi.hpp_snapshot, p.hpp, 0) as product_hpp,
                SUM(oi.quantity) as total_sold,
                SUM(oi.quantity * oi.price) as total_sales_amount,
                SUM(oi.quantity * (oi.price - COALESCE(oi.hpp_snapshot, p.hpp, 0))) as total_profit,
                CASE WHEN SUM(oi.quantity * oi.price) > 0
                    THEN ROUND(SUM(oi.quantity * (oi.price - COALESCE(oi.hpp_snapshot, p.hpp, 0))) / SUM(oi.quantity * oi.price) * 100, 2)
                    ELSE 0 END as margin_pct
             FROM `order_items` oi
             JOIN `orders` o ON oi.order_id = o.id
             LEFT JOIN `products` p ON oi.product_id = p.id
             WHERE o.store_id = ? AND o.order_status = 'delivered'
             GROUP BY oi.product_id, product_name, product_image, product_price, product_hpp
             ORDER BY total_sold DESC
             LIMIT 8",
            [$storeId]
        );

        // 4. Transaksi terakhir dengan profit per pesanan (pakai hpp_snapshot)
        $recentOrders = Database::query(
            "SELECT
                o.id,
                o.order_code,
                o.order_amount,
                o.order_amount * 0.90 as net_amount,
                o.order_status,
                o.created_at,
                COALESCE((
                    SELECT SUM(oi2.quantity * (oi2.price - COALESCE(oi2.hpp_snapshot, p2.hpp, 0)))
                    FROM order_items oi2
                    LEFT JOIN products p2 ON oi2.product_id = p2.id
                    WHERE oi2.order_id = o.id
                ), 0) as order_profit,
                COALESCE((
                    SELECT SUM(oi2.quantity * COALESCE(oi2.hpp_snapshot, p2.hpp, 0))
                    FROM order_items oi2
                    LEFT JOIN products p2 ON oi2.product_id = p2.id
                    WHERE oi2.order_id = o.id
                ), 0) as order_cogs
             FROM `orders` o
             WHERE o.store_id = ?
             ORDER BY o.created_at DESC
             LIMIT 15",
            [$storeId]
        );

        // Hitung margin_pct per order
        foreach ($recentOrders as &$ord) {
            $gross = (float)($ord['order_amount'] ?? 0);
            $profit = (float)($ord['order_profit'] ?? 0);
            $ord['margin_pct'] = $gross > 0 ? round($profit / $gross * 100, 1) : 0;
            $ord['order_profit'] = $profit;
            $ord['order_cogs']   = (float)($ord['order_cogs'] ?? 0);
        }
        unset($ord);

        // 5. Breakdown Metode Pembayaran
        $paymentBreakdown = Database::query(
            "SELECT
                COALESCE(payment_method, 'cod') as payment_method,
                COUNT(*) as count,
                COALESCE(SUM(order_amount), 0) as total_amount
             FROM `orders`
             WHERE store_id = ? AND order_status = 'delivered'
             GROUP BY payment_method",
            [$storeId]
        );

        // 6. Breakdown Tipe Pengantaran
        $deliveryBreakdown = Database::query(
            "SELECT
                COALESCE(delivery_type, 'driver') as delivery_type,
                COUNT(*) as count
             FROM `orders`
             WHERE store_id = ? AND order_status = 'delivered'
             GROUP BY delivery_type",
            [$storeId]
        );

        $wallet = $this->walletModel->getOrCreate($userId, 'vendor');

        $this->successResponse('Statistik dan insight penjualan toko berhasil diambil', [
            'store'               => $store,
            'kpi'                 => $kpi,
            'daily_trends'        => $dailyTrends,
            'top_products'        => $topProducts,
            'recent_orders'       => $recentOrders,
            'payment_breakdown'   => $paymentBreakdown,
            'delivery_breakdown'  => $deliveryBreakdown,
            'wallet'              => $wallet,
        ]);
    }

    public function posCheckout(): void
    {
        $userId = auth_id();
        $store = $this->storeModel->findByVendorId($userId);
        if (!$store) {
            $this->errorResponse('Toko tidak ditemukan.');
            return;
        }

        $storeId = (int)$store['id'];
        $data = $this->getPost();

        $items = $data['items'] ?? [];
        if (is_string($items)) {
            $items = json_decode($items, true) ?: [];
        }

        if (empty($items)) {
            $this->errorResponse('Keranjang kasir masih kosong.');
            return;
        }

        $customerName = sanitize($data['customer_name'] ?? 'Pelanggan Langsung (POS)');
        $customerPhone = sanitize($data['customer_phone'] ?? '-');
        $paymentMethod = strtolower($data['payment_method'] ?? 'cash');
        $notes = sanitize($data['notes'] ?? 'Transaksi Kasir POS Toko');
        $cashGiven = (float)($data['cash_given'] ?? 0);
        $discountAmount = (float)($data['discount_amount'] ?? 0);

        $orderAmount = 0.0;
        $orderItemsToInsert = [];

        foreach ($items as $item) {
            $pId = (int)($item['product_id'] ?? 0);
            $qty = max(1, (int)($item['quantity'] ?? 1));
            $price = (float)($item['price'] ?? 0);

            $prod = $pId ? $this->productModel->find($pId) : null;
            $hpp = 0.0;
            $img = null;
            if ($prod) {
                if ($price <= 0) {
                    $price = (float)($prod['price'] ?? 0);
                }
                $pName = $prod['name'];
                $hpp   = (float)($prod['hpp'] ?? 0);
                $img   = $prod['image'] ?? null;
                
                // Kurangi stok jika tersedia
                if ((int)($prod['stock'] ?? 0) > 0) {
                    $newStock = max(0, (int)$prod['stock'] - $qty);
                    $this->productModel->update($pId, ['stock' => $newStock]);
                }
            } else {
                $pName = sanitize($item['name'] ?? 'Produk POS');
            }

            $itemSubtotal = $price * $qty;
            $orderAmount += $itemSubtotal;

            // Variation & Addons JSON
            $varJson = null;
            if (!empty($item['variation_name'])) {
                $varJson = json_encode(['id' => $item['variation_id'] ?? null, 'name' => $item['variation_name'], 'price' => $price]);
            }

            $addonsJson = null;
            if (!empty($item['addons']) && is_array($item['addons'])) {
                $addonsJson = json_encode(['items' => $item['addons']]);
            } elseif (!empty($item['addons_text'])) {
                $addonsJson = json_encode(['text' => $item['addons_text']]);
            }

            $orderItemsToInsert[] = [
                'product_id'             => $pId ?: null,
                'product_name'           => $pName,
                'quantity'               => $qty,
                'price'                  => $price,
                'total_price'            => $itemSubtotal,
                'variation_json'         => $varJson,
                'variation_name'         => $item['variation_name'] ?? null,
                'addons_json'            => $addonsJson,
                'addons_text'            => $item['addons_text'] ?? null,
                'hpp_snapshot'           => $hpp,
                'product_image_snapshot' => $img,
                'notes'                  => sanitize($item['notes'] ?? '')
            ];
        }

        $totalAmount = max(0, $orderAmount - $discountAmount);
        $changeAmount = ($paymentMethod === 'cash' && $cashGiven >= $totalAmount) ? ($cashGiven - $totalAmount) : 0.0;

        $orderCode = 'POS-' . strtoupper(substr(uniqid(), -5)) . date('is');
        $now = date('Y-m-d H:i:s');
        $otp = str_pad((string)rand(1000, 9999), 4, '0', STR_PAD_LEFT);

        // Simpan sebagai pesanan selesai langsung (POS Kasir Offline)
        $orderData = [
            'order_code'             => $orderCode,
            'customer_id'            => $userId,
            'store_id'               => $storeId,
            'module_id'              => (int)($store['module_id'] ?? 1),
            'zone_id'                => (int)($store['zone_id'] ?? 1),
            'order_amount'           => $orderAmount,
            'delivery_charge'        => 0.0,
            'tax_amount'             => 0.0,
            'coupon_discount'        => $discountAmount,
            'total_amount'           => $totalAmount,
            'payment_method'         => $paymentMethod === 'cash' ? 'cod' : $paymentMethod,
            'payment_status'         => 'paid',
            'order_status'           => 'delivered',
            'order_type'             => 'takeaway',
            'delivery_type'          => 'merchant',
            'order_notes'            => $notes . " [Customer: {$customerName}]",
            'delivery_address_json'  => json_encode(['address' => 'Kasir / Transaksi Langsung di Toko', 'customer_name' => $customerName, 'phone' => $customerPhone]),
            'otp'                    => $otp,
            'distance_km'            => 0.0,
            'confirmed_at'           => $now,
            'processing_at'          => $now,
            'picked_up_at'           => $now,
            'delivered_at'           => $now,
            'created_at'             => $now,
            'updated_at'             => $now,
        ];

        // Check if is_pos column exists in orders table
        try {
            $orderData['is_pos'] = 1;
            $orderId = Database::insert('orders', $orderData);
        } catch (Exception $e) {
            unset($orderData['is_pos']);
            $orderId = Database::insert('orders', $orderData);
        }

        foreach ($orderItemsToInsert as $oItem) {
            Database::insert('order_items', [
                'order_id'               => $orderId,
                'product_id'             => $oItem['product_id'],
                'product_name'           => $oItem['product_name'],
                'quantity'               => $oItem['quantity'],
                'price'                  => $oItem['price'],
                'total_price'            => $oItem['total_price'],
                'variation_json'         => $oItem['variation_json'],
                'addons_json'            => $oItem['addons_json'],
                'hpp_snapshot'           => $oItem['hpp_snapshot'],
                'product_image_snapshot' => $oItem['product_image_snapshot'],
            ]);
        }

        $this->successResponse('Transaksi kasir POS berhasil dicatat!', [
            'order_id'        => $orderId,
            'order_code'      => $orderCode,
            'order_amount'    => $orderAmount,
            'discount_amount' => $discountAmount,
            'total_amount'    => $totalAmount,
            'payment_method'  => $paymentMethod,
            'cash_given'      => $cashGiven,
            'change_amount'   => $changeAmount,
            'customer_name'   => $customerName,
            'created_at'      => date('d/m/Y H:i'),
            'store_name'      => $store['name'],
            'store_address'   => $store['address'] ?? '',
            'store_phone'     => $store['phone'] ?? '',
            'items'           => $orderItemsToInsert,
        ]);
    }

    public function smartInsights(): void
    {
        $this->ensureHppColumn();
        $userId = auth_id();
        $store = $this->storeModel->findByVendorId($userId);
        if (!$store) {
            $this->errorResponse('Toko tidak ditemukan.');
            return;
        }
        $storeId = (int)$store['id'];

        // 1. Low Stock Products (stock <= 5 and status = 1)
        $lowStockItems = Database::query(
            "SELECT id, name, price, COALESCE(hpp, 0) as hpp, stock, image, barcode 
             FROM products 
             WHERE store_id = :sid AND status = 1 AND stock <= 5 
             ORDER BY stock ASC LIMIT 15",
            [':sid' => $storeId]
        );

        // 2. Menu Engineering Matrix (Last 60 days of delivered orders)
        $productPerformance = Database::query(
            "SELECT 
                oi.product_id,
                COALESCE(p.name, oi.product_name) as name,
                COALESCE(oi.product_image_snapshot, p.image) as image,
                p.stock,
                COALESCE(p.price, oi.price) as price,
                COALESCE(oi.hpp_snapshot, p.hpp, 0) as hpp,
                SUM(oi.quantity) as total_sold,
                SUM(oi.total_price) as total_revenue,
                SUM((oi.price - COALESCE(oi.hpp_snapshot, p.hpp, 0)) * oi.quantity) as total_profit,
                AVG(CASE WHEN oi.price > 0 THEN ((oi.price - COALESCE(oi.hpp_snapshot, p.hpp, 0)) / oi.price * 100) ELSE 0 END) as avg_margin_pct
             FROM order_items oi
             JOIN orders o ON oi.order_id = o.id
             LEFT JOIN products p ON oi.product_id = p.id
             WHERE o.store_id = :sid AND o.order_status = 'delivered' AND o.created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
             GROUP BY oi.product_id, name, image, p.stock, price, hpp
             ORDER BY total_sold DESC",
            [':sid' => $storeId]
        );

        $totalSoldAll = 0;
        $itemCount = count($productPerformance);
        foreach ($productPerformance as $p) {
            $totalSoldAll += (int)$p['total_sold'];
        }
        $avgSold = $itemCount > 0 ? ($totalSoldAll / $itemCount) : 1;

        $starProducts = [];
        $potentialProducts = [];
        $thinMarginProducts = [];

        foreach ($productPerformance as $p) {
            $sold = (int)$p['total_sold'];
            $margin = (float)$p['avg_margin_pct'];
            $profit = (float)$p['total_profit'];
            $itemHpp = (float)$p['hpp'];

            $itemData = [
                'product_id'   => $p['product_id'],
                'name'         => $p['name'],
                'image'        => $p['image'],
                'stock'        => $p['stock'] !== null ? (int)$p['stock'] : 0,
                'price'        => (float)$p['price'],
                'hpp'          => $itemHpp,
                'total_sold'   => $sold,
                'total_profit' => $profit,
                'margin_pct'   => round($margin, 1),
            ];

            if ($sold >= $avgSold && $margin >= 25) {
                $itemData['badge'] = 'Bintang';
                $itemData['tip'] = 'Menu paling laris & menguntungkan. Pertahankan stok & pasang promo!';
                $starProducts[] = $itemData;
            } elseif ($sold < $avgSold && $margin >= 35 && $itemHpp > 0) {
                $itemData['badge'] = 'Potensial';
                $itemData['tip'] = 'Margin tinggi tapi penjualan masih rendah. Rekomendasi: Beri diskon flash sale!';
                $potentialProducts[] = $itemData;
            } elseif ($sold >= $avgSold && $margin < 20 && $itemHpp > 0) {
                $itemData['badge'] = 'Margin Tipis';
                $itemData['tip'] = 'Laris tapi laba bersih tipis. Rekomendasi: Naikkan harga sedikit atau optimasi HPP.';
                $thinMarginProducts[] = $itemData;
            }
        }

        // 3. Peak Hours Distribution (Last 30 days)
        $hourlyOrders = Database::query(
            "SELECT 
                HOUR(o.created_at) as order_hour,
                COUNT(o.id) as order_count,
                SUM(o.total_amount) as total_sales
             FROM orders o
             WHERE o.store_id = :sid AND o.order_status = 'delivered' AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY HOUR(o.created_at)
             ORDER BY order_hour ASC",
            [':sid' => $storeId]
        );

        $blocks = [
            'pagi'  => ['key' => 'pagi',  'label' => 'Pagi (06:00 - 10:59)', 'count' => 0, 'sales' => 0.0, 'icon' => 'wb_sunny_rounded'],
            'siang' => ['key' => 'siang', 'label' => 'Siang (11:00 - 14:59)', 'count' => 0, 'sales' => 0.0, 'icon' => 'restaurant_rounded'],
            'sore'  => ['key' => 'sore',  'label' => 'Sore (15:00 - 17:59)', 'count' => 0, 'sales' => 0.0, 'icon' => 'local_cafe_rounded'],
            'malam' => ['key' => 'malam', 'label' => 'Malam (18:00 - 23:59)', 'count' => 0, 'sales' => 0.0, 'icon' => 'nights_stay_rounded'],
        ];

        $peakHour = null;
        $maxPeakCount = -1;

        foreach ($hourlyOrders as $h) {
            $hr = (int)$h['order_hour'];
            $cnt = (int)$h['order_count'];
            $sls = (float)$h['total_sales'];

            if ($cnt > $maxPeakCount) {
                $maxPeakCount = $cnt;
                $peakHour = sprintf("%02d:00 - %02d:00", $hr, ($hr + 1) % 24);
            }

            if ($hr >= 6 && $hr < 11) {
                $blocks['pagi']['count'] += $cnt;
                $blocks['pagi']['sales'] += $sls;
            } elseif ($hr >= 11 && $hr < 15) {
                $blocks['siang']['count'] += $cnt;
                $blocks['siang']['sales'] += $sls;
            } elseif ($hr >= 15 && $hr < 18) {
                $blocks['sore']['count'] += $cnt;
                $blocks['sore']['sales'] += $sls;
            } elseif ($hr >= 18 || $hr < 6) {
                $blocks['malam']['count'] += $cnt;
                $blocks['malam']['sales'] += $sls;
            }
        }

        $busiestBlock = 'siang';
        $busiestCount = -1;
        foreach ($blocks as $k => $b) {
            if ($b['count'] > $busiestCount) {
                $busiestCount = $b['count'];
                $busiestBlock = $k;
            }
        }

        $this->successResponse('Smart Business Insights retrieved successfully.', [
            'low_stock_count'       => count($lowStockItems),
            'low_stock_items'       => $lowStockItems,
            'star_products'         => array_slice($starProducts, 0, 5),
            'potential_products'    => array_slice($potentialProducts, 0, 5),
            'thin_margin_products'  => array_slice($thinMarginProducts, 0, 5),
            'hourly_trends'         => $hourlyOrders,
            'time_blocks'           => array_values($blocks),
            'busiest_period'        => $blocks[$busiestBlock]['label'],
            'busiest_hour'          => $peakHour ?? 'Belum ada data',
        ]);
    }

    public function dailySettlement(): void
    {
        $this->ensureHppColumn();
        $userId = auth_id();
        $store = $this->storeModel->findByVendorId($userId);
        if (!$store) {
            $this->errorResponse('Toko tidak ditemukan.');
            return;
        }
        $storeId = (int)$store['id'];
        $date = sanitize($_GET['date'] ?? date('Y-m-d'));

        // 1. Orders Summary for the day
        $orders = Database::query(
            "SELECT 
                o.id, o.order_code, o.order_amount, o.delivery_charge, o.total_amount,
                o.payment_method, o.order_status, o.order_type, o.delivery_type, o.created_at,
                COALESCE(SUM(oi.hpp_snapshot * oi.quantity), 0) as total_cogs
             FROM orders o
             LEFT JOIN order_items oi ON o.id = oi.order_id
             WHERE o.store_id = :sid AND DATE(o.created_at) = :dt
             GROUP BY o.id, o.order_code, o.order_amount, o.delivery_charge, o.total_amount, o.payment_method, o.order_status, o.order_type, o.delivery_type, o.created_at
             ORDER BY o.created_at DESC",
            [':sid' => $storeId, ':dt' => $date]
        );

        $totalOrders = count($orders);
        $completedOrders = 0;
        $canceledOrders = 0;
        $grossSales = 0.0;
        $netRevenue = 0.0;
        $totalCogs = 0.0;
        $cashAmount = 0.0;
        $nonCashAmount = 0.0;
        $posOrdersCount = 0;
        $onlineOrdersCount = 0;

        foreach ($orders as $ord) {
            $st = $ord['order_status'];
            $payMethod = strtolower($ord['payment_method'] ?? 'cash');
            $isPos = ($ord['order_type'] === 'takeaway' && $ord['delivery_type'] === 'merchant') || (isset($ord['is_pos']) && $ord['is_pos'] == 1);
            $amount = (float)$ord['order_amount'];
            $cogs = (float)$ord['total_cogs'];

            if ($st === 'delivered') {
                $completedOrders++;
                $grossSales += $amount;
                $net = $amount * 0.90; // Net after platform commission
                $netRevenue += $net;
                $totalCogs += $cogs;

                if ($payMethod === 'cod' || $payMethod === 'cash') {
                    $cashAmount += $amount;
                } else {
                    $nonCashAmount += $amount;
                }

                if ($isPos) {
                    $posOrdersCount++;
                } else {
                    $onlineOrdersCount++;
                }
            } elseif ($st === 'canceled') {
                $canceledOrders++;
            }
        }

        $grossProfit = $netRevenue - $totalCogs;
        $marginPct = $netRevenue > 0 ? ($grossProfit / $netRevenue * 100) : 0.0;

        // Generate formatted WhatsApp message for owner
        $formattedDate = date('d F Y', strtotime($date));
        $storeName = $store['name'];
        $rpGross = number_format($grossSales, 0, ',', '.');
        $rpNet = number_format($netRevenue, 0, ',', '.');
        $rpCash = number_format($cashAmount, 0, ',', '.');
        $rpNonCash = number_format($nonCashAmount, 0, ',', '.');
        $rpCogs = number_format($totalCogs, 0, ',', '.');
        $rpProfit = number_format($grossProfit, 0, ',', '.');
        $marginStr = number_format($marginPct, 1);

        $waMessage = "*📊 LAPORAN TUTUP KASIR HARIAN*\n"
                   . "*Toko:* {$storeName}\n"
                   . "*Tanggal:* {$formattedDate}\n"
                   . "--------------------------------\n"
                   . "✅ *Pesanan Berhasil:* {$completedOrders} Transaksi\n"
                   . ($canceledOrders > 0 ? "❌ *Pesanan Batal:* {$canceledOrders} Transaksi\n" : "")
                   . "💰 *Total Omzet Kotor:* Rp {$rpGross}\n"
                   . "💵 *Kas Tunai (Cash):* Rp {$rpCash}\n"
                   . "💳 *Non-Tunai (Digital/Transfer):* Rp {$rpNonCash}\n"
                   . "--------------------------------\n"
                   . "📦 *Total Modal (HPP):* Rp {$rpCogs}\n"
                   . "💚 *Laba Bersih:* Rp {$rpProfit} ({$marginStr}%)\n"
                   . "--------------------------------\n"
                   . "Laporan digenerate otomatis oleh *CicalengkaGO Merchant*.";

        $this->successResponse('Daily settlement generated successfully.', [
            'date'               => $date,
            'formatted_date'     => $formattedDate,
            'store_name'         => $storeName,
            'total_orders'       => $totalOrders,
            'completed_orders'   => $completedOrders,
            'canceled_orders'    => $canceledOrders,
            'gross_sales'        => $grossSales,
            'net_revenue'        => $netRevenue,
            'cash_amount'        => $cashAmount,
            'non_cash_amount'    => $nonCashAmount,
            'total_cogs'         => $totalCogs,
            'gross_profit'       => $grossProfit,
            'margin_pct'         => round($marginPct, 1),
            'pos_orders_count'   => $posOrdersCount,
            'online_orders_count'=> $onlineOrdersCount,
            'wa_message'         => $waMessage,
            'recent_orders'      => array_slice($orders, 0, 15),
        ]);
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
            $this->errorResponse('Harap lengkapi informasi tujuan pencairan, nomor rekening/e-wallet, dan nama pemilik rekening.');
            return;
        }

        try {
            $req = $this->withdrawModel->requestPayout(
                $userId,
                'vendor',
                $amount,
                $bankName,
                $accNumber,
                $accHolder
            );

            $this->successResponse('Pengajuan penarikan dana berhasil! Dana akan diproses oleh tim CicalengkaGO.', [
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
        $store = $this->storeModel->findByVendorId($userId);
        if ($store) {
            attach_store_schedule_data($store);
        }

        if ($this->isJsonRequest()) {
            if ($user && isset($user['password'])) {
                unset($user['password']);
            }
            $this->successResponse('Data profil toko berhasil diambil', [
                'user'  => $user,
                'store' => $store,
            ]);
            return;
        }

        $this->view('vendor.profile', [
            'title'      => 'Profil Pemilik & Pengaturan Toko',
            'user'       => $user,
            'store'      => $store,
            'active_tab' => 'profile'
        ], 'vendor_layout');
    }

    public function updateProfile(): void
    {
        $data = $this->getPost();
        $userId = auth_id();
        if (!$userId) {
            $userId = (int)($data['user_id'] ?? $_POST['user_id'] ?? $_GET['user_id'] ?? 0);
            if (!$userId) {
                if ($this->isJsonRequest()) {
                    $this->errorResponse('Akses tidak terotentikasi. Silakan masuk kembali.', null, 401);
                    return;
                }
                $this->redirect('login');
                return;
            }
        }

        $userModel = new \App\Models\User();
        $dbUser = $userModel->find($userId);

        $name         = sanitize($data['name'] ?? ($data['store_name'] ?? ($dbUser['name'] ?? 'Mitra Resto')));
        $email        = sanitize($data['email'] ?? ($dbUser['email'] ?? ''));
        $phone        = sanitize($data['phone'] ?? ($data['store_phone'] ?? ($dbUser['phone'] ?? '')));
        $storeName    = sanitize($data['store_name'] ?? ($name ?: 'Toko Mitra'));
        $storeAddress = sanitize($data['store_address'] ?? ($data['address'] ?? ''));
        $storePhone   = sanitize($data['store_phone'] ?? ($phone ?: ''));
        $storeLat     = isset($data['latitude']) ? (float)$data['latitude'] : null;
        $storeLng     = isset($data['longitude']) ? (float)$data['longitude'] : null;

        if (empty($name) && empty($storeName)) {
            if ($this->isJsonRequest()) {
                $this->errorResponse('Nama resto wajib diisi.');
                return;
            }
            $_SESSION['error'] = 'Nama resto wajib diisi.';
            $this->redirect('vendor/profile');
            return;
        }

        // Handle User Avatar Upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $avatarPath = upload_image($_FILES['avatar'], 'profiles');
            if ($avatarPath) {
                $userModel->update($userId, ['avatar' => $avatarPath]);
                $_SESSION['user']['avatar'] = $avatarPath;
            }
        }

        $store = $this->storeModel->findByVendorId($userId);
        if (!$store && !empty($data['store_id'])) {
            $store = $this->storeModel->find((int)$data['store_id']);
        }

        if ($store) {
            $storeUpdates = [];
            if (!empty($storeName)) $storeUpdates['name'] = $storeName;
            if (!empty($storeAddress)) $storeUpdates['address'] = $storeAddress;
            if (!empty($storePhone)) $storeUpdates['phone'] = $storePhone;
            if ($storeLat !== null && $storeLng !== null && $storeLat != 0 && $storeLng != 0) {
                $storeUpdates['latitude'] = $storeLat;
                $storeUpdates['longitude'] = $storeLng;
            }

            // Sync full store settings from live schema
            if (isset($data['minimum_order'])) $storeUpdates['minimum_order'] = (float)$data['minimum_order'];
            if (isset($data['delivery_time'])) $storeUpdates['delivery_time'] = sanitize($data['delivery_time']);
            if (isset($data['tax'])) {
                $storeUpdates['tax'] = (float)$data['tax'];
                $storeUpdates['tax_percent'] = (float)$data['tax'];
            }
            if (isset($data['service_charge'])) $storeUpdates['service_charge'] = (float)$data['service_charge'];
            if (isset($data['is_open'])) $storeUpdates['is_open'] = (!empty($data['is_open']) && $data['is_open'] != '0') ? 1 : 0;
            if (isset($data['opening_time'])) {
                $storeUpdates['opening_time'] = !empty($data['opening_time']) ? date('H:i:s', strtotime($data['opening_time'])) : '08:00:00';
            }
            if (isset($data['closing_time'])) {
                $storeUpdates['closing_time'] = !empty($data['closing_time']) ? date('H:i:s', strtotime($data['closing_time'])) : '22:00:00';
            }
            if (isset($data['bank_name'])) $storeUpdates['bank_name'] = sanitize($data['bank_name']);
            if (isset($data['bank_account_number'])) $storeUpdates['bank_account_number'] = sanitize($data['bank_account_number']);
            if (isset($data['bank_account_name'])) $storeUpdates['bank_account_name'] = sanitize($data['bank_account_name']);

            // Handle Store Logo Upload
            if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === UPLOAD_ERR_OK) {
                $logoPath = upload_image($_FILES['store_logo'], 'stores');
                if ($logoPath) {
                    $storeUpdates['logo'] = $logoPath;
                }
            }

            // Handle Store Cover / Banner Upload
            if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] === UPLOAD_ERR_OK) {
                $coverPath = upload_image($_FILES['cover_photo'], 'stores');
                if ($coverPath) {
                    $storeUpdates['cover_photo'] = $coverPath;
                }
            }

            try {
                if (!empty($storeUpdates)) {
                    $this->storeModel->update($store['id'], $storeUpdates);
                }
            } catch (\Throwable $e) {
                error_log("[VendorController] Store update error: " . $e->getMessage());
            }

            // Sync with store_schedules table (Day 0 to 6)
            if (isset($data['opening_time']) || isset($data['closing_time'])) {
                $finalOp = $storeUpdates['opening_time'] ?? (!empty($store['opening_time']) ? date('H:i:s', strtotime($store['opening_time'])) : '08:00:00');
                $finalCl = $storeUpdates['closing_time'] ?? (!empty($store['closing_time']) ? date('H:i:s', strtotime($store['closing_time'])) : '22:00:00');
                try {
                    for ($d = 0; $d <= 6; $d++) {
                        $existingSch = Database::fetchOne("SELECT id FROM store_schedules WHERE store_id = ? AND day_of_week = ? LIMIT 1", [$store['id'], $d]);
                        if ($existingSch) {
                            Database::execute("UPDATE store_schedules SET opening_time = ?, closing_time = ? WHERE id = ?", [$finalOp, $finalCl, $existingSch['id']]);
                        } else {
                            Database::insert('store_schedules', [
                                'store_id'     => $store['id'],
                                'day_of_week'  => $d,
                                'opening_time' => $finalOp,
                                'closing_time' => $finalCl,
                            ]);
                        }
                    }
                } catch (\Throwable $e) {
                    error_log("[VendorController] Sync store_schedules error: " . $e->getMessage());
                }
            }
        } else {
            // Auto-create store if vendor does not have a store record yet
            $logoPath = 'assets/images/stores/default.jpg';
            if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === UPLOAD_ERR_OK) {
                $up = upload_image($_FILES['store_logo'], 'stores');
                if ($up) $logoPath = $up;
            }

            $storeId = $this->storeModel->create([
                'vendor_id'      => $userId,
                'module_id'      => 1,
                'zone_id'        => 1,
                'name'           => !empty($storeName) ? $storeName : ($name . ' Store'),
                'phone'          => !empty($storePhone) ? $storePhone : $phone,
                'email'          => !empty($email) ? $email : ($dbUser['email'] ?? ''),
                'logo'           => $logoPath,
                'cover_photo'    => 'assets/images/stores/default_cover.jpg',
                'address'        => !empty($storeAddress) ? $storeAddress : 'Cicalengka, Kab. Bandung',
                'latitude'       => $storeLat ?? -6.9840,
                'longitude'      => $storeLng ?? 107.8340,
                'minimum_order'  => 10000.00,
                'delivery_time'  => '20-30 Menit',
                'is_open'        => 1,
                'status'         => 'approved',
                'rating'         => 5.0,
                'reviews_count'  => 0,
                'order_count'    => 0
            ]);
            $store = $this->storeModel->find($storeId);
        }

        $currentPassword = $data['current_password'] ?? '';
        $newPassword     = $data['new_password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        $passwordUpdate = null;
        if (!empty($newPassword) || !empty($currentPassword)) {
            if (empty($currentPassword)) {
                if ($this->isJsonRequest()) {
                    $this->errorResponse('Harap masukkan Kata Sandi Saat Ini untuk memverifikasi perubahan kata sandi.');
                    return;
                }
                $_SESSION['error'] = 'Harap masukkan Kata Sandi Saat Ini untuk memverifikasi perubahan kata sandi.';
                $this->redirect('vendor/profile');
                return;
            }

            if (!password_verify($currentPassword, $dbUser['password'] ?? '')) {
                if ($this->isJsonRequest()) {
                    $this->errorResponse('Kata Sandi Saat Ini yang Anda masukkan salah.');
                    return;
                }
                $_SESSION['error'] = 'Kata Sandi Saat Ini yang Anda masukkan salah.';
                $this->redirect('vendor/profile');
                return;
            }

            if (strlen($newPassword) < 6) {
                if ($this->isJsonRequest()) {
                    $this->errorResponse('Kata Sandi Baru harus memiliki minimal 6 karakter.');
                    return;
                }
                $_SESSION['error'] = 'Kata Sandi Baru harus memiliki minimal 6 karakter.';
                $this->redirect('vendor/profile');
                return;
            }

            if ($newPassword !== $confirmPassword) {
                if ($this->isJsonRequest()) {
                    $this->errorResponse('Konfirmasi Kata Sandi Baru tidak cocok.');
                    return;
                }
                $_SESSION['error'] = 'Konfirmasi Kata Sandi Baru tidak cocok.';
                $this->redirect('vendor/profile');
                return;
            }

            $passwordUpdate = password_hash($newPassword, PASSWORD_BCRYPT);
        }

        // JSON API Flow (Mobile App) -> Update directly
        if ($this->isJsonRequest()) {
            try {
                $userUpdates = ['name' => $name];
                if (!empty($phone)) $userUpdates['phone'] = $phone;
                if (!empty($email)) $userUpdates['email'] = $email;
                if (!empty($passwordUpdate)) $userUpdates['password'] = $passwordUpdate;
                $userModel->update($userId, $userUpdates);
            } catch (\Throwable $e) {
                error_log("[VendorController] User update error: " . $e->getMessage());
            }

            $freshStore = $this->storeModel->findByVendorId($userId);
            if (!$freshStore && !empty($store['id'])) {
                $freshStore = $this->storeModel->find($store['id']);
            }
            if ($freshStore) {
                attach_store_schedule_data($freshStore);
            }
            $freshUser = $userModel->find($userId);
            if (!empty($freshUser)) unset($freshUser['password']);

            $this->successResponse('Profil toko dan pengaturan resto berhasil diperbarui!', [
                'user'  => $freshUser,
                'store' => $freshStore
            ]);
            return;
        }

        $currentUser = auth_user();
        $isEmailChanged    = (!empty($email) && strtolower($email) !== strtolower($currentUser['email'] ?? ''));
        $isPhoneChanged    = (!empty($phone) && trim($phone) !== trim($currentUser['phone'] ?? ''));
        $isPasswordChanged = !empty($passwordUpdate);

        if ($isEmailChanged || $isPhoneChanged || $isPasswordChanged) {
            if ($isEmailChanged) {
                $existing = Database::fetchOne("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1", [$email, $userId]);
                if ($existing) {
                    $_SESSION['error'] = 'Alamat email ini sudah terdaftar pada akun lain.';
                    $this->redirect('vendor/profile');
                    return;
                }
            }

            if ($isPhoneChanged) {
                $existingPhone = Database::fetchOne("SELECT id FROM users WHERE phone = ? AND id != ? LIMIT 1", [$phone, $userId]);
                if ($existingPhone) {
                    $_SESSION['error'] = 'Nomor WhatsApp ini sudah terdaftar pada akun lain.';
                    $this->redirect('vendor/profile');
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
                'role'       => $currentUser['role'] ?? 'vendor',
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
                    error_log("[VendorController] Profile update WA OTP failed: " . $e->getMessage());
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

        if (isset($_SESSION['user'])) {
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['phone'] = $phone;
        }

        $_SESSION['success'] = 'Profil Mitra Toko berhasil diperbarui!';
        $this->redirect('vendor/profile');
    }

    // ════════════════════════════════════════════════════════════════════════
    // BAHAN BAKU (RAW MATERIALS)
    // ════════════════════════════════════════════════════════════════════════

    /**
     * GET /api/v1/vendor/raw-materials
     * Daftar semua bahan baku milik toko
     */
    public function rawMaterials(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Unauthorized / Sesi login telah berakhir', null, 401);
            return;
        }

        $store = $this->storeModel->findByVendorId($userId);
        if (!$store) {
            $this->errorResponse('Toko tidak ditemukan', null, 404);
            return;
        }

        $rm = new \App\Models\RawMaterial();
        $list = $rm->getByStore((int)$store['id']);

        $this->successResponse('OK', ['raw_materials' => $list]);
    }

    /**
     * POST /api/v1/vendor/raw-materials/save
     * Buat atau update bahan baku
     * Body: { id?, name, unit, price_per_unit, stock_qty, description? }
     */
    public function saveRawMaterial(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Unauthorized / Sesi login telah berakhir', null, 401);
            return;
        }

        $store = $this->storeModel->findByVendorId($userId);
        if (!$store) {
            $this->errorResponse('Toko tidak ditemukan', null, 404);
            return;
        }

        $body = $this->getJsonBody();
        $name          = trim($body['name'] ?? '');
        $unit          = trim($body['unit'] ?? 'gr');
        $pricePerUnit  = (float)($body['price_per_unit'] ?? 0);
        $stockQty      = (float)($body['stock_qty'] ?? 0);
        $description   = trim($body['description'] ?? '');

        if ($name === '') {
            $this->errorResponse('Nama bahan baku wajib diisi');
            return;
        }
        if ($pricePerUnit < 0) {
            $this->errorResponse('Harga tidak boleh negatif');
            return;
        }

        $rm = new \App\Models\RawMaterial();
        $data = [
            'id'             => !empty($body['id']) ? (int)$body['id'] : null,
            'store_id'       => (int)$store['id'],
            'name'           => $name,
            'unit'           => $unit,
            'price_per_unit' => $pricePerUnit,
            'stock_qty'      => $stockQty,
            'description'    => $description ?: null,
        ];

        $savedId = $rm->save($data);
        $saved   = $rm->find($savedId);

        $this->successResponse('Bahan baku berhasil disimpan', ['raw_material' => $saved]);
    }

    /**
     * POST /api/v1/vendor/raw-materials/delete
     * Body: { id }
     */
    public function deleteRawMaterial(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Unauthorized / Sesi login telah berakhir', null, 401);
            return;
        }

        $store = $this->storeModel->findByVendorId($userId);
        if (!$store) {
            $this->errorResponse('Toko tidak ditemukan', null, 404);
            return;
        }

        $body = $this->getJsonBody();
        $id   = (int)($body['id'] ?? 0);
        if ($id <= 0) {
            $this->errorResponse('ID bahan baku tidak valid');
            return;
        }

        $rm = new \App\Models\RawMaterial();
        $rm->delete($id, (int)$store['id']);

        $this->successResponse('Bahan baku berhasil dihapus');
    }

    /**
     * GET /api/v1/vendor/products/{id}/recipe
     * Ambil resep produk beserta bahan bakunya
     */
    public function getProductRecipe(string $id = '0'): void
    {
        $userId    = auth_id();
        if (!$userId) {
            $this->errorResponse('Unauthorized / Sesi login telah berakhir', null, 401);
            return;
        }

        $store     = $this->storeModel->findByVendorId($userId);
        $productId = (int)$id;

        if (!$store) {
            $this->errorResponse('Toko tidak ditemukan', null, 404);
            return;
        }

        // Pastikan produk milik toko ini
        $product = Database::fetchOne(
            "SELECT id, name, hpp FROM `products` WHERE `id` = ? AND `store_id` = ?",
            [$productId, $store['id']]
        );
        if (!$product) {
            $this->errorResponse('Produk tidak ditemukan', null, 404);
            return;
        }

        $rm      = new \App\Models\RawMaterial();
        $recipe  = $rm->getProductRecipe($productId);
        $allMats = $rm->getByStore((int)$store['id']);

        $totalHpp = array_sum(array_column($recipe, 'cost'));

        $this->successResponse('OK', [
            'product'       => $product,
            'recipe'        => $recipe,
            'all_materials' => $allMats,
            'total_hpp'     => $totalHpp,
        ]);
    }

    /**
     * POST /api/v1/vendor/products/recipe/save
     * Simpan / update resep produk
     * Body: { product_id, ingredients: [{ raw_material_id, qty_used }, ...] }
     */
    public function saveProductRecipe(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->errorResponse('Unauthorized / Sesi login telah berakhir', null, 401);
            return;
        }

        $store  = $this->storeModel->findByVendorId($userId);
        if (!$store) {
            $this->errorResponse('Toko tidak ditemukan', null, 404);
            return;
        }

        $body       = $this->getJsonBody();
        $productId  = (int)($body['product_id'] ?? 0);
        $ingredients = $body['ingredients'] ?? [];

        if ($productId <= 0) {
            $this->errorResponse('product_id wajib diisi');
            return;
        }

        // Pastikan produk milik toko ini
        $product = Database::fetchOne(
            "SELECT id, name FROM `products` WHERE `id` = ? AND `store_id` = ?",
            [$productId, $store['id']]
        );
        if (!$product) {
            $this->errorResponse('Produk tidak ditemukan', null, 404);
            return;
        }

        $rm      = new \App\Models\RawMaterial();
        $hpp     = $rm->saveProductRecipe($productId, (array)$ingredients);
        $recipe  = $rm->getProductRecipe($productId);

        $newPrice = isset($body['new_price']) ? (float)$body['new_price'] : null;
        if ($newPrice !== null && $newPrice > 0) {
            Database::query("UPDATE `products` SET `price` = ? WHERE `id` = ?", [$newPrice, $productId]);
        }

        $updatedProduct = Database::fetchOne("SELECT id, name, price, hpp FROM `products` WHERE `id` = ?", [$productId]);

        $this->successResponse('Resep dan harga berhasil disimpan', [
            'product_id' => $productId,
            'total_hpp'  => $hpp,
            'price'      => (float)($updatedProduct['price'] ?? 0),
            'recipe'     => $recipe,
        ]);
    }
}

