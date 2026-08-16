<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\DeliveryMan;
use App\Models\Order;
use App\Models\Wallet;
use App\Services\DeliveryService;
use App\Core\Database;
use Exception;

class DeliveryController extends Controller
{
    private DeliveryMan $dmModel;
    private Order $orderModel;
    private Wallet $walletModel;
    private DeliveryService $deliveryService;

    public function __construct()
    {
        $this->dmModel = new DeliveryMan();
        $this->orderModel = new Order();
        $this->walletModel = new Wallet();
        $this->deliveryService = new DeliveryService();
    }

    public function dashboard(): void
    {
        $userId = auth_id();
        $dm = $this->dmModel->findByUserId($userId);

        if (!$dm) {
            // Auto create delivery man record if driver user
            $dmId = $this->dmModel->create([
                'user_id'           => $userId,
                'zone_id'           => 1,
                'vehicle_type'      => 'Motor Honda Beat',
                'vehicle_number'    => 'D 1234 CCG',
                'identity_number'   => '3204000000000001',
                'is_online'         => 1,
                'is_active'         => 1,
                'current_latitude'  => -6.9840,
                'current_longitude' => 107.8340
            ]);
            $dm = $this->dmModel->find($dmId);
        }

        // Active Order
        $activeOrder = null;
        if (!empty($dm['current_order_id'])) {
            $activeOrder = $this->orderModel->find($dm['current_order_id']);
            if ($activeOrder && $activeOrder['order_status'] !== 'delivered' && $activeOrder['order_status'] !== 'canceled') {
                $activeOrder = $this->orderModel->findByCode($activeOrder['order_code']);
            } else {
                $activeOrder = null;
                Database::update('delivery_men', ['current_order_id' => null], 'id = ?', [$dm['id']]);
            }
        }

        // Available nearby orders in driver zone
        $availableOrders = $this->orderModel->getAvailableForDelivery((int)$dm['zone_id']);

        // Today's summary
        $wallet = $this->walletModel->getOrCreate($userId, 'delivery_man');

        $this->view('delivery.dashboard', [
            'title'            => 'Kurir Dashboard - CicalengkaGO',
            'driver'           => $dm,
            'active_order'     => $activeOrder,
            'available_orders' => $availableOrders,
            'wallet'           => $wallet,
            'active_tab'       => 'home'
        ], 'delivery_layout');
    }

    public function toggleOnline(): void
    {
        $userId = auth_id();
        $dm = $this->dmModel->findByUserId($userId);
        if (!$dm) {
            $this->errorResponse('Driver tidak ditemukan');
            return;
        }

        $newStatus = $dm['is_online'] ? 0 : 1;
        $this->dmModel->update($dm['id'], ['is_online' => $newStatus]);

        $this->successResponse($newStatus ? 'Anda sekarang ONLINE dan siap menerima order.' : 'Anda sekarang OFFLINE.', [
            'is_online' => $newStatus
        ]);
    }

    public function acceptOrder(): void
    {
        $userId = auth_id();
        $data = $this->getPost();
        $orderId = (int)($data['order_id'] ?? 0);

        try {
            $this->deliveryService->acceptOrder($userId, $orderId);
            $order = $this->orderModel->find($orderId);
            $this->successResponse('Pesanan berhasil diterima! Segera menuju ke lokasi penjemputan.', [
                'order_code' => $order['order_code']
            ]);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    public function updateDeliveryStatus(): void
    {
        $userId = auth_id();
        $data = $this->getPost();
        $orderId = (int)($data['order_id'] ?? 0);
        $status = sanitize($data['status'] ?? '');
        $otp = sanitize($data['otp'] ?? '');

        try {
            $this->deliveryService->updateOrderStatus($userId, $orderId, $status, $otp);
            $this->successResponse('Status pengantaran berhasil diperbarui.');
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    public function updateLocation(): void
    {
        $userId = auth_id();
        $data = $this->getPost();
        $lat = (float)($data['lat'] ?? 0);
        $lng = (float)($data['lng'] ?? 0);

        $dm = $this->dmModel->findByUserId($userId);
        if ($dm && $lat != 0 && $lng != 0) {
            $this->dmModel->updateLocation($dm['id'], $lat, $lng, $dm['current_order_id']);
            $this->successResponse('Lokasi diperbarui.');
            return;
        }

        $this->errorResponse('Invalid coordinates.');
    }

    public function earnings(): void
    {
        $userId = auth_id();
        $wallet = $this->walletModel->getOrCreate($userId, 'delivery_man');
        $transactions = $this->walletModel->getTransactions($userId, 50);

        $this->view('delivery.earnings', [
            'title'        => 'Pendapatan & Saldo Driver',
            'wallet'       => $wallet,
            'transactions' => $transactions,
            'active_tab'   => 'earnings'
        ], 'delivery_layout');
    }

    public function profile(): void
    {
        $userId = auth_id();
        $user = auth_user();
        $dm = $this->dmModel->findByUserId($userId);

        $this->view('delivery.profile', [
            'title'      => 'Profil Mitra Driver',
            'user'       => $user,
            'driver'     => $dm,
            'active_tab' => 'profile'
        ], 'delivery_layout');
    }

    public function updateProfile(): void
    {
        $userId = auth_id();
        if (!$userId) {
            $this->redirect('login');
            return;
        }

        $data = $this->getPost();
        $name          = sanitize($data['name'] ?? '');
        $email         = sanitize($data['email'] ?? '');
        $phone         = sanitize($data['phone'] ?? '');
        $vehicleType   = sanitize($data['vehicle_type'] ?? '');
        $vehicleNumber = sanitize($data['vehicle_number'] ?? '');

        if (empty($name) || empty($email) || empty($phone)) {
            $_SESSION['error'] = 'Nama, email, dan nomor HP driver wajib diisi.';
            $this->redirect('delivery/profile');
            return;
        }

        // Handle Driver Avatar Upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $avatarPath = upload_image($_FILES['avatar'], 'profiles');
            if ($avatarPath) {
                (new \App\Models\User())->update($userId, ['avatar' => $avatarPath]);
                $_SESSION['user']['avatar'] = $avatarPath;
            }
        }

        $dm = $this->dmModel->findByUserId($userId);
        if ($dm && (!empty($vehicleType) || !empty($vehicleNumber))) {
            $this->dmModel->update($dm['id'], [
                'vehicle_type'   => $vehicleType ?: $dm['vehicle_type'],
                'vehicle_number' => $vehicleNumber ?: $dm['vehicle_number']
            ]);
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
                $this->redirect('delivery/profile');
                return;
            }

            if (!password_verify($currentPassword, $dbUser['password'] ?? '')) {
                $_SESSION['error'] = 'Kata Sandi Saat Ini yang Anda masukkan salah.';
                $this->redirect('delivery/profile');
                return;
            }

            if (strlen($newPassword) < 6) {
                $_SESSION['error'] = 'Kata Sandi Baru harus memiliki minimal 6 karakter.';
                $this->redirect('delivery/profile');
                return;
            }

            if ($newPassword !== $confirmPassword) {
                $_SESSION['error'] = 'Konfirmasi Kata Sandi Baru tidak cocok.';
                $this->redirect('delivery/profile');
                return;
            }

            $passwordUpdate = password_hash($newPassword, PASSWORD_BCRYPT);
        }

        $currentUser = auth_user();

        if (strtolower($email) !== strtolower($currentUser['email'] ?? '')) {
            $existing = Database::fetchOne("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1", [$email, $userId]);
            if ($existing) {
                $_SESSION['error'] = 'Alamat email ini sudah terdaftar pada akun lain.';
                $this->redirect('delivery/profile');
                return;
            }

            $otp = sprintf("%06d", rand(100000, 999999));

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

            \App\Services\EmailService::sendOtpEmail($email, $name, $otp);

            $_SESSION['info'] = "Kode verifikasi OTP dikirimkan ke email baru Anda ({$email}). Masukkan kode untuk konfirmasi perubahan profil.";
            $this->redirect('verify-otp');
            return;
        }

        $updateFields = [
            'name'  => $name,
            'phone' => $phone
        ];
        if ($passwordUpdate) {
            $updateFields['password'] = $passwordUpdate;
        }

        (new \App\Models\User())->update($userId, $updateFields);

        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['phone'] = $phone;

        $_SESSION['success'] = $passwordUpdate 
            ? 'Profil dan Kata Sandi Driver berhasil diperbarui!' 
            : 'Profil Mitra Driver berhasil diperbarui!';
        $this->redirect('delivery/profile');
    }
}
