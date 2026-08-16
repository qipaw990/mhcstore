<?php
/**
 * System Constants
 */

if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__, 2));
if (!defined('BASE_PATH')) define('BASE_PATH', ROOT_PATH);
if (!defined('APP_PATH')) define('APP_PATH', ROOT_PATH . '/app');
if (!defined('PUBLIC_PATH')) define('PUBLIC_PATH', ROOT_PATH . '/public');
if (!defined('VIEWS_PATH')) define('VIEWS_PATH', ROOT_PATH . '/views');
if (!defined('STORAGE_PATH')) define('STORAGE_PATH', ROOT_PATH . '/storage');
if (!defined('UPLOADS_PATH')) define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');

// User Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_VENDOR', 'vendor');
define('ROLE_CUSTOMER', 'customer');
define('ROLE_DELIVERY', 'delivery_man');

// Order Statuses
define('ORDER_PENDING', 'pending');
define('ORDER_CONFIRMED', 'confirmed');
define('ORDER_PROCESSING', 'processing');
define('ORDER_HANDOVER', 'handover');
define('ORDER_PICKED_UP', 'picked_up');
define('ORDER_ON_THE_WAY', 'on_the_way');
define('ORDER_DELIVERED', 'delivered');
define('ORDER_CANCELED', 'canceled');
define('ORDER_REFUNDED', 'refunded');

// Payment Statuses
define('PAYMENT_UNPAID', 'unpaid');
define('PAYMENT_PAID', 'paid');
define('PAYMENT_FAILED', 'failed');
define('PAYMENT_REFUNDED', 'refunded');

// Payment Methods
define('PAY_COD', 'cod');
define('PAY_WALLET', 'wallet');
define('PAY_QRIS', 'qris');
define('PAY_TRANSFER', 'bank_transfer');

// Module Types
define('MODULE_FOOD', 'food');
define('MODULE_GROCERY', 'grocery');
define('MODULE_PHARMACY', 'pharmacy');
define('MODULE_ECOMMERCE', 'ecommerce');
define('MODULE_PARCEL', 'parcel');
