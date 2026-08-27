<?php
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');

require_once APP_PATH . '/config/constants.php';
require_once APP_PATH . '/helpers/format.php';
require_once APP_PATH . '/autoload.php';

use App\Core\Database;

$order = Database::fetchOne("SELECT * FROM `orders` WHERE id = 2");
echo "=== ORDER ID 2 DETAILS ===\n";
print_r($order);
