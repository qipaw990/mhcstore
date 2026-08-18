<?php
namespace App\Services;

use App\Core\Database;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Wallet;
use App\Models\Store;
use App\Models\Product;
use Exception;

class OrderService
{
    private Order $orderModel;
    private Cart $cartModel;
    private Coupon $couponModel;
    private Wallet $walletModel;

    public function __construct()
    {
        $this->orderModel = new Order();
        $this->cartModel = new Cart();
        $this->couponModel = new Coupon();
        $this->walletModel = new Wallet();
    }

    public function createOrderFromCart(int $customerId, array $data): array
    {
        return Database::transaction(function () use ($customerId, $data) {
            // Auto cancel any unclaimed orders older than 60 seconds
            \App\Models\Order::autoCancelUnclaimedOrders();

            // Restrict 1 active order per customer
            $activeOrder = Database::fetchOne(
                "SELECT id, order_code FROM `orders` WHERE `customer_id` = ? AND `order_status` NOT IN ('delivered', 'canceled') LIMIT 1",
                [$customerId]
            );
            if ($activeOrder) {
                throw new Exception("Anda masih memiliki pesanan aktif (#{$activeOrder['order_code']}) yang sedang berlangsung. Harap selesaikan atau tunggu pesanan sebelumnya selesai sebelum membuat pesanan baru.");
            }

            $cartData = $this->cartModel->getUserCart($customerId);
            if (empty($cartData['items'])) {
                throw new Exception("Keranjang belanja kosong.");
            }

            $storeId = $cartData['store_id'];
            $store = (new Store())->find($storeId);
            if (!$store) {
                throw new Exception("Toko tidak ditemukan.");
            }

            // Validasi stok semua item sebelum order dibuat
            foreach ($cartData['items'] as $item) {
                $product = Database::fetchOne(
                    "SELECT id, name, stock, status FROM `products` WHERE `id` = ? LIMIT 1",
                    [(int)$item['product_id']]
                );
                if (!$product || !$product['status']) {
                    throw new Exception("Produk \"" . ($product['name'] ?? 'Tidak diketahui') . "\" sudah tidak tersedia.");
                }
                if ((int)$product['stock'] < (int)$item['quantity']) {
                    throw new Exception("Stok produk \"" . $product['name'] . "\" tidak mencukupi. Sisa: " . $product['stock'] . ", diminta: " . $item['quantity'] . ".");
                }
            }

            $orderAmount = (float)$cartData['subtotal'];
            
            // Calculate accurate distance from GPS Coordinates
            $destLat = (float)($data['delivery_address']['lat'] ?? -6.9840);
            $destLng = (float)($data['delivery_address']['lng'] ?? 107.8340);
            $storeLat = (float)($store['latitude'] ?? -6.9835);
            $storeLng = (float)($store['longitude'] ?? 107.8335);

            if ($destLat != 0 && $destLng != 0 && $storeLat != 0 && $storeLng != 0) {
                $calculatedDist = haversine_distance($storeLat, $storeLng, $destLat, $destLng);
                $distanceKm = max(0.5, round($calculatedDist, 1));
            } else {
                $distanceKm = (float)($data['distance_km'] ?? 1.5);
            }

            $deliveryCharge = calculate_delivery_fee($distanceKm, (float)$store['delivery_fee']);

            // Coupon Calculation
            $couponDiscount = 0.00;
            $couponCode = null;
            if (!empty($data['coupon_code'])) {
                $coupon = $this->couponModel->validateCoupon($data['coupon_code'], $orderAmount);
                if ($coupon) {
                    $couponCode = $coupon['code'];
                    $couponDiscount = (float)$coupon['calculated_discount'];
                    // Increment coupon usage
                    Database::execute("UPDATE `coupons` SET `usage_count` = `usage_count` + 1 WHERE `id` = ?", [$coupon['id']]);
                }
            }

            $taxAmount = 0.00;
            if ((float)$store['tax_percent'] > 0) {
                $taxAmount = ($orderAmount * ((float)$store['tax_percent'] / 100));
            }

            $totalAmount = max(0, ($orderAmount - $couponDiscount) + $deliveryCharge + $taxAmount);
            $paymentMethod = $data['payment_method'] ?? 'cod';
            $paymentStatus = 'unpaid';

            // Wallet payment deduction
            if ($paymentMethod === 'wallet') {
                $this->walletModel->debit(
                    $customerId,
                    $totalAmount,
                    'order_payment',
                    "Pembayaran pesanan di {$store['name']}"
                );
                $paymentStatus = 'paid';
            }

            $orderCode = 'CCG-' . strtoupper(substr(uniqid(), -6)) . rand(10, 99);
            $otp = str_pad((string)rand(1000, 9999), 4, '0', STR_PAD_LEFT);

            $isCodOrPaid = ($paymentMethod === 'cod' || $paymentStatus === 'paid');
            $orderStatus = $isCodOrPaid ? 'confirmed' : 'pending';
            $confirmedAt = $isCodOrPaid ? date('Y-m-d H:i:s') : null;

            // Create Order record
            $orderId = Database::insert('orders', [
                'order_code'            => $orderCode,
                'customer_id'           => $customerId,
                'store_id'              => $storeId,
                'delivery_man_id'       => null,
                'module_id'             => $store['module_id'],
                'zone_id'               => $store['zone_id'],
                'order_amount'          => $orderAmount,
                'delivery_charge'       => $deliveryCharge,
                'coupon_code'           => $couponCode,
                'coupon_discount'       => $couponDiscount,
                'tax_amount'            => $taxAmount,
                'total_amount'          => $totalAmount,
                'payment_status'        => $paymentStatus,
                'payment_method'        => $paymentMethod,
                'order_status'          => $orderStatus,
                'order_type'            => $data['order_type'] ?? 'delivery',
                'delivery_address_json' => json_encode($data['delivery_address'] ?? []),
                'order_notes'           => $data['order_notes'] ?? null,
                'otp'                   => $otp,
                'distance_km'           => $distanceKm,
                'confirmed_at'          => $confirmedAt
            ]);

            // Create Order Items
            foreach ($cartData['items'] as $item) {
                Database::insert('order_items', [
                    'order_id'        => $orderId,
                    'product_id'      => $item['product_id'],
                    'product_name'    => $item['product_name'],
                    'price'           => $item['price'],
                    'quantity'        => $item['quantity'],
                    'variation_json'  => !empty($item['variation_id']) ? json_encode(['name' => $item['variation_name']]) : null,
                    'addons_json'     => $item['addons_json'],
                    'total_price'     => $item['item_total']
                ]);

                // Decrement product stock (prevent negative stock)
                Database::execute(
                    "UPDATE `products` SET 
                        `stock` = GREATEST(0, `stock` - ?),
                        `order_count` = `order_count` + 1,
                        `status` = IF(`stock` - ? <= 0, 0, `status`)
                     WHERE `id` = ?",
                    [$item['quantity'], $item['quantity'], $item['product_id']]
                );
            }
            Database::execute("UPDATE `stores` SET `order_count` = `order_count` + 1 WHERE `id` = ?", [$storeId]);

            // Clear Cart
            $this->cartModel->clearCart($customerId);

            // Send notification to customer
            Database::insert('notifications', [
                'user_id'   => $customerId,
                'title'     => "Pesanan #{$orderCode} Diterima",
                'message'   => "Pesanan Anda di {$store['name']} sedang disiapkan oleh penjual.",
                'type'      => 'order',
                'data_json' => json_encode(['order_code' => $orderCode, 'order_id' => $orderId])
            ]);

            return [
                'order_id'   => $orderId,
                'order_code' => $orderCode,
                'total'      => $totalAmount,
                'otp'        => $otp
            ];
        });
    }

    public function createParcelOrder(int $customerId, array $data): array
    {
        return Database::transaction(function () use ($customerId, $data) {
            // Auto cancel any unclaimed orders older than 60 seconds
            \App\Models\Order::autoCancelUnclaimedOrders();

            // Restrict 1 active order per customer
            $activeOrder = Database::fetchOne(
                "SELECT id, order_code FROM `orders` WHERE `customer_id` = ? AND `order_status` NOT IN ('delivered', 'canceled') LIMIT 1",
                [$customerId]
            );
            if ($activeOrder) {
                throw new Exception("Anda masih memiliki pesanan aktif (#{$activeOrder['order_code']}) yang sedang berlangsung. Harap selesaikan atau tunggu pesanan sebelumnya selesai sebelum membuat pesanan baru.");
            }

            $destLat = (float)($data['destination_address']['lat'] ?? -6.9840);
            $destLng = (float)($data['destination_address']['lng'] ?? 107.8340);
            $pickupLat = (float)($data['parcel_details']['pickup_lat'] ?? -6.9840);
            $pickupLng = (float)($data['parcel_details']['pickup_lng'] ?? 107.8340);

            if ($destLat != 0 && $destLng != 0 && $pickupLat != 0 && $pickupLng != 0) {
                $calculatedDist = haversine_distance($pickupLat, $pickupLng, $destLat, $destLng);
                $distanceKm = max(0.5, round($calculatedDist, 1));
            } else {
                $distanceKm = (float)($data['distance_km'] ?? 3.0);
            }

            $deliveryCharge = calculate_delivery_fee($distanceKm, 8000.00, 3000.00);
            $totalAmount = $deliveryCharge;
            $paymentMethod = $data['payment_method'] ?? 'cod';
            $paymentStatus = 'unpaid';

            if ($paymentMethod === 'wallet') {
                $this->walletModel->debit(
                    $customerId,
                    $totalAmount,
                    'order_payment',
                    "Pembayaran pengiriman paket parcel Cicalengka"
                );
                $paymentStatus = 'paid';
            }

            $orderCode = 'PCL-' . strtoupper(substr(uniqid(), -6)) . rand(10, 99);
            $otp = str_pad((string)rand(1000, 9999), 4, '0', STR_PAD_LEFT);

            $isCodOrPaid = ($paymentMethod === 'cod' || $paymentStatus === 'paid');
            $orderStatus = $isCodOrPaid ? 'confirmed' : 'pending';
            $confirmedAt = $isCodOrPaid ? date('Y-m-d H:i:s') : null;

            $orderId = Database::insert('orders', [
                'order_code'            => $orderCode,
                'customer_id'           => $customerId,
                'store_id'              => null,
                'delivery_man_id'       => null,
                'module_id'             => 5, // Parcel module
                'zone_id'               => 1,
                'order_amount'          => 0.00,
                'delivery_charge'       => $deliveryCharge,
                'coupon_discount'       => 0.00,
                'tax_amount'            => 0.00,
                'total_amount'          => $totalAmount,
                'payment_status'        => $paymentStatus,
                'payment_method'        => $paymentMethod,
                'order_status'          => $orderStatus,
                'order_type'            => 'parcel',
                'delivery_address_json' => json_encode($data['destination_address'] ?? []),
                'order_notes'           => $data['parcel_notes'] ?? 'Pengiriman Paket Kilat Cicalengka',
                'otp'                   => $otp,
                'distance_km'           => $distanceKm,
                'parcel_details_json'   => json_encode($data['parcel_details'] ?? []),
                'confirmed_at'          => $confirmedAt
            ]);

            return [
                'order_id'   => $orderId,
                'order_code' => $orderCode,
                'total'      => $totalAmount,
                'otp'        => $otp
            ];
        });
    }
}
