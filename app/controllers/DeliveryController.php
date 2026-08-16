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
}
