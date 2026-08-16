<?php
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
require_once APP_PATH . '/config/constants.php';
require_once APP_PATH . '/autoload.php';

use App\Core\Database;
use App\Services\AuthService;

// Update driver phone to 081234567891
Database::execute("UPDATE users SET phone = '081234567891' WHERE id = 4");

$auth = new AuthService();

echo "--- Test 1: Driver Login with Email (driver@cicalengkago.id) ---\n";
try {
    $u1 = $auth->login('driver@cicalengkago.id', 'password');
    echo "SUCCESS: " . $u1['name'] . " (" . $u1['role'] . ")\n";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}

echo "\n--- Test 2: Driver Login with Phone (081234567891) ---\n";
try {
    $u2 = $auth->login('081234567891', 'password');
    echo "SUCCESS: " . $u2['name'] . " (" . $u2['role'] . ")\n";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}

echo "\n--- Test 3: Resto Login with Email (vendor@cicalengkago.id) ---\n";
try {
    $u3 = $auth->login('vendor@cicalengkago.id', 'password');
    echo "SUCCESS: " . $u3['name'] . " (" . $u3['role'] . ")\n";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}

echo "\n--- Test 4: Customer Login with Email (customer@cicalengkago.id) ---\n";
try {
    $u4 = $auth->login('customer@cicalengkago.id', 'password');
    echo "SUCCESS: " . $u4['name'] . " (" . $u4['role'] . ")\n";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}

echo "\n--- Test 5: Admin Login with Email (admin@cicalengkago.id) ---\n";
try {
    $u5 = $auth->login('admin@cicalengkago.id', 'password');
    echo "SUCCESS: " . $u5['name'] . " (" . $u5['role'] . ")\n";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
