<?php
/**
 * CicalengkaGO - Product Variations & Addons Seeder
 * Populates realistic size variations and toppings (Popping Boba, Keju Mozarella, Extra Sambal, etc.) for all products & stores.
 * 
 * Usage:
 *   php database/seed_product_variations_addons.php
 *   php database/seed_product_variations_addons.php [HOST] [USER] [PASS] [DBNAME] [PORT]
 */

$host   = $argv[1] ?? getenv('DB_HOST') ?: '127.0.0.1';
$user   = $argv[2] ?? getenv('DB_USERNAME') ?: getenv('DB_USER') ?: 'root';
$pass   = $argv[3] ?? getenv('DB_PASSWORD') ?: getenv('DB_PASS') ?: '';
$dbname = $argv[4] ?? getenv('DB_DATABASE') ?: getenv('DB_NAME') ?: 'cicalengkago_db';
$port   = $argv[5] ?? getenv('DB_PORT') ?: '3306';

echo "=========================================================\n";
echo " 🍧 CicalengkaGO — Seeding Variasi & Topping Addons Produk\n";
echo "=========================================================\n";
echo " Host     : {$host}:{$port}\n";
echo " Database : {$dbname}\n";
echo "=========================================================\n\n";

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "[✓] Koneksi ke MySQL berhasil!\n\n";

    // 1. Ambil semua Toko
    $stores = $pdo->query("SELECT id, name, module_id FROM `stores`")->fetchAll();
    echo "🏬 Ditemukan " . count($stores) . " toko mitra.\n";

    // Daftar template addons per kategori / module
    $drinkAddons = [
        ['name' => 'Popping Boba', 'price' => 3000.00],
        ['name' => 'Brown Sugar Pearl (Boba Kenyal)', 'price' => 3000.00],
        ['name' => 'Grass Jelly (Cincau Hitam)', 'price' => 2500.00],
        ['name' => 'Cream Cheese Foam', 'price' => 4000.00],
        ['name' => 'Pudding Coklat Lembut', 'price' => 3000.00],
        ['name' => 'Ekstra Susu Kental Manis', 'price' => 2000.00],
    ];

    $foodAddons = [
        ['name' => 'Ekstra Sambal Bawang / Matah', 'price' => 2000.00],
        ['name' => 'Keju Mozarella Melt', 'price' => 5000.00],
        ['name' => 'Telur Ceplok / Dadar', 'price' => 4000.00],
        ['name' => 'Tahu & Tempe Goreng', 'price' => 3000.00],
        ['name' => 'Ekstra Nasi Putih Pulen', 'price' => 4000.00],
        ['name' => 'Kol Goreng Gurih', 'price' => 3000.00],
    ];

    $snackSeblakAddons = [
        ['name' => 'Ekstra Dumpling Keju', 'price' => 3500.00],
        ['name' => 'Ekstra Cikuwa Gurih', 'price' => 3000.00],
        ['name' => 'Ekstra Cuanki Lidah', 'price' => 2500.00],
        ['name' => 'Ekstra Telur Orak-Arik', 'price' => 4000.00],
        ['name' => 'Ekstra Ceker Empuk', 'price' => 4000.00],
        ['name' => 'Ekstra Sosis Sapi', 'price' => 3000.00],
    ];

    $sweetSnackAddons = [
        ['name' => 'Ekstra Keju Parut Melimpah', 'price' => 4000.00],
        ['name' => 'Ekstra Coklat Meses Premium', 'price' => 3000.00],
        ['name' => 'Saus Keju Lumer', 'price' => 3500.00],
        ['name' => 'Mayonnaise & Saus Sambal Ekstra', 'price' => 2000.00],
    ];

    // Seed Addons untuk setiap Store
    $addonStmt = $pdo->prepare("
        INSERT INTO `product_addons` (`store_id`, `name`, `price`, `status`)
        VALUES (?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE `price` = VALUES(`price`), `status` = 1
    ");

    $countAddonsAdded = 0;
    foreach ($stores as $s) {
        $stId = (int)$s['id'];
        $stName = strtolower($s['name']);

        // Pilih addon yang cocok dengan tipe toko
        $selectedAddons = [];
        if (preg_match('/(boba|teh|kopi|coffee|tea|jus|juice|drink|milk|ice|es|cafe)/', $stName)) {
            $selectedAddons = array_merge($selectedAddons, $drinkAddons);
        }
        if (preg_match('/(ayam|geprek|bebek|nasi|warung|rm|resto|kitchen|sate|padang|sunda|pecel)/', $stName)) {
            $selectedAddons = array_merge($selectedAddons, $foodAddons);
        }
        if (preg_match('/(seblak|bakso|mie|baso|cuanki|cilok|cilor|cireng)/', $stName)) {
            $selectedAddons = array_merge($selectedAddons, $snackSeblakAddons);
        }
        if (preg_match('/(martabak|roti|pisang|donut|kue|pastry|snack|jajan)/', $stName)) {
            $selectedAddons = array_merge($selectedAddons, $sweetSnackAddons);
        }

        // Default: berikan kombinasi minuman + makanan agar selalu ada pilihan
        if (empty($selectedAddons)) {
            $selectedAddons = array_merge(array_slice($foodAddons, 0, 4), array_slice($drinkAddons, 0, 3));
        }

        foreach ($selectedAddons as $ad) {
            // Cek apakah addon dengan nama persis sudah ada di toko ini
            $exist = $pdo->prepare("SELECT id FROM `product_addons` WHERE `store_id` = ? AND `name` = ? LIMIT 1");
            $exist->execute([$stId, $ad['name']]);
            if (!$exist->fetch()) {
                $addonStmt->execute([$stId, $ad['name'], $ad['price']]);
                $countAddonsAdded++;
            }
        }
    }
    echo "✨ Berhasil menambahkan {$countAddonsAdded} opsi topping & addon pada toko.\n\n";

    // 2. Ambil semua Produk
    $products = $pdo->query("SELECT id, store_id, name, price FROM `products`")->fetchAll();
    echo "🍽️ Ditemukan " . count($products) . " produk kuliner.\n";

    $varInsert = $pdo->prepare("
        INSERT INTO `product_variations` (`product_id`, `name`, `price`, `stock`)
        VALUES (?, ?, ?, 100)
    ");

    $countVarsAdded = 0;
    foreach ($products as $p) {
        $pId = (int)$p['id'];
        $pName = strtolower($p['name']);
        $pPrice = (float)$p['price'];

        // Cek apakah produk ini sudah punya variasi
        $chk = $pdo->prepare("SELECT id FROM `product_variations` WHERE `product_id` = ? LIMIT 1");
        $chk->execute([$pId]);
        if ($chk->fetch()) {
            continue; // Sudah ada variasi
        }

        $variations = [];

        if (preg_match('/(teh|kopi|coffee|tea|boba|jus|juice|drink|milk|ice|es|coklat|taro|matcha|yakult|alpukat|jeruk|lemon)/', $pName)) {
            // Variasi Ukuran Minuman
            $variations = [
                ['name' => 'Ukuran Regular (Medium)', 'price' => $pPrice],
                ['name' => 'Ukuran Large (+Es Segar)', 'price' => $pPrice + 4000.00],
                ['name' => 'Ukuran Jumbo / Big Cup', 'price' => $pPrice + 7000.00],
            ];
        } elseif (preg_match('/(seblak|mie|bakso|baso|cuanki|cireng|cilok|makaroni|level)/', $pName)) {
            // Variasi Tingkat Kepedasan Seblak/Mie
            $variations = [
                ['name' => 'Level 1 - Sedang (Gurih)', 'price' => $pPrice],
                ['name' => 'Level 2 - Pedas Nikmat', 'price' => $pPrice + 1000.00],
                ['name' => 'Level 3 - Ekstra Pedas Nampol', 'price' => $pPrice + 2000.00],
                ['name' => 'Level 5 - Pedas Mampus (Sultan)', 'price' => $pPrice + 4000.00],
            ];
        } elseif (preg_match('/(ayam|bebek|nasi|geprek|bakar|goreng|sate|steak|katsu|ikan|lele|penyet)/', $pName)) {
            // Variasi Paket Porsi Makanan
            $variations = [
                ['name' => 'Ala Carte (Hanya Menu Utama)', 'price' => $pPrice],
                ['name' => 'Paket Puas (Dada / Paha Atas)', 'price' => $pPrice + 3000.00],
                ['name' => 'Paket Komplit (+ Nasi & Es Teh Manis)', 'price' => $pPrice + 7000.00],
            ];
        } elseif (preg_match('/(martabak|roti|pizza|burger|snack|kentang|dimsum|pisang|kue)/', $pName)) {
            // Variasi Porsi Cemilan
            $variations = [
                ['name' => 'Porsi Sedang (Regular)', 'price' => $pPrice],
                ['name' => 'Porsi Jumbo / Double Size', 'price' => $pPrice + 6000.00],
            ];
        } else {
            // Variasi Umum (Porsi Standar / Jumbo)
            $variations = [
                ['name' => 'Porsi Standar', 'price' => $pPrice],
                ['name' => 'Porsi Jumbo (+ Ekstra)', 'price' => $pPrice + 5000.00],
            ];
        }

        foreach ($variations as $v) {
            $varInsert->execute([$pId, $v['name'], $v['price']]);
            $countVarsAdded++;
        }
    }

    echo "✨ Berhasil menambahkan {$countVarsAdded} variasi produk (Ukuran, Level Pedas, Paket Komplit).\n\n";

    echo "=========================================================\n";
    echo " ✅ SEEDING VARIASI & TOPPING SELESAI!\n";
    echo "=========================================================\n";

} catch (\PDOException $e) {
    echo "❌ Error Database: " . $e->getMessage() . "\n";
    exit(1);
}
