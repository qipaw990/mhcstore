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
    Router::post('/payment/midtrans/notification', [PaymentController::class, 'notification']);
    Router::get('/modules', [ApiController::class, 'modules']);
    Router::get('/stores', [ApiController::class, 'stores']);
    Router::get('/stores/{id}', [ApiController::class, 'storeDetail']);
    Router::get('/products', [ApiController::class, 'products']);

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
        Router::post('/chats/read', [\App\Controllers\ChatController::class, 'markAsRead']);
        Router::get('/chats/unread-count', [\App\Controllers\ChatController::class, 'unreadCount']);

        // Call API
        Router::post('/calls/initiate', [\App\Controllers\CallController::class, 'initiate']);
        Router::get('/calls/poll', [\App\Controllers\CallController::class, 'poll']);
        Router::post('/calls/answer', [\App\Controllers\CallController::class, 'answer']);
        Router::post('/calls/reject', [\App\Controllers\CallController::class, 'reject']);
        Router::post('/calls/end', [\App\Controllers\CallController::class, 'end']);
        Router::post('/calls/ice-candidate', [\App\Controllers\CallController::class, 'iceCandidate']);
    });
});

// Unversioned API Routes (/api/...)
Router::group(['prefix' => '/api'], function () {
    Router::post('/login', [ApiController::class, 'login']);
    Router::post('/auth/login', [ApiController::class, 'login']);
    Router::post('/register', [ApiController::class, 'register']);
    Router::post('/auth/register', [ApiController::class, 'register']);
    Router::get('/modules', [ApiController::class, 'modules']);
    Router::get('/stores', [ApiController::class, 'stores']);
    Router::get('/stores/{id}', [ApiController::class, 'storeDetail']);
    Router::get('/products', [ApiController::class, 'products']);
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
    Router::post('/chats/read', [\App\Controllers\ChatController::class, 'markAsRead']);
    Router::get('/chats/unread-count', [\App\Controllers\ChatController::class, 'unreadCount']);

    // Call API
    Router::post('/calls/initiate', [\App\Controllers\CallController::class, 'initiate']);
    Router::get('/calls/poll', [\App\Controllers\CallController::class, 'poll']);
    Router::post('/calls/answer', [\App\Controllers\CallController::class, 'answer']);
    Router::post('/calls/reject', [\App\Controllers\CallController::class, 'reject']);
    Router::post('/calls/end', [\App\Controllers\CallController::class, 'end']);
    Router::post('/calls/ice-candidate', [\App\Controllers\CallController::class, 'iceCandidate']);
});
