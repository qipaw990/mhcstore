<?php
/**
 * Web Routes Definition
 */

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\CustomerController;
use App\Controllers\CartController;
use App\Controllers\OrderController;
use App\Controllers\PaymentController;
use App\Controllers\DeliveryController;
use App\Controllers\VendorController;
use App\Controllers\AdminController;

// ==========================================
// 1. Auth Routes
// ==========================================
Router::get('/login', [AuthController::class, 'showLogin']);
Router::post('/login', [AuthController::class, 'handleLogin']);
Router::get('/verify-otp', [AuthController::class, 'showVerifyOtp']);
Router::post('/verify-otp', [AuthController::class, 'handleVerifyOtp']);
Router::post('/resend-otp', [AuthController::class, 'handleResendOtp']);
Router::get('/register', [AuthController::class, 'showRegister']);
Router::post('/register', [AuthController::class, 'handleRegister']);
Router::get('/logout', [AuthController::class, 'logout']);

// ==========================================
// 2. Customer PWA Routes (Public / Auth)
// ==========================================
Router::get('/', [CustomerController::class, 'home']);
Router::get('/search', [CustomerController::class, 'search']);
Router::get('/stores/{id}', [CustomerController::class, 'storeDetail']);
Router::get('/parcel', [CustomerController::class, 'parcel']);
Router::get('/profile', [CustomerController::class, 'profile']);
Router::get('/wallet', [CustomerController::class, 'wallet'], ['AuthMiddleware']);
Router::post('/wallet/topup-midtrans', [PaymentController::class, 'topupSnap'], ['AuthMiddleware']);
Router::post('/payment/verify', [PaymentController::class, 'verifyClientCallback']);
Router::post('/payment/midtrans/notification', [PaymentController::class, 'notification']);
Router::get('/notifications', [CustomerController::class, 'notifications'], ['AuthMiddleware']);

// Cart
Router::get('/cart', [CartController::class, 'viewCart']);
Router::post('/cart/add', [CartController::class, 'add']);
Router::post('/cart/update-qty', [CartController::class, 'updateQty']);
Router::post('/cart/remove', [CartController::class, 'remove']);
Router::post('/cart/clear', [CartController::class, 'clear']);

// Orders & Checkout
Router::get('/checkout', [OrderController::class, 'checkout'], ['AuthMiddleware']);
Router::post('/orders/place', [OrderController::class, 'placeOrder'], ['AuthMiddleware']);
Router::post('/orders/place-parcel', [OrderController::class, 'placeParcel'], ['AuthMiddleware']);
Router::post('/parcel/place', [OrderController::class, 'placeParcel'], ['AuthMiddleware']);
Router::get('/orders', [OrderController::class, 'ordersList'], ['AuthMiddleware']);
Router::get('/orders/{code}/tracking', [OrderController::class, 'tracking']);
Router::get('/orders/{code}/live-tracking', [OrderController::class, 'getLiveTracking']);

// ==========================================
// 3. Delivery Man PWA Routes
// ==========================================
Router::group(['prefix' => '/delivery', 'middleware' => ['DeliveryMiddleware']], function () {
    Router::get('', [DeliveryController::class, 'dashboard']);
    Router::post('/toggle-online', [DeliveryController::class, 'toggleOnline']);
    Router::post('/accept-order', [DeliveryController::class, 'acceptOrder']);
    Router::post('/update-status', [DeliveryController::class, 'updateDeliveryStatus']);
    Router::post('/update-location', [DeliveryController::class, 'updateLocation']);
    Router::get('/earnings', [DeliveryController::class, 'earnings']);
});

// ==========================================
// 4. Vendor Portal Routes
// ==========================================
Router::group(['prefix' => '/vendor', 'middleware' => ['VendorMiddleware']], function () {
    Router::get('', [VendorController::class, 'dashboard']);
    Router::post('/toggle-status', [VendorController::class, 'toggleStoreStatus']);
    Router::get('/orders', [VendorController::class, 'orders']);
    Router::post('/orders/update-status', [VendorController::class, 'updateOrderStatus']);
    Router::get('/products', [VendorController::class, 'products']);
    Router::get('/products/create', [VendorController::class, 'productForm']);
    Router::get('/products/edit/{id}', [VendorController::class, 'productForm']);
    Router::post('/products/save', [VendorController::class, 'saveProduct']);
    Router::post('/products/delete', [VendorController::class, 'deleteProduct']);
    Router::get('/wallet', [VendorController::class, 'wallet']);
});

// ==========================================
// 5. Super Admin Routes
// ==========================================
Router::group(['prefix' => '/admin', 'middleware' => ['AdminMiddleware']], function () {
    Router::get('', [AdminController::class, 'dashboard']);

    // Orders & Dispatch
    Router::get('/orders', [AdminController::class, 'orders']);
    Router::get('/orders/detail/{id}', [AdminController::class, 'orderDetail']);
    Router::get('/orders/invoice/{id}', [AdminController::class, 'invoice']);
    Router::post('/orders/assign-driver', [AdminController::class, 'assignDriver']);
    Router::post('/orders/update-status', [AdminController::class, 'updateOrderStatus']);
    Router::post('/orders/cancel', [AdminController::class, 'cancelOrder']);

    // Zones
    Router::get('/zones', [AdminController::class, 'zones']);
    Router::post('/zones/save', [AdminController::class, 'saveZone']);
    Router::post('/zones/delete', [AdminController::class, 'deleteZone']);

    // Modules
    Router::get('/modules', [AdminController::class, 'modules']);
    Router::post('/modules/save', [AdminController::class, 'saveModule']);
    Router::post('/modules/toggle-status', [AdminController::class, 'toggleModuleStatus']);
    Router::post('/modules/delete', [AdminController::class, 'deleteModule']);

    // Stores / Merchants
    Router::get('/stores', [AdminController::class, 'stores']);
    Router::post('/stores/save', [AdminController::class, 'saveStore']);
    Router::post('/stores/toggle-open', [AdminController::class, 'toggleStoreOpen']);
    Router::post('/stores/update-status', [AdminController::class, 'updateStoreStatus']);
    Router::post('/stores/delete', [AdminController::class, 'deleteStore']);

    // Products
    Router::get('/products', [AdminController::class, 'products']);
    Router::post('/products/save', [AdminController::class, 'saveProduct']);
    Router::post('/products/update-stock', [AdminController::class, 'updateProductStock']);
    Router::post('/products/toggle-status', [AdminController::class, 'toggleProductStatus']);
    Router::post('/products/delete', [AdminController::class, 'deleteProduct']);

    // Delivery Men / Fleet
    Router::get('/delivery-men', [AdminController::class, 'deliveryMen']);
    Router::get('/delivery-men/wallet-history/{id}', [AdminController::class, 'deliveryManWalletHistory']);
    Router::post('/delivery-men/save', [AdminController::class, 'saveDeliveryMan']);
    Router::post('/delivery-men/toggle-status', [AdminController::class, 'toggleDeliveryManStatus']);
    Router::post('/delivery-men/delete', [AdminController::class, 'deleteDeliveryMan']);
    Router::post('/delivery-men/topup', [AdminController::class, 'topupDeliveryMan']);

    // Customers
    Router::get('/customers', [AdminController::class, 'customers']);
    Router::get('/customers/history/{id}', [AdminController::class, 'customerHistory']);
    Router::post('/customers/toggle-status', [AdminController::class, 'toggleCustomerStatus']);
    Router::post('/customers/topup', [AdminController::class, 'topupCustomer']);

    // Banners
    Router::get('/banners', [AdminController::class, 'banners']);
    Router::post('/banners/save', [AdminController::class, 'saveBanner']);
    Router::post('/banners/delete', [AdminController::class, 'deleteBanner']);

    // Settings
    Router::get('/settings', [AdminController::class, 'settings']);
    Router::post('/settings/save', [AdminController::class, 'saveSettings']);

    // Midtrans & Email Gateway Diagnostics
    Router::get('/midtrans/status/{code}', [AdminController::class, 'getMidtransStatus']);
    Router::post('/midtrans/test-connection', [AdminController::class, 'testMidtransApi']);
    Router::post('/email/test-send', [AdminController::class, 'testEmailGateway']);
});

