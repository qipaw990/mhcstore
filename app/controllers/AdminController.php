<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Zone;
use App\Models\Module;
use App\Models\Store;
use App\Models\Order;
use App\Models\Product;
use App\Models\DeliveryMan;
use App\Models\Banner;
use App\Models\BusinessSetting;
use App\Models\User;
use App\Models\Wallet;
use App\Core\Database;
use Exception;

class AdminController extends Controller
{
    // =========================================================================
    // 1. Executive Dashboard
    // =========================================================================
    public function dashboard(): void
    {
        $totalOrders = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM orders")['c'] ?? 0);
        $totalRevenue = (float)(Database::fetchOne("SELECT SUM(total_amount) as s FROM orders WHERE payment_status = 'paid'")['s'] ?? 0);
        $totalStores = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM stores")['c'] ?? 0);
        $totalDrivers = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM delivery_men")['c'] ?? 0);
        $totalCustomers = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM users WHERE role = 'customer'")['c'] ?? 0);

        $recentOrders = Database::query("
            SELECT o.*, s.name as store_name, u.name as customer_name
            FROM `orders` o
            LEFT JOIN `stores` s ON o.store_id = s.id
            JOIN `users` u ON o.customer_id = u.id
            ORDER BY o.id DESC LIMIT 10
        ");

        $modules = Database::query("SELECT m.*, COUNT(s.id) as store_count FROM modules m LEFT JOIN stores s ON m.id = s.module_id GROUP BY m.id");

        $this->view('admin.dashboard', [
            'title'           => 'Super Admin Dashboard - CicalengkaGO',
            'total_orders'    => $totalOrders,
            'total_revenue'   => $totalRevenue,
            'total_stores'    => $totalStores,
            'total_drivers'   => $totalDrivers,
            'total_customers' => $totalCustomers,
            'recent_orders'   => $recentOrders,
            'modules'         => $modules,
            'active_tab'      => 'dashboard'
        ], 'admin_layout');
    }

    // =========================================================================
    // 2. Orders & Live Dispatch
    // =========================================================================
    public function orders(): void
    {
        $statusFilter = sanitize($this->getQuery('status') ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 50;

        $whereClause = "";
        $params = [];
        if ($statusFilter !== '') {
            $whereClause = " WHERE o.order_status = ?";
            $params[] = $statusFilter;
        }

        $totalOrdersCount = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM `orders` o {$whereClause}", $params)['c'] ?? 0);
        $totalPages = max(1, (int)ceil($totalOrdersCount / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT o.*, s.name as store_name, s.latitude as store_lat, s.longitude as store_lng,
                   u.name as customer_name, u.phone as customer_phone,
                   dmu.name as dm_name, dmu.phone as dm_phone, dm.current_latitude as dm_lat, dm.current_longitude as dm_lng
            FROM `orders` o
            LEFT JOIN `stores` s ON o.store_id = s.id
            JOIN `users` u ON o.customer_id = u.id
            LEFT JOIN `delivery_men` dm ON o.delivery_man_id = dm.id
            LEFT JOIN `users` dmu ON dm.user_id = dmu.id
            {$whereClause}
            ORDER BY o.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $orders = Database::query($sql, $params);

        $drivers = Database::query("
            SELECT dm.*, u.name, u.phone, u.avatar
            FROM `delivery_men` dm
            JOIN `users` u ON dm.user_id = u.id
            WHERE dm.is_active = 1
        ");

        $stores = Database::query("SELECT id, name, latitude, longitude, address, phone, logo FROM `stores`");

        $this->view('admin.orders', [
            'title'          => 'Pusat Pemantauan & Dispatch Pesanan',
            'orders'         => $orders,
            'drivers'        => $drivers,
            'stores'         => $stores,
            'status_filter'  => $statusFilter,
            'active_tab'     => 'orders',
            'total_orders'   => $totalOrdersCount,
            'current_page'   => $page,
            'total_pages'    => $totalPages,
            'per_page'       => $perPage
        ], 'admin_layout');
    }

    public function orderDetail(string $id): void
    {
        $order = (new Order())->findByIdOrCode($id);

        if (!$order) {
            $this->errorResponse('Pesanan tidak ditemukan.', null, 404);
            return;
        }

        $this->json(['success' => true, 'data' => $order]);
    }

    public function invoice(string $id): void
    {
        $order = (new Order())->findByIdOrCode($id);

        if (!$order) {
            die('Invoice pesanan tidak ditemukan.');
        }

        $orderId = (int)$order['id'];
        $items = Database::query("SELECT * FROM `order_items` WHERE `order_id` = ?", [$orderId]);
        $delAddress = json_decode($order['delivery_address_json'] ?? '{}', true) ?: [];
        $parcelDetails = json_decode($order['parcel_details_json'] ?? '{}', true) ?: [];

        $this->view('admin.invoice', [
            'order'         => $order,
            'items'         => $items,
            'delAddress'    => $delAddress,
            'parcelDetails' => $parcelDetails
        ], null); // Render standalone printable invoice
    }

    public function assignDriver(): void
    {
        $data = $this->getPost();
        $orderId = (int)($data['order_id'] ?? 0);
        $driverId = (int)($data['delivery_man_id'] ?? 0);

        if (!$orderId || !$driverId) {
            $this->errorResponse('Data order atau driver tidak valid.');
            return;
        }

        Database::execute("UPDATE `orders` SET `delivery_man_id` = ?, `order_status` = 'on_the_way', `handover_at` = NOW() WHERE `id` = ?", [$driverId, $orderId]);
        Database::execute("UPDATE `delivery_men` SET `current_order_id` = ? WHERE `id` = ?", [$orderId, $driverId]);

        $this->successResponse('Driver berhasil ditugaskan untuk mengantar pesanan.');
    }

    public function updateOrderStatus(): void
    {
        $data = $this->getPost();
        $orderId = (int)($data['order_id'] ?? 0);
        $status = sanitize($data['status'] ?? '');

        if (!$orderId || empty($status)) {
            $this->errorResponse('Data order status tidak valid.');
            return;
        }

        $timestampCol = match ($status) {
            'confirmed'   => 'confirmed_at',
            'processing'  => 'processing_at',
            'handover'    => 'handover_at',
            'picked_up'   => 'picked_up_at',
            'delivered'   => 'delivered_at',
            'canceled'    => 'canceled_at',
            default       => null
        };

        if ($timestampCol) {
            Database::execute("UPDATE `orders` SET `order_status` = ?, `{$timestampCol}` = NOW() WHERE `id` = ?", [$status, $orderId]);
        } else {
            Database::execute("UPDATE `orders` SET `order_status` = ? WHERE `id` = ?", [$status, $orderId]);
        }

        if ($status === 'delivered') {
            Database::execute("UPDATE `orders` SET `payment_status` = 'paid' WHERE `id` = ?", [$orderId]);
        }

        $this->successResponse("Status pesanan berhasil diubah menjadi {$status}.");
    }

    public function cancelOrder(): void
    {
        $data = $this->getPost();
        $orderId = (int)($data['order_id'] ?? 0);
        $reason = sanitize($data['reason'] ?? 'Dibatalkan oleh Administrator');

        Database::execute("UPDATE `orders` SET `order_status` = 'canceled', `canceled_at` = NOW(), `cancellation_reason` = ? WHERE `id` = ?", [$reason, $orderId]);
        
        // Panggil auto refund jika pesanan telah dibayar
        Order::refundOrderIfPaid($orderId, $reason);

        $this->successResponse('Pesanan berhasil dibatalkan dan pengembalian dana (jika ada) telah dikreditkan ke CicalengkaPay.');
    }

    public function deleteOrder(): void
    {
        $data = $this->getPost();
        $orderId = (int)($data['order_id'] ?? $data['id'] ?? 0);

        if (!$orderId) {
            $this->errorResponse('ID pesanan tidak valid.');
            return;
        }

        $order = (new Order())->find($orderId);
        if (!$order) {
            $this->errorResponse('Pesanan tidak ditemukan atau sudah dihapus.');
            return;
        }

        try {
            Database::transaction(function () use ($orderId) {
                // 1. Reset driver current order assignment if this order was active
                Database::execute("UPDATE `delivery_men` SET `current_order_id` = NULL WHERE `current_order_id` = ?", [$orderId]);
                // 2. Delete delivery trackings
                Database::execute("DELETE FROM `delivery_trackings` WHERE `order_id` = ?", [$orderId]);
                // 3. Delete reviews
                Database::execute("DELETE FROM `reviews` WHERE `order_id` = ?", [$orderId]);
                // 4. Delete order items
                Database::execute("DELETE FROM `order_items` WHERE `order_id` = ?", [$orderId]);
                // 5. Delete the order
                Database::execute("DELETE FROM `orders` WHERE `id` = ?", [$orderId]);
            });

            $this->successResponse("Pesanan #{$order['order_code']} berhasil dihapus permanen.");
        } catch (\Exception $e) {
            $this->errorResponse('Gagal menghapus pesanan: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // 3. Zones Management
    // =========================================================================
    public function zones(): void
    {
        $zones = (new Zone())->all();
        $this->view('admin.zones', [
            'title'      => 'Manajemen Zona Pengantaran & Polygon',
            'zones'      => $zones,
            'active_tab' => 'zones'
        ], 'admin_layout');
    }

    public function saveZone(): void
    {
        $data = $this->getPost();
        $id = !empty($data['id']) ? (int)$data['id'] : null;

        $rawCoords = $data['coordinates_json'] ?? '[]';
        // Validate and ensure proper JSON format
        $decoded = json_decode($rawCoords, true);
        if (!is_array($decoded)) {
            $rawCoords = '[]';
        }

        $zoneData = [
            'name'                   => sanitize($data['name'] ?? 'Zona Cicalengka'),
            'coordinates_json'       => $rawCoords,
            'min_delivery_charge'    => (float)($data['min_delivery_charge'] ?? 5000),
            'per_km_delivery_charge' => (float)($data['per_km_delivery_charge'] ?? 2500),
            'center_latitude'        => (float)($data['center_latitude'] ?? -6.9840),
            'center_longitude'       => (float)($data['center_longitude'] ?? 107.8340),
            'status'                 => isset($data['status']) ? (int)$data['status'] : 1
        ];

        if ($id) {
            (new Zone())->update($id, $zoneData);
            $_SESSION['success'] = 'Cakupan wilayah dan tarif zona berhasil diperbarui!';
        } else {
            (new Zone())->create($zoneData);
            $_SESSION['success'] = 'Zona pengantaran baru berhasil ditambahkan!';
        }

        $this->redirect('admin/zones');
    }

    public function deleteZone(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        if ($id) {
            (new Zone())->delete($id);
            $this->successResponse('Zona berhasil dihapus.');
            return;
        }
        $this->errorResponse('ID zona tidak valid.');
    }

    // =========================================================================
    // 4. Business Modules
    // =========================================================================
    public function modules(): void
    {
        $modules = Database::query("
            SELECT m.*, COUNT(s.id) as store_count
            FROM `modules` m
            LEFT JOIN `stores` s ON m.id = s.module_id
            GROUP BY m.id
            ORDER BY m.id ASC
        ");

        $this->view('admin.modules', [
            'title'      => 'Manajemen Modul Bisnis Multi-Vendor',
            'modules'    => $modules,
            'active_tab' => 'modules'
        ], 'admin_layout');
    }

    public function saveModule(): void
    {
        $data = $this->getPost();
        $id = !empty($data['id']) ? (int)$data['id'] : null;

        $moduleData = [
            'name'        => sanitize($data['name']),
            'module_type' => sanitize($data['module_type'] ?? 'food'),
            'icon'        => sanitize($data['icon'] ?? 'bi-box'),
            'theme_color' => sanitize($data['theme_color'] ?? '#2563eb'),
            'description' => sanitize($data['description'] ?? ''),
            'status'      => isset($data['status']) ? (int)$data['status'] : 1
        ];

        if ($id) {
            (new Module())->update($id, $moduleData);
        } else {
            (new Module())->create($moduleData);
        }

        $this->redirect('admin/modules');
    }

    public function toggleModuleStatus(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        $status = (int)($data['status'] ?? 0);

        (new Module())->update($id, ['status' => $status]);
        $this->successResponse('Status modul berhasil diperbarui.');
    }

    public function deleteModule(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        if ($id) {
            (new Module())->delete($id);
            $this->successResponse('Modul berhasil dihapus.');
            return;
        }
        $this->errorResponse('ID modul tidak valid.');
    }

    // =========================================================================
    // 5. Stores / Merchants
    // =========================================================================
    public function stores(): void
    {
        $page = max(1, (int)($this->getQuery('page') ?? 1));
        $search = trim(sanitize($this->getQuery('search') ?? ''));
        $perPage = 50;

        $whereConditions = [];
        $params = [];

        if (!empty($search)) {
            $whereConditions[] = "(s.name LIKE ? OR u.name LIKE ? OR s.address LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $whereClause = !empty($whereConditions) ? " WHERE " . implode(" AND ", $whereConditions) : "";

        // 1. Total counts for KPI Cards
        $totalStoresCount = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM stores s LEFT JOIN users u ON s.vendor_id = u.id {$whereClause}", $params)['c'] ?? 0);
        $totalOpenStores = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM stores s LEFT JOIN users u ON s.vendor_id = u.id " . ($whereClause ? $whereClause . " AND s.is_open = 1" : "WHERE s.is_open = 1"), $params)['c'] ?? 0);
        $totalClosedStores = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM stores s LEFT JOIN users u ON s.vendor_id = u.id " . ($whereClause ? $whereClause . " AND s.is_open = 0" : "WHERE s.is_open = 0"), $params)['c'] ?? 0);
        $totalSuspendedStores = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM stores s LEFT JOIN users u ON s.vendor_id = u.id " . ($whereClause ? $whereClause . " AND s.status = 'suspended'" : "WHERE s.status = 'suspended'"), $params)['c'] ?? 0);

        $totalPages = max(1, (int)ceil($totalStoresCount / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        // 2. Fetch paginated stores
        $sql = "
            SELECT s.*, m.name as module_name, u.name as vendor_name, u.email as vendor_email, z.name as zone_name,
                   COALESCE(pc.product_count, 0) as product_count
            FROM `stores` s
            JOIN `modules` m ON s.module_id = m.id
            JOIN `users` u ON s.vendor_id = u.id
            LEFT JOIN `zones` z ON s.zone_id = z.id
            LEFT JOIN (
                SELECT store_id, COUNT(*) as product_count FROM products GROUP BY store_id
            ) pc ON s.id = pc.store_id
            {$whereClause}
            ORDER BY s.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $stores = Database::query($sql, $params);
        $modules = (new Module())->all();
        $zones = (new Zone())->all();
        $vendors = Database::query("SELECT id, name, email, phone FROM users WHERE role = 'vendor'");

        $this->view('admin.stores', [
            'title'                 => 'Daftar Toko & Mitra Merchant',
            'stores'                => $stores,
            'modules'               => $modules,
            'zones'                 => $zones,
            'vendors'               => $vendors,
            'active_tab'            => 'stores',
            'total_stores'          => $totalStoresCount,
            'total_open_stores'     => $totalOpenStores,
            'total_closed_stores'   => $totalClosedStores,
            'total_suspended_stores'=> $totalSuspendedStores,
            'current_page'          => $page,
            'total_pages'           => $totalPages,
            'per_page'              => $perPage,
            'search'                => $search
        ], 'admin_layout');
    }

    public function saveStore(): void
    {
        $data = $this->getPost();
        $id = !empty($data['id']) ? (int)$data['id'] : null;

        $vendorId = !empty($data['vendor_id']) ? (int)$data['vendor_id'] : null;
        if (!$vendorId && !empty($data['vendor_email'])) {
            $userModel = new User();
            $existing = $userModel->findByEmail($data['vendor_email']);
            if ($existing) {
                $vendorId = $existing['id'];
            } else {
                $vendorId = $userModel->create([
                    'role'      => 'vendor',
                    'name'      => sanitize($data['vendor_name'] ?? $data['name']),
                    'email'     => sanitize($data['vendor_email']),
                    'phone'     => sanitize($data['phone'] ?? '081234567890'),
                    'password'  => password_hash($data['vendor_password'] ?? '123456', PASSWORD_DEFAULT),
                    'is_active' => 1
                ]);
            }
        }

        $storeData = [
            'name'          => sanitize($data['name']),
            'module_id'     => (int)($data['module_id'] ?? 1),
            'zone_id'       => (int)($data['zone_id'] ?? 1),
            'vendor_id'     => $vendorId ?? 1,
            'phone'         => sanitize($data['phone'] ?? ''),
            'email'         => sanitize($data['email'] ?? ''),
            'address'       => sanitize($data['address'] ?? 'Cicalengka'),
            'latitude'      => (float)($data['latitude'] ?? -6.9840),
            'longitude'     => (float)($data['longitude'] ?? 107.8340),
            'minimum_order' => (float)($data['minimum_order'] ?? 10000),
            'delivery_time' => sanitize($data['delivery_time'] ?? '20-30 Menit'),
            'delivery_fee'  => (float)($data['delivery_fee'] ?? 5000),
            'tax_percent'   => (float)($data['tax_percent'] ?? 0),
            'is_open'       => isset($data['is_open']) ? (int)$data['is_open'] : 1,
            'status'        => sanitize($data['status'] ?? 'approved')
        ];

        if ($id) {
            (new Store())->update($id, $storeData);
        } else {
            $storeData['logo'] = 'assets/images/stores/store1.jpg';
            $storeData['cover_photo'] = 'assets/images/stores/cover1.jpg';
            (new Store())->create($storeData);
        }

        $this->redirect('admin/stores');
    }

    public function toggleStoreOpen(): void
    {
        $data = $this->getPost();
        $storeId = (int)($data['store_id'] ?? 0);
        $isOpen = (int)($data['is_open'] ?? 0);

        (new Store())->update($storeId, ['is_open' => $isOpen]);
        $this->successResponse('Status buka/tutup toko berhasil diubah.');
    }

    public function updateStoreStatus(): void
    {
        $data = $this->getPost();
        $storeId = (int)($data['store_id'] ?? 0);
        $status = sanitize($data['status'] ?? 'approved');

        (new Store())->update($storeId, ['status' => $status]);
        $this->successResponse('Status akun toko berhasil diperbarui.');
    }

    public function deleteStore(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        if ($id) {
            (new Store())->delete($id);
            $this->successResponse('Toko berhasil dihapus.');
            return;
        }
        $this->errorResponse('ID toko tidak valid.');
    }

    public function bulkDeleteStores(): void
    {
        $data = $this->getPost();
        $ids = $data['ids'] ?? [];
        if (!is_array($ids)) {
            $ids = explode(',', (string)$ids);
        }
        $ids = array_filter(array_map('intval', $ids));

        if (empty($ids)) {
            $this->errorResponse('Pilih setidaknya satu toko untuk dihapus.');
            return;
        }

        try {
            $inClause = implode(',', $ids);
            Database::execute("DELETE FROM `products` WHERE `store_id` IN ({$inClause})");
            Database::execute("DELETE FROM `store_schedules` WHERE `store_id` IN ({$inClause})");
            Database::execute("DELETE FROM `stores` WHERE `id` IN ({$inClause})");

            $this->successResponse(count($ids) . ' toko mitra berhasil dihapus.');
        } catch (\Exception $e) {
            $this->errorResponse('Gagal menghapus toko: ' . $e->getMessage());
        }
    }

    public function deleteAllStores(): void
    {
        try {
            Database::execute("SET FOREIGN_KEY_CHECKS = 0;");
            Database::execute("TRUNCATE TABLE `products`");
            Database::execute("TRUNCATE TABLE `store_schedules`");
            Database::execute("TRUNCATE TABLE `stores`");
            Database::execute("TRUNCATE TABLE `carts`");
            Database::execute("TRUNCATE TABLE `order_items`");
            Database::execute("TRUNCATE TABLE `orders`");
            Database::execute("DELETE FROM `users` WHERE `role` = 'vendor'");
            Database::execute("DELETE FROM `wallets` WHERE `user_type` = 'vendor'");
            Database::execute("UPDATE `modules` SET `stores_count` = 0");
            Database::execute("SET FOREIGN_KEY_CHECKS = 1;");

            $this->successResponse('Seluruh data toko mitra, produk, dan akun vendor berhasil dikosongkan!');
        } catch (\Exception $e) {
            $this->errorResponse('Gagal mengosongkan toko: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // 6. Products Management
    // =========================================================================
    public function products(): void
    {
        $storeFilter = (int)($this->getQuery('store_id') ?? 0);
        $search = trim(sanitize($this->getQuery('search') ?? ''));
        $page = max(1, (int)($this->getQuery('page') ?? 1));
        $perPage = 50;

        $whereConditions = [];
        $params = [];

        if ($storeFilter > 0) {
            $whereConditions[] = "p.store_id = ?";
            $params[] = $storeFilter;
        }

        if (!empty($search)) {
            $whereConditions[] = "(p.name LIKE ? OR s.name LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $whereClause = !empty($whereConditions) ? " WHERE " . implode(" AND ", $whereConditions) : "";

        // 1. Total counts for KPI Cards
        $totalProductsCount = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM products p JOIN stores s ON p.store_id = s.id {$whereClause}", $params)['c'] ?? 0);
        $totalActiveProducts = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM products p JOIN stores s ON p.store_id = s.id " . ($whereClause ? $whereClause . " AND p.status = 1" : "WHERE p.status = 1"), $params)['c'] ?? 0);
        $totalDiscountProducts = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM products p JOIN stores s ON p.store_id = s.id " . ($whereClause ? $whereClause . " AND p.discount > 0" : "WHERE p.discount > 0"), $params)['c'] ?? 0);
        $totalLowStockProducts = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM products p JOIN stores s ON p.store_id = s.id " . ($whereClause ? $whereClause . " AND p.stock < 10" : "WHERE p.stock < 10"), $params)['c'] ?? 0);

        $totalPages = max(1, (int)ceil($totalProductsCount / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        // 2. Fetch paginated products
        $sql = "
            SELECT p.*, s.name as store_name, m.name as module_name
            FROM `products` p
            JOIN `stores` s ON p.store_id = s.id
            JOIN `modules` m ON p.module_id = m.id
            {$whereClause}
            ORDER BY p.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $products = Database::query($sql, $params);
        $stores = Database::query("SELECT id, name FROM stores ORDER BY name ASC");
        $modules = (new Module())->all();

        $this->view('admin.products', [
            'title'                  => 'Katalog Semua Produk Platform',
            'products'               => $products,
            'stores'                 => $stores,
            'modules'                => $modules,
            'store_filter'           => $storeFilter,
            'active_tab'             => 'products',
            'total_products'         => $totalProductsCount,
            'total_active_products'  => $totalActiveProducts,
            'total_discount_products'=> $totalDiscountProducts,
            'total_low_stock_products'=> $totalLowStockProducts,
            'current_page'           => $page,
            'total_pages'            => $totalPages,
            'per_page'               => $perPage,
            'search'                 => $search
        ], 'admin_layout');
    }

    public function saveProduct(): void
    {
        $data = $this->getPost();
        $id = !empty($data['id']) ? (int)$data['id'] : null;

        $storeId = (int)($data['store_id'] ?? 1);
        $store = (new Store())->find($storeId);
        $moduleId = $store['module_id'] ?? 1;

        $productData = [
            'store_id'       => $storeId,
            'module_id'      => $moduleId,
            'category_id'    => (int)($data['category_id'] ?? 1),
            'name'           => sanitize($data['name']),
            'description'    => sanitize($data['description'] ?? ''),
            'price'          => (float)($data['price'] ?? 0),
            'discount'       => (float)($data['discount'] ?? 0),
            'discount_type'  => sanitize($data['discount_type'] ?? 'amount'),
            'unit'           => sanitize($data['unit'] ?? 'Porsi'),
            'stock'          => (int)($data['stock'] ?? 100),
            'is_veg'         => isset($data['is_veg']) ? 1 : 0,
            'is_recommended' => isset($data['is_recommended']) ? 1 : 0,
            'status'         => isset($data['status']) ? (int)$data['status'] : 1
        ];

        if ($id) {
            (new Product())->update($id, $productData);
        } else {
            $productData['image'] = 'assets/images/products/food1.jpg';
            (new Product())->create($productData);
        }

        $this->redirect('admin/products');
    }

    public function updateProductStock(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        $stock = (int)($data['stock'] ?? 0);

        (new Product())->update($id, ['stock' => $stock]);
        $this->successResponse('Stok produk berhasil diperbarui.');
    }

    public function toggleProductStatus(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        $status = (int)($data['status'] ?? 0);

        (new Product())->update($id, ['status' => $status]);
        $this->successResponse('Status produk berhasil diperbarui.');
    }

    public function deleteProduct(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        if ($id) {
            (new Product())->delete($id);
            $this->successResponse('Produk berhasil dihapus.');
            return;
        }
        $this->errorResponse('ID produk tidak valid.');
    }

    // =========================================================================
    // 7. Delivery Men / Kurir Fleet
    // =========================================================================
    public function deliveryMen(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $search = trim(sanitize($_GET['search'] ?? ''));
        $perPage = 50;

        $whereClause = "";
        $params = [];

        if (!empty($search)) {
            $whereClause = " WHERE (u.name LIKE ? OR u.phone LIKE ? OR dm.vehicle_number LIKE ?)";
            $params = ["%{$search}%", "%{$search}%", "%{$search}%"];
        }

        $totalDriversCount = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM `delivery_men` dm JOIN `users` u ON dm.user_id = u.id {$whereClause}", $params)['c'] ?? 0);
        $totalPages = max(1, (int)ceil($totalDriversCount / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $drivers = Database::query("
            SELECT dm.*, u.name, u.email, u.phone, u.avatar, z.name as zone_name,
                   COALESCE(w.balance, 0) as wallet_balance
            FROM `delivery_men` dm
            JOIN `users` u ON dm.user_id = u.id
            LEFT JOIN `zones` z ON dm.zone_id = z.id
            LEFT JOIN `wallets` w ON w.user_id = u.id AND w.user_type = 'delivery_man'
            {$whereClause}
            ORDER BY dm.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ", $params);

        $zones = (new Zone())->all();

        $this->view('admin.delivery_men', [
            'title'         => 'Armada Driver & Kurir CicalengkaGO',
            'drivers'       => $drivers,
            'zones'         => $zones,
            'active_tab'    => 'drivers',
            'total_drivers' => $totalDriversCount,
            'current_page'  => $page,
            'total_pages'   => $totalPages,
            'per_page'      => $perPage,
            'search'        => $search
        ], 'admin_layout');
    }

    public function deliveryManWalletHistory(string $id): void
    {
        $userId = (int)$id;
        $wallet = Database::fetchOne("SELECT id, balance FROM wallets WHERE user_id = ? AND user_type = 'delivery_man' LIMIT 1", [$userId]);
        
        $transactions = [];
        if ($wallet) {
            $transactions = Database::query("
                SELECT * FROM `wallet_transactions`
                WHERE `wallet_id` = ?
                ORDER BY id DESC LIMIT 20
            ", [$wallet['id']]);
        }

        $this->json(['success' => true, 'balance' => $wallet['balance'] ?? 0, 'transactions' => $transactions]);
    }

    public function saveDeliveryMan(): void
    {
        $data = $this->getPost();
        $id = !empty($data['id']) ? (int)$data['id'] : null;

        if (!$id) {
            $userModel = new User();
            $existing = $userModel->findByEmail($data['email']);
            if ($existing) {
                $userId = $existing['id'];
            } else {
                $userId = $userModel->create([
                    'role'      => 'delivery_man',
                    'name'      => sanitize($data['name']),
                    'email'     => sanitize($data['email']),
                    'phone'     => sanitize($data['phone']),
                    'password'  => password_hash($data['password'] ?? '123456', PASSWORD_DEFAULT),
                    'avatar'    => 'assets/images/users/driver.png',
                    'is_active' => 1
                ]);
            }

            (new DeliveryMan())->create([
                'user_id'           => $userId,
                'zone_id'           => (int)($data['zone_id'] ?? 1),
                'vehicle_type'      => sanitize($data['vehicle_type'] ?? 'Motor'),
                'vehicle_number'    => sanitize($data['vehicle_number'] ?? 'D 1234 CCG'),
                'identity_type'     => sanitize($data['identity_type'] ?? 'KTP'),
                'identity_number'   => sanitize($data['identity_number'] ?? '3204000000000001'),
                'is_online'         => 1,
                'is_active'         => 1,
                'current_latitude'  => -6.9840,
                'current_longitude' => 107.8340
            ]);

            (new Wallet())->getOrCreate($userId, 'delivery_man');
        } else {
            $dm = (new DeliveryMan())->find($id);
            if ($dm) {
                (new User())->update($dm['user_id'], [
                    'name'  => sanitize($data['name']),
                    'phone' => sanitize($data['phone'])
                ]);
                (new DeliveryMan())->update($id, [
                    'zone_id'        => (int)($data['zone_id'] ?? 1),
                    'vehicle_type'   => sanitize($data['vehicle_type'] ?? 'Motor'),
                    'vehicle_number' => sanitize($data['vehicle_number'] ?? 'D 1234 CCG')
                ]);
            }
        }

        $this->redirect('admin/delivery-men');
    }

    public function toggleDeliveryManStatus(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        $status = (int)($data['status'] ?? 0);

        (new DeliveryMan())->update($id, ['is_active' => $status]);
        $this->successResponse('Status driver berhasil diperbarui.');
    }

    public function topupDeliveryMan(): void
    {
        $data = $this->getPost();
        $userId = (int)($data['user_id'] ?? 0);
        $amount = (float)($data['amount'] ?? 0);
        $notes = sanitize($data['notes'] ?? 'Top-up saldo kurir oleh Super Admin');

        if ($userId && $amount > 0) {
            (new Wallet())->credit($userId, $amount, 'topup', $notes);

            // Record in topup_logs
            (new \App\Models\TopupLog())->create([
                'topup_code'     => 'MANUAL-DM-' . $userId . '-' . time(),
                'user_id'        => $userId,
                'amount'         => $amount,
                'payment_method' => 'manual_admin',
                'payment_type'   => 'manual_admin',
                'status'         => 'success',
                'notes'          => $notes,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s')
            ]);

            $this->successResponse('Top-up saldo kurir berhasil.');
            return;
        }
        $this->errorResponse('Nominal topup tidak valid.');
    }

    public function deleteDeliveryMan(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        if ($id) {
            (new DeliveryMan())->delete($id);
            $this->successResponse('Driver berhasil dihapus.');
            return;
        }
        $this->errorResponse('ID driver tidak valid.');
    }

    // =========================================================================
    // 8. Customers Management
    // =========================================================================
    public function customers(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $search = trim(sanitize($_GET['search'] ?? ''));
        $perPage = 50;

        $whereClause = " WHERE u.role = 'customer'";
        $params = [];

        if (!empty($search)) {
            $whereClause .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
            $params = ["%{$search}%", "%{$search}%", "%{$search}%"];
        }

        $totalCustomersCount = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM `users` u {$whereClause}", $params)['c'] ?? 0);
        $totalPages = max(1, (int)ceil($totalCustomersCount / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $customers = Database::query("
            SELECT u.*,
                   COALESCE(oc.order_count, 0) as order_count,
                   COALESCE(w.balance, 0) as wallet_balance
            FROM `users` u
            LEFT JOIN (
                SELECT customer_id, COUNT(*) as order_count FROM orders GROUP BY customer_id
            ) oc ON u.id = oc.customer_id
            LEFT JOIN `wallets` w ON w.user_id = u.id AND w.user_type = 'customer'
            {$whereClause}
            ORDER BY u.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ", $params);

        $this->view('admin.customers', [
            'title'           => 'Manajemen Pengguna & Pelanggan',
            'customers'       => $customers,
            'active_tab'      => 'customers',
            'total_customers' => $totalCustomersCount,
            'current_page'    => $page,
            'total_pages'     => $totalPages,
            'per_page'        => $perPage,
            'search'          => $search
        ], 'admin_layout');
    }

    public function customerHistory(string $id): void
    {
        $userId = (int)$id;
        $orders = Database::query("
            SELECT o.*, s.name as store_name
            FROM `orders` o
            LEFT JOIN `stores` s ON o.store_id = s.id
            WHERE o.customer_id = ?
            ORDER BY o.id DESC LIMIT 15
        ", [$userId]);

        $this->json(['success' => true, 'orders' => $orders]);
    }

    public function toggleCustomerStatus(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        $status = (int)($data['status'] ?? 0);

        (new User())->update($id, ['is_active' => $status]);
        $this->successResponse('Status pelanggan berhasil diperbarui.');
    }

    public function topupCustomer(): void
    {
        $data = $this->getPost();
        $userId = (int)($data['user_id'] ?? 0);
        $amount = (float)($data['amount'] ?? 0);
        $notes = sanitize($data['notes'] ?? 'Top-up saldo CicalengkaPay oleh Super Admin');

        if ($userId && $amount > 0) {
            (new Wallet())->credit($userId, $amount, 'topup', $notes);

            // Record in topup_logs
            (new \App\Models\TopupLog())->create([
                'topup_code'     => 'MANUAL-' . $userId . '-' . time(),
                'user_id'        => $userId,
                'amount'         => $amount,
                'payment_method' => 'manual_admin',
                'payment_type'   => 'manual_admin',
                'status'         => 'success',
                'notes'          => $notes,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s')
            ]);

            $this->successResponse('Top-up saldo CicalengkaPay berhasil ditambahkan.');
            return;
        }
        $this->errorResponse('Nominal top-up tidak valid.');
    }

    // =========================================================================
    // 9. Banners & Promos
    // =========================================================================
    public function banners(): void
    {
        $banners = Database::query("
            SELECT b.*, m.name as module_name
            FROM `banners` b
            LEFT JOIN `modules` m ON b.module_id = m.id
            ORDER BY b.priority ASC, b.id DESC
        ");

        $modules = (new Module())->activeModules();

        $this->view('admin.banners', [
            'title'      => 'Manajemen Banner Promo & Slider',
            'banners'    => $banners,
            'modules'    => $modules,
            'active_tab' => 'banners'
        ], 'admin_layout');
    }

    public function saveBanner(): void
    {
        $data = $this->getPost();
        $id = !empty($data['id']) ? (int)$data['id'] : null;
        $imagePath = 'assets/images/banners/banner1.jpg';

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploaded = upload_image($_FILES['image'], 'banners');
            if ($uploaded) {
                $imagePath = $uploaded;
            }
        } elseif (!empty($data['image_url'])) {
            $imagePath = sanitize($data['image_url']);
        }

        $bannerData = [
            'title'       => sanitize($data['title']),
            'module_id'   => !empty($data['module_id']) ? (int)$data['module_id'] : null,
            'banner_type' => $data['banner_type'] ?? 'main_banner',
            'target_type' => $data['target_type'] ?? 'store',
            'target_id'   => sanitize($data['target_id'] ?? '1'),
            'status'      => 1,
            'priority'    => (int)($data['priority'] ?? 1)
        ];

        if ($imagePath) {
            $bannerData['image'] = $imagePath;
        }

        if ($id) {
            (new Banner())->update($id, $bannerData);
        } else {
            (new Banner())->create($bannerData);
        }

        $this->redirect('admin/banners');
    }

    public function deleteBanner(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        if ($id) {
            (new Banner())->delete($id);
            $this->successResponse('Banner berhasil dihapus.');
            return;
        }
        $this->errorResponse('ID banner tidak valid.');
    }

    // =========================================================================
    // 10. System Settings
    // =========================================================================
    public function settings(): void
    {
        $settings = Database::query("SELECT * FROM `business_settings`");
        $settingsMap = [];
        foreach ($settings as $s) {
            $settingsMap[$s['key_name']] = $s['value_text'];
        }

        $this->view('admin.settings', [
            'title'        => 'Pengaturan Sistem & Operasional Platform',
            'settings'     => $settingsMap,
            'active_tab'   => 'settings'
        ], 'admin_layout');
    }

    public function saveSettings(): void
    {
        $data = $this->getPost();

        // List of all boolean toggle switches in system settings
        $booleanSwitches = [
            'maintenance_mode',
            'otp_delivery_verification',
            'wallet_payment_status',
            'single_active_order_driver',
            'require_login_otp',
            'require_customer_otp'
        ];

        // Explicitly set boolean switches: if unchecked (missing from POST), save as '0'; if checked, save as '1'
        foreach ($booleanSwitches as $switchKey) {
            $val = !empty($data[$switchKey]) ? '1' : '0';
            BusinessSetting::set($switchKey, $val);
            unset($data[$switchKey]);
        }

        // Save all other settings fields
        foreach ($data as $key => $val) {
            if ($key !== '_method') {
                if (in_array($key, ['admin_commission_percent', 'tax_percent', 'delivery_charge_min', 'delivery_charge_per_km'])) {
                    $val = str_replace(',', '.', trim($val));
                }
                BusinessSetting::set($key, sanitize($val));
            }
        }

        $_SESSION['success'] = 'Semua konfigurasi sistem & pengaturan operasional berhasil disimpan!';
        $this->redirect('admin/settings');
    }

    // =========================================================================
    // 11. Midtrans Status & Diagnostics
    // =========================================================================
    public function getMidtransStatus(string $orderCode): void
    {
        try {
            $midtransService = new \App\Services\MidtransService();
            $result = $midtransService->getTransactionStatus($orderCode);

            // Also check database order record
            $order = Database::fetchOne("SELECT * FROM `orders` WHERE `order_code` = ? OR `id` = ? LIMIT 1", [$orderCode, (int)$orderCode]);

            $this->json([
                'success'  => true,
                'midtrans' => $result,
                'db_order' => $order
            ]);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    public function testMidtransApi(): void
    {
        try {
            $midtransService = new \App\Services\MidtransService();
            $result = $midtransService->testApiConnection();
            $this->json($result);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    public function testEmailGateway(): void
    {
        try {
            $data = $this->getPost();
            $targetEmail = sanitize($data['test_email'] ?? $_SESSION['user']['email'] ?? 'admin@cicalengkago.id');
            $result = \App\Services\EmailService::testEmailGateway($targetEmail);
            $this->json($result);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    public function profile(): void
    {
        $user = auth_user();
        $this->view('admin.profile', [
            'title'      => 'Profil Super Admin - CicalengkaGO',
            'user'       => $user,
            'active_tab' => 'profile'
        ], 'admin_layout');
    }

    public function updateProfile(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->redirect('login');
            return;
        }

        $data = $this->getPost();
        $name  = sanitize($data['name'] ?? '');
        $email = sanitize($data['email'] ?? '');
        $phone = sanitize($data['phone'] ?? '');

        if (empty($name) || empty($email) || empty($phone)) {
            $_SESSION['error'] = 'Nama, email, dan nomor HP wajib diisi.';
            $this->redirect('admin/profile');
            return;
        }

        $avatarPath = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $avatarPath = upload_image($_FILES['avatar'], 'profiles');
            if ($avatarPath) {
                (new \App\Models\User())->update($userId, ['avatar' => $avatarPath]);
                $_SESSION['user']['avatar'] = $avatarPath;
            }
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
                $this->redirect('admin/profile');
                return;
            }

            if (!password_verify($currentPassword, $dbUser['password'] ?? '')) {
                $_SESSION['error'] = 'Kata Sandi Saat Ini yang Anda masukkan salah.';
                $this->redirect('admin/profile');
                return;
            }

            if (strlen($newPassword) < 6) {
                $_SESSION['error'] = 'Kata Sandi Baru harus memiliki minimal 6 karakter.';
                $this->redirect('admin/profile');
                return;
            }

            if ($newPassword !== $confirmPassword) {
                $_SESSION['error'] = 'Konfirmasi Kata Sandi Baru tidak cocok.';
                $this->redirect('admin/profile');
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
                    $this->redirect('admin/profile');
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

        $_SESSION['success'] = 'Profil Admin berhasil diperbarui!';
        $this->redirect('admin/profile');
    }

    // =========================================================================
    // 12. Payouts / Withdrawal Management
    // =========================================================================
    public function withdrawals(): void
    {
        $statusFilter = sanitize($_GET['status'] ?? 'all');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 50;

        $whereSql = "1=1";
        $params = [];

        if ($statusFilter !== 'all' && in_array($statusFilter, ['pending', 'approved', 'rejected'])) {
            $whereSql .= " AND wr.status = ?";
            $params[] = $statusFilter;
        }

        $totalWithdrawals = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM `withdraw_requests` wr JOIN `users` u ON wr.user_id = u.id WHERE {$whereSql}", $params)['c'] ?? 0);
        $totalPages = max(1, (int)ceil($totalWithdrawals / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $withdrawals = Database::query("
            SELECT wr.*, u.name as user_name, u.email as user_email, u.phone as user_phone
            FROM `withdraw_requests` wr
            JOIN `users` u ON wr.user_id = u.id
            WHERE {$whereSql}
            ORDER BY wr.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ", $params);

        $pendingCount = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM `withdraw_requests` WHERE status = 'pending'")['c'] ?? 0);
        $totalPaid = (float)(Database::fetchOne("SELECT COALESCE(SUM(amount), 0) as s FROM `withdraw_requests` WHERE status = 'approved'")['s'] ?? 0);

        $this->view('admin.withdrawals', [
            'title'            => 'Pencairan Dana (Withdrawal) Mitra - CicalengkaGO Admin',
            'withdrawals'      => $withdrawals,
            'pending_count'    => $pendingCount,
            'total_paid'       => $totalPaid,
            'current_filter'   => $statusFilter,
            'active_tab'       => 'withdrawals',
            'total_withdrawals'=> $totalWithdrawals,
            'current_page'     => $page,
            'total_pages'      => $totalPages,
            'per_page'         => $perPage
        ], 'admin_layout');
    }

    public function updateWithdrawStatus(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        $status = sanitize($data['status'] ?? '');
        $adminNotes = sanitize($data['admin_notes'] ?? '');

        if (!$id || !in_array($status, ['approved', 'rejected'])) {
            $this->errorResponse('Data penarikan atau status tidak valid.');
            return;
        }

        $req = Database::fetchOne("SELECT * FROM `withdraw_requests` WHERE id = ?", [$id]);
        if (!$req) {
            $this->errorResponse('Data penarikan tidak ditemukan.');
            return;
        }

        if ($req['status'] !== 'pending') {
            $this->errorResponse("Pengajuan ini sudah berstatus {$req['status']}.");
            return;
        }

        if ($status === 'approved') {
            Database::update('withdraw_requests', [
                'status'       => 'approved',
                'admin_notes'  => $adminNotes ?: 'Transfer dana telah berhasil diproses oleh admin.',
                'processed_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$id]);

            $this->successResponse('Status penarikan berhasil disetujui (Approved).');
            return;
        }

        if ($status === 'rejected') {
            Database::update('withdraw_requests', [
                'status'       => 'rejected',
                'admin_notes'  => $adminNotes ?: 'Penarikan ditolak oleh admin dan saldo telah dikembalikan.',
                'processed_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$id]);

            // Refund balance to user wallet
            $walletModel = new Wallet();
            $walletModel->credit(
                (int)$req['user_id'],
                (float)$req['amount'],
                'refund',
                "Pengembalian dana penarikan ditolak ({$req['withdraw_code']})",
                $req['withdraw_code']
            );

            // Revert total_withdrawn
            Database::execute(
                "UPDATE `wallets` SET `total_withdrawn` = GREATEST(0, `total_withdrawn` - ?) WHERE `user_id` = ?",
                [(float)$req['amount'], (int)$req['user_id']]
            );

            $this->successResponse('Pengajuan penarikan berhasil ditolak dan saldo telah dikembalikan ke dompet mitra.');
            return;
        }
    }

    // =========================================================================
    // 13. Midtrans Top-Up Management
    // =========================================================================
    public function topups(): void
    {
        $statusFilter = sanitize($_GET['status'] ?? 'all');
        $search = sanitize($_GET['search'] ?? '');
        $period = sanitize($_GET['period'] ?? 'all');

        $where = ["1=1"];
        $params = [];

        if ($statusFilter !== 'all' && in_array($statusFilter, ['pending', 'success', 'failed', 'canceled'])) {
            $where[] = "tl.status = ?";
            $params[] = $statusFilter;
        }

        if (!empty($search)) {
            $where[] = "(tl.topup_code LIKE ? OR u.name LIKE ? OR u.phone LIKE ? OR u.email LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if ($period === 'today') {
            $where[] = "DATE(tl.created_at) = CURDATE()";
        } elseif ($period === 'week') {
            $where[] = "tl.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        } elseif ($period === 'month') {
            $where[] = "tl.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        }

        $whereClause = implode(' AND ', $where);

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 50;

        $totalTopupsFiltered = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM `topup_logs` tl JOIN `users` u ON tl.user_id = u.id WHERE {$whereClause}", $params)['c'] ?? 0);
        $totalPages = max(1, (int)ceil($totalTopupsFiltered / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $topups = Database::query("
            SELECT tl.*, u.name as user_name, u.email as user_email, u.phone as user_phone, u.role as user_role, u.avatar as user_avatar,
                   COALESCE(w.balance, 0) as current_wallet_balance
            FROM `topup_logs` tl
            JOIN `users` u ON tl.user_id = u.id
            LEFT JOIN `wallets` w ON w.user_id = u.id AND w.user_type = 'customer'
            WHERE {$whereClause}
            ORDER BY tl.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ", $params);

        // Calculate KPI summaries
        $statsSuccess = Database::fetchOne("
            SELECT COALESCE(SUM(amount), 0) as total_amount, COUNT(*) as count 
            FROM `topup_logs` 
            WHERE status = 'success'
        ");

        $statsPending = Database::fetchOne("
            SELECT COALESCE(SUM(amount), 0) as total_amount, COUNT(*) as count 
            FROM `topup_logs` 
            WHERE status = 'pending'
        ");

        $statsToday = Database::fetchOne("
            SELECT COALESCE(SUM(amount), 0) as total_amount, COUNT(*) as count 
            FROM `topup_logs` 
            WHERE status = 'success' AND DATE(created_at) = CURDATE()
        ");

        $statsFailed = Database::fetchOne("
            SELECT COUNT(*) as count 
            FROM `topup_logs` 
            WHERE status IN ('failed', 'canceled')
        ");

        $totalAll = (int)(Database::fetchOne("SELECT COUNT(*) as count FROM `topup_logs`")['count'] ?? 0);

        // List of all active users for manual top-up modal dropdown
        $usersList = Database::query("SELECT id, name, email, phone, role FROM `users` WHERE is_active = 1 ORDER BY name ASC");

        $this->view('admin.topups', [
            'title'                 => 'Manajemen Top-Up Saldo Midtrans - CicalengkaGO Admin',
            'topups'                => $topups,
            'total_success_amount'  => (float)($statsSuccess['total_amount'] ?? 0),
            'total_success_count'   => (int)($statsSuccess['count'] ?? 0),
            'total_pending_amount'  => (float)($statsPending['total_amount'] ?? 0),
            'total_pending_count'   => (int)($statsPending['count'] ?? 0),
            'today_success_amount'  => (float)($statsToday['total_amount'] ?? 0),
            'today_success_count'   => (int)($statsToday['count'] ?? 0),
            'total_failed_count'    => (int)($statsFailed['count'] ?? 0),
            'total_all_count'       => $totalAll,
            'current_status'        => $statusFilter,
            'current_search'        => $search,
            'current_period'        => $period,
            'users_list'            => $usersList,
            'active_tab'            => 'topups',
            'total_topups'          => $totalTopupsFiltered,
            'current_page'          => $page,
            'total_pages'           => $totalPages,
            'per_page'              => $perPage
        ], 'admin_layout');
    }

    public function topupDetail(string $id): void
    {
        $log = Database::fetchOne("
            SELECT tl.*, u.name as user_name, u.email as user_email, u.phone as user_phone, u.role as user_role, u.avatar as user_avatar,
                   COALESCE(w.balance, 0) as current_wallet_balance
            FROM `topup_logs` tl
            JOIN `users` u ON tl.user_id = u.id
            LEFT JOIN `wallets` w ON w.user_id = u.id AND w.user_type = 'customer'
            WHERE tl.id = ? OR tl.topup_code = ?
            LIMIT 1
        ", [(int)$id, $id]);

        if (!$log) {
            $this->errorResponse('Data log top up tidak ditemukan.', null, 404);
            return;
        }

        // Check if there is a wallet_transaction for this topup_code
        $walletTx = Database::fetchOne("
            SELECT * FROM `wallet_transactions` 
            WHERE `reference_id` = ? LIMIT 1
        ", [$log['topup_code']]);

        // Attempt to get live Midtrans status if connected
        $midtransStatus = null;
        try {
            $midtransService = new \App\Services\MidtransService();
            $midtransStatus = $midtransService->getTransactionStatus($log['topup_code']);
        } catch (\Throwable $e) {
            $midtransStatus = ['success' => false, 'message' => $e->getMessage()];
        }

        $this->json([
            'success'         => true,
            'data'            => $log,
            'wallet_tx'       => $walletTx,
            'midtrans_status' => $midtransStatus
        ]);
    }

    public function syncTopupStatus(): void
    {
        $data = $this->getPost();
        $topupCode = trim($data['topup_code'] ?? '');

        if (empty($topupCode)) {
            $this->errorResponse('Kode Top-Up tidak valid.');
            return;
        }

        try {
            $midtransService = new \App\Services\MidtransService();
            $liveStatus = $midtransService->getTransactionStatus($topupCode);

            if (empty($liveStatus['success']) || empty($liveStatus['data'])) {
                $this->errorResponse($liveStatus['message'] ?? 'Data transaksi tidak ditemukan di server Midtrans.');
                return;
            }

            $midtransData = $liveStatus['data'];
            $result = $midtransService->processNotification($midtransData);

            $updatedLog = Database::fetchOne("SELECT * FROM `topup_logs` WHERE `topup_code` = ? LIMIT 1", [$topupCode]);

            $this->successResponse('Sinkronisasi status Midtrans berhasil!', [
                'process_result' => $result,
                'updated_log'    => $updatedLog,
                'midtrans_data'  => $midtransData
            ]);
        } catch (\Throwable $e) {
            $this->errorResponse('Gagal menyinkronkan status Midtrans: ' . $e->getMessage());
        }
    }

    public function manualApproveTopup(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        $adminNotes = sanitize($data['admin_notes'] ?? 'Disetujui dan diselesaikan secara manual oleh Administrator');

        if (!$id) {
            $this->errorResponse('ID transaksi top up tidak valid.');
            return;
        }

        $log = (new \App\Models\TopupLog())->find($id);
        if (!$log) {
            $this->errorResponse('Data top up tidak ditemukan.');
            return;
        }

        if ($log['status'] === 'success') {
            $this->errorResponse('Transaksi top up ini sudah berstatus Berhasil (Success).');
            return;
        }

        $userId = (int)$log['user_id'];
        $amount = (float)$log['amount'];
        $topupCode = $log['topup_code'];
        $paymentType = $log['payment_type'] ?: 'midtrans_manual_admin';

        try {
            Database::transaction(function () use ($userId, $amount, $topupCode, $paymentType, $adminNotes, $id) {
                // Check if wallet was credited
                $existingTx = Database::fetchOne("SELECT id FROM `wallet_transactions` WHERE `reference_id` = ? LIMIT 1", [$topupCode]);
                if (!$existingTx && $amount > 0) {
                    $walletModel = new \App\Models\Wallet();
                    $walletModel->credit(
                        $userId,
                        $amount,
                        'topup',
                        "Top Up CicalengkaPay ({$paymentType}) - Disetujui Admin: {$adminNotes}",
                        $topupCode
                    );

                    (new \App\Models\Notification())->createNotification(
                        $userId,
                        'Top Up Berhasil Dikonfirmasi! 🎉',
                        "Saldo CicalengkaPay sebesar " . format_rupiah($amount) . " telah berhasil ditambahkan ke akun Anda.",
                        'wallet'
                    );
                }

                Database::update('topup_logs', [
                    'status'       => 'success',
                    'notes'        => $adminNotes,
                    'updated_at'   => date('Y-m-d H:i:s')
                ], 'id = ?', [$id]);
            });

            $this->successResponse("Top Up #{$topupCode} sebesar " . format_rupiah($amount) . " berhasil disetujui & saldo telah masuk ke akun pengguna.");
        } catch (\Throwable $e) {
            $this->errorResponse('Gagal menyetujui transaksi: ' . $e->getMessage());
        }
    }

    public function manualCancelTopup(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        $adminNotes = sanitize($data['admin_notes'] ?? 'Dibatalkan oleh Administrator');

        if (!$id) {
            $this->errorResponse('ID transaksi tidak valid.');
            return;
        }

        $log = (new \App\Models\TopupLog())->find($id);
        if (!$log) {
            $this->errorResponse('Data transaksi tidak ditemukan.');
            return;
        }

        if ($log['status'] === 'success') {
            $this->errorResponse('Tidak dapat membatalkan transaksi yang sudah berhasil (Success).');
            return;
        }

        (new \App\Models\TopupLog())->markFailed($log['topup_code'], $adminNotes);
        $this->successResponse("Transaksi #{$log['topup_code']} berhasil dibatalkan.");
    }

    // =========================================================================
    // WhatsApp Gateway Management
    // =========================================================================

    public function whatsapp(): void
    {
        $this->view('admin.whatsapp', [
            'title'      => 'WhatsApp Gateway - CicalengkaGO',
            'active_tab' => 'whatsapp',
        ], 'admin_layout');
    }

    /**
     * Proxy status check dari PHP ke Node.js gateway
     * Diakses oleh sidebar JS polling & halaman WA admin
     */
    public function waStatus(): void
    {
        $wa = new \App\Services\WhatsAppService();
        $gatewayUrl = rtrim(\App\Models\BusinessSetting::get('whatsapp_gateway_url', 'http://localhost:3005'), '/');
        $secret     = \App\Models\BusinessSetting::get('whatsapp_gateway_secret', 'cicago_wa_secret_2024');

        $context = stream_context_create([
            'http' => ['method' => 'GET', 'timeout' => 5, 'ignore_errors' => true]
        ]);

        $response = @file_get_contents($gatewayUrl . '/status', false, $context);
        if ($response === false) {
            $this->json(['success' => false, 'ready' => false, 'status' => 'OFFLINE', 'message' => 'Gateway tidak dapat dihubungi.']);
            return;
        }

        $data = json_decode($response, true) ?? [];
        $this->json($data);
    }

    public function waToggleOtp(): void
    {
        $current = \App\Models\BusinessSetting::get('whatsapp_otp_enabled', '1');
        $new     = $current === '1' ? '0' : '1';
        \App\Models\BusinessSetting::set('whatsapp_otp_enabled', $new);
        \App\Models\BusinessSetting::set('otp_verification_channel', $new === '1' ? 'whatsapp_primary' : 'email_only');
        $_SESSION['success'] = $new === '1'
            ? 'OTP WhatsApp berhasil diaktifkan.'
            : 'OTP WhatsApp dinonaktifkan (akan fallback ke Email).';
        $this->redirect('admin/whatsapp');
    }

    public function waSetOtpChannel(): void
    {
        $data    = $this->getPost();
        $channel = sanitize(trim($data['otp_verification_channel'] ?? 'whatsapp_primary'));

        $allowed = ['whatsapp_primary', 'email_primary', 'whatsapp_only', 'email_only'];
        if (!in_array($channel, $allowed, true)) {
            $channel = 'whatsapp_primary';
        }

        \App\Models\BusinessSetting::set('otp_verification_channel', $channel);
        \App\Models\BusinessSetting::set('whatsapp_otp_enabled', $channel === 'email_only' ? '0' : '1');

        $labels = [
            'whatsapp_primary' => 'WhatsApp (Utama) + Fallback Email',
            'email_primary'    => 'Email (Utama) + Fallback WhatsApp',
            'whatsapp_only'   => 'Hanya WhatsApp (WhatsApp-Only)',
            'email_only'      => 'Hanya Email (Email-Only)',
        ];

        $_SESSION['success'] = 'Mode channel verifikasi OTP diubah ke: ' . ($labels[$channel] ?? $channel);
        $this->redirect('admin/whatsapp');
    }

    public function waSaveSettings(): void
    {
        $data = $this->getPost();

        $url    = sanitize(trim($data['whatsapp_gateway_url']    ?? 'http://localhost:3005'));
        $secret = sanitize(trim($data['whatsapp_gateway_secret'] ?? 'cicago_wa_secret_2024'));
        $casaos = sanitize(trim($data['whatsapp_casaos_url']     ?? ''));

        \App\Models\BusinessSetting::set('whatsapp_gateway_url',    $url);
        \App\Models\BusinessSetting::set('whatsapp_gateway_secret', $secret);
        \App\Models\BusinessSetting::set('whatsapp_casaos_url',     $casaos);

        $_SESSION['success'] = 'Konfigurasi WhatsApp Gateway berhasil disimpan.';
        $this->redirect('admin/whatsapp');
    }

    public function waSendTest(): void
    {
        $data  = json_decode(file_get_contents('php://input'), true) ?? $this->getPost();
        $phone = sanitize(trim($data['phone'] ?? ''));

        if (empty($phone)) {
            $this->json(['success' => false, 'message' => 'Nomor HP wajib diisi.']);
            return;
        }

        $otp = sprintf('%06d', rand(100000, 999999));
        $wa  = new \App\Services\WhatsAppService();

        if (!$wa->isReady()) {
            $this->json([
                'success' => false,
                'message' => 'Gateway belum terhubung/belum scan QR. Silakan scan QR Code terlebih dahulu di dashboard admin.'
            ]);
            return;
        }

        $ok = $wa->sendOtp($phone, 'Admin Test', $otp);

        if ($ok) {
            $this->json(['success' => true, 'message' => "OTP test ({$otp}) berhasil dikirim ke {$phone}."]);
        } else {
            $err = $wa->getLastError() ?: 'Periksa log atau pastikan Secret Key & URL Gateway sudah benar.';
            $this->json([
                'success' => false, 
                'message' => 'Gagal mengirim OTP: ' . $err
            ]);
        }
    }

    public function waSendMessage(): void
    {
        $data    = json_decode(file_get_contents('php://input'), true) ?? $this->getPost();
        $phone   = sanitize(trim($data['phone']   ?? ''));
        $message = trim($data['message'] ?? '');

        if (empty($phone) || empty($message)) {
            $this->json(['success' => false, 'message' => 'Nomor HP dan pesan wajib diisi.']);
            return;
        }

        $wa = new \App\Services\WhatsAppService();
        $ok = $wa->sendMessage($phone, $message);

        if ($ok) {
            $this->json(['success' => true, 'message' => "Pesan berhasil dikirim ke {$phone}."]);
        } else {
            $this->json(['success' => false, 'message' => 'Gagal mengirim pesan. Pastikan gateway online dan terhubung.']);
        }
    }

    public function waRestart(): void
    {
        $gatewayUrl = rtrim(\App\Models\BusinessSetting::get('whatsapp_gateway_url', 'http://localhost:3005'), '/');
        $secret     = \App\Models\BusinessSetting::get('whatsapp_gateway_secret', 'cicago_wa_secret_2024');

        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "X-WA-Secret: {$secret}\r\nContent-Type: application/json\r\n",
                'content' => '{}',
                'timeout' => 8,
                'ignore_errors' => true,
            ]
        ]);

        $response = @file_get_contents($gatewayUrl . '/restart', false, $context);
        $data     = $response ? (json_decode($response, true) ?? []) : [];

        if (!empty($data['success'])) {
            $this->json(['success' => true, 'message' => 'Gateway sedang di-restart.']);
        } else {
            $this->errorResponse('Gagal me-restart gateway.');
        }
    }

    public function waDownloadCompose(): void
    {
        $file = ROOT_PATH . '/whatsapp-gateway/docker-compose.yml';
        if (!file_exists($file)) {
            die('File docker-compose.yml tidak ditemukan.');
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="cicago-wa-gateway-docker-compose.yml"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }

    public function waDownloadDockerfile(): void
    {
        $file = ROOT_PATH . '/whatsapp-gateway/Dockerfile';
        if (!file_exists($file)) {
            die('File Dockerfile tidak ditemukan.');
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="Dockerfile"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}
