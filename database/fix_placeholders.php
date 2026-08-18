<?php
/**
 * Script to fix placeholder images in products by matching product names to specific realistic food images,
 * downloading them locally, and cleaning up old dummy products.
 */
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
require_once APP_PATH . '/autoload.php';
require_once APP_PATH . '/helpers/upload.php';

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=cicalengkago_db;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\Throwable $e) {
    die("Database connection failed: " . $e->getMessage());
}

$isCli = (php_sapi_name() === 'cli');
$nl = $isCli ? "\n" : "<br>";

echo "=== FIXING PLACEHOLDER PRODUCT IMAGES ===" . $nl;

// Food image mapping based on keywords
function get_food_image_by_name(string $name): string {
    $nameLower = strtolower($name);

    if (str_contains($nameLower, 'nasi goreng') || str_contains($nameLower, 'nasgor')) {
        return 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=500&q=80';
    }
    if (str_contains($nameLower, 'kwetiau') || str_contains($nameLower, 'kwetiew')) {
        return 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=500&q=80';
    }
    if (str_contains($nameLower, 'mie') || str_contains($nameLower, 'ramen') || str_contains($nameLower, 'noodle')) {
        return 'https://images.unsplash.com/photo-1612927601601-6638404737ce?w=500&q=80';
    }
    if (str_contains($nameLower, 'ayam') || str_contains($nameLower, 'geprek') || str_contains($nameLower, 'chicken')) {
        return 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?w=500&q=80';
    }
    if (str_contains($nameLower, 'seblak')) {
        return 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=500&q=80';
    }
    if (str_contains($nameLower, 'bakso') || str_contains($nameLower, 'baso')) {
        return 'https://images.unsplash.com/photo-1541696432-82c6da8ce7bf?w=500&q=80';
    }
    if (str_contains($nameLower, 'sate') || str_contains($nameLower, 'satay')) {
        return 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=500&q=80';
    }
    if (str_contains($nameLower, 'martabak') || str_contains($nameLower, 'cake') || str_contains($nameLower, 'cheese')) {
        return 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=500&q=80';
    }
    if (str_contains($nameLower, 'es') || str_contains($nameLower, 'kopi') || str_contains($nameLower, 'tea') || str_contains($nameLower, 'drink') || str_contains($nameLower, 'latte')) {
        return 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=500&q=80';
    }
    if (str_contains($nameLower, 'wonton') || str_contains($nameLower, 'dimsum')) {
        return 'https://images.unsplash.com/photo-1496116218417-1a781b1c416c?w=500&q=80';
    }
    if (str_contains($nameLower, 'burger')) {
        return 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=500&q=80';
    }
    if (str_contains($nameLower, 'fries') || str_contains($nameLower, 'kentang')) {
        return 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?w=500&q=80';
    }
    if (str_contains($nameLower, 'rendang') || str_contains($nameLower, 'padang') || str_contains($nameLower, 'nasi timbel') || str_contains($nameLower, 'liwet')) {
        return 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&q=80';
    }

    return 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=500&q=80';
}

// 1. Remove duplicate/old dummy products if a store has scraped products
// e.g. For store 24, delete old dummy products like 'Nasi Goreng Pete' if real scraped 'Nasi Goreng Berayan Spesial Komplit' exists
$scrapedStores = $pdo->query("SELECT DISTINCT store_id FROM products WHERE image LIKE 'uploads/products/grab_%'")->fetchAll(PDO::FETCH_COLUMN);

foreach ($scrapedStores as $sid) {
    // Delete non-grab products for this store
    $stmtDel = $pdo->prepare("DELETE FROM products WHERE store_id = ? AND image NOT LIKE 'uploads/products/grab_%'");
    $stmtDel->execute([$sid]);
    $deletedCount = $stmtDel->rowCount();
    if ($deletedCount > 0) {
        echo "   [Store #{$sid}] Cleaned up {$deletedCount} old dummy products." . $nl;
    }
}

// 2. For remaining products, check if image is unsplash placeholder or generic salad bowl (photo-1546069901-ba9599a7e63c)
$products = $pdo->query("SELECT id, name, image FROM products")->fetchAll(PDO::FETCH_ASSOC);
$updatedCount = 0;

foreach ($products as $p) {
    $pid = (int)$p['id'];
    $img = $p['image'];

    // Needs fix if empty, unsplash, default, or photo-1546069901-ba9599a7e63c
    $needsFix = empty($img) 
        || str_contains($img, 'default') 
        || str_contains($img, 'unsplash') 
        || str_contains($img, 'photo-1546069901-ba9599a7e63c');

    if ($needsFix) {
        $targetRemoteUrl = get_food_image_by_name($p['name']);
        $localPath = download_and_save_image($targetRemoteUrl, 'products');

        if (!empty($localPath)) {
            $stmtUp = $pdo->prepare("UPDATE products SET image = ? WHERE id = ?");
            $stmtUp->execute([$localPath, $pid]);
            $updatedCount++;
            echo "   [Product #{$pid}] Replaced placeholder image for '{$p['name']}' -> {$localPath}" . $nl;
        }
    }
}

echo "✅ Finished! Updated {$updatedCount} product images to high quality local food photos." . $nl;
