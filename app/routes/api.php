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
});
