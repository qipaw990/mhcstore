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
}
