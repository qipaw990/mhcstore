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
use App\Controllers\ChatController;

// ==========================================
// 1. Auth Routes
// ==========================================
Router::get('/login', [AuthController::class, 'showLogin']);
Router::post('/login', [AuthController::class, 'handleLogin']);
Router::get('/refresh-captcha', [AuthController::class, 'refreshCaptcha']);
Router::get('/verify-otp', [AuthController::class, 'showVerifyOtp']);
Router::post('/verify-otp', [AuthController::class, 'handleVerifyOtp']);
Router::get('/profile/verify-otp', [AuthController::class, 'showVerifyOtp']);
Router::post('/profile/verify-otp', [AuthController::class, 'handleVerifyOtp']);
Router::get('/admin/verify-otp', [AuthController::class, 'showVerifyOtp']);
Router::post('/admin/verify-otp', [AuthController::class, 'handleVerifyOtp']);
Router::get('/vendor/verify-otp', [AuthController::class, 'showVerifyOtp']);
Router::post('/vendor/verify-otp', [AuthController::class, 'handleVerifyOtp']);
Router::get('/delivery/verify-otp', [AuthController::class, 'showVerifyOtp']);
Router::post('/delivery/verify-otp', [AuthController::class, 'handleVerifyOtp']);
Router::post('/resend-otp', [AuthController::class, 'handleResendOtp']);
Router::get('/register', [AuthController::class, 'showRegister']);
Router::post('/register', [AuthController::class, 'handleRegister']);
Router::get('/forgot-password', [AuthController::class, 'showForgotPassword']);
Router::post('/forgot-password', [AuthController::class, 'handleForgotPassword']);
Router::get('/reset-password', [AuthController::class, 'showResetPassword']);
Router::post('/reset-password', [AuthController::class, 'handleResetPassword']);
Router::get('/logout', [AuthController::class, 'logout']);

// ==========================================
// 2. Customer PWA Routes (Public / Auth)
// ==========================================
Router::get('/', [CustomerController::class, 'home']);
Router::get('/search', [CustomerController::class, 'search']);
Router::get('/explore-stores', [CustomerController::class, 'exploreStores']);
Router::get('/stores', [CustomerController::class, 'exploreStores']);
Router::get('/stores/{id}', [CustomerController::class, 'storeDetail']);
Router::get('/parcel', [CustomerController::class, 'parcel']);
Router::get('/profile', [CustomerController::class, 'profile']);
Router::post('/profile/update', [CustomerController::class, 'updateProfile'], ['AuthMiddleware']);
Router::get('/wallet', [CustomerController::class, 'wallet'], ['AuthMiddleware']);
Router::post('/wallet/topup-midtrans', [PaymentController::class, 'topupSnap'], ['AuthMiddleware']);
Router::post('/payment/topup-snap', [PaymentController::class, 'topupSnap'], ['AuthMiddleware']);
Router::post('/payment/topup-update-status', [PaymentController::class, 'updateTopupStatus'], ['AuthMiddleware']);
Router::post('/payment/verify', [PaymentController::class, 'verifyClientCallback']);
Router::post('/payment/simulate-sandbox-success', [PaymentController::class, 'simulateSandboxSuccess']);
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
Router::get('/orders/live-list', [OrderController::class, 'getLiveOrdersList'], ['AuthMiddleware']);
Router::get('/orders/{code}/tracking', [OrderController::class, 'tracking']);
Router::get('/orders/{code}/live-tracking', [OrderController::class, 'getLiveTracking']);
Router::post('/orders/get-snap-token', [OrderController::class, 'getSnapToken'], ['AuthMiddleware']);
Router::post('/orders/cancel-unpaid', [OrderController::class, 'cancelUnpaid'], ['AuthMiddleware']);
Router::post('/orders/review', [OrderController::class, 'submitReview'], ['AuthMiddleware']);
Router::post('/reviews/submit', [OrderController::class, 'submitReview'], ['AuthMiddleware']);
Router::get('/orders/{code}', [OrderController::class, 'showOrder']);

// In-App Chat Routes — no middleware, auth handled inside controller via order_code
Router::get('/chats/messages', [ChatController::class, 'getMessages']);
Router::post('/chats/send', [ChatController::class, 'sendMessage']);
Router::post('/chats/read', [ChatController::class, 'markAsRead']);
Router::get('/chats/unread-count', [ChatController::class, 'unreadCount']);

// In-App Voice Call Routes
Router::post('/calls/initiate', [\App\Controllers\CallController::class, 'initiate']);
Router::get('/calls/poll', [\App\Controllers\CallController::class, 'poll']);
Router::post('/calls/answer', [\App\Controllers\CallController::class, 'answer']);
Router::post('/calls/reject', [\App\Controllers\CallController::class, 'reject']);
Router::post('/calls/end', [\App\Controllers\CallController::class, 'end']);
Router::post('/calls/ice-candidate', [\App\Controllers\CallController::class, 'iceCandidate']);

// ==========================================
// 3. Delivery Man PWA Routes
// ==========================================
Router::group(['prefix' => '/delivery', 'middleware' => ['DeliveryMiddleware']], function () {
    Router::get('', [DeliveryController::class, 'dashboard']);
    Router::get('/live-dashboard', [DeliveryController::class, 'getLiveDashboard']);
    Router::post('/toggle-online', [DeliveryController::class, 'toggleOnline']);
    Router::post('/accept-order', [DeliveryController::class, 'acceptOrder']);
    Router::post('/update-status', [DeliveryController::class, 'updateDeliveryStatus']);
    Router::post('/update-location', [DeliveryController::class, 'updateLocation']);
    Router::get('/batch-status', [DeliveryController::class, 'getBatchStatus']);
    Router::get('/earnings', [DeliveryController::class, 'earnings']);
    Router::post('/withdraw', [DeliveryController::class, 'requestWithdraw']);
    Router::get('/profile', [DeliveryController::class, 'profile']);
    Router::post('/profile/update', [DeliveryController::class, 'updateProfile']);
});

// ==========================================
// 4. Vendor Portal Routes
// ==========================================
Router::group(['prefix' => '/vendor', 'middleware' => ['VendorMiddleware']], function () {
    Router::get('', [VendorController::class, 'dashboard']);
    Router::post('/toggle-status', [VendorController::class, 'toggleStoreStatus']);
    Router::get('/orders', [VendorController::class, 'orders']);
    Router::get('/orders/check-new', [VendorController::class, 'checkNewOrders']);
    Router::post('/orders/update-status', [VendorController::class, 'updateOrderStatus']);
    Router::get('/products', [VendorController::class, 'products']);
    Router::get('/products/create', [VendorController::class, 'productForm']);
    Router::get('/products/edit/{id}', [VendorController::class, 'productForm']);
    Router::post('/products/save', [VendorController::class, 'saveProduct']);
    Router::post('/products/delete', [VendorController::class, 'deleteProduct']);
    Router::post('/products/toggle-status', [VendorController::class, 'toggleProductStatus']);
    Router::get('/wallet', [VendorController::class, 'wallet']);
    Router::post('/wallet/withdraw', [VendorController::class, 'requestWithdraw']);
    Router::get('/profile', [VendorController::class, 'profile']);
    Router::post('/profile/update', [VendorController::class, 'updateProfile']);
});

// ==========================================
// 5. Super Admin Routes
// ==========================================
Router::group(['prefix' => '/admin', 'middleware' => ['AdminMiddleware']], function () {
    Router::get('', [AdminController::class, 'dashboard']);

    // Orders & Dispatch
    Router::get('/orders', [AdminController::class, 'orders']);
    Router::get('/orders/{id}', [AdminController::class, 'orderDetail']);
    Router::get('/orders/detail/{id}', [AdminController::class, 'orderDetail']);
    Router::get('/orders/invoice/{id}', [AdminController::class, 'invoice']);
    Router::get('/invoice/{id}', [AdminController::class, 'invoice']);
    Router::post('/orders/assign-driver', [AdminController::class, 'assignDriver']);
    Router::post('/orders/update-status', [AdminController::class, 'updateOrderStatus']);
    Router::post('/orders/cancel', [AdminController::class, 'cancelOrder']);
    Router::post('/orders/delete', [AdminController::class, 'deleteOrder']);

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
    Router::post('/stores/bulk-delete', [AdminController::class, 'bulkDeleteStores']);
    Router::post('/stores/delete-all', [AdminController::class, 'deleteAllStores']);

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
    Router::post('/banners/toggle-status', [AdminController::class, 'toggleBannerStatus']);
    Router::post('/banners/delete', [AdminController::class, 'deleteBanner']);

    // Settings & Profile
    Router::get('/profile', [AdminController::class, 'profile']);
    Router::post('/profile/update', [AdminController::class, 'updateProfile']);
    Router::get('/settings', [AdminController::class, 'settings']);
    Router::post('/settings/save', [AdminController::class, 'saveSettings']);

    // Midtrans & Email Gateway Diagnostics
    Router::get('/midtrans/status/{code}', [AdminController::class, 'getMidtransStatus']);
    Router::post('/midtrans/test-connection', [AdminController::class, 'testMidtransApi']);
    Router::post('/email/test-send', [AdminController::class, 'testEmailGateway']);

    // Payouts & Withdrawals
    // Top-ups & Withdrawals
    Router::get('/topups', [AdminController::class, 'topups']);
    Router::post('/topups/manual-approve', [AdminController::class, 'manualApproveTopup']);
    Router::post('/topups/manual-cancel', [AdminController::class, 'manualCancelTopup']);
    Router::get('/withdrawals', [AdminController::class, 'withdrawals']);
    Router::post('/withdrawals/update-status', [AdminController::class, 'updateWithdrawalStatus']);

    // In-House Automated Payment & Bank Setup
    Router::get('/payment-methods', [AdminController::class, 'paymentMethods']);
    Router::post('/payment-methods/save-bank', [AdminController::class, 'savePaymentBank']);
    Router::post('/payment-methods/save-qris', [AdminController::class, 'savePaymentQris']);
    Router::post('/payment-invoices/approve', [AdminController::class, 'approvePaymentInvoice']);
    Router::post('/payment-methods/test-webhook', [AdminController::class, 'testPaymentWebhook']);

    // WhatsApp Gateway Management
    Router::get('/whatsapp', [AdminController::class, 'whatsapp']);
    Router::get('/whatsapp/status', [AdminController::class, 'waStatus']);
    Router::post('/whatsapp/toggle-otp', [AdminController::class, 'waToggleOtp']);
    Router::post('/whatsapp/set-channel', [AdminController::class, 'waSetOtpChannel']);
    Router::post('/whatsapp/save-settings', [AdminController::class, 'waSaveSettings']);
    Router::post('/whatsapp/send-test', [AdminController::class, 'waSendTest']);
    Router::post('/whatsapp/send-message', [AdminController::class, 'waSendMessage']);
    Router::post('/whatsapp/restart', [AdminController::class, 'waRestart']);
    Router::get('/whatsapp/download-compose', [AdminController::class, 'waDownloadCompose']);
    Router::get('/whatsapp/download-dockerfile', [AdminController::class, 'waDownloadDockerfile']);
});

// API Extension Scraper Import Route & Schedule Filler
Router::post('/api/import-store', [\App\Controllers\ApiController::class, 'importStore']);
Router::options('/api/import-store', [\App\Controllers\ApiController::class, 'importStore']);
Router::get('/api/fill-schedules', [\App\Controllers\ApiController::class, 'fillSchedules']);
Router::get('/fill-schedules', [\App\Controllers\ApiController::class, 'fillSchedules']);
Router::get('/api/fix-images', [\App\Controllers\ApiController::class, 'fixImages']);
Router::get('/fix-images', [\App\Controllers\ApiController::class, 'fixImages']);
Router::post('/api/login', [\App\Controllers\ApiController::class, 'login']);
Router::options('/api/login', [\App\Controllers\ApiController::class, 'login']);
Router::post('/api/register', [\App\Controllers\ApiController::class, 'register']);
Router::options('/api/register', [\App\Controllers\ApiController::class, 'register']);
Router::get('/api/home-data', [\App\Controllers\ApiController::class, 'homeData']);
Router::get('/api/coupons', [\App\Controllers\ApiController::class, 'coupons']);
Router::get('/api/vouchers', [\App\Controllers\ApiController::class, 'coupons']);
Router::post('/api/coupons/validate', [\App\Controllers\ApiController::class, 'validateCoupon']);
Router::post('/coupons/validate', [\App\Controllers\ApiController::class, 'validateCoupon']);
Router::get('/api/products/{id}', [\App\Controllers\ApiController::class, 'productDetail']);
Router::get('/api/products/{id}/reviews', [\App\Controllers\ApiController::class, 'productReviews']);
Router::get('/api/banners', [\App\Controllers\ApiController::class, 'banners']);
Router::get('/api/categories', [\App\Controllers\ApiController::class, 'categories']);
Router::get('/api/clear-stores', [\App\Controllers\ApiController::class, 'clearStores']);
Router::post('/api/clear-stores', [\App\Controllers\ApiController::class, 'clearStores']);
Router::get('/api/explore-stores', [CustomerController::class, 'exploreStores']);
Router::get('/api/search', [CustomerController::class, 'search']);
Router::get('/api/stores/{id}', [CustomerController::class, 'storeDetail']);
Router::get('/api/wallet', [CustomerController::class, 'wallet']);
Router::get('/api/profile', [CustomerController::class, 'profile']);
Router::get('/api/notifications', [CustomerController::class, 'notifications']);

// ==========================================
// In-House Payment System (Bank Transfer / QRIS / Kode Unik / Webhook)
// ==========================================
Router::get('/api/payment/banks', [PaymentController::class, 'getBanks']);
Router::get('/payment/banks', [PaymentController::class, 'getBanks']);
Router::post('/api/payment/create-invoice', [PaymentController::class, 'createInvoice']);
Router::post('/payment/create-invoice', [PaymentController::class, 'createInvoice']);
Router::get('/api/payment/check-invoice', [PaymentController::class, 'checkInvoice']);
Router::get('/payment/check-invoice', [PaymentController::class, 'checkInvoice']);
Router::post('/api/payment/auto-webhook', [PaymentController::class, 'autoWebhook']);
Router::post('/payment/auto-webhook', [PaymentController::class, 'autoWebhook']);
Router::post('/api/payment/simulate-pay', [PaymentController::class, 'simulatePay']);
Router::post('/payment/simulate-pay', [PaymentController::class, 'simulatePay']);
Router::post('/api/payment/transfer', [PaymentController::class, 'transfer']);
Router::post('/payment/transfer', [PaymentController::class, 'transfer']);
Router::post('/wallet/transfer', [PaymentController::class, 'transfer']);



