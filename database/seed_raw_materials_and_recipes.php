<?php
/**
 * Seeder Bahan Baku & Resep Produk CicalengkaGO
 * 
 * Menghasilkan data seed:
 * 1. Master Bahan Baku (Raw Materials) dengan harga per satuan (ml, gr, pcs, butir, lembar, dll.)
 * 2. Racikan Resep Produk (Product Recipes)
 * 3. Otomatis menghitung Total HPP tiap produk
 * 4. Otomatis menentukan dan mengupdate Harga Jual berdasarkan margin keuntungan (%) atau nominal (Rp)
 * 
 * Jalankan: php database/seed_raw_materials_and_recipes.php
 */

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

require_once APP_PATH . '/config/constants.php';
require_once APP_PATH . '/autoload.php';

use App\Core\Database;
use App\Models\RawMaterial;

echo "=========================================================================\n";
echo " CicalengkaGO — SEEDER BAHAN BAKU & RESEP PRODUK DENGAN HITUNG HPP OTOMATIS\n";
echo "=========================================================================\n\n";

// Pastikan tabel siap
RawMaterial::ensureTablesExist();

// ── 1. DEFINISI MASTER BAHAN BAKU PER TOKO ──────────────────────────────────
$storeMaterials = [
    // Toko 1: Ayam Geprek Cicalengka Juara
    1 => [
        ['name' => 'Ayam Potong Segar (Fillet/Dada/Paha)', 'unit' => 'potong', 'price_per_unit' => 6000, 'stock_qty' => 150, 'description' => 'Ayam segar potong 8 dari pasar Cicalengka'],
        ['name' => 'Beras Pulen Cianjur', 'unit' => 'gr', 'price_per_unit' => 15, 'stock_qty' => 50000, 'description' => 'Beras super pulen (Rp 15.000 / kg)'],
        ['name' => 'Cabai Rawit Merah & Bawang', 'unit' => 'gr', 'price_per_unit' => 40, 'stock_qty' => 5000, 'description' => 'Cabai domba segar & bawang merah (Rp 40.000 / kg)'],
        ['name' => 'Minyak Goreng Sawit', 'unit' => 'ml', 'price_per_unit' => 18, 'stock_qty' => 20000, 'description' => 'Minyak goreng kemasan (Rp 18.000 / liter)'],
        ['name' => 'Tepung Bumbu Crispy Rahasia', 'unit' => 'gr', 'price_per_unit' => 20, 'stock_qty' => 10000, 'description' => 'Racikan tepung terigu + maizena + bumbu (Rp 20.000 / kg)'],
        ['name' => 'Keju Mozzarella Melt', 'unit' => 'gr', 'price_per_unit' => 120, 'stock_qty' => 3000, 'description' => 'Keju mozzarella parut (Rp 120.000 / kg)'],
        ['name' => 'Jeruk Peras Segar Asli', 'unit' => 'butir', 'price_per_unit' => 1200, 'stock_qty' => 200, 'description' => 'Jeruk peras manis segar'],
        ['name' => 'Gula Pasir Kristal', 'unit' => 'gr', 'price_per_unit' => 18, 'stock_qty' => 10000, 'description' => 'Gula pasir premium (Rp 18.000 / kg)'],
        ['name' => 'Biang Teh Melati / Celup', 'unit' => 'bungkus', 'price_per_unit' => 500, 'stock_qty' => 300, 'description' => 'Kantong teh seduh wangi melati'],
        ['name' => 'Es Batu Tube Kristal Higienis', 'unit' => 'gr', 'price_per_unit' => 3, 'stock_qty' => 50000, 'description' => 'Es batu tube kristal (Rp 3.000 / kg)'],
        ['name' => 'Packaging Paper Lunch Box & Kertas Nasi', 'unit' => 'pcs', 'price_per_unit' => 850, 'stock_qty' => 500, 'description' => 'Kotak kemasan ramah lingkungan + sendok plastik'],
        ['name' => 'Cup Plastik 16oz/22oz + Tutup & Sedotan', 'unit' => 'pcs', 'price_per_unit' => 550, 'stock_qty' => 500, 'description' => 'Cup minuman sablon + sedotan steril'],
    ],

    // Toko 2: Sate Maranggi Alun-Alun Cicalengka
    2 => [
        ['name' => 'Daging Sapi Has Pilihan', 'unit' => 'gr', 'price_per_unit' => 130, 'stock_qty' => 20000, 'description' => 'Daging sapi lokal segar (Rp 130.000 / kg)'],
        ['name' => 'Tusuk Sate Bambu Kuat', 'unit' => 'pcs', 'price_per_unit' => 50, 'stock_qty' => 5000, 'description' => 'Tusuk sate bambu halus'],
        ['name' => 'Bumbu Marinasi Maranggi (Kecap & Rempah)', 'unit' => 'ml', 'price_per_unit' => 35, 'stock_qty' => 5000, 'description' => 'Kecap manis + ketumbar + gula merah + rempah'],
        ['name' => 'Iga Sapi Potong Segar', 'unit' => 'gr', 'price_per_unit' => 110, 'stock_qty' => 15000, 'description' => 'Iga sapi sop segar (Rp 110.000 / kg)'],
        ['name' => 'Kuah Sop Rempah & Sayuran (Wortel/Kentang)', 'unit' => 'porsi', 'price_per_unit' => 4500, 'stock_qty' => 100, 'description' => 'Racikan kuah kaldu rempah + sayuran lengkap'],
        ['name' => 'Sambal Tomat Oncom Maranggi', 'unit' => 'porsi', 'price_per_unit' => 1500, 'stock_qty' => 200, 'description' => 'Sambal tomat cabai rawit khas Maranggi'],
        ['name' => 'Packaging Thinwall Box Sop / Sate', 'unit' => 'pcs', 'price_per_unit' => 1200, 'stock_qty' => 400, 'description' => 'Wadah plastik tahan panas anti tumpah'],
    ],

    // Toko 8: Warung Seblak 33 Cicalengka
    8 => [
        ['name' => 'Kerupuk Bawang / Aci Aneka Warna', 'unit' => 'gr', 'price_per_unit' => 22, 'stock_qty' => 10000, 'description' => 'Kerupuk mentah aneka bentuk (Rp 22.000 / kg)'],
        ['name' => 'Ceker Ayam Bersih & Empuk', 'unit' => 'pcs', 'price_per_unit' => 800, 'stock_qty' => 200, 'description' => 'Ceker ayam rebus empuk'],
        ['name' => 'Bakso Sapi Kecil & Sosis Sapi', 'unit' => 'pcs', 'price_per_unit' => 750, 'stock_qty' => 300, 'description' => 'Toping bakso & sosis iris'],
        ['name' => 'Bumbu Kuah Kencur & Cabai Seblak Pedas', 'unit' => 'porsi', 'price_per_unit' => 1800, 'stock_qty' => 200, 'description' => 'Kencur + bawang + cabai rawit + minyak rempah'],
        ['name' => 'Telur Ayam Segar', 'unit' => 'butir', 'price_per_unit' => 2000, 'stock_qty' => 150, 'description' => 'Telur ayam negeri (Rp 2.000 / butir)'],
        ['name' => 'Mangkok Kertas Paper Bowl 650ml', 'unit' => 'pcs', 'price_per_unit' => 900, 'stock_qty' => 500, 'description' => 'Paper bowl anti bocor'],
    ],

    // Toko 10: Martabak MR ZA Cicalengka
    10 => [
        ['name' => 'Tepung Terigu Segitiga Biru', 'unit' => 'gr', 'price_per_unit' => 14, 'stock_qty' => 25000, 'description' => 'Tepung terigu protein sedang (Rp 14.000 / kg)'],
        ['name' => 'Mentega Wisman / Blue Band', 'unit' => 'gr', 'price_per_unit' => 45, 'stock_qty' => 5000, 'description' => 'Margarin & butter wangi'],
        ['name' => 'Cokelat Meises Ceres Premium', 'unit' => 'gr', 'price_per_unit' => 60, 'stock_qty' => 4000, 'description' => 'Meises cokelat leleh'],
        ['name' => 'Keju Cheddar Kraft Parut', 'unit' => 'gr', 'price_per_unit' => 95, 'stock_qty' => 3000, 'description' => 'Keju cheddar gurih (Rp 95.000 / kg)'],
        ['name' => 'Susu Kental Manis Frisian Flag', 'unit' => 'ml', 'price_per_unit' => 30, 'stock_qty' => 6000, 'description' => 'SKM putih manis legit'],
        ['name' => 'Kulit Martabak Telur Spesial', 'unit' => 'lembar', 'price_per_unit' => 1500, 'stock_qty' => 200, 'description' => 'Kulit martabak adonan lentur'],
        ['name' => 'Daging Sapi Cincang Bumbu Kari', 'unit' => 'gr', 'price_per_unit' => 120, 'stock_qty' => 5000, 'description' => 'Isian daging sapi martabak asin'],
        ['name' => 'Daun Bawang & Bawang Bombay', 'unit' => 'gr', 'price_per_unit' => 25, 'stock_qty' => 4000, 'description' => 'Sayuran iris martabak telur'],
        ['name' => 'Dus Martabak Sablon Eksklusif', 'unit' => 'pcs', 'price_per_unit' => 1100, 'stock_qty' => 500, 'description' => 'Kotak dus martabak tebal'],
    ],
];

// ── 2. SEEDING MASTER BAHAN BAKU ────────────────────────────────────────────
$materialIdMap = []; // [store_id => [material_name => id]]

echo "[1/4] Menanam data master Bahan Baku...\n";
foreach ($storeMaterials as $storeId => $materials) {
    // Pastikan toko ada
    $store = Database::fetchOne("SELECT id, name FROM `stores` WHERE `id` = ?", [$storeId]);
    if (!$store) {
        echo "  [-] Toko ID #{$storeId} tidak ditemukan, lewati.\n";
        continue;
    }

    echo "  [+] Menanam bahan untuk Toko: {$store['name']} (ID: {$storeId})\n";
    $materialIdMap[$storeId] = [];

    foreach ($materials as $mat) {
        // Cek jika sudah ada
        $existing = Database::fetchOne(
            "SELECT id FROM `raw_materials` WHERE `store_id` = ? AND `name` = ?",
            [$storeId, $mat['name']]
        );

        if ($existing) {
            $matId = (int)$existing['id'];
            Database::query(
                "UPDATE `raw_materials` SET `unit` = ?, `price_per_unit` = ?, `stock_qty` = ?, `description` = ? WHERE `id` = ?",
                [$mat['unit'], $mat['price_per_unit'], $mat['stock_qty'], $mat['description'], $matId]
            );
        } else {
            Database::query(
                "INSERT INTO `raw_materials` (`store_id`, `name`, `unit`, `price_per_unit`, `stock_qty`, `description`) VALUES (?, ?, ?, ?, ?, ?)",
                [$storeId, $mat['name'], $mat['unit'], $mat['price_per_unit'], $mat['stock_qty'], $mat['description']]
            );
            $matId = (int)Database::getPdo()->lastInsertId();
        }

        $materialIdMap[$storeId][$mat['name']] = $matId;
    }
}
echo "  [✓] Selesai menanam master bahan baku.\n\n";

// ── 3. DEFINISI RACIKAN RESEP PRODUK (PRODUCT RECIPES) ──────────────────────
// Format: [productId => ['margin_pct' => 50, 'ingredients' => [['name' => ..., 'qty' => ...]]]]
$productRecipes = [
    // Toko 1 Products
    1 => [
        'name' => 'Paket Ayam Geprek Sambal Bawang + Nasi',
        'store_id' => 1,
        'target_margin_pct' => 50.0, // Untung 50% dari HPP
        'ingredients' => [
            ['name' => 'Ayam Potong Segar (Fillet/Dada/Paha)', 'qty' => 1.0],      // Rp 6.000
            ['name' => 'Beras Pulen Cianjur', 'qty' => 150.0],                     // 150g x Rp 15 = Rp 2.250
            ['name' => 'Cabai Rawit Merah & Bawang', 'qty' => 30.0],               // 30g x Rp 40 = Rp 1.200
            ['name' => 'Minyak Goreng Sawit', 'qty' => 50.0],                      // 50ml x Rp 18 = Rp 900
            ['name' => 'Tepung Bumbu Crispy Rahasia', 'qty' => 40.0],              // 40g x Rp 20 = Rp 800
            ['name' => 'Packaging Paper Lunch Box & Kertas Nasi', 'qty' => 1.0],   // 1pcs x Rp 850 = Rp 850
        ],
        // Total HPP = Rp 12.000 -> Harga Jual 50% margin = Rp 18.000
    ],
    2 => [
        'name' => 'Ayam Geprek Keju Mozzarella',
        'store_id' => 1,
        'target_margin_pct' => 60.0, // Untung 60%
        'ingredients' => [
            ['name' => 'Ayam Potong Segar (Fillet/Dada/Paha)', 'qty' => 1.0],      // Rp 6.000
            ['name' => 'Keju Mozzarella Melt', 'qty' => 40.0],                     // 40g x Rp 120 = Rp 4.800
            ['name' => 'Cabai Rawit Merah & Bawang', 'qty' => 30.0],               // Rp 1.200
            ['name' => 'Minyak Goreng Sawit', 'qty' => 50.0],                      // Rp 900
            ['name' => 'Tepung Bumbu Crispy Rahasia', 'qty' => 40.0],              // Rp 800
            ['name' => 'Packaging Paper Lunch Box & Kertas Nasi', 'qty' => 1.0],   // Rp 850
        ],
        // Total HPP = Rp 14.550 -> Harga Jual 65% margin ≈ Rp 24.000
    ],
    3 => [
        'name' => 'Es Jeruk Peras Segar Asli',
        'store_id' => 1,
        'target_margin_pct' => 53.8,
        'ingredients' => [
            ['name' => 'Jeruk Peras Segar Asli', 'qty' => 2.0],                    // 2 x Rp 1.200 = Rp 2.400
            ['name' => 'Gula Pasir Kristal', 'qty' => 30.0],                       // 30g x Rp 18 = Rp 540
            ['name' => 'Es Batu Tube Kristal Higienis', 'qty' => 150.0],           // 150g x Rp 3 = Rp 450
            ['name' => 'Cup Plastik 16oz/22oz + Tutup & Sedotan', 'qty' => 1.0],   // Rp 550
        ],
        // Total HPP = Rp 3.940 -> Harga Jual ≈ Rp 6.000
    ],
    4 => [
        'name' => 'Es Teh Manis Jumbo',
        'store_id' => 1,
        'target_margin_pct' => 100.0, // Untung 100%
        'ingredients' => [
            ['name' => 'Biang Teh Melati / Celup', 'qty' => 1.0],                  // Rp 500
            ['name' => 'Gula Pasir Kristal', 'qty' => 25.0],                       // 25g x Rp 18 = Rp 450
            ['name' => 'Es Batu Tube Kristal Higienis', 'qty' => 170.0],           // 170g x Rp 3 = Rp 510
            ['name' => 'Cup Plastik 16oz/22oz + Tutup & Sedotan', 'qty' => 1.0],   // Rp 550
        ],
        // Total HPP = Rp 2.010 -> Harga Jual ≈ Rp 4.000
    ],

    // Toko 2 Products
    5 => [
        'name' => 'Sate Maranggi Sapi Porsi 10 Tusuk',
        'store_id' => 2,
        'target_margin_pct' => 55.0,
        'ingredients' => [
            ['name' => 'Daging Sapi Has Pilihan', 'qty' => 120.0],                 // 120g x Rp 130 = Rp 15.600
            ['name' => 'Tusuk Sate Bambu Kuat', 'qty' => 10.0],                    // 10 x Rp 50 = Rp 500
            ['name' => 'Bumbu Marinasi Maranggi (Kecap & Rempah)', 'qty' => 40.0], // 40ml x Rp 35 = Rp 1.400
            ['name' => 'Sambal Tomat Oncom Maranggi', 'qty' => 1.0],               // Rp 1.500
            ['name' => 'Packaging Thinwall Box Sop / Sate', 'qty' => 1.0],         // Rp 1.200
        ],
        // Total HPP = Rp 20.200 -> Harga Jual ≈ Rp 35.000 (Untung Rp 14.800 / 73%)
    ],
    6 => [
        'name' => 'Sop Iga Sapi Kuah Rempah Cicalengka',
        'store_id' => 2,
        'target_margin_pct' => 50.0,
        'ingredients' => [
            ['name' => 'Iga Sapi Potong Segar', 'qty' => 150.0],                   // 150g x Rp 110 = Rp 16.500
            ['name' => 'Kuah Sop Rempah & Sayuran (Wortel/Kentang)', 'qty' => 1.0],// Rp 4.500
            ['name' => 'Sambal Tomat Oncom Maranggi', 'qty' => 1.0],               // Rp 1.500
            ['name' => 'Packaging Thinwall Box Sop / Sate', 'qty' => 1.0],         // Rp 1.200
        ],
        // Total HPP = Rp 23.700 -> Harga Jual ≈ Rp 38.000 (Untung Rp 14.300 / 60%)
    ],
];

// ── 4. MENANAM RESEP & MENGHITUNG HPP SERTA HARGA JUAL ──────────────────────
echo "[2/4] Menghubungkan Resep Bahan Baku ke Produk...\n";

$summaryTable = [];

foreach ($productRecipes as $productId => $recipeConfig) {
    $storeId = $recipeConfig['store_id'];
    $product = Database::fetchOne("SELECT id, name, price FROM `products` WHERE `id` = ?", [$productId]);

    if (!$product) {
        echo "  [-] Produk ID #{$productId} tidak ditemukan, lewati.\n";
        continue;
    }

    // Bersihkan resep lama produk
    Database::query("DELETE FROM `product_raw_materials` WHERE `product_id` = ?", [$productId]);

    $calculatedHpp = 0.0;

    foreach ($recipeConfig['ingredients'] as $ing) {
        $matName = $ing['name'];
        $qty     = (float)$ing['qty'];

        $matId = $materialIdMap[$storeId][$matName] ?? null;
        if (!$matId) {
            $found = Database::fetchOne(
                "SELECT id, price_per_unit FROM `raw_materials` WHERE `store_id` = ? AND `name` = ?",
                [$storeId, $matName]
            );
            if ($found) {
                $matId = (int)$found['id'];
                $pricePerUnit = (float)$found['price_per_unit'];
            } else {
                continue;
            }
        } else {
            $found = Database::fetchOne("SELECT price_per_unit FROM `raw_materials` WHERE `id` = ?", [$matId]);
            $pricePerUnit = (float)($found['price_per_unit'] ?? 0);
        }

        // Insert ke product_raw_materials
        Database::query(
            "INSERT INTO `product_raw_materials` (`product_id`, `raw_material_id`, `qty_used`) VALUES (?, ?, ?)",
            [$productId, $matId, $qty]
        );

        $calculatedHpp += $qty * $pricePerUnit;
    }

    // Hitung Harga Jual Baru dari Target Margin Keuntungan
    $targetMargin = (float)$recipeConfig['target_margin_pct'];
    
    // Pembulatan ke kelipatan Rp 500 terdekat agar harga jual rapi
    $rawCalculatedPrice = $calculatedHpp * (1 + ($targetMargin / 100));
    $suggestedPrice = ceil($rawCalculatedPrice / 500) * 500;
    
    // Pertahankan harga existing jika sudah mendekati atau gunakan suggestedPrice
    $currentPrice = (float)$product['price'];
    $finalPrice = ($currentPrice > $calculatedHpp) ? $currentPrice : $suggestedPrice;
    
    $profitNominal = $finalPrice - $calculatedHpp;
    $actualMarginPct = $calculatedHpp > 0 ? ($profitNominal / $calculatedHpp * 100) : 0;

    // Update HPP dan Harga Jual pada tabel products
    Database::query(
        "UPDATE `products` SET `hpp` = ?, `price` = ? WHERE `id` = ?",
        [$calculatedHpp, $finalPrice, $productId]
    );

    $summaryTable[] = [
        'id'             => $productId,
        'name'           => $product['name'],
        'hpp'            => $calculatedHpp,
        'price'          => $finalPrice,
        'profit_nominal' => $profitNominal,
        'margin_pct'     => $actualMarginPct,
    ];

    echo "  [✓] Produk #{$productId} '{$product['name']}':\n";
    echo "      -> HPP Terhitung : Rp " . number_format($calculatedHpp, 0, ',', '.') . "\n";
    echo "      -> Harga Jual    : Rp " . number_format($finalPrice, 0, ',', '.') . "\n";
    echo "      -> Untung Bersih : Rp " . number_format($profitNominal, 0, ',', '.') . " (+". number_format($actualMarginPct, 1) . "%)\n\n";
}

echo "=========================================================================\n";
echo " TABEL REKAPITULASI RESEP HPP & MARGIN KEUNTUNGAN\n";
echo "=========================================================================\n";
printf("%-4s | %-32s | %-12s | %-12s | %-12s | %-8s\n", "ID", "Nama Produk", "HPP (Modal)", "Harga Jual", "Untung (Rp)", "Margin %");
echo "-------------------------------------------------------------------------\n";
foreach ($summaryTable as $row) {
    printf(
        "%-4d | %-32s | Rp %-9s | Rp %-9s | Rp %-9s | +%s%%\n",
        $row['id'],
        mb_strimwidth($row['name'], 0, 32, '...'),
        number_format($row['hpp'], 0, ',', '.'),
        number_format($row['price'], 0, ',', '.'),
        number_format($row['profit_nominal'], 0, ',', '.'),
        number_format($row['margin_pct'], 1)
    );
}
echo "=========================================================================\n";
echo " SEEDING & PERHITUNGAN HPP SELESAI DENGAN SUKSES!\n";
echo "=========================================================================\n";
