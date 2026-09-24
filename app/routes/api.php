<?php
/**
 * API Routes Definition (/api/v1/...)
 */

use App\Core\Router;
use App\Controllers\ApiController;
use App\Controllers\PaymentController;

// Versioned API Routes (/api/v1/...)
Router::group(['prefix' => '/api/v1'], function () {
    // Public API & Webhooks
    Router::post('/auth/login', [ApiController::class, 'login']);
    Router::post('/login', [ApiController::class, 'login']);
    Router::post('/auth/register', [ApiController::class, 'register']);
    Router::post('/register', [ApiController::class, 'register']);
    Router::post('/vendor/register', [ApiController::class, 'registerMerchant']);
    Router::post('/register-merchant', [ApiController::class, 'registerMerchant']);
    Router::post('/payment/midtrans/notification', [PaymentController::class, 'dokuNotification']); // legacy alias -> DOKU
    Router::get('/modules', [ApiController::class, 'modules']);
    Router::get('/stores', [ApiController::class, 'stores']);
    Router::get('/products', [ApiController::class, 'products']);
    Router::get('/products/{id}', [ApiController::class, 'productDetail']);
    Router::get('/products/{id}/reviews', [ApiController::class, 'productReviews']);

    // Authenticated API (Token Required)
    Router::group(['middleware' => ['ApiAuthMiddleware']], function () {
        Router::get('/cart', [ApiController::class, 'cart']);
        Router::post('/cart', [ApiController::class, 'cart']);
        Router::post('/checkout', [ApiController::class, 'checkout']);
        Router::get('/orders', [ApiController::class, 'orders']);
        Router::get('/orders/{code}', [ApiController::class, 'orderTracking']);
        Router::get('/wallet', [ApiController::class, 'wallet']);
        Router::get('/notifications', [ApiController::class, 'notifications']);
        Router::post('/delivery/location', [ApiController::class, 'updateDriverLocation']);
        Router::post('/delivery/update-location', [ApiController::class, 'updateDriverLocation']);

        // Chat API
        Router::get('/chats/messages', [\App\Controllers\ChatController::class, 'getMessages']);
        Router::post('/chats/send', [\App\Controllers\ChatController::class, 'sendMessage']);
        Router::post('/chats/send-photo', [\App\Controllers\ChatController::class, 'sendPhoto']);
        Router::get('/chats/store-messages', [\App\Controllers\ChatController::class, 'getStoreMessages']);
        Router::post('/chats/store-send', [\App\Controllers\ChatController::class, 'sendStoreMessage']);
        Router::post('/chats/store-send-photo', [\App\Controllers\ChatController::class, 'sendStorePhoto']);
        Router::post('/chats/read', [\App\Controllers\ChatController::class, 'markAsRead']);
        Router::get('/chats/unread-count', [\App\Controllers\ChatController::class, 'unreadCount']);

        // Call API
        Router::post('/calls/initiate', [\App\Controllers\CallController::class, 'initiate']);
        Router::get('/calls/poll', [\App\Controllers\CallController::class, 'poll']);
        Router::post('/calls/answer', [\App\Controllers\CallController::class, 'answer']);
        Router::post('/calls/reject', [\App\Controllers\CallController::class, 'reject']);
        Router::post('/calls/end', [\App\Controllers\CallController::class, 'end']);
        Router::post('/calls/ice-candidate', [\App\Controllers\CallController::class, 'iceCandidate']);

        // Vendor / Merchant API (requires token)
        Router::get('/vendor/products', [\App\Controllers\VendorController::class, 'products']);
        Router::post('/vendor/products', [\App\Controllers\VendorController::class, 'saveProduct']);
        Router::post('/vendor/products/save', [\App\Controllers\VendorController::class, 'saveProduct']);
        Router::post('/vendor/products/create', [\App\Controllers\VendorController::class, 'saveProduct']);
        Router::post('/vendor/products/toggle-status', [\App\Controllers\VendorController::class, 'toggleProductStatus']);
        Router::post('/vendor/products/delete', [\App\Controllers\VendorController::class, 'deleteProduct']);
        Router::get('/vendor/wallet', [\App\Controllers\VendorController::class, 'wallet']);
        Router::post('/vendor/wallet/withdraw', [\App\Controllers\VendorController::class, 'requestWithdraw']);
        Router::get('/vendor/raw-materials', [\App\Controllers\VendorController::class, 'rawMaterials']);
        Router::post('/vendor/raw-materials/save', [\App\Controllers\VendorController::class, 'saveRawMaterial']);
        Router::post('/vendor/raw-materials/delete', [\App\Controllers\VendorController::class, 'deleteRawMaterial']);
        Router::get('/vendor/products/{id}/recipe', [\App\Controllers\VendorController::class, 'getProductRecipe']);
        Router::post('/vendor/products/recipe/save', [\App\Controllers\VendorController::class, 'saveProductRecipe']);
        Router::post('/vendor/products/{id}/recipe/save', [\App\Controllers\VendorController::class, 'saveProductRecipe']);
        Router::post('/vendor/products/{id}/recipe', [\App\Controllers\VendorController::class, 'saveProductRecipe']);
        Router::post('/products/recipe/save', [\App\Controllers\VendorController::class, 'saveProductRecipe']);
        Router::post('/products/{id}/recipe/save', [\App\Controllers\VendorController::class, 'saveProductRecipe']);
        Router::get('/products/{id}/recipe', [\App\Controllers\VendorController::class, 'getProductRecipe']);
        Router::get('/raw-materials', [\App\Controllers\VendorController::class, 'rawMaterials']);
        Router::post('/raw-materials/save', [\App\Controllers\VendorController::class, 'saveRawMaterial']);
        Router::post('/raw-materials/delete', [\App\Controllers\VendorController::class, 'deleteRawMaterial']);
    });
});

// Unversioned API Routes (/api/...)
Router::group(['prefix' => '/api'], function () {
    Router::post('/login', [ApiController::class, 'login']);
    Router::post('/auth/login', [ApiController::class, 'login']);
    Router::post('/verify-otp', [ApiController::class, 'verifyOtp']);
    Router::post('/resend-otp', [ApiController::class, 'resendOtp']);
    Router::get('/logout', [ApiController::class, 'logout']);
    Router::post('/logout', [ApiController::class, 'logout']);
    Router::post('/register', [ApiController::class, 'register']);
    Router::post('/auth/register', [ApiController::class, 'register']);
    Router::get('/modules', [ApiController::class, 'modules']);
    Router::get('/stores', [ApiController::class, 'stores']);
    Router::get('/stores/{id}', [ApiController::class, 'storeDetail']);
    Router::get('/products', [ApiController::class, 'products']);
    Router::get('/products/{id}', [ApiController::class, 'productDetail']);
    Router::get('/products/{id}/reviews', [ApiController::class, 'productReviews']);
    Router::get('/coupons', [ApiController::class, 'coupons']);
    Router::post('/coupons/validate', [ApiController::class, 'validateCoupon']);
    Router::get('/cart', [ApiController::class, 'cart']);
    Router::post('/cart', [ApiController::class, 'cart']);
    Router::post('/cart/add', [\App\Controllers\CartController::class, 'add']);
    Router::post('/cart/update-qty', [\App\Controllers\CartController::class, 'updateQty']);
    Router::post('/cart/remove', [\App\Controllers\CartController::class, 'remove']);
    Router::post('/cart/clear', [\App\Controllers\CartController::class, 'clear']);
    Router::post('/checkout', [ApiController::class, 'checkout']);
    Router::get('/orders', [ApiController::class, 'orders']);
    Router::get('/orders/{code}', [ApiController::class, 'orderTracking']);
    Router::get('/wallet', [ApiController::class, 'wallet']);
    Router::get('/notifications', [ApiController::class, 'notifications']);
    Router::post('/delivery/location', [ApiController::class, 'updateDriverLocation']);
    Router::post('/delivery/update-location', [ApiController::class, 'updateDriverLocation']);

    // Chat API
    Router::get('/chats/messages', [\App\Controllers\ChatController::class, 'getMessages']);
    Router::post('/chats/send', [\App\Controllers\ChatController::class, 'sendMessage']);
    Router::post('/chats/send-photo', [\App\Controllers\ChatController::class, 'sendPhoto']);
    Router::get('/chats/store-messages', [\App\Controllers\ChatController::class, 'getStoreMessages']);
    Router::post('/chats/store-send', [\App\Controllers\ChatController::class, 'sendStoreMessage']);
    Router::post('/chats/store-send-photo', [\App\Controllers\ChatController::class, 'sendStorePhoto']);
    Router::post('/chats/read', [\App\Controllers\ChatController::class, 'markAsRead']);
    Router::get('/chats/unread-count', [\App\Controllers\ChatController::class, 'unreadCount']);

    // Call API
    Router::post('/calls/initiate', [\App\Controllers\CallController::class, 'initiate']);
    Router::get('/calls/poll', [\App\Controllers\CallController::class, 'poll']);
    Router::post('/calls/answer', [\App\Controllers\CallController::class, 'answer']);
    Router::post('/calls/reject', [\App\Controllers\CallController::class, 'reject']);
    Router::post('/calls/end', [\App\Controllers\CallController::class, 'end']);
    Router::post('/calls/ice-candidate', [\App\Controllers\CallController::class, 'iceCandidate']);

    // Vendor / Merchant API (token checked inside controller via auth_id)
    Router::get('/vendor/products', [\App\Controllers\VendorController::class, 'products']);
    Router::post('/vendor/products', [\App\Controllers\VendorController::class, 'saveProduct']);
    Router::post('/vendor/products/save', [\App\Controllers\VendorController::class, 'saveProduct']);
    Router::post('/vendor/products/create', [\App\Controllers\VendorController::class, 'saveProduct']);
    Router::post('/vendor/products/toggle-status', [\App\Controllers\VendorController::class, 'toggleProductStatus']);
    Router::post('/vendor/products/delete', [\App\Controllers\VendorController::class, 'deleteProduct']);
    Router::get('/vendor/products/find-by-barcode', [\App\Controllers\VendorController::class, 'findProductByBarcode']);
    Router::post('/vendor/products/stock-in', [\App\Controllers\VendorController::class, 'stockIn']);
    Router::get('/vendor/wallet', [\App\Controllers\VendorController::class, 'wallet']);
    Router::post('/vendor/wallet/withdraw', [\App\Controllers\VendorController::class, 'requestWithdraw']);
    Router::get('/vendor/orders', [\App\Controllers\VendorController::class, 'orders']);
    Router::get('/vendor/orders/check-new', [\App\Controllers\VendorController::class, 'checkNewOrders']);
    Router::post('/vendor/orders/update-status', [\App\Controllers\VendorController::class, 'updateOrderStatus']);
    Router::post('/vendor/toggle-status', [\App\Controllers\VendorController::class, 'toggleStoreStatus']);
    Router::get('/vendor/profile', [\App\Controllers\VendorController::class, 'profile']);
    Router::post('/vendor/profile/update', [\App\Controllers\VendorController::class, 'updateProfile']);
    Router::get('/vendor/raw-materials', [\App\Controllers\VendorController::class, 'rawMaterials']);
    Router::post('/vendor/raw-materials/save', [\App\Controllers\VendorController::class, 'saveRawMaterial']);
    Router::post('/vendor/raw-materials/delete', [\App\Controllers\VendorController::class, 'deleteRawMaterial']);
    Router::get('/vendor/products/{id}/recipe', [\App\Controllers\VendorController::class, 'getProductRecipe']);
    Router::post('/vendor/products/recipe/save', [\App\Controllers\VendorController::class, 'saveProductRecipe']);
    Router::post('/vendor/products/{id}/recipe/save', [\App\Controllers\VendorController::class, 'saveProductRecipe']);
    Router::post('/vendor/products/{id}/recipe', [\App\Controllers\VendorController::class, 'saveProductRecipe']);
    Router::post('/products/recipe/save', [\App\Controllers\VendorController::class, 'saveProductRecipe']);
    Router::post('/products/{id}/recipe/save', [\App\Controllers\VendorController::class, 'saveProductRecipe']);
    Router::get('/products/{id}/recipe', [\App\Controllers\VendorController::class, 'getProductRecipe']);
    Router::get('/raw-materials', [\App\Controllers\VendorController::class, 'rawMaterials']);
    Router::post('/raw-materials/save', [\App\Controllers\VendorController::class, 'saveRawMaterial']);
    Router::post('/raw-materials/delete', [\App\Controllers\VendorController::class, 'deleteRawMaterial']);
    Router::post('/vendor/addons/delete', [\App\Controllers\VendorController::class, 'deleteAddon']);
});
