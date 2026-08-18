<?php
/**
 * Utility script to convert any remote/hotlinked image URLs in DB to local storage files
 */
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
require_once APP_PATH . '/autoload.php';
require_once APP_PATH . '/helpers/upload.php';

try {
    $pdo = \App\Core\Database::getPdo();
} catch (\Throwable $e) {
    try {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=cicalengkago_db;charset=utf8mb4', 'root', '');
    } catch (\Throwable $e2) {
        die("Database connection failed: " . $e2->getMessage());
    }
}

$isCli = (php_sapi_name() === 'cli');
$nl = $isCli ? "\n" : "<br>";

echo "=== FIXING DEPRECATED & REMOTE IMAGES IN DATABASE ===" . $nl;

// 1. Fix Store Logos & Covers
$stores = $pdo->query("SELECT id, name, logo, cover_photo FROM stores")->fetchAll(\PDO::FETCH_ASSOC);
$updatedStores = 0;

foreach ($stores as $s) {
    $sid = (int)$s['id'];
    $newLogo = $s['logo'];
    $newCover = $s['cover_photo'];
    $changed = false;

    if (!empty($s['logo']) && (str_starts_with($s['logo'], 'http://') || str_starts_with($s['logo'], 'https://'))) {
        $newLogo = download_and_save_image($s['logo'], 'stores');
        $changed = true;
    }
    if (!empty($s['cover_photo']) && (str_starts_with($s['cover_photo'], 'http://') || str_starts_with($s['cover_photo'], 'https://'))) {
        $newCover = download_and_save_image($s['cover_photo'], 'stores');
        $changed = true;
    }

    if ($changed) {
        $stmtUp = $pdo->prepare("UPDATE stores SET logo = ?, cover_photo = ? WHERE id = ?");
        $stmtUp->execute([$newLogo, $newCover, $sid]);
        $updatedStores++;
        echo "   [Store #{$sid}] Updated local images for {$s['name']}" . $nl;
    }
}

// 2. Fix Product Images
$products = $pdo->query("SELECT id, name, image FROM products")->fetchAll(\PDO::FETCH_ASSOC);
$updatedProducts = 0;

foreach ($products as $p) {
    $pid = (int)$p['id'];
    if (!empty($p['image']) && (str_starts_with($p['image'], 'http://') || str_starts_with($p['image'], 'https://'))) {
        $newImg = download_and_save_image($p['image'], 'products');
        $stmtUpP = $pdo->prepare("UPDATE products SET image = ? WHERE id = ?");
        $stmtUpP->execute([$newImg, $pid]);
        $updatedProducts++;
        echo "   [Product #{$pid}] Downloaded local image for {$p['name']}" . $nl;
    }
}

echo "✅ Finished! Updated {$updatedStores} stores and {$updatedProducts} products to local server storage." . $nl;
