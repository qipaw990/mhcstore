<?php
/**
 * Utility to import any scraped GrabFood JSON file directly into cicalengkago_db
 */

$jsonFile = 'C:/Users/smkmu/Downloads/grabfood_nasi_goreng_berayan_urip___cicalengka_wetan.json';
if (!file_exists($jsonFile)) {
    die("File tidak ditemukan: {$jsonFile}\n");
}

$data = json_decode(file_get_contents($jsonFile), true);
if (!$data || empty($data['name'])) {
    die("JSON file invalid or missing store name.\n");
}

$pdo = new PDO('mysql:host=127.0.0.1;dbname=cicalengkago_db;charset=utf8mb4', 'root', '');

$storeName = trim($data['name']);
$address   = !empty($data['address']) ? trim($data['address']) : 'Cicalengka, Kab. Bandung';
$lat       = !empty($data['latitude']) ? (float)$data['latitude'] : -6.9840;
$lng       = !empty($data['longitude']) ? (float)$data['longitude'] : 107.8350;
$phone     = !empty($data['phone']) ? $data['phone'] : ('08' . rand(100000000, 999999999));
$catName   = !empty($data['category']) ? $data['category'] : 'Nasi Goreng & Olahan';
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
require_once APP_PATH . '/helpers/upload.php';

$rawLogo  = !empty($data['logo']) ? $data['logo'] : 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=500&q=80';
$rawCover = !empty($data['cover_photo']) ? $data['cover_photo'] : 'https://images.unsplash.com/photo-1512058564366-18510be2db19?w=800&q=80';

$logo  = download_and_save_image($rawLogo, 'stores');
$cover = download_and_save_image($rawCover, 'stores');

// 1. Vendor
$email = 'vendor_' . preg_replace('/[^a-z0-9]/', '', strtolower($storeName)) . '@cicalengkago.id';
$stmtV = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmtV->execute([$email]);
$vUser = $stmtV->fetch();

if ($vUser) {
    $vendorId = (int)$vUser['id'];
} else {
    $stmtInsV = $pdo->prepare("INSERT INTO users (role, name, email, phone, password, is_active) VALUES ('vendor', ?, ?, ?, ?, 1)");
    $stmtInsV->execute(["Mitra " . $storeName, $email, $phone, password_hash('vendor123', PASSWORD_BCRYPT)]);
    $vendorId = (int)$pdo->lastInsertId();
    $pdo->exec("INSERT INTO wallets (user_id, user_type, balance) VALUES ({$vendorId}, 'vendor', 0)");
}

// 2. Category
$stmtC = $pdo->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
$stmtC->execute([$catName]);
$cRow = $stmtC->fetch();
if ($cRow) {
    $catId = (int)$cRow['id'];
} else {
    $stmtInsC = $pdo->prepare("INSERT INTO categories (module_id, name, icon, status) VALUES (1, ?, 'bi-egg-fried', 1)");
    $stmtInsC->execute([$catName]);
    $catId = (int)$pdo->lastInsertId();
}

// 3. Store
$stmtS = $pdo->prepare("SELECT id FROM stores WHERE name = ? LIMIT 1");
$stmtS->execute([$storeName]);
$sRow = $stmtS->fetch();

if ($sRow) {
    $storeId = (int)$sRow['id'];
    $stmtUp = $pdo->prepare("UPDATE stores SET logo = ?, cover_photo = ?, address = ?, latitude = ?, longitude = ? WHERE id = ?");
    $stmtUp->execute([$logo, $cover, $address, $lat, $lng, $storeId]);
    echo "[UPDATED STORE] {$storeName} (ID: {$storeId})\n";
} else {
    $stmtInsS = $pdo->prepare("
        INSERT INTO stores (vendor_id, module_id, zone_id, name, phone, email, logo, cover_photo, address, latitude, longitude, minimum_order, delivery_time, delivery_fee, is_open, status, rating, reviews_count)
        VALUES (?, 1, 1, ?, ?, ?, ?, ?, ?, ?, ?, 0.00, '15-25 min', 5000.00, 1, 'approved', 4.8, 145)
    ");
    $stmtInsS->execute([$vendorId, $storeName, $phone, $email, $logo, $cover, $address, $lat, $lng]);
    $storeId = (int)$pdo->lastInsertId();
    echo "[NEW STORE IMPORTED] {$storeName} (ID: {$storeId})\n";

    for ($d = 0; $d <= 6; $d++) {
        $pdo->exec("INSERT INTO store_schedules (store_id, day_of_week, opening_time, closing_time) VALUES ({$storeId}, {$d}, '08:00:00', '22:00:00')");
    }
}

// 4. Products & Images
$products = $data['products'] ?? [];
$importedCount = 0;

foreach ($products as $p) {
    $pName  = trim($p['name'] ?? '');
    if (empty($pName)) continue;
    $pDesc     = trim($p['description'] ?? '');
    $pPrice    = (float)($p['price'] ?? 15000);
    $rawPImage = !empty($p['image']) ? $p['image'] : $rawLogo;
    $pImage    = download_and_save_image($rawPImage, 'products');
    $pRec      = !empty($p['is_recommended']) ? 1 : 0;

    $stmtP = $pdo->prepare("SELECT id FROM products WHERE store_id = ? AND name = ? LIMIT 1");
    $stmtP->execute([$storeId, $pName]);
    $pRow = $stmtP->fetch();

    if ($pRow) {
        $stmtUpP = $pdo->prepare("UPDATE products SET price = ?, description = ?, image = ? WHERE id = ?");
        $stmtUpP->execute([$pPrice, $pDesc, $pImage, $pRow['id']]);
        echo "   + Updated Product: {$pName} (Rp " . number_format($pPrice, 0, ',', '.') . ")\n";
    } else {
        $stmtInsP = $pdo->prepare("
            INSERT INTO products (store_id, module_id, category_id, name, description, image, price, discount, discount_type, unit, stock, is_veg, is_recommended, status, rating, reviews_count)
            VALUES (?, 1, ?, ?, ?, ?, ?, 0, 'percent', 'pcs', 100, 0, ?, 1, 5.00, 15)
        ");
        $stmtInsP->execute([$storeId, $catId, $pName, $pDesc, $pImage, $pPrice, $pRec]);
        echo "   + Inserted Product: {$pName} (Rp " . number_format($pPrice, 0, ',', '.') . ")\n";
    }
    $importedCount++;
}

// Update stores_count
$stmtCount = $pdo->query("SELECT COUNT(*) as cnt FROM stores WHERE module_id = 1");
$cnt = (int)($stmtCount->fetch()['cnt'] ?? 0);
$pdo->exec("UPDATE modules SET stores_count = {$cnt} WHERE id = 1");

echo "\nSUCCESS! Imported Store '{$storeName}' with {$importedCount} products into CicalengkaGO!\n";
