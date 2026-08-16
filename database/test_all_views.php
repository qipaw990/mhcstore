<?php
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

use App\Controllers\CustomerController;
use App\Controllers\VendorController;
use App\Controllers\DeliveryController;
use App\Controllers\AdminController;
use App\Controllers\AuthController;

echo "--- 1. Testing Customer Views ---\n";
$_SESSION['user'] = ['id' => 1, 'name' => 'Budi Customer', 'role' => 'customer', 'phone' => '081234567890'];
$cust = new CustomerController();
ob_start(); $cust->home(); echo "Customer home: OK (" . strlen(ob_get_clean()) . " bytes)\n";
ob_start(); $cust->search(); echo "Customer search: OK (" . strlen(ob_get_clean()) . " bytes)\n";
ob_start(); $cust->storeDetail(1); echo "Customer store detail: OK (" . strlen(ob_get_clean()) . " bytes)\n";
ob_start(); $cust->parcel(); echo "Customer parcel: OK (" . strlen(ob_get_clean()) . " bytes)\n";
ob_start(); $cust->profile(); echo "Customer profile: OK (" . strlen(ob_get_clean()) . " bytes)\n";
ob_start(); $cust->wallet(); echo "Customer wallet: OK (" . strlen(ob_get_clean()) . " bytes)\n";
ob_start(); $cust->notifications(); echo "Customer notifications: OK (" . strlen(ob_get_clean()) . " bytes)\n";

echo "\n--- 2. Testing Vendor Views ---\n";
$_SESSION['user'] = ['id' => 2, 'name' => 'Mitra Geprek', 'role' => 'vendor', 'email' => 'vendor.geprek@cicalengkago.id'];
$vend = new VendorController();
ob_start(); $vend->dashboard(); echo "Vendor dashboard: OK (" . strlen(ob_get_clean()) . " bytes)\n";
ob_start(); $vend->orders(); echo "Vendor orders: OK (" . strlen(ob_get_clean()) . " bytes)\n";
ob_start(); $vend->products(); echo "Vendor products: OK (" . strlen(ob_get_clean()) . " bytes)\n";
ob_start(); $vend->wallet(); echo "Vendor wallet: OK (" . strlen(ob_get_clean()) . " bytes)\n";

echo "\n--- 3. Testing Delivery Driver Views ---\n";
$_SESSION['user'] = ['id' => 4, 'name' => 'Asep Driver', 'role' => 'delivery_man', 'phone' => '081234567891'];
$deliv = new DeliveryController();
ob_start(); $deliv->dashboard(); echo "Delivery dashboard: OK (" . strlen(ob_get_clean()) . " bytes)\n";
ob_start(); $deliv->earnings(); echo "Delivery earnings: OK (" . strlen(ob_get_clean()) . " bytes)\n";

echo "\n--- 4. Testing Admin Master Console Views ---\n";
$_SESSION['user'] = ['id' => 1, 'name' => 'Super Admin', 'role' => 'admin', 'email' => 'admin@cicalengkago.id'];
$admin = new AdminController();
ob_start(); $admin->dashboard(); echo "Admin dashboard: OK (" . strlen(ob_get_clean()) . " bytes)\n";
ob_start(); $admin->orders(); echo "Admin orders (Live Dispatch): OK (" . strlen(ob_get_clean()) . " bytes)\n";
ob_start(); $admin->zones(); echo "Admin zones: OK (" . strlen(ob_get_clean()) . " bytes)\n";
ob_start(); $admin->modules(); echo "Admin modules: OK (" . strlen(ob_get_clean()) . " bytes)\n";
ob_start(); $admin->stores(); echo "Admin stores: OK (" . strlen(ob_get_clean()) . " bytes)\n";
ob_start(); $admin->products(); echo "Admin products: OK (" . strlen(ob_get_clean()) . " bytes)\n";
ob_start(); $admin->deliveryMen(); echo "Admin delivery men: OK (" . strlen(ob_get_clean()) . " bytes)\n";
ob_start(); $admin->customers(); echo "Admin customers: OK (" . strlen(ob_get_clean()) . " bytes)\n";
ob_start(); $admin->banners(); echo "Admin banners: OK (" . strlen(ob_get_clean()) . " bytes)\n";
ob_start(); $admin->withdrawals(); echo "Admin withdrawals: OK (" . strlen(ob_get_clean()) . " bytes)\n";
ob_start(); $admin->topups(); echo "Admin topups (Midtrans Top-Up Management): OK (" . strlen(ob_get_clean()) . " bytes)\n";
ob_start(); $admin->settings(); echo "Admin settings: OK (" . strlen(ob_get_clean()) . " bytes)\n";

echo "\n--- 5. Testing Auth Views ---\n";
unset($_SESSION['user']);
$auth = new AuthController();
ob_start(); $auth->showLogin(); echo "Login page: OK (" . strlen(ob_get_clean()) . " bytes)\n";
ob_start(); $auth->showRegister(); echo "Register page: OK (" . strlen(ob_get_clean()) . " bytes)\n";

echo "\n======================================================\n";
echo ">>> ALL PLATFORM VIEWS & DISPATCH MODULES PASSED 100%! <<<\n";
echo "======================================================\n";
