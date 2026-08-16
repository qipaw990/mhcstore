<?php
namespace App\Services;

use App\Core\Database;
use App\Models\Order;
use App\Models\DeliveryMan;
use App\Models\Wallet;
use Exception;

class DeliveryService
{
    private Order $orderModel;
    private DeliveryMan $dmModel;
    private Wallet $walletModel;

    public function __construct()
    {
        $this->orderModel = new Order();
        $this->dmModel = new DeliveryMan();
        $this->walletModel = new Wallet();
    }

    public function acceptOrder(int $dmUserId, int $orderId): bool
    {
        $dm = $this->dmModel->findByUserId($dmUserId);
        if (!$dm || !$dm['is_online']) {
            throw new Exception("Status driver harus online untuk menerima pesanan.");
        }

        // Batasan: Cek jika driver masih memiliki pesanan aktif yang belum selesai
        if (!empty($dm['current_order_id'])) {
            $existingActive = $this->orderModel->find($dm['current_order_id']);
            if ($existingActive && !in_array($existingActive['order_status'], ['delivered', 'canceled'])) {
                throw new Exception("Anda masih memiliki pesanan aktif (#{$existingActive['order_code']}). Selesaikan pengantaran saat ini terlebih dahulu sebelum mengambil pesanan baru!");
            }
        }

        $activeOngoing = Database::fetchOne(
            "SELECT order_code FROM `orders` WHERE `delivery_man_id` = ? AND `order_status` NOT IN ('delivered', 'canceled') LIMIT 1",
            [$dm['id']]
        );
        if ($activeOngoing) {
            throw new Exception("Anda masih memiliki pesanan aktif (#{$activeOngoing['order_code']}). Selesaikan pengantaran saat ini terlebih dahulu!");
        }

        $order = $this->orderModel->find($orderId);
        if (!$order) {
            throw new Exception("Pesanan tidak ditemukan.");
        }
        if (!empty($order['delivery_man_id'])) {
            throw new Exception("Pesanan sudah diambil oleh driver lain.");
        }

        return Database::transaction(function () use ($dm, $orderId) {
            Database::update('orders', [
                'delivery_man_id' => $dm['id'],
                'order_status'    => 'processing',
                'processing_at'   => date('Y-m-d H:i:s')
            ], 'id = ?', [$orderId]);

            Database::update('delivery_men', [
                'current_order_id' => $orderId
            ], 'id = ?', [$dm['id']]);

            return true;
        });
    }

    public function updateOrderStatus(int $dmUserId, int $orderId, string $status, ?string $otp = null): bool
    {
        $dm = $this->dmModel->findByUserId($dmUserId);
        $order = $this->orderModel->find($orderId);

        if (!$order || $order['delivery_man_id'] != $dm['id']) {
            throw new Exception("Akses pesanan tidak valid.");
        }

        return Database::transaction(function () use ($dm, $order, $status, $otp) {
            $updateData = ['order_status' => $status];
            $now = date('Y-m-d H:i:s');

            if ($status === 'picked_up') {
                $updateData['picked_up_at'] = $now;
                $updateData['order_status'] = 'on_the_way';
            } elseif ($status === 'delivered') {
                // Verify OTP
                if (empty($otp) || trim($otp) !== trim($order['otp'])) {
                    throw new Exception("Kode OTP pengantaran salah!");
                }
                $updateData['delivered_at'] = $now;
                $updateData['payment_status'] = 'paid';

                // Credit Driver Earnings (80% of delivery charge)
                $dmEarning = (float)$order['delivery_charge'] * 0.85;
                $this->walletModel->credit(
                    $dm['user_id'],
                    $dmEarning,
                    'order_earning',
                    "Ongkir selesai pesanan #{$order['order_code']}",
                    (string)$order['id']
                );

                // Credit Vendor Earnings (if store order)
                if (!empty($order['store_id'])) {
                    $store = Database::fetchOne("SELECT vendor_id, name FROM stores WHERE id = ?", [$order['store_id']]);
                    if ($store) {
                        $vendorEarning = (float)$order['order_amount'] * 0.90; // 10% platform fee
                        $this->walletModel->credit(
                            $store['vendor_id'],
                            $vendorEarning,
                            'order_earning',
                            "Penjualan pesanan #{$order['order_code']}",
                            (string)$order['id']
                        );
                    }
                }

                // Update Driver statistics
                Database::execute("UPDATE `delivery_men` SET `total_orders` = `total_orders` + 1, `current_order_id` = NULL WHERE `id` = ?", [$dm['id']]);
            }

            Database::update('orders', $updateData, 'id = ?', [$order['id']]);
            return true;
        });
    }
}
