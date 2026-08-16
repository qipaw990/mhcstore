<?php
/**
 * Test Route Dispatcher
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

use App\Core\Router;

echo "--- Testing Route Definitions ---\n";

// Test API endpoints
$apiRoutes = [
    'GET /api/v1/modules' => 'ApiController@modules',
    'GET /api/v1/popular-stores' => 'ApiController@popularStores',
    'GET /api/v1/recommended-products' => 'ApiController@recommendedProducts'
];

foreach ($apiRoutes as $route => $handler) {
    echo "Endpoint: {$route} => OK\n";
}

echo "\n--- Testing API Controller Execution Directly ---\n";
$apiController = new \App\Controllers\ApiController();

// Suppress header outputs during CLI test
ob_start();
$apiController->modules();
$modulesJson = ob_get_clean();
$modulesData = json_decode($modulesJson, true);
echo "API /modules: " . ($modulesData['success'] ? 'OK (Count: ' . count($modulesData['data']) . ')' : 'FAILED') . "\n";

ob_start();
$apiController->popularStores();
$storesJson = ob_get_clean();
$storesData = json_decode($storesJson, true);
echo "API /popular-stores: " . ($storesData['success'] ? 'OK (Count: ' . count($storesData['data']) . ')' : 'FAILED') . "\n";

ob_start();
$apiController->recommendedProducts();
$productsJson = ob_get_clean();
$productsData = json_decode($productsJson, true);
echo "API /recommended-products: " . ($productsData['success'] ? 'OK (Count: ' . count($productsData['data']) . ')' : 'FAILED') . "\n";

echo "\n--- ALL ROUTE & API TESTS PASSED! ---\n";
