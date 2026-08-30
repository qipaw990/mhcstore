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

        // Auto-heal vendor wallet credit for any delivered orders that haven't been credited yet
        $deliveredOrders = Database::query(
            "SELECT id, order_code, order_amount FROM `orders` WHERE `store_id` = ? AND `order_status` = 'delivered'",
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

        $order = $this->orderModel->find($orderId);
        if (!$order) {
            $this->errorResponse('Pesanan tidak ditemukan.');
            return;
        }

        $updateData = ['order_status' => $status];
        if ($status === 'processing') {
            $updateData['processing_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'handover') {
            $updateData['handover_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'canceled') {
            $updateData['cancellation_reason'] = 'Dibatalkan oleh Mitra Toko';
            $updateData['canceled_at'] = date('Y-m-d H:i:s');
            Order::refundOrderIfPaid($order, 'Dibatalkan oleh Mitra Toko');
        }

        Database::update('orders', $updateData, 'id = ?', [$orderId]);
        $this->successResponse("Status pesanan #{$order['order_code']} diperbarui menjadi {$status}.");
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
        $userId = auth_id();
        $store = $this->storeModel->findByVendorId($userId);
        if (!$store) {
            $this->errorResponse('Toko tidak ditemukan.');
            return;
        }

        $data = $this->getPost();
        $id = !empty($data['id']) ? (int)$data['id'] : null;

        $imagePath = $data['existing_image'] ?? 'assets/images/products/default.jpg';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploaded = upload_image($_FILES['image'], 'products');
            if ($uploaded) {
                $imagePath = $uploaded;
            }
        }

        $productData = [
            'store_id'       => $store['id'],
            'module_id'      => $store['module_id'],
            'category_id'    => (int)($data['category_id'] ?? 1),
            'name'           => sanitize($data['name']),
            'description'    => sanitize($data['description'] ?? ''),
            'image'          => $imagePath,
            'price'          => (float)($data['price'] ?? 0),
            'discount'       => (float)($data['discount'] ?? 0),
            'discount_type'  => $data['discount_type'] ?? 'percent',
            'unit'           => sanitize($data['unit'] ?? 'porsi'),
            'stock'          => (int)($data['stock'] ?? 100),
            'is_veg'         => !empty($data['is_veg']) ? 1 : 0,
            'is_recommended' => !empty($data['is_recommended']) ? 1 : 0,
            'status'         => 1
        ];

        if ($id) {
            $this->productModel->update($id, $productData);
        } else {
            $id = $this->productModel->create($productData);
        }

        if ($this->isJsonRequest()) {
            $this->successResponse('Menu produk berhasil disimpan!', [
                'product_id' => $id,
                'product'    => $this->productModel->find($id)
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

        if ($this->isJsonRequest()) {
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
        $userId = auth_id();
        if (!$userId) {
            if ($this->isJsonRequest()) {
                $this->errorResponse('Akses tidak terotentikasi. Silakan masuk kembali.', null, 401);
                return;
            }
            $this->redirect('login');
            return;
        }

        $userModel = new \App\Models\User();
        $dbUser = $userModel->find($userId);

        $data = $this->getPost();
        $name         = sanitize($data['name'] ?? ($dbUser['name'] ?? ''));
        $email        = sanitize($data['email'] ?? ($dbUser['email'] ?? ''));
        $phone        = sanitize($data['phone'] ?? ($dbUser['phone'] ?? ''));
        $storeName    = sanitize($data['store_name'] ?? ($name ?: 'Toko Mitra'));
        $storeAddress = sanitize($data['store_address'] ?? '');
        $storePhone   = sanitize($data['store_phone'] ?? $phone);
        $storeLat     = isset($data['latitude']) ? (float)$data['latitude'] : null;
        $storeLng     = isset($data['longitude']) ? (float)$data['longitude'] : null;

        if (empty($name) || empty($phone)) {
            if ($this->isJsonRequest()) {
                $this->errorResponse('Nama dan nomor HP pemilik/toko wajib diisi.');
                return;
            }
            $_SESSION['error'] = 'Nama, email, dan nomor HP pemilik wajib diisi.';
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
        if ($store) {
            $storeUpdates = [];
            if (!empty($storeName)) $storeUpdates['name'] = $storeName;
            if (!empty($storeAddress)) $storeUpdates['address'] = $storeAddress;
            if (!empty($storePhone)) $storeUpdates['phone'] = $storePhone;
            if ($storeLat !== null && $storeLng !== null && $storeLat != 0 && $storeLng != 0) {
                $storeUpdates['latitude'] = $storeLat;
                $storeUpdates['longitude'] = $storeLng;
            }

            // Handle Store Logo Upload
            if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === UPLOAD_ERR_OK) {
                $logoPath = upload_image($_FILES['store_logo'], 'stores');
                if ($logoPath) {
                    $storeUpdates['logo'] = $logoPath;
                }
            }

            if (!empty($storeUpdates)) {
                $this->storeModel->update($store['id'], $storeUpdates);
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
            $userUpdates = ['name' => $name];
            if (!empty($phone)) $userUpdates['phone'] = $phone;
            if (!empty($email)) $userUpdates['email'] = $email;
            if (!empty($passwordUpdate)) $userUpdates['password'] = $passwordUpdate;
            $userModel->update($userId, $userUpdates);

            $freshStore = $this->storeModel->findByVendorId($userId);
            $freshUser = $userModel->find($userId);
            if (!empty($freshUser)) unset($freshUser['password']);

            $this->successResponse('Profil toko dan titik lokasi berhasil diperbarui!', [
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
}
