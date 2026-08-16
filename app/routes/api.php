<?php
/**
 * API Routes Definition (/api/v1/...)
 */

use App\Core\Router;
use App\Controllers\ApiController;
use App\Controllers\PaymentController;

Router::group(['prefix' => '/api/v1'], function () {
    // Public API & Webhooks
    Router::post('/auth/login', [ApiController::class, 'login']);
    Router::post('/auth/register', [ApiController::class, 'register']);
    Router::post('/payment/midtrans/notification', [PaymentController::class, 'notification']);
    Router::get('/modules', [ApiController::class, 'modules']);
    Router::get('/stores', [ApiController::class, 'stores']);
    Router::get('/stores/{id}', [ApiController::class, 'storeDetail']);
    Router::get('/products', [ApiController::class, 'products']);

    // Authenticated API (Token Required)
    Router::group(['middleware' => ['ApiAuthMiddleware']], function () {
        Router::get('/cart', [ApiController::class, 'cart']);
        Router::post('/checkout', [ApiController::class, 'checkout']);
        Router::get('/orders', [ApiController::class, 'orders']);
        Router::get('/orders/{code}', [ApiController::class, 'orderTracking']);
        Router::get('/wallet', [ApiController::class, 'wallet']);
        Router::get('/notifications', [ApiController::class, 'notifications']);
        Router::post('/delivery/location', [ApiController::class, 'updateDriverLocation']);
    });
});
