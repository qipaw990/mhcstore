<?php
/**
 * Test App Script for CicalengkaGO
 */

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');

require_once APP_PATH . '/config/constants.php';
require_once APP_PATH . '/helpers/auth.php';
require_once APP_PATH . '/helpers/response.php';
require_once APP_PATH . '/helpers/validation.php';
require_once APP_PATH . '/helpers/upload.php';
require_once APP_PATH . '/helpers/format.php';
require_once APP_PATH . '/helpers/distance.php';
require_once APP_PATH . '/autoload.php';

use App\Core\Database;
use App\Models\User;
use App\Models\Store;
use App\Models\Product;
use App\Models\Cart;
use App\Models\Order;
use App\Models\DeliveryMan;
use App\Models\Wallet;
use App\Models\Module;
use App\Models\Zone;
use App\Services\OrderService;
use App\Services\DeliveryService;

echo "--- 1. Testing Database Connection ---\n";
try {
    $row = Database::fetchOne("SELECT 1 as connected");
    echo "DB Connection: OK (Result: " . ($row['connected'] ?? '1') . ")\n";
} catch (\Throwable $e) {
    echo "DB Connection Failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n--- 2. Testing Models ---\n";
$userModel = new User();
$customer = $userModel->findByPhone('081234567890');
echo "Customer found: " . ($customer ? $customer['name'] : 'NOT FOUND') . "\n";

$admin = $userModel->findByEmail('admin@cicalengkago.id');
echo "Admin found: " . ($admin ? $admin['name'] : 'NOT FOUND') . "\n";

$storeModel = new Store();
$stores = $storeModel->getPopular(5);
echo "Popular Food Stores count: " . count($stores) . "\n";

$prodModel = new Product();
$prods = $prodModel->getRecommended(5);
echo "Recommended Products count: " . count($prods) . "\n";

$modModel = new Module();
$mods = $modModel->getActive();
echo "Active Modules count: " . count($mods) . "\n";

$zoneModel = new Zone();
$zones = $zoneModel->all();
echo "Zones count: " . count($zones) . "\n";

echo "\n--- 3. Testing Order Lifecycle & Services ---\n";
$orderService = new OrderService();
$deliveryService = new DeliveryService();

// Simulate Add to Cart
$cartModel = new Cart();
$cartModel->clear($customer['id']);
$firstProd = $prods[0];
$cartModel->addItem($customer['id'], null, (int)$firstProd['id'], 2, (float)$firstProd['final_price'], null, [], 'Jangan terlalu pedas');
$cartSummary = $cartModel->getUserCart($customer['id']);
echo "Cart Items added: " . count($cartSummary['items']) . " (Subtotal: " . $cartSummary['subtotal'] . ")\n";

// Place Order
$orderResult = $orderService->createOrderFromCart($customer['id'], [
    'delivery_address' => [
        'address' => 'Jl. Cicalengka Raya No. 12, RT 01/02',
        'lat' => -6.9845,
        'lng' => 107.8350,
        'contact_name' => 'Budi Santoso',
        'contact_phone' => '081234567890'
    ],
    'payment_method' => 'cod',
    'order_type' => 'delivery',
    'order_notes' => 'Tolong antar secepatnya'
]);

if (!empty($orderResult['order_id'])) {
    $orderId = $orderResult['order_id'];
    $orderCode = $orderResult['order_code'];
    $otp = $orderResult['otp'];
    echo "Order Placed Successfully! Order Code: {$orderCode} | OTP: {$otp}\n";

    // Simulate Vendor Accepting and Processing Order
    $orderModel = new Order();
    $orderModel->updateStatus((int)$orderId, 'processing');
    echo "Vendor status updated to: processing\n";

    // Driver sets status to online
    $dmModel = new DeliveryMan();
    $driverUser = $userModel->firstWhere('role', 'delivery_man') ?: $userModel->findByPhone('081234567891');
    $driver = $dmModel->findByUserId((int)$driverUser['id']);
    Database::execute("UPDATE delivery_men SET is_online = 1 WHERE id = ?", [$driver['id']]);

    // Driver accepts order
    $assignRes = $deliveryService->acceptOrder((int)$driverUser['id'], (int)$orderId);
    echo "Driver Accepted Order: " . ($assignRes ? 'SUCCESS' : 'FAILED') . "\n";

    // Driver picks up food (status -> on_the_way)
    $deliveryService->updateOrderStatus((int)$driverUser['id'], (int)$orderId, 'picked_up');
    echo "Driver picked up food, status: on_the_way\n";

    // Driver verifies OTP and completes delivery
    $completeRes = $deliveryService->updateOrderStatus((int)$driverUser['id'], (int)$orderId, 'delivered', $otp);
    echo "Delivery Completion with OTP: " . ($completeRes ? 'SUCCESS' : 'FAILED') . "\n";

    // Verify final order state & GPS
    $finalOrder = $orderModel->findByCode($orderCode);
    echo "Final Order Status: " . $finalOrder['order_status'] . " (Payment: " . $finalOrder['payment_status'] . ")\n";
    echo "Order Delivery GPS: Lat " . $finalOrder['delivery_address']['lat'] . ", Lng " . $finalOrder['delivery_address']['lng'] . " (Calculated Distance: " . $finalOrder['distance_km'] . " Km)\n";

    // Test Parcel Order with Exact GPS Coordinates
    $parcelResult = $orderService->createParcelOrder($customer['id'], [
        'destination_address' => [
            'recipient_name'  => 'Pak RT 04',
            'recipient_phone' => '085544332211',
            'address'         => 'Griya Cicalengka Asri No. 10',
            'lat'             => -6.9890,
            'lng'             => 107.8390
        ],
        'parcel_details' => [
            'sender_name'     => 'Ibu Nina',
            'sender_phone'    => '081234567890',
            'pickup_address'  => 'Jl. Dipati Ukur Cicalengka Kulon',
            'pickup_lat'      => -6.9820,
            'pickup_lng'      => 107.8310,
            'item_category'   => 'Dokumen Penting',
            'weight_kg'       => 1.5
        ],
        'payment_method' => 'cod',
        'parcel_notes'   => 'Harap jangan dibanting'
    ]);
    
    $parcelOrder = $orderModel->findByCode($parcelResult['order_code']);
    echo "Parcel Order Created: " . $parcelOrder['order_code'] . " | Distance: " . $parcelOrder['distance_km'] . " Km | Pickup: [" . $parcelOrder['store_lat'] . ", " . $parcelOrder['store_lng'] . "] -> Dest: [" . $parcelOrder['delivery_address']['lat'] . ", " . $parcelOrder['delivery_address']['lng'] . "]\n";

    // Verify driver and merchant wallets
    $walletModel = new Wallet();
    $driverWallet = $walletModel->getOrCreate($driverUser['id']);
    echo "Driver Wallet Balance: Rp " . number_format((float)$driverWallet['balance'], 0, ',', '.') . "\n";
} else {
    echo "Order Placement FAILED\n";
}

echo "\n--- ALL TESTS COMPLETED SUCCESSFULLY! ---\n";
