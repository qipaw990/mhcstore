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

    /**
     * Mapping role DB -> label Bahasa Indonesia
     */
    private function roleLabel(string $role): string
    {
        return match (strtolower($role)) {
            'customer' => 'Pelanggan',
            'delivery_man', 'driver', 'delivery' => 'Driver',
            'vendor', 'merchant', 'store' => 'Merchant',
            default => ucfirst(str_replace('_', ' ', $role))
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'calling' => 'Berdering',
            'connected' => 'Terhubung',
            'rejected' => 'Ditolak',
            'ended' => 'Selesai',
            'no_answer' => 'Tidak Dijawab',
            default => ucfirst($status)
        };
    }

    private function directionLabel(string $callerRole, string $receiverRole): string
    {
        $c = $this->roleLabel($callerRole);
        $d = $this->roleLabel($receiverRole);
        return "{$c} → {$d}";
    }

    /**
     * 9. Voice Calls Monitoring & History
     * Arah panggilan yang didukung:
     * - Pelanggan ↔ Driver
     * - Pelanggan ↔ Merchant
     */
    public function voiceCalls(): void
    {
        $status = sanitize($this->getQuery('status') ?? '');
        $search = sanitize($this->getQuery('search') ?? '');
        $direction = sanitize($this->getQuery('direction') ?? ''); // e.g. cust_driver, cust_merchant
        $page   = max(1, (int)($this->getQuery('page') ?? 1));
        $limit  = max(10, min(100, (int)($this->getQuery('limit') ?? 25)));
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if (!empty($status) && $status !== 'all') {
            $where[] = "vc.status = ?";
            $params[] = $status;
        }

        if (!empty($search)) {
            $where[] = "(vc.order_code LIKE ? OR uc.name LIKE ? OR ud.name LIKE ? OR s.name LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($direction === 'cust_driver') {
            $where[] = "((vc.caller_role = 'customer' AND vc.receiver_role IN ('delivery_man','driver','delivery')) OR (vc.caller_role IN ('delivery_man','driver','delivery') AND vc.receiver_role = 'customer'))";
        } elseif ($direction === 'cust_merchant') {
            $where[] = "((vc.caller_role = 'customer' AND vc.receiver_role IN ('vendor','merchant','store')) OR (vc.caller_role IN ('vendor','merchant','store') AND vc.receiver_role = 'customer'))";
        } elseif ($direction === 'cust_to_driver') {
            $where[] = "(vc.caller_role = 'customer' AND vc.receiver_role IN ('delivery_man','driver','delivery'))";
        } elseif ($direction === 'driver_to_cust') {
            $where[] = "(vc.caller_role IN ('delivery_man','driver','delivery') AND vc.receiver_role = 'customer')";
        } elseif ($direction === 'cust_to_merchant') {
            $where[] = "(vc.caller_role = 'customer' AND vc.receiver_role IN ('vendor','merchant','store'))";
        } elseif ($direction === 'merchant_to_cust') {
            $where[] = "(vc.caller_role IN ('vendor','merchant','store') AND vc.receiver_role = 'customer')";
        }

        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $countRow = Database::fetchOne("
            SELECT COUNT(*) as c 
            FROM voice_calls vc
            LEFT JOIN users uc ON vc.caller_id = uc.id
            LEFT JOIN users ud ON vc.receiver_id = ud.id
            LEFT JOIN orders o ON vc.order_code = o.order_code
            LEFT JOIN stores s ON o.store_id = s.id
            {$whereSql}
        ", $params);
        $totalCount = (int)($countRow['c'] ?? 0);

        $sql = "
            SELECT vc.*,
                   uc.name as caller_name, uc.avatar as caller_avatar, uc.phone as caller_phone,
                   ud.name as receiver_name, ud.avatar as receiver_avatar, ud.phone as receiver_phone,
                   s.name as store_name, s.logo as store_logo
            FROM voice_calls vc
            LEFT JOIN users uc ON vc.caller_id = uc.id
            LEFT JOIN users ud ON vc.receiver_id = ud.id
            LEFT JOIN orders o ON vc.order_code = o.order_code
            LEFT JOIN stores s ON o.store_id = s.id
            {$whereSql}
            ORDER BY vc.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $rawCalls = Database::query($sql, $params);
        $calls = [];
        foreach ($rawCalls as $vc) {
            $callerRole = $vc['caller_role'] ?? 'unknown';
            $receiverRole = $vc['receiver_role'] ?? 'unknown';

            $cName = $vc['caller_name'] ?? 'Pengguna';
            if (in_array(strtolower($callerRole), ['vendor', 'merchant', 'store'], true) && !empty($vc['store_name'])) {
                $cName = $vc['store_name'];
            }
            $cAvatar = $vc['caller_avatar'] ?? '';
            if (in_array(strtolower($callerRole), ['vendor', 'merchant', 'store'], true) && !empty($vc['store_logo'])) {
                $cAvatar = $vc['store_logo'];
            }

            $rName = $vc['receiver_name'] ?? 'Pengguna';
            if (in_array(strtolower($receiverRole), ['vendor', 'merchant', 'store'], true) && !empty($vc['store_name'])) {
                $rName = $vc['store_name'];
            }
            $rAvatar = $vc['receiver_avatar'] ?? '';
            if (in_array(strtolower($receiverRole), ['vendor', 'merchant', 'store'], true) && !empty($vc['store_logo'])) {
                $rAvatar = $vc['store_logo'];
            }

            $durSec = null;
            $durMin = null;
            if (!empty($vc['connected_at']) && in_array($vc['status'], ['ended', 'connected'], true)) {
                $endTime = $vc['status'] === 'connected' ? time() : strtotime($vc['updated_at'] ?? '');
                $durSec = max(0, $endTime - strtotime($vc['connected_at']));
                $durMin = sprintf('%02d:%02d', floor($durSec / 60), $durSec % 60);
            }

            $calls[] = array_merge($vc, [
                'caller_name'         => $cName,
                'caller_avatar'       => $cAvatar,
                'caller_role_label'   => $this->roleLabel($callerRole),
                'receiver_name'       => $rName,
                'receiver_avatar'     => $rAvatar,
                'receiver_role_label' => $this->roleLabel($receiverRole),
                'status_label'        => $this->statusLabel($vc['status'] ?? ''),
                'direction_label'     => $this->directionLabel($callerRole, $receiverRole),
                'store_name'          => $vc['store_name'] ?? '',
                'duration_seconds'    => $durSec,
                'duration_formatted'  => $durMin,
            ]);
        }

        $statsRow = Database::fetchOne("
            SELECT 
                COUNT(*) as total_calls,
                SUM(CASE WHEN vc.status = 'connected' THEN 1 ELSE 0 END) as connected,
                SUM(CASE WHEN vc.status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN vc.status = 'ended' THEN 1 ELSE 0 END) as ended,
                SUM(CASE WHEN vc.status = 'calling' THEN 1 ELSE 0 END) as calling,
                SUM(CASE WHEN vc.status = 'no_answer' THEN 1 ELSE 0 END) as no_answer,
                AVG(CASE WHEN vc.connected_at IS NOT NULL AND vc.updated_at IS NOT NULL 
                    THEN TIMESTAMPDIFF(SECOND, vc.connected_at, vc.updated_at) ELSE NULL END) as avg_duration_sec,
                SUM(CASE WHEN vc.caller_role = 'customer' AND vc.receiver_role IN ('delivery_man','driver','delivery') THEN 1 ELSE 0 END) as cust_to_driver,
                SUM(CASE WHEN vc.caller_role IN ('delivery_man','driver','delivery') AND vc.receiver_role = 'customer' THEN 1 ELSE 0 END) as driver_to_cust,
                SUM(CASE WHEN vc.caller_role = 'customer' AND vc.receiver_role IN ('vendor','merchant','store') THEN 1 ELSE 0 END) as cust_to_merchant,
                SUM(CASE WHEN vc.caller_role IN ('vendor','merchant','store') AND vc.receiver_role = 'customer' THEN 1 ELSE 0 END) as merchant_to_cust
            FROM voice_calls vc
        ");

        $this->successResponse('Daftar riwayat panggilan suara', [
            'calls'        => $calls,
            'total'        => $totalCount,
            'current_page' => $page,
            'total_pages'  => ceil($totalCount / $limit),
            'limit'        => $limit,
            'stats'        => [
                'total'      => (int)($statsRow['total_calls'] ?? 0),
                'connected'  => (int)($statsRow['connected'] ?? 0),
                'rejected'   => (int)($statsRow['rejected'] ?? 0),
                'ended'      => (int)($statsRow['ended'] ?? 0),
                'calling'    => (int)($statsRow['calling'] ?? 0),
                'no_answer'  => (int)($statsRow['no_answer'] ?? 0),
                'avg_duration_sec' => round((float)($statsRow['avg_duration_sec'] ?? 0), 1),
                'directions' => [
                    'cust_to_driver'     => (int)($statsRow['cust_to_driver'] ?? 0),
                    'driver_to_cust'     => (int)($statsRow['driver_to_cust'] ?? 0),
                    'cust_to_merchant'   => (int)($statsRow['cust_to_merchant'] ?? 0),
                    'merchant_to_cust'   => (int)($statsRow['merchant_to_cust'] ?? 0),
                ]
            ],
            'supported_directions' => [
                'cust_driver'    => 'Pelanggan ↔ Driver',
                'cust_merchant'  => 'Pelanggan ↔ Merchant',
            ]
        ]);
    }

    /**
     * 10. Voice Call Detail & Force End
     */
    public function voiceCallDetail(): void
    {
        $callId = (int)($this->getQuery('id') ?? 0);
        if (!$callId) {
            $this->errorResponse('ID panggilan tidak valid');
            return;
        }

        $call = Database::fetchOne("
            SELECT vc.*,
                   uc.name as caller_name, uc.avatar as caller_avatar, uc.phone as caller_phone,
                   ud.name as receiver_name, ud.avatar as receiver_avatar, ud.phone as receiver_phone,
                   s.name as store_name, s.logo as store_logo
            FROM voice_calls vc
            LEFT JOIN users uc ON vc.caller_id = uc.id
            LEFT JOIN users ud ON vc.receiver_id = ud.id
            LEFT JOIN orders o ON vc.order_code = o.order_code
            LEFT JOIN stores s ON o.store_id = s.id
            WHERE vc.id = ? LIMIT 1
        ", [$callId]);

        if (!$call) {
            $this->errorResponse('Data panggilan tidak ditemukan', null, 404);
            return;
        }

        $callerRole = $call['caller_role'] ?? 'unknown';
        $receiverRole = $call['receiver_role'] ?? 'unknown';

        $cName = $call['caller_name'] ?? 'Pengguna';
        if (in_array(strtolower($callerRole), ['vendor', 'merchant', 'store'], true) && !empty($call['store_name'])) {
            $cName = $call['store_name'];
        }
        $cAvatar = $call['caller_avatar'] ?? '';
        if (in_array(strtolower($callerRole), ['vendor', 'merchant', 'store'], true) && !empty($call['store_logo'])) {
            $cAvatar = $call['store_logo'];
        }

        $rName = $call['receiver_name'] ?? 'Pengguna';
        if (in_array(strtolower($receiverRole), ['vendor', 'merchant', 'store'], true) && !empty($call['store_name'])) {
            $rName = $call['store_name'];
        }
        $rAvatar = $call['receiver_avatar'] ?? '';
        if (in_array(strtolower($receiverRole), ['vendor', 'merchant', 'store'], true) && !empty($call['store_logo'])) {
            $rAvatar = $call['store_logo'];
        }

        $durationSec = null;
        if (!empty($call['connected_at']) && in_array($call['status'], ['ended', 'connected'], true)) {
            $endTime = $call['status'] === 'connected' ? time() : strtotime($call['updated_at'] ?? '');
            $durationSec = max(0, $endTime - strtotime($call['connected_at']));
        }

        $call['caller_name'] = $cName;
        $call['caller_avatar'] = $cAvatar;
        $call['caller_role_label'] = $this->roleLabel($callerRole);
        $call['receiver_name'] = $rName;
        $call['receiver_avatar'] = $rAvatar;
        $call['receiver_role_label'] = $this->roleLabel($receiverRole);
        $call['status_label'] = $this->statusLabel($call['status'] ?? '');
        $call['direction_label'] = $this->directionLabel($callerRole, $receiverRole);
        $call['duration_seconds'] = $durationSec;
        $call['duration_formatted'] = $durationSec !== null
            ? sprintf('%02d:%02d', floor($durationSec / 60), $durationSec % 60)
            : null;

        $this->successResponse('Detail panggilan suara', ['call' => $call]);
    }

    public function voiceCallForceEnd(): void
    {
        $data = $this->getPost();
        $callId = (int)($data['id'] ?? 0);
        if (!$callId) {
            $this->errorResponse('ID panggilan tidak valid');
            return;
        }

        $call = Database::fetchOne("SELECT id, status FROM voice_calls WHERE id = ? LIMIT 1", [$callId]);
        if (!$call) {
            $this->errorResponse('Panggilan tidak ditemukan', null, 404);
            return;
        }

        if (!in_array($call['status'], ['calling', 'connected'], true)) {
            $this->errorResponse('Panggilan sudah tidak aktif');
            return;
        }

        Database::execute("UPDATE voice_calls SET status = 'ended' WHERE id = ?", [$callId]);
        $this->successResponse('Panggilan berhasil diakhiri oleh admin');
    }

    /**
     * 11. Customer CRM Management
     */
    public function customers(): void
    {
        $search = sanitize($this->getQuery('search') ?? '');
        $page   = max(1, (int)($this->getQuery('page') ?? 1));
        $limit  = max(10, min(100, (int)($this->getQuery('limit') ?? 25)));
        $offset = ($page - 1) * $limit;

        $where = ["u.role = 'customer'"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(u.name LIKE ? OR u.phone LIKE ? OR u.email LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $whereSql = "WHERE " . implode(" AND ", $where);

        $count = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM users u {$whereSql}", $params)['c'] ?? 0);

        $sql = "
            SELECT u.*,
                   COALESCE(oc.order_count, 0) as order_count,
                   COALESCE(w.balance, 0) as wallet_balance
            FROM users u
            LEFT JOIN (
                SELECT customer_id, COUNT(*) as order_count FROM orders GROUP BY customer_id
            ) oc ON u.id = oc.customer_id
            LEFT JOIN wallets w ON w.user_id = u.id AND w.user_type = 'customer'
            {$whereSql}
            ORDER BY u.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $customers = Database::query($sql, $params);

        $this->successResponse('Daftar pengguna & pelanggan', [
            'customers'    => $customers,
            'total'        => $count,
            'current_page' => $page,
            'total_pages'  => ceil($count / $limit)
        ]);
    }

    public function customerHistory(): void
    {
        $id = (int)($this->getQuery('id') ?? 0);
        if (!$id) {
            $this->errorResponse('Customer ID tidak valid');
            return;
        }

        $orders = Database::query("
            SELECT o.*, s.name as store_name
            FROM orders o
            LEFT JOIN stores s ON o.store_id = s.id
            WHERE o.customer_id = ?
            ORDER BY o.id DESC LIMIT 20
        ", [$id]);

        $this->successResponse('Riwayat pesanan pelanggan', ['orders' => $orders]);
    }

    public function toggleCustomerStatus(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        if (!$id) {
            $this->errorResponse('ID pelanggan tidak valid');
            return;
        }

        Database::execute("UPDATE users SET is_active = NOT is_active WHERE id = ?", [$id]);
        $updated = Database::fetchOne("SELECT is_active FROM users WHERE id = ?", [$id]);
        $this->successResponse('Status pelanggan berhasil diperbarui', $updated);
    }

    public function topupCustomer(): void
    {
        $data = $this->getPost();
        $userId = (int)($data['user_id'] ?? 0);
        $amount = (float)($data['amount'] ?? 0);
        $notes  = sanitize($data['notes'] ?? 'Top-up saldo CicalengkaPay oleh Super Admin');

        if (!$userId || $amount <= 0) {
            $this->errorResponse('User ID dan nominal topup wajib valid');
            return;
        }

        (new \App\Models\Wallet())->credit($userId, $amount, 'topup', $notes);
        (new \App\Models\TopupLog())->create([
            'topup_code'     => 'ADM-TOP-' . $userId . '-' . time(),
            'user_id'        => $userId,
            'amount'         => $amount,
            'payment_method' => 'manual_admin',
            'payment_type'   => 'manual_admin',
            'status'         => 'success',
            'notes'          => $notes,
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s')
        ]);

        $wallet = Database::fetchOne("SELECT balance FROM wallets WHERE user_id = ? AND user_type = 'customer'", [$userId]);
        $this->successResponse('Saldo berhasil ditambahkan ke pelanggan', ['balance' => $wallet['balance'] ?? $amount]);
    }

    /**
     * 12. Banners Promo & Carousel Management
     */
    public function banners(): void
    {
        $banners = Database::query("
            SELECT b.*, m.name as module_name
            FROM banners b
            LEFT JOIN modules m ON b.module_id = m.id
            ORDER BY b.priority ASC, b.id DESC
        ");

        $modules = Database::query("SELECT id, name FROM modules WHERE status = 1 ORDER BY id ASC");
        $stores = Database::query("SELECT id, name, module_id FROM stores WHERE status = 'approved' ORDER BY name ASC");

        $this->successResponse('Daftar banner promo', [
            'banners' => $banners,
            'modules' => $modules,
            'stores'  => $stores
        ]);
    }

    public function saveBanner(): void
    {
        $data = $this->getPost();
        $id = !empty($data['id']) ? (int)$data['id'] : null;
        $title = sanitize($data['title'] ?? '');
        $image = sanitize($data['image'] ?? '');
        $moduleId = !empty($data['module_id']) ? (int)$data['module_id'] : null;
        $priority = max(1, (int)($data['priority'] ?? 1));
        $targetType = sanitize($data['target_type'] ?? 'store');
        $targetId = sanitize($data['target_id'] ?? '1');
        $status = isset($data['status']) ? (int)$data['status'] : 1;

        if (empty($title)) {
            $this->errorResponse('Judul banner wajib diisi');
            return;
        }

        if ($id) {
            Database::execute("
                UPDATE banners SET 
                    title = ?, image = ?, module_id = ?, priority = ?, 
                    target_type = ?, target_id = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ", [$title, $image, $moduleId, $priority, $targetType, $targetId, $status, $id]);
            $this->successResponse('Banner promo berhasil diperbarui');
        } else {
            Database::execute("
                INSERT INTO banners (title, image, module_id, priority, target_type, target_id, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ", [$title, $image, $moduleId, $priority, $targetType, $targetId, $status]);
            $this->successResponse('Banner promo baru berhasil ditambahkan');
        }
    }

    public function toggleBannerStatus(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        if (!$id) {
            $this->errorResponse('Banner ID tidak valid');
            return;
        }

        Database::execute("UPDATE banners SET status = NOT status WHERE id = ?", [$id]);
        $updated = Database::fetchOne("SELECT status FROM banners WHERE id = ?", [$id]);
        $this->successResponse('Status banner berhasil diperbarui', $updated);
    }

    public function deleteBanner(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        if (!$id) {
            $this->errorResponse('Banner ID tidak valid');
            return;
        }

        Database::execute("DELETE FROM banners WHERE id = ?", [$id]);
        $this->successResponse('Banner berhasil dihapus');
    }

    /**
     * 13. Service Modules Management
     */
    public function modules(): void
    {
        $modules = Database::query("
            SELECT m.*, COUNT(s.id) as store_count
            FROM modules m
            LEFT JOIN stores s ON m.id = s.module_id
            GROUP BY m.id
            ORDER BY m.id ASC
        ");

        $this->successResponse('Daftar modul layanan', ['modules' => $modules]);
    }

    public function saveModule(): void
    {
        $data = $this->getPost();
        $id = !empty($data['id']) ? (int)$data['id'] : null;
        $name = sanitize($data['name'] ?? '');
        $moduleType = sanitize($data['module_type'] ?? 'food');
        $icon = sanitize($data['icon'] ?? 'bi-box');
        $themeColor = sanitize($data['theme_color'] ?? '#00AA13');
        $description = sanitize($data['description'] ?? '');
        $status = isset($data['status']) ? (int)$data['status'] : 1;

        if (empty($name)) {
            $this->errorResponse('Nama modul wajib diisi');
            return;
        }

        if ($id) {
            Database::execute("
                UPDATE modules SET 
                    name = ?, module_type = ?, icon = ?, theme_color = ?, 
                    description = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ", [$name, $moduleType, $icon, $themeColor, $description, $status, $id]);
            $this->successResponse('Modul layanan berhasil diperbarui');
        } else {
            Database::execute("
                INSERT INTO modules (name, module_type, icon, theme_color, description, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ", [$name, $moduleType, $icon, $themeColor, $description, $status]);
            $this->successResponse('Modul layanan baru berhasil ditambahkan');
        }
    }

    public function toggleModuleStatus(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        if (!$id) {
            $this->errorResponse('Module ID tidak valid');
            return;
        }

        Database::execute("UPDATE modules SET status = NOT status WHERE id = ?", [$id]);
        $updated = Database::fetchOne("SELECT status FROM modules WHERE id = ?", [$id]);
        $this->successResponse('Status modul berhasil diperbarui', $updated);
    }

    /**
     * 14. Operational Zones & Geofencing
     */
    public function zones(): void
    {
        $zones = Database::query("SELECT * FROM zones ORDER BY id ASC");
        $this->successResponse('Daftar zona operasional', ['zones' => $zones]);
    }

    public function saveZone(): void
    {
        $data = $this->getPost();
        $id = !empty($data['id']) ? (int)$data['id'] : null;
        $name = sanitize($data['name'] ?? 'Zona Cicalengka');
        $coords = $data['coordinates_json'] ?? '[]';
        if (is_array($coords)) {
            $coords = json_encode($coords);
        }
        $minCharge = (float)($data['min_delivery_charge'] ?? 5000);
        $perKmCharge = (float)($data['per_km_delivery_charge'] ?? 2500);
        $lat = (float)($data['center_latitude'] ?? -6.9840);
        $lng = (float)($data['center_longitude'] ?? 107.8340);
        $status = isset($data['status']) ? (int)$data['status'] : 1;

        if ($id) {
            Database::execute("
                UPDATE zones SET 
                    name = ?, coordinates_json = ?, min_delivery_charge = ?, 
                    per_km_delivery_charge = ?, center_latitude = ?, center_longitude = ?, 
                    status = ?, updated_at = NOW()
                WHERE id = ?
            ", [$name, $coords, $minCharge, $perKmCharge, $lat, $lng, $status, $id]);
            $this->successResponse('Zona operasional berhasil diperbarui');
        } else {
            Database::execute("
                INSERT INTO zones (name, coordinates_json, min_delivery_charge, per_km_delivery_charge, center_latitude, center_longitude, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ", [$name, $coords, $minCharge, $perKmCharge, $lat, $lng, $status]);
            $this->successResponse('Zona operasional baru berhasil ditambahkan');
        }
    }

    public function deleteZone(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        if (!$id) {
            $this->errorResponse('Zone ID tidak valid');
            return;
        }

        Database::execute("DELETE FROM zones WHERE id = ?", [$id]);
        $this->successResponse('Zona berhasil dihapus');
    }

    /**
     * 15. Wallet Topups Management
     */
    public function topups(): void
    {
        $status = sanitize($this->getQuery('status') ?? 'all');
        $search = sanitize($this->getQuery('search') ?? '');
        $page   = max(1, (int)($this->getQuery('page') ?? 1));
        $limit  = max(10, min(100, (int)($this->getQuery('limit') ?? 25)));
        $offset = ($page - 1) * $limit;

        $where = ["1=1"];
        $params = [];

        if (!empty($status) && $status !== 'all') {
            $where[] = "tl.status = ?";
            $params[] = $status;
        }

        if (!empty($search)) {
            $where[] = "(tl.topup_code LIKE ? OR u.name LIKE ? OR u.phone LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $whereSql = "WHERE " . implode(" AND ", $where);

        $count = (int)(Database::fetchOne("
            SELECT COUNT(*) as c 
            FROM topup_logs tl 
            JOIN users u ON tl.user_id = u.id 
            {$whereSql}
        ", $params)['c'] ?? 0);

        $sql = "
            SELECT tl.*, u.name as user_name, u.phone as user_phone, u.email as user_email, u.role as user_role,
                   COALESCE(w.balance, 0) as current_wallet_balance
            FROM topup_logs tl
            JOIN users u ON tl.user_id = u.id
            LEFT JOIN wallets w ON w.user_id = u.id AND w.user_type = 'customer'
            {$whereSql}
            ORDER BY tl.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $topups = Database::query($sql, $params);

        $pendingCount = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM topup_logs WHERE status = 'pending'")['c'] ?? 0);

        $this->successResponse('Daftar riwayat topup saldo', [
            'topups'        => $topups,
            'total'         => $count,
            'pending_count' => $pendingCount,
            'current_page'  => $page,
            'total_pages'   => ceil($count / $limit)
        ]);
    }

    public function manualApproveTopup(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        $notes = sanitize($data['notes'] ?? 'Disetujui manual oleh Super Admin');

        if (!$id) {
            $this->errorResponse('ID topup tidak valid');
            return;
        }

        $topup = Database::fetchOne("SELECT * FROM topup_logs WHERE id = ? LIMIT 1", [$id]);
        if (!$topup) {
            $this->errorResponse('Data topup tidak ditemukan', null, 404);
            return;
        }

        if ($topup['status'] === 'success') {
            $this->errorResponse('Topup ini sudah disetujui sebelumnya');
            return;
        }

        $userId = (int)$topup['user_id'];
        $amount = (float)$topup['amount'];

        (new \App\Models\Wallet())->credit($userId, $amount, 'topup', "Top-up disetujui admin ({$topup['topup_code']})");
        Database::execute("UPDATE topup_logs SET status = 'success', notes = ?, updated_at = NOW() WHERE id = ?", [$notes, $id]);

        $this->successResponse('Topup berhasil disetujui dan saldo berhasil dikreditkan ke pengguna');
    }

    public function manualCancelTopup(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        $notes = sanitize($data['notes'] ?? 'Ditolak oleh Super Admin');

        if (!$id) {
            $this->errorResponse('ID topup tidak valid');
            return;
        }

        Database::execute("UPDATE topup_logs SET status = 'canceled', notes = ?, updated_at = NOW() WHERE id = ?", [$notes, $id]);
        $this->successResponse('Pengajuan topup berhasil dibatalkan');
    }

    /**
     * 16. Withdrawals Management
     */
    public function withdrawals(): void
    {
        $status = sanitize($this->getQuery('status') ?? 'all');
        $page   = max(1, (int)($this->getQuery('page') ?? 1));
        $limit  = max(10, min(100, (int)($this->getQuery('limit') ?? 25)));
        $offset = ($page - 1) * $limit;

        $where = ["1=1"];
        $params = [];

        if (in_array($status, ['pending', 'approved', 'rejected'])) {
            $where[] = "wr.status = ?";
            $params[] = $status;
        }

        $whereSql = "WHERE " . implode(" AND ", $where);

        $count = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM withdraw_requests wr {$whereSql}", $params)['c'] ?? 0);

        $sql = "
            SELECT wr.*, u.name as user_name, u.phone as user_phone, u.email as user_email, u.role as user_role
            FROM withdraw_requests wr
            LEFT JOIN users u ON wr.user_id = u.id
            {$whereSql}
            ORDER BY wr.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $withdrawals = Database::query($sql, $params);

        $pendingCount = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM withdraw_requests WHERE status = 'pending'")['c'] ?? 0);
        $totalPaid = (float)(Database::fetchOne("SELECT COALESCE(SUM(amount), 0) as s FROM withdraw_requests WHERE status = 'approved'")['s'] ?? 0);

        $this->successResponse('Daftar pencairan dana penarikan', [
            'withdrawals'   => $withdrawals,
            'total'         => $count,
            'pending_count' => $pendingCount,
            'total_paid'    => $totalPaid,
            'current_page'  => $page,
            'total_pages'   => ceil($count / $limit)
        ]);
    }

    public function updateWithdrawalStatus(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        $status = sanitize($data['status'] ?? '');
        $notes = sanitize($data['admin_notes'] ?? '');

        if (!$id || !in_array($status, ['approved', 'rejected'])) {
            $this->errorResponse('ID atau status pencairan dana tidak valid');
            return;
        }

        $req = Database::fetchOne("SELECT * FROM withdraw_requests WHERE id = ? LIMIT 1", [$id]);
        if (!$req) {
            $this->errorResponse('Pengajuan penarikan dana tidak ditemukan', null, 404);
            return;
        }

        if ($req['status'] !== 'pending') {
            $this->errorResponse('Pengajuan ini sudah diproses sebelumnya');
            return;
        }

        Database::execute("
            UPDATE withdraw_requests 
            SET status = ?, admin_notes = ?, approved_at = NOW(), updated_at = NOW() 
            WHERE id = ?
        ", [$status, $notes, $id]);

        if ($status === 'rejected') {
            (new \App\Models\Wallet())->credit((int)$req['user_id'], (float)$req['amount'], 'refund', "Pengembalian dana penarikan ditolak: {$notes}");
        }

        $this->successResponse("Status penarikan dana berhasil diubah ke {$status}");
    }

    /**
     * 17. Payment Methods & Gateway Configuration
     */
    public function paymentMethods(): void
    {
        $rows = Database::query("SELECT key_name, value_text FROM business_settings");
        $settings = [];
        foreach ($rows as $r) {
            $settings[$r['key_name']] = $r['value_text'];
        }

        $banks = [];
        if (!empty($settings['inhouse_banks'])) {
            $banks = json_decode($settings['inhouse_banks'], true) ?: [];
        }

        $this->successResponse('Metode pembayaran & gateway', [
            'banks'               => $banks,
            'qris_image'          => $settings['inhouse_qris_image'] ?? '',
            'qris_merchant_name'  => $settings['inhouse_qris_merchant_name'] ?? 'CicalengkaGO Official',
            'qris_nmid'           => $settings['inhouse_qris_nmid'] ?? '',
            'midtrans_enabled'    => ($settings['midtrans_enabled'] ?? '0') === '1',
            'midtrans_server_key' => $settings['midtrans_server_key'] ?? '',
            'midtrans_client_key' => $settings['midtrans_client_key'] ?? '',
            'doku_enabled'        => ($settings['doku_enabled'] ?? '0') === '1',
            'cod_enabled'         => ($settings['cod_enabled'] ?? '1') === '1',
            'wallet_enabled'      => ($settings['wallet_enabled'] ?? '1') === '1',
        ]);
    }

    public function savePaymentBank(): void
    {
        $data = $this->getPost();
        $banks = $data['banks'] ?? [];
        if (is_string($banks)) {
            $banks = json_decode($banks, true) ?: [];
        }

        $jsonStr = json_encode($banks);
        $exists = Database::fetchOne("SELECT id FROM business_settings WHERE key_name = 'inhouse_banks'");
        if ($exists) {
            Database::execute("UPDATE business_settings SET value_text = ? WHERE key_name = 'inhouse_banks'", [$jsonStr]);
        } else {
            Database::execute("INSERT INTO business_settings (key_name, value_text) VALUES ('inhouse_banks', ?)", [$jsonStr]);
        }

        $this->successResponse('Daftar rekening bank transfer berhasil diperbarui');
    }

    public function savePaymentQris(): void
    {
        $data = $this->getPost();
        $merchantName = sanitize($data['merchant_name'] ?? '');
        $nmid         = sanitize($data['nmid'] ?? '');
        $image        = sanitize($data['image'] ?? '');

        $updates = [
            'inhouse_qris_merchant_name' => $merchantName,
            'inhouse_qris_nmid'          => $nmid,
            'inhouse_qris_image'         => $image
        ];

        foreach ($updates as $k => $v) {
            $exists = Database::fetchOne("SELECT id FROM business_settings WHERE key_name = ?", [$k]);
            if ($exists) {
                Database::execute("UPDATE business_settings SET value_text = ? WHERE key_name = ?", [$v, $k]);
            } else {
                Database::execute("INSERT INTO business_settings (key_name, value_text) VALUES (?, ?)", [$k, $v]);
            }
        }

        $this->successResponse('Pengaturan QRIS resmi berhasil disimpan');
    }

    /**
     * 18. WhatsApp Notification Gateway Management
     */
    public function whatsapp(): void
    {
        $rows = Database::query("SELECT key_name, value_text FROM business_settings WHERE key_name LIKE 'wa_%'");
        $settings = [];
        foreach ($rows as $r) {
            $settings[$r['key_name']] = $r['value_text'];
        }

        $this->successResponse('Pengaturan WhatsApp Gateway', [
            'status'        => 'online',
            'gateway_url'   => $settings['wa_gateway_url'] ?? 'http://localhost:3300',
            'api_key'       => $settings['wa_api_key'] ?? '',
            'sender_number' => $settings['wa_sender_number'] ?? '6281234567890',
            'otp_channel'   => $settings['wa_otp_channel'] ?? 'whatsapp',
            'tpl_order_placed'    => $settings['wa_tpl_order_placed'] ?? 'Halo {customer_name}, pesanan #{order_code} Anda telah dibuat.',
            'tpl_driver_assigned' => $settings['wa_tpl_driver_assigned'] ?? 'Driver {driver_name} sedang menuju resto untuk pesanan #{order_code}.',
            'tpl_delivered'       => $settings['wa_tpl_delivered'] ?? 'Pesanan #{order_code} telah berhasil diantar. Terima kasih!'
        ]);
    }

    public function waSendTest(): void
    {
        $data = $this->getPost();
        $phone = sanitize($data['phone'] ?? '');
        $msg   = sanitize($data['message'] ?? 'Halo dari Super Admin CicalengkaGO!');

        if (empty($phone)) {
            $this->errorResponse('Nomor HP tujuan wajib diisi');
            return;
        }

        $this->successResponse("Simulasi pesan WhatsApp berhasil dikirim ke {$phone}");
    }

    /**
     * 19. Admin Profile Management
     */
    public function profile(): void
    {
        $user = Database::fetchOne("SELECT id, name, email, phone, avatar, role, created_at FROM users WHERE role = 'admin' OR role = 'super_admin' ORDER BY id ASC LIMIT 1");
        $this->successResponse('Profil administrator', ['user' => $user]);
    }

    public function updateProfile(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        $name = sanitize($data['name'] ?? '');
        $email = sanitize($data['email'] ?? '');
        $phone = sanitize($data['phone'] ?? '');
        $password = $data['password'] ?? '';

        if (!$id || empty($name) || empty($email)) {
            $this->errorResponse('Nama dan email wajib diisi');
            return;
        }

        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            Database::execute("UPDATE users SET name = ?, email = ?, phone = ?, password = ?, updated_at = NOW() WHERE id = ?", [$name, $email, $phone, $hash, $id]);
        } else {
            Database::execute("UPDATE users SET name = ?, email = ?, phone = ?, updated_at = NOW() WHERE id = ?", [$name, $email, $phone, $id]);
        }

        $this->successResponse('Profil administrator berhasil diperbarui');
    }

    /**
     * 20. Order Printable Invoice / Detail
     */
    public function orderInvoice(): void
    {
        $codeOrId = $this->getQuery('id') ?? $this->getQuery('code') ?? '';
        if (empty($codeOrId)) {
            $this->errorResponse('ID pesanan tidak valid');
            return;
        }

        $order = (new Order())->findByIdOrCode((string)$codeOrId);
        if (!$order) {
            $this->errorResponse('Pesanan tidak ditemukan', null, 404);
            return;
        }

        $orderId = (int)$order['id'];
        $items = Database::query("SELECT * FROM order_items WHERE order_id = ?", [$orderId]);
        $delAddress = json_decode($order['delivery_address_json'] ?? '{}', true) ?: [];
        $parcelDetails = json_decode($order['parcel_details_json'] ?? '{}', true) ?: [];

        $store = null;
        if (!empty($order['store_id'])) {
            $store = Database::fetchOne("SELECT * FROM stores WHERE id = ? LIMIT 1", [(int)$order['store_id']]);
        }

        $customer = Database::fetchOne("SELECT id, name, phone, email FROM users WHERE id = ? LIMIT 1", [(int)$order['customer_id']]);
        $driver = null;
        if (!empty($order['delivery_man_id'])) {
            $driver = Database::fetchOne("
                SELECT dm.*, u.name, u.phone 
                FROM delivery_men dm 
                JOIN users u ON dm.user_id = u.id 
                WHERE dm.id = ? LIMIT 1
            ", [(int)$order['delivery_man_id']]);
        }

        $this->successResponse('Data invoice pesanan', [
            'order'          => $order,
            'items'          => $items,
            'store'          => $store,
            'customer'       => $customer,
            'driver'         => $driver,
            'delAddress'     => $delAddress,
            'parcelDetails'  => $parcelDetails
        ]);
    }

    /**
     * 21. Driver Management Actions
     */
    public function saveDeliveryMan(): void
    {
        $data = $this->getPost();
        $id = !empty($data['id']) ? (int)$data['id'] : null;
        $name = sanitize($data['name'] ?? '');
        $phone = sanitize($data['phone'] ?? '');
        $email = sanitize($data['email'] ?? '');
        $vehicleType = sanitize($data['vehicle_type'] ?? 'motor');
        $identityNumber = sanitize($data['identity_number'] ?? '');
        $licensePlate = sanitize($data['license_plate'] ?? '');

        if (empty($name) || empty($phone)) {
            $this->errorResponse('Nama dan nomor HP kurir wajib diisi');
            return;
        }

        if ($id) {
            $dm = Database::fetchOne("SELECT user_id FROM delivery_men WHERE id = ?", [$id]);
            if ($dm) {
                Database::execute("UPDATE users SET name = ?, phone = ?, email = ? WHERE id = ?", [$name, $phone, $email, $dm['user_id']]);
            }
            Database::execute("
                UPDATE delivery_men SET 
                    vehicle_type = ?, identity_number = ?, license_plate = ?, updated_at = NOW() 
                WHERE id = ?
            ", [$vehicleType, $identityNumber, $licensePlate, $id]);
            $this->successResponse('Data kurir berhasil diperbarui');
        } else {
            // Create user first
            $pwd = password_hash('123456', PASSWORD_BCRYPT);
            Database::execute("
                INSERT INTO users (name, phone, email, password, role, is_active, created_at, updated_at)
                VALUES (?, ?, ?, ?, 'delivery_man', 1, NOW(), NOW())
            ", [$name, $phone, $email, $pwd]);
            $newUserId = (int)Database::lastInsertId();

            Database::execute("
                INSERT INTO delivery_men (user_id, vehicle_type, identity_number, license_plate, is_active, created_at, updated_at)
                VALUES (?, ?, ?, ?, 1, NOW(), NOW())
            ", [$newUserId, $vehicleType, $identityNumber, $licensePlate]);

            // Create initial wallet
            Database::execute("
                INSERT INTO wallets (user_id, user_type, balance, created_at, updated_at)
                VALUES (?, 'delivery_man', 0, NOW(), NOW())
            ", [$newUserId]);

            $this->successResponse('Kurir delivery baru berhasil didaftarkan');
        }
    }

    public function topupDeliveryMan(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        $amount = (float)($data['amount'] ?? 0);
        $notes = sanitize($data['notes'] ?? 'Top-up saldo operasional driver oleh Super Admin');

        if (!$id || $amount <= 0) {
            $this->errorResponse('ID Driver dan nominal saldo tidak valid');
            return;
        }

        $dm = Database::fetchOne("SELECT user_id FROM delivery_men WHERE id = ?", [$id]);
        if (!$dm) {
            $this->errorResponse('Driver tidak ditemukan', null, 404);
            return;
        }

        $userId = (int)$dm['user_id'];
        (new \App\Models\Wallet())->credit($userId, $amount, 'topup', $notes);

        $this->successResponse('Saldo driver berhasil ditambahkan');
    }

    public function deleteDeliveryMan(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        if (!$id) {
            $this->errorResponse('Driver ID tidak valid');
            return;
        }

        $dm = Database::fetchOne("SELECT user_id FROM delivery_men WHERE id = ?", [$id]);
        if ($dm) {
            Database::execute("DELETE FROM delivery_men WHERE id = ?", [$id]);
            Database::execute("DELETE FROM users WHERE id = ?", [$dm['user_id']]);
        }
        $this->successResponse('Driver berhasil dihapus');
    }

    /**
     * 22. Store & Product Actions
     */
    public function saveStore(): void
    {
        $data = $this->getPost();
        $id = !empty($data['id']) ? (int)$data['id'] : null;
        $name = sanitize($data['name'] ?? '');
        $phone = sanitize($data['phone'] ?? '');
        $address = sanitize($data['address'] ?? '');
        $moduleId = (int)($data['module_id'] ?? 1);
        $commission = (float)($data['commission'] ?? 10);
        $isOpen = isset($data['is_open']) ? (int)$data['is_open'] : 1;
        $active = isset($data['active']) ? (int)$data['active'] : 1;

        if (empty($name)) {
            $this->errorResponse('Nama resto/toko wajib diisi');
            return;
        }

        if ($id) {
            Database::execute("
                UPDATE stores SET 
                    name = ?, phone = ?, address = ?, module_id = ?, 
                    commission = ?, is_open = ?, active = ?, updated_at = NOW()
                WHERE id = ?
            ", [$name, $phone, $address, $moduleId, $commission, $isOpen, $active, $id]);
            $this->successResponse('Mitra toko/resto berhasil diperbarui');
        } else {
            Database::execute("
                INSERT INTO stores (name, phone, address, module_id, commission, is_open, active, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'approved', NOW(), NOW())
            ", [$name, $phone, $address, $moduleId, $commission, $isOpen, $active]);
            $this->successResponse('Mitra toko/resto baru berhasil ditambahkan');
        }
    }

    public function saveProduct(): void
    {
        $data = $this->getPost();
        $id = !empty($data['id']) ? (int)$data['id'] : null;
        $name = sanitize($data['name'] ?? '');
        $storeId = (int)($data['store_id'] ?? 1);
        $price = (float)($data['price'] ?? 0);
        $discount = (float)($data['discount'] ?? 0);
        $description = sanitize($data['description'] ?? '');
        $image = sanitize($data['image'] ?? '');
        $status = isset($data['status']) ? (int)$data['status'] : 1;

        if (empty($name) || $price <= 0) {
            $this->errorResponse('Nama produk dan harga wajib valid');
            return;
        }

        if ($id) {
            Database::execute("
                UPDATE products SET 
                    name = ?, store_id = ?, price = ?, discount = ?, 
                    description = ?, image = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ", [$name, $storeId, $price, $discount, $description, $image, $status, $id]);
            $this->successResponse('Produk berhasil diperbarui');
        } else {
            Database::execute("
                INSERT INTO products (name, store_id, price, discount, description, image, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ", [$name, $storeId, $price, $discount, $description, $image, $status]);
            $this->successResponse('Produk baru berhasil ditambahkan');
        }
    }

    public function deleteProduct(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        if (!$id) {
            $this->errorResponse('Product ID tidak valid');
            return;
        }

        Database::execute("DELETE FROM products WHERE id = ?", [$id]);
        $this->successResponse('Produk berhasil dihapus');
    }
}

