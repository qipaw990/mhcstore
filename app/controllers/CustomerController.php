<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Module;
use App\Models\Store;
use App\Models\Product;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Cart;
use App\Models\Wallet;
use App\Models\Notification;
use App\Models\Coupon;
use App\Models\CustomerAddress;
use App\Core\Database;

class CustomerController extends Controller
{
    private Module $moduleModel;
    private Store $storeModel;
    private Product $productModel;
    private Banner $bannerModel;
    private Cart $cartModel;
    private Wallet $walletModel;

    public function __construct()
    {
        $this->moduleModel = new Module();
        $this->storeModel = new Store();
        $this->productModel = new Product();
        $this->bannerModel = new Banner();
        $this->cartModel = new Cart();
        $this->walletModel = new Wallet();
    }

    public function home(): void
    {
        $userId = auth_id();
        $selectedModuleId = (int)($_GET['module_id'] ?? 1); // Default to Kuliner/Food

        $modules = $this->moduleModel->activeModules();
        $banners = $this->bannerModel->getActiveBanners($selectedModuleId);
        $categories = (new Category())->getByModule($selectedModuleId);
        $popularStores = $this->storeModel->getByModule($selectedModuleId);
        $recommendedProducts = $this->productModel->getRecommended(10);
        $cartSummary = $this->cartModel->getUserCart($userId, session_id());
        
        $walletBalance = 0.00;
        if ($userId) {
            $wallet = $this->walletModel->getOrCreate($userId, 'customer');
            $walletBalance = (float)$wallet['balance'];
        }

        $coupons = (new Coupon())->where('status', 1);

        $this->view('customer.home', [
            'title'               => 'CicalengkaGO - Pesan Antar Makanan & Kebutuhan Cicalengka',
            'modules'             => $modules,
            'selected_module_id'  => $selectedModuleId,
            'banners'             => $banners,
            'categories'          => $categories,
            'popular_stores'      => $popularStores,
            'recommended_products'=> $recommendedProducts,
            'cart_summary'        => $cartSummary,
            'wallet_balance'      => $walletBalance,
            'coupons'             => $coupons,
            'active_tab'          => 'home'
        ], 'customer_layout');
    }

    public function search(): void
    {
        $query = trim($_GET['q'] ?? '');
        $moduleId = !empty($_GET['module_id']) ? (int)$_GET['module_id'] : null;
        
        $products = [];
        $stores = [];

        if (!empty($query)) {
            $products = $this->productModel->search($query, $moduleId);
            $stores = Database::query("SELECT * FROM `stores` WHERE `name` LIKE ? OR `address` LIKE ?", ["%{$query}%", "%{$query}%"]);
        }

        $this->view('customer.search', [
            'title'      => 'Cari Kuliner & Produk di Cicalengka',
            'query'      => $query,
            'products'   => $products,
            'stores'     => $stores,
            'active_tab' => 'search'
        ], 'customer_layout');
    }

    public function storeDetail(int $id): void
    {
        $store = $this->storeModel->findWithDetails($id);
        if (!$store) {
            $this->redirect('404');
            return;
        }

        $products = $this->productModel->getByStore($id);
        $cartSummary = $this->cartModel->getUserCart(auth_id(), session_id());
        
        $reviewModel = new \App\Models\Review();
        $reviewModel->recalculateStoreRating($id);
        $store = $this->storeModel->findWithDetails($id);
        $reviews = $reviewModel->getStoreReviews($id, 15);

        $this->view('customer.store', [
            'title'        => $store['name'] . ' - CicalengkaGO',
            'store'        => $store,
            'products'     => $products,
            'reviews'      => $reviews,
            'cart_summary' => $cartSummary,
            'active_tab'   => 'store'
        ], 'customer_layout');
    }

    public function parcel(): void
    {
        $userId = auth_id();
        $walletBalance = 0.00;
        if ($userId) {
            $wallet = $this->walletModel->getOrCreate($userId, 'customer');
            $walletBalance = (float)$wallet['balance'];
        }

        $this->view('customer.parcel', [
            'title'          => 'Kirim Paket Kilat Cicalengka (Cicago Parcel)',
            'wallet_balance' => $walletBalance,
            'active_tab'     => 'parcel'
        ], 'customer_layout');
    }

    public function profile(): void
    {
        $userId = auth_id();
        $user = auth_user();
        $wallet = null;
        $addresses = [];

        if ($userId) {
            $wallet = $this->walletModel->getOrCreate($userId, 'customer');
            $addresses = Database::query("SELECT * FROM `customer_addresses` WHERE `user_id` = ? ORDER BY `is_default` DESC", [$userId]);
        }

        $this->view('customer.profile', [
            'title'      => 'Akun Saya - CicalengkaGO',
            'user'       => $user,
            'wallet'     => $wallet,
            'addresses'  => $addresses,
            'active_tab' => 'profile'
        ], 'customer_layout');
    }

    public function wallet(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->redirect('login');
            return;
        }

        $midtransService = new \App\Services\MidtransService();

        // Auto-settle if redirected back from Midtrans payment finish
        $orderId = $_GET['order_id'] ?? '';
        $txnStatus = $_GET['transaction_status'] ?? $_GET['status'] ?? '';
        $statusCode = (string)($_GET['status_code'] ?? '');
        if (!empty($orderId) && str_starts_with($orderId, 'TOPUP-') && ($txnStatus === 'settlement' || $txnStatus === 'capture' || $statusCode === '200')) {
            try {
                $midtransService->processNotification([
                    'order_id'           => $orderId,
                    'transaction_status' => 'settlement',
                    'fraud_status'       => 'accept',
                    'payment_type'       => $_GET['payment_type'] ?? 'midtrans_redirect'
                ]);
            } catch (\Exception $e) {}
        }

        $wallet = $this->walletModel->getOrCreate($userId, 'customer');
        $transactions = $this->walletModel->getTransactions($userId, 50);

        $topupLogModel = new \App\Models\TopupLog();
        $topupLogs = $topupLogModel->getByUser($userId, null, 50);
        $topupStats = $topupLogModel->getStats($userId);

        $this->view('customer.wallet', [
            'title'        => 'Dompet Digital CicalengkaPay',
            'wallet'       => $wallet,
            'transactions' => $transactions,
            'topup_logs'   => $topupLogs,
            'topup_stats'  => $topupStats,
            'client_key'   => $midtransService->getClientKey(),
            'snap_url'     => $midtransService->getSnapUrl(),
            'is_sandbox'   => $midtransService->isSandbox(),
            'active_tab'   => 'profile'
        ], 'customer_layout');
    }

    public function notifications(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->redirect('login');
            return;
        }

        $notifModel = new Notification();
        $notifications = $notifModel->getUserNotifications($userId, 50);

        // Mark all as read
        Database::execute("UPDATE `notifications` SET `is_read` = 1 WHERE `user_id` = ?", [$userId]);

        $activeChats = Database::query("
            SELECT o.id, o.order_code, o.order_status, o.order_type, o.delivery_man_id,
                   dmu.name as dm_name, dmu.phone as dm_phone, dmu.avatar as dm_avatar,
                   (SELECT message FROM chats WHERE order_id = o.id ORDER BY id DESC LIMIT 1) as last_message,
                   (SELECT created_at FROM chats WHERE order_id = o.id ORDER BY id DESC LIMIT 1) as last_chat_time,
                   (SELECT COUNT(*) FROM chats WHERE order_id = o.id AND sender_id != ? AND is_read = 0) as unread_chat_count
            FROM `orders` o
            LEFT JOIN `delivery_men` dm ON o.delivery_man_id = dm.id
            LEFT JOIN `users` dmu ON dm.user_id = dmu.id
            WHERE o.customer_id = ? AND o.order_status IN ('confirmed', 'processing', 'handover', 'picked_up', 'on_the_way')
            ORDER BY o.id DESC
        ", [$userId, $userId]);

        $this->view('customer.notifications', [
            'title'         => 'Chat & Notifikasi',
            'notifications' => $notifications,
            'active_chats'  => $activeChats,
            'active_tab'    => 'chat'
        ], 'customer_layout');
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
            $this->redirect('profile');
            return;
        }

        // Handle Customer Avatar Upload
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
                $this->redirect('profile');
                return;
            }

            if (!password_verify($currentPassword, $dbUser['password'] ?? '')) {
                $_SESSION['error'] = 'Kata Sandi Saat Ini yang Anda masukkan salah.';
                $this->redirect('profile');
                return;
            }

            if (strlen($newPassword) < 6) {
                $_SESSION['error'] = 'Kata Sandi Baru harus memiliki minimal 6 karakter.';
                $this->redirect('profile');
                return;
            }

            if ($newPassword !== $confirmPassword) {
                $_SESSION['error'] = 'Konfirmasi Kata Sandi Baru tidak cocok.';
                $this->redirect('profile');
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
                    $this->redirect('profile');
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

        $_SESSION['success'] = 'Profil Anda berhasil diperbarui!';
        $this->redirect('profile');
    }
}
