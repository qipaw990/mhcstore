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

        $sql = "
            SELECT o.*, s.name as store_name, s.latitude as store_lat, s.longitude as store_lng,
                   u.name as customer_name, u.phone as customer_phone,
                   dmu.name as dm_name, dmu.phone as dm_phone, dm.current_latitude as dm_lat, dm.current_longitude as dm_lng
            FROM `orders` o
            LEFT JOIN `stores` s ON o.store_id = s.id
            JOIN `users` u ON o.customer_id = u.id
            LEFT JOIN `delivery_men` dm ON o.delivery_man_id = dm.id
            LEFT JOIN `users` dmu ON dm.user_id = dmu.id
        ";

        if ($statusFilter !== '') {
            $sql .= " WHERE o.order_status = " . Database::getInstance()->quote($statusFilter);
        }

        $sql .= " ORDER BY o.id DESC";

        $orders = Database::query($sql);

        $drivers = Database::query("
            SELECT dm.*, u.name, u.phone, u.avatar
            FROM `delivery_men` dm
            JOIN `users` u ON dm.user_id = u.id
            WHERE dm.is_active = 1
        ");

        $stores = Database::query("SELECT id, name, latitude, longitude, address, phone FROM `stores`");

        $this->view('admin.orders', [
            'title'          => 'Pusat Pemantauan & Dispatch Pesanan',
            'orders'         => $orders,
            'drivers'        => $drivers,
            'stores'         => $stores,
            'status_filter'  => $statusFilter,
            'active_tab'     => 'orders'
        ], 'admin_layout');
    }

    public function orderDetail(string $id): void
    {
        $orderId = (int)$id;
        $order = Database::fetchOne("
            SELECT o.*, s.name as store_name, s.address as store_address, s.phone as store_phone,
                   u.name as customer_name, u.phone as customer_phone, u.email as customer_email,
                   dmu.name as dm_name, dmu.phone as dm_phone, dm.vehicle_type, dm.vehicle_number
            FROM `orders` o
            LEFT JOIN `stores` s ON o.store_id = s.id
            JOIN `users` u ON o.customer_id = u.id
            LEFT JOIN `delivery_men` dm ON o.delivery_man_id = dm.id
            LEFT JOIN `users` dmu ON dm.user_id = dmu.id
            WHERE o.id = ? LIMIT 1
        ", [$orderId]);

        if (!$order) {
            $this->errorResponse('Pesanan tidak ditemukan.', null, 404);
            return;
        }

        $order['items'] = Database::query("SELECT * FROM `order_items` WHERE `order_id` = ?", [$orderId]);
        $order['delivery_address'] = json_decode($order['delivery_address_json'] ?? '{}', true) ?: [];
        $order['parcel_details'] = json_decode($order['parcel_details_json'] ?? '{}', true) ?: [];

        $this->json(['success' => true, 'data' => $order]);
    }

    public function invoice(string $id): void
    {
        $orderId = (int)$id;
        $order = Database::fetchOne("
            SELECT o.*, s.name as store_name, s.address as store_address, s.phone as store_phone,
                   u.name as customer_name, u.phone as customer_phone, u.email as customer_email,
                   dmu.name as dm_name, dmu.phone as dm_phone, dm.vehicle_type, dm.vehicle_number
            FROM `orders` o
            LEFT JOIN `stores` s ON o.store_id = s.id
            JOIN `users` u ON o.customer_id = u.id
            LEFT JOIN `delivery_men` dm ON o.delivery_man_id = dm.id
            LEFT JOIN `users` dmu ON dm.user_id = dmu.id
            WHERE o.id = ? LIMIT 1
        ", [$orderId]);

        if (!$order) {
            die('Invoice pesanan tidak ditemukan.');
        }

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
        $this->successResponse('Pesanan berhasil dibatalkan.');
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
        $stores = Database::query("
            SELECT s.*, m.name as module_name, u.name as vendor_name, u.email as vendor_email, z.name as zone_name,
                   (SELECT COUNT(*) FROM products p WHERE p.store_id = s.id) as product_count
            FROM `stores` s
            JOIN `modules` m ON s.module_id = m.id
            JOIN `users` u ON s.vendor_id = u.id
            LEFT JOIN `zones` z ON s.zone_id = z.id
            ORDER BY s.id DESC
        ");

        $modules = (new Module())->all();
        $zones = (new Zone())->all();
        $vendors = Database::query("SELECT id, name, email, phone FROM users WHERE role = 'vendor'");

        $this->view('admin.stores', [
            'title'      => 'Daftar Toko & Mitra Merchant',
            'stores'     => $stores,
            'modules'    => $modules,
            'zones'      => $zones,
            'vendors'    => $vendors,
            'active_tab' => 'stores'
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

    // =========================================================================
    // 6. Products Management
    // =========================================================================
    public function products(): void
    {
        $storeFilter = (int)($this->getQuery('store_id') ?? 0);

        $sql = "
            SELECT p.*, s.name as store_name, m.name as module_name
            FROM `products` p
            JOIN `stores` s ON p.store_id = s.id
            JOIN `modules` m ON p.module_id = m.id
        ";

        if ($storeFilter > 0) {
            $sql .= " WHERE p.store_id = {$storeFilter}";
        }

        $sql .= " ORDER BY p.id DESC";

        $products = Database::query($sql);
        $stores = Database::query("SELECT id, name FROM stores");
        $modules = (new Module())->all();

        $this->view('admin.products', [
            'title'        => 'Katalog Semua Produk Platform',
            'products'     => $products,
            'stores'       => $stores,
            'modules'      => $modules,
            'store_filter' => $storeFilter,
            'active_tab'   => 'products'
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
        $drivers = Database::query("
            SELECT dm.*, u.name, u.email, u.phone, u.avatar, z.name as zone_name,
                   (SELECT balance FROM wallets WHERE user_id = u.id AND user_type = 'delivery_man' LIMIT 1) as wallet_balance
            FROM `delivery_men` dm
            JOIN `users` u ON dm.user_id = u.id
            LEFT JOIN `zones` z ON dm.zone_id = z.id
            ORDER BY dm.id DESC
        ");

        $zones = (new Zone())->all();

        $this->view('admin.delivery_men', [
            'title'      => 'Armada Driver & Kurir CicalengkaGO',
            'drivers'    => $drivers,
            'zones'      => $zones,
            'active_tab' => 'drivers'
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

        if ($userId && $amount > 0) {
            (new Wallet())->credit($userId, 'delivery_man', $amount, 'topup', 'Top-up saldo oleh Super Admin');
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
        $customers = Database::query("
            SELECT u.*,
                   (SELECT COUNT(*) FROM orders WHERE customer_id = u.id) as order_count,
                   (SELECT balance FROM wallets WHERE user_id = u.id AND user_type = 'customer' LIMIT 1) as wallet_balance
            FROM `users` u
            WHERE u.role = 'customer'
            ORDER BY u.id DESC
        ");

        $this->view('admin.customers', [
            'title'      => 'Manajemen Pengguna & Pelanggan',
            'customers'  => $customers,
            'active_tab' => 'customers'
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

        if ($userId && $amount > 0) {
            (new Wallet())->credit($userId, 'customer', $amount, 'topup', 'Top-up saldo CicagoPay oleh Admin');
            $this->successResponse('Top-up CicagoPay berhasil ditambahkan.');
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
        foreach ($data as $key => $val) {
            if ($key !== '_method') {
                BusinessSetting::set($key, sanitize($val));
            }
        }

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
}
