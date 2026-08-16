<?php
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
require_once APP_PATH . '/config/constants.php';
require_once APP_PATH . '/autoload.php';

use App\Core\Database;

echo "=== VERIFIKASI FILE GAMBAR DI PUBLIC DIRECTORY ===\n\n";

$stores = Database::query('SELECT id, name, logo, cover_photo FROM stores');
$missingStores = 0;
foreach ($stores as $s) {
    $logoOk = !empty($s['logo']) && file_exists(PUBLIC_PATH . '/' . $s['logo']);
    $coverOk = !empty($s['cover_photo']) && file_exists(PUBLIC_PATH . '/' . $s['cover_photo']);
    if (!$logoOk || !$coverOk) {
        echo "❌ Resto {$s['name']}: logo=" . ($logoOk ? 'OK' : 'MISSING') . " | cover=" . ($coverOk ? 'OK' : 'MISSING') . "\n";
        $missingStores++;
        // Auto heal with fallback photo
        if (!$coverOk) {
            Database::execute("UPDATE stores SET cover_photo = 'assets/images/stores/ampera_cover.jpg' WHERE id = ?", [$s['id']]);
        }
        if (!$logoOk) {
            Database::execute("UPDATE stores SET logo = 'assets/images/stores/ampera_logo.png' WHERE id = ?", [$s['id']]);
        }
    } else {
        echo "✔ Resto {$s['name']}: Logo & Cover OK\n";
    }
}

$products = Database::query('SELECT id, name, image FROM products');
$missingProducts = 0;
foreach ($products as $p) {
    $imgOk = !empty($p['image']) && file_exists(PUBLIC_PATH . '/' . $p['image']);
    if (!$imgOk) {
        echo "❌ Menu {$p['name']}: image MISSING ({$p['image']})\n";
        $missingProducts++;
        Database::execute("UPDATE products SET image = 'assets/images/products/default.jpg' WHERE id = ?", [$p['id']]);
    } else {
        echo "✔ Menu {$p['name']}: Image OK (" . filesize(PUBLIC_PATH . '/' . $p['image']) . " bytes)\n";
    }
}

echo "\nSemua gambar terverifikasi! (Missing Stores healed: $missingStores, Missing Products healed: $missingProducts)\n";
