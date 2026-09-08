<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\BusinessSetting;
use App\Models\Order;
use Exception;

class AdminApiController extends Controller
{
    public function __construct()
    {
        // Allow CORS for decoupled React admin container with origin validation
        $httpOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if (!empty($httpOrigin)) {
            $parsed = parse_url($httpOrigin);
            $originHost = $parsed['host'] ?? '';
            $originScheme = $parsed['scheme'] ?? '';

            $isAllowedOrigin = in_array($originHost, ['localhost', '127.0.0.1', '10.0.2.2'], true)
                || in_array($originScheme, ['capacitor', 'ionic'], true)
                || preg_match('/^(.*\.)?(cicago\.store|cicalengkago\.com|cicalengkago\.store)$/i', $originHost)
                || (isset($_SERVER['HTTP_HOST']) && strcasecmp($originHost, parse_url('http://' . $_SERVER['HTTP_HOST'], PHP_URL_HOST) ?? '') === 0);

            if ($isAllowedOrigin && !headers_sent()) {
                header("Access-Control-Allow-Origin: $httpOrigin");
                header("Access-Control-Allow-Credentials: true");
                header("Access-Control-Max-Age: 86400");
            }
        }
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
            }
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
            }
            exit(0);
        }
    }

    /**
     * Helper to verify admin session or token
     */
    private function checkAdminAuth(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check PHP session
        if (!empty($_SESSION['user']) && in_array($_SESSION['user']['role'] ?? '', ['admin', 'super_admin'])) {
            return true;
        }

        // Check Bearer Token
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            $user = Database::fetchOne("SELECT * FROM users WHERE api_token = ? AND role IN ('admin', 'super_admin') LIMIT 1", [$token]);
            if ($user) {
                $_SESSION['user'] = $user;
                return true;
            }
        }

        return false;
    }

    private function requireAdmin(): void
    {
        // For development flexibility or session-based admin
        if (!$this->checkAdminAuth()) {
            // If running local/same origin or session exists
            if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'] ?? '', ['admin', 'super_admin'])) {
                $this->errorResponse('Akses ditolak. Sesi administrator diperlukan.', null, 401);
                exit;
            }
        }
    }

    /**
     * 1. Dashboard Executive Analytics & Charts Insights
     */
    public function metrics(): void
    {
        try {
            // General Counters
            $totalOrders = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM orders")['c'] ?? 0);
            $totalRevenue = (float)(Database::fetchOne("SELECT SUM(total_amount) as s FROM orders WHERE payment_status = 'paid'")['s'] ?? 0);
            $totalStores = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM stores")['c'] ?? 0);
            $totalDrivers = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM delivery_men")['c'] ?? 0);
            $activeDrivers = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM delivery_men WHERE is_active = 1")['c'] ?? 0);
            $totalCustomers = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM users WHERE role = 'customer'")['c'] ?? 0);

            // Commission & Profit Insight
            $adminCommSetting = (float)(Database::fetchOne("SELECT value_text FROM business_settings WHERE key_name = 'admin_commission_percent'")['value_text'] ?? 10);
            $platformProfit = $totalRevenue * ($adminCommSetting / 100);

            // Order Status Pipeline
            $statusCounts = Database::query("
                SELECT order_status, COUNT(*) as count 
                FROM orders 
                GROUP BY order_status
            ");
            $pipeline = [
                'pending'    => 0,
                'confirmed'  => 0,
                'processing' => 0,
                'on_the_way' => 0,
                'delivered'  => 0,
                'canceled'   => 0
            ];
            foreach ($statusCounts as $sc) {
                if (isset($pipeline[$sc['order_status']])) {
                    $pipeline[$sc['order_status']] = (int)$sc['count'];
                }
            }

            // Payment Methods Breakdown Insight
            $paymentsBreakdown = Database::query("
                SELECT payment_method, COUNT(*) as total_orders, SUM(total_amount) as total_volume 
                FROM orders 
                GROUP BY payment_method
            ");

            // Modules / Category Performance
            $modulesPerformance = Database::query("
                SELECT m.id, m.name, COUNT(DISTINCT s.id) as store_count, COUNT(o.id) as order_count, COALESCE(SUM(o.total_amount), 0) as total_sales
                FROM modules m
                LEFT JOIN stores s ON m.id = s.module_id
                LEFT JOIN orders o ON s.id = o.store_id
                GROUP BY m.id
            ");

            // Recent 7 Days Revenue Trend (Dynamic Insight)
            $revenueTrend = Database::query("
                SELECT 
                    DATE(created_at) as date_val,
                    COUNT(*) as order_count,
                    COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) as revenue
                FROM orders
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at)
                ORDER BY DATE(created_at) ASC
            ");

            // If empty (e.g. fresh DB), provide structured dummy dates with actual sums
            if (empty($revenueTrend)) {
                $days = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
                $revenueTrend = [];
                foreach ($days as $i => $d) {
                    $revenueTrend[] = [
                        'day' => $d,
                        'revenue' => round($totalRevenue / 7 * (0.8 + ($i * 0.08))),
                        'orders' => max(1, round($totalOrders / 7))
                    ];
                }
            }

            // Top 5 Best Seller Products Insight
            $topProducts = Database::query("
                SELECT p.id, p.name, p.price, p.image, s.name as store_name, COUNT(oi.id) as total_sold
                FROM products p
                LEFT JOIN order_items oi ON p.id = oi.product_id
                LEFT JOIN stores s ON p.store_id = s.id
                GROUP BY p.id
                ORDER BY total_sold DESC
                LIMIT 5
            ");

            // Top 5 Stores by Sales Insight
            $topStores = Database::query("
                SELECT s.id, s.name, s.logo, s.address, COUNT(o.id) as total_orders, COALESCE(SUM(o.total_amount), 0) as total_omset
                FROM stores s
                LEFT JOIN orders o ON s.id = o.store_id
                GROUP BY s.id
                ORDER BY total_omset DESC
                LIMIT 5
            ");

            // Recent 10 Orders with complete relations
            $recentOrders = Database::query("
                SELECT o.*, s.name as store_name, u.name as customer_name, u.phone as customer_phone
                FROM orders o
                LEFT JOIN stores s ON o.store_id = s.id
                JOIN users u ON o.customer_id = u.id
                ORDER BY o.id DESC LIMIT 10
            ");

            $this->successResponse('Data analitik berhasil dimuat', [
                'kpi' => [
                    'total_revenue'      => $totalRevenue,
                    'platform_profit'    => $platformProfit,
                    'commission_rate'    => $adminCommSetting,
                    'total_orders'       => $totalOrders,
                    'total_stores'       => $totalStores,
                    'total_drivers'      => $totalDrivers,
                    'active_drivers'     => $activeDrivers,
                    'total_customers'    => $totalCustomers,
                    'success_rate'       => $totalOrders > 0 ? round(($pipeline['delivered'] / $totalOrders) * 100, 1) : 100
                ],
                'pipeline'             => $pipeline,
                'revenue_trend'        => $revenueTrend,
                'payments_breakdown'   => $paymentsBreakdown,
                'modules_performance'  => $modulesPerformance,
                'top_products'         => $topProducts,
                'top_stores'           => $topStores,
                'recent_orders'        => $recentOrders
            ]);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * 2. Live Dispatch & Orders Management
     */
    public function orders(): void
    {
        $status = sanitize($this->getQuery('status') ?? '');
        $search = sanitize($this->getQuery('search') ?? '');
        $page   = max(1, (int)($this->getQuery('page') ?? 1));
        $limit  = max(10, min(100, (int)($this->getQuery('limit') ?? 25)));
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if (!empty($status) && $status !== 'all') {
            $where[] = "o.order_status = ?";
            $params[] = $status;
        }

        if (!empty($search)) {
            $where[] = "(o.order_code LIKE ? OR u.name LIKE ? OR s.name LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $countRow = Database::fetchOne("
            SELECT COUNT(*) as c 
            FROM orders o
            JOIN users u ON o.customer_id = u.id
            LEFT JOIN stores s ON o.store_id = s.id
            {$whereSql}
        ", $params);
        $totalCount = (int)($countRow['c'] ?? 0);

        $sql = "
            SELECT o.*, s.name as store_name, s.latitude as store_lat, s.longitude as store_lng,
                   u.name as customer_name, u.phone as customer_phone,
                   dmu.name as driver_name, dmu.phone as driver_phone,
                   dm.current_latitude as driver_lat, dm.current_longitude as driver_lng
            FROM orders o
            JOIN users u ON o.customer_id = u.id
            LEFT JOIN stores s ON o.store_id = s.id
            LEFT JOIN delivery_men dm ON o.delivery_man_id = dm.id
            LEFT JOIN users dmu ON dm.user_id = dmu.id
            {$whereSql}
            ORDER BY o.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $orders = Database::query($sql, $params);

        $drivers = Database::query("
            SELECT dm.id, dm.user_id, u.name, u.phone, dm.is_active, dm.current_order_id,
                   dm.current_latitude as lat, dm.current_longitude as lng
            FROM delivery_men dm
            JOIN users u ON dm.user_id = u.id
            WHERE dm.is_active = 1
        ");

        $this->successResponse('Daftar pesanan live dispatch', [
            'orders'       => $orders,
            'drivers'      => $drivers,
            'total'        => $totalCount,
            'current_page' => $page,
            'total_pages'  => ceil($totalCount / $limit),
            'limit'        => $limit
        ]);
    }

    /**
     * 3. Assign Driver to Order
     */
    public function assignDriver(): void
    {
        $data = $this->getPost();
        $orderId  = (int)($data['order_id'] ?? 0);
        $driverId = (int)($data['driver_id'] ?? $data['delivery_man_id'] ?? 0);

        if (!$orderId || !$driverId) {
            $this->errorResponse('Order ID dan Driver ID wajib diisi.');
            return;
        }

        Database::execute("UPDATE orders SET delivery_man_id = ?, order_status = 'on_the_way', handover_at = NOW() WHERE id = ?", [$driverId, $orderId]);
        Database::execute("UPDATE delivery_men SET current_order_id = ? WHERE id = ?", [$orderId, $driverId]);

        $this->successResponse('Driver berhasil ditugaskan untuk pengantaran');
    }

    /**
     * 4. Update Order Status
     */
    public function updateOrderStatus(): void
    {
        $data = $this->getPost();
        $orderId = (int)($data['order_id'] ?? 0);
        $status  = sanitize($data['status'] ?? '');

        if (!$orderId || empty($status)) {
            $this->errorResponse('Parameter status tidak valid.');
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
            Database::execute("UPDATE orders SET order_status = ?, `{$timestampCol}` = NOW() WHERE id = ?", [$status, $orderId]);
        } else {
            Database::execute("UPDATE orders SET order_status = ? WHERE id = ?", [$status, $orderId]);
        }

        if ($status === 'delivered') {
            Database::execute("UPDATE orders SET payment_status = 'paid' WHERE id = ?", [$orderId]);
            $ord = Database::fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
            if (!empty($ord['delivery_man_id'])) {
                Database::execute("UPDATE delivery_men SET current_order_id = NULL WHERE id = ?", [$ord['delivery_man_id']]);
            }
        }

        $this->successResponse("Status pesanan #{$orderId} berhasil diubah ke {$status}");
    }

    /**
     * 5. Flexible Business Settings & Payment Switches (GET & SAVE)
     */
    public function getSettings(): void
    {
        $rows = Database::query("SELECT key_name, value_text FROM business_settings");
        $settings = [];
        foreach ($rows as $r) {
            $settings[$r['key_name']] = $r['value_text'];
        }

        // Parse in-house banks
        $banks = [];
        if (!empty($settings['inhouse_banks'])) {
            $banks = json_decode($settings['inhouse_banks'], true) ?: [];
        }

        $this->successResponse('Pengaturan bisnis dan payment gateway', [
            'settings' => $settings,
            'banks'    => $banks
        ]);
    }

    public function saveSettings(): void
    {
        $data = $this->getPost();

        // Update settings in database
        foreach ($data as $key => $val) {
            if ($key === 'inhouse_banks' && is_array($val)) {
                $val = json_encode($val);
            }
            if (is_scalar($val)) {
                $exists = Database::fetchOne("SELECT id FROM business_settings WHERE key_name = ?", [$key]);
                if ($exists) {
                    Database::execute("UPDATE business_settings SET value_text = ? WHERE key_name = ?", [(string)$val, $key]);
                } else {
                    Database::execute("INSERT INTO business_settings (key_name, value_text) VALUES (?, ?)", [$key, (string)$val]);
                }
            }
        }

        $this->successResponse('Pengaturan skema bisnis dan payment berhasil disimpan');
    }

    /**
     * 6. Merchant / Store List & Toggle Open/Status
     */
    public function stores(): void
    {
        $search = sanitize($this->getQuery('search') ?? '');
        $where = "";
        $params = [];
        if (!empty($search)) {
            $where = "WHERE s.name LIKE ? OR s.phone LIKE ? OR s.address LIKE ?";
            $params = ["%{$search}%", "%{$search}%", "%{$search}%"];
        }

        $stores = Database::query("
            SELECT s.*, m.name as module_name, u.email as vendor_email,
                   COUNT(p.id) as product_count,
                   (SELECT COUNT(*) FROM orders o WHERE o.store_id = s.id) as order_count
            FROM stores s
            LEFT JOIN modules m ON s.module_id = m.id
            LEFT JOIN users u ON s.vendor_id = u.id
            LEFT JOIN products p ON s.id = p.store_id
            {$where}
            GROUP BY s.id
            ORDER BY s.id DESC
        ", $params);

        $this->successResponse('Daftar mitra toko/resto', ['stores' => $stores]);
    }

    public function toggleStoreStatus(): void
    {
        $data = $this->getPost();
        $storeId = (int)($data['store_id'] ?? 0);
        $field   = sanitize($data['field'] ?? 'active'); // 'active' or 'is_open'
        
        if (!$storeId) {
            $this->errorResponse('Store ID tidak valid');
            return;
        }

        $col = ($field === 'is_open') ? 'is_open' : 'active';
        Database::execute("UPDATE stores SET `{$col}` = NOT `{$col}` WHERE id = ?", [$storeId]);
        
        $updated = Database::fetchOne("SELECT active, is_open FROM stores WHERE id = ?", [$storeId]);
        $this->successResponse('Status toko berhasil diperbarui', $updated);
    }

    /**
     * 7. Products Catalog & Stock Switch
     */
    public function products(): void
    {
        $search  = sanitize($this->getQuery('search') ?? '');
        $storeId = (int)($this->getQuery('store_id') ?? 0);
        $page    = max(1, (int)($this->getQuery('page') ?? 1));
        $limit   = 30;
        $offset  = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = "(p.name LIKE ? OR s.name LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($storeId > 0) {
            $where[] = "p.store_id = ?";
            $params[] = $storeId;
        }

        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $count = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM products p LEFT JOIN stores s ON p.store_id = s.id {$whereSql}", $params)['c'] ?? 0);

        $sql = "
            SELECT p.*, s.name as store_name, c.name as category_name
            FROM products p
            LEFT JOIN stores s ON p.store_id = s.id
            LEFT JOIN categories c ON p.category_id = c.id
            {$whereSql}
            ORDER BY p.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $products = Database::query($sql, $params);

        $this->successResponse('Katalog produk', [
            'products'     => $products,
            'total'        => $count,
            'current_page' => $page,
            'total_pages'  => ceil($count / $limit)
        ]);
    }

    public function toggleProductStatus(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        if (!$id) {
            $this->errorResponse('ID produk tidak valid');
            return;
        }

        Database::execute("UPDATE products SET status = NOT status WHERE id = ?", [$id]);
        $this->successResponse('Status produk berhasil diubah');
    }

    /**
     * 8. Driver Fleet Monitoring & Saldo
     */
    public function drivers(): void
    {
        $drivers = Database::query("
            SELECT dm.*, u.name, u.phone, u.avatar, u.email,
                   w.balance as wallet_balance,
                   (SELECT COUNT(*) FROM orders o WHERE o.delivery_man_id = dm.id AND o.order_status = 'delivered') as completed_orders
            FROM delivery_men dm
            JOIN users u ON dm.user_id = u.id
            LEFT JOIN wallets w ON u.id = w.user_id
            ORDER BY dm.is_active DESC, dm.id DESC
        ");

        $this->successResponse('Armada kurir delivery', ['drivers' => $drivers]);
    }

    public function toggleDriverStatus(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        if (!$id) {
            $this->errorResponse('ID kurir tidak valid');
            return;
        }

        Database::execute("UPDATE delivery_men SET is_active = NOT is_active WHERE id = ?", [$id]);
        $this->successResponse('Status kurir berhasil diperbarui');
    }
}
