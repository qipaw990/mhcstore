<?php
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
require_once APP_PATH . '/config/constants.php';
require_once APP_PATH . '/helpers/auth.php';
require_once APP_PATH . '/helpers/format.php';
require_once APP_PATH . '/autoload.php';

use App\Core\Database;

echo "=== MEMULAI SINKRONISASI DATA GOFOOD CICALENGKA ===\n\n";

// 1. Ensure zone 1 (Kecamatan Cicalengka) exists
$zone = Database::fetchOne("SELECT * FROM `zones` WHERE `id` = 1");
if (!$zone) {
    Database::execute("INSERT INTO `zones` (`id`, `name`, `coordinates`, `status`, `created_at`) VALUES (1, 'Kecamatan Cicalengka', '{\"type\":\"Polygon\",\"coordinates\":[[[-6.9700,107.8150],[-6.9700,107.8550],[-6.9950,107.8650],[-7.0050,107.8350],[-6.9950,107.8100]]]}', 1, NOW())");
    echo "Zone 1 Kecamatan Cicalengka verified.\n";
}

// 2. Ensure Modules exist
$modules = [
    1 => ['name' => 'GoFood (Kuliner)', 'module_type' => 'food', 'theme' => '#EE2737'],
    2 => ['name' => 'GoMart (Sembako)', 'module_type' => 'grocery', 'theme' => '#FF6A00'],
    3 => ['name' => 'GoMed (Apotek)', 'module_type' => 'pharmacy', 'theme' => '#0081A0'],
    4 => ['name' => 'GoShop (Retail)', 'module_type' => 'ecommerce', 'theme' => '#9333EA'],
];

foreach ($modules as $mId => $mInfo) {
    $existingMod = Database::fetchOne("SELECT * FROM `modules` WHERE `id` = ?", [$mId]);
    if (!$existingMod) {
        Database::execute("INSERT INTO `modules` (`id`, `name`, `module_type`, `thumbnail`, `status`, `stores_count`, `created_at`) VALUES (?, ?, ?, 'assets/images/modules/default.png', 1, 1, NOW())", [$mId, $mInfo['name'], $mInfo['module_type']]);
    }
}
echo "Modules verified.\n";

// 3. Define Real GoFood Cicalengka Categories
$categories = [
    1 => ['name' => 'Ayam Geprek & Fried Chicken', 'module_id' => 1, 'priority' => 1],
    2 => ['name' => 'Sate Maranggi & Olahan Daging', 'module_id' => 1, 'priority' => 2],
    3 => ['name' => 'Masakan Sunda & Nasi Liwet', 'module_id' => 1, 'priority' => 3],
    4 => ['name' => 'Seblak & Jajanan Pedas Cicalengka', 'module_id' => 1, 'priority' => 4],
    5 => ['name' => 'Bakso & Mie Kocok Cicalengka', 'module_id' => 1, 'priority' => 5],
    6 => ['name' => 'Martabak Manis & Telur', 'module_id' => 1, 'priority' => 6],
    7 => ['name' => 'Kopi, Minuman Segar & Cafe', 'module_id' => 1, 'priority' => 7],
    8 => ['name' => 'Sembako & Kebutuhan Rumah', 'module_id' => 2, 'priority' => 8],
    9 => ['name' => 'Obat & Vitamin Kesehatan', 'module_id' => 3, 'priority' => 9],
];

foreach ($categories as $cId => $cData) {
    $existingCat = Database::fetchOne("SELECT * FROM `categories` WHERE `id` = ?", [$cId]);
    if ($existingCat) {
        Database::execute("UPDATE `categories` SET `name` = ?, `module_id` = ?, `priority` = ? WHERE `id` = ?", [$cData['name'], $cData['module_id'], $cData['priority'], $cId]);
    } else {
        Database::execute("INSERT INTO `categories` (`id`, `name`, `image`, `parent_id`, `status`, `priority`, `module_id`, `created_at`) VALUES (?, ?, 'assets/images/categories/default.png', 0, 1, ?, ?, NOW())", [$cId, $cData['name'], $cData['priority'], $cData['module_id']]);
    }
}
echo "GoFood Categories synchronized.\n";

// 4. Authentic GoFood Merchants Data in Cicalengka
$gofoodStores = [
    [
        'vendor_name'   => 'Warung Nasi Ampera Cicalengka',
        'vendor_email'  => 'ampera.cicalengka@cicalengkago.com',
        'vendor_phone'  => '081222334401',
        'store_name'    => 'Warung Nasi Ampera (Cabang Cicalengka)',
        'module_id'     => 1,
        'logo'          => 'assets/images/stores/ampera_logo.png',
        'cover_photo'   => 'assets/images/stores/ampera_cover.jpg',
        'address'       => 'Jl. Raya Cicalengka - Majalaya No. 88, Cicalengka',
        'latitude'      => -6.982500,
        'longitude'     => 107.832000,
        'delivery_time' => '15-25 mnt',
        'delivery_fee'  => 5000,
        'rating'        => 4.9,
        'reviews_count' => 850,
        'order_count'   => 1420,
        'products'      => [
            [
                'name' => 'Paket Nasi Liwet Komplit Ampera',
                'category_id' => 3,
                'description' => 'Nasi liwet harum dengan ayam bakar madu, tahu, tempe, lalapan segar dan sambal dadak khas Sunda.',
                'image' => 'assets/images/products/nasi_liwet_ampera.jpg',
                'price' => 35000,
                'discount' => 5000,
                'discount_type' => 'amount',
                'is_recommended' => 1
            ],
            [
                'name' => 'Gurame Goreng Kipas Sambal Dadak',
                'category_id' => 3,
                'description' => 'Gurame segar digoreng renyah garing dengan cocolan sambal terasi dadak pedas mantap.',
                'image' => 'assets/images/products/gurame_goreng.jpg',
                'price' => 48000,
                'discount' => 10,
                'discount_type' => 'percent',
                'is_recommended' => 1
            ],
            [
                'name' => 'Sayur Asem Khas Ampera',
                'category_id' => 3,
                'description' => 'Kuah sayur asem segar kaya rempah dengan melinjo, jagung manis, dan kacang tanah.',
                'image' => 'assets/images/products/sop_iga.jpg',
                'price' => 12000,
                'discount' => 0,
                'discount_type' => 'amount',
                'is_recommended' => 0
            ],
            [
                'name' => 'Es Teh Manis Jumbo Ampera',
                'category_id' => 7,
                'description' => 'Teh wangi melati segar dingin porsi jumbo pelepas dahaga.',
                'image' => 'assets/images/products/es_teh.jpg',
                'price' => 6000,
                'discount' => 0,
                'discount_type' => 'amount',
                'is_recommended' => 0
            ]
        ]
    ],
    [
        'vendor_name'   => 'Kedai Makarim Cicalengka',
        'vendor_email'  => 'makarim.cicalengka@cicalengkago.com',
        'vendor_phone'  => '081222334402',
        'store_name'    => 'Kedai Makarim - Ayam Geprek & Sate Taichan',
        'module_id'     => 1,
        'logo'          => 'assets/images/stores/geprek_logo.png',
        'cover_photo'   => 'assets/images/stores/geprek_cover.jpg',
        'address'       => 'Jl. Dipati Ukur No. 24, Cicalengka Kulon',
        'latitude'      => -6.984500,
        'longitude'     => 107.834500,
        'delivery_time' => '15-20 mnt',
        'delivery_fee'  => 4000,
        'rating'        => 4.8,
        'reviews_count' => 620,
        'order_count'   => 980,
        'products'      => [
            [
                'name' => 'Ricebowl Fillet Ayam Geprek Sambal Bawang',
                'category_id' => 1,
                'description' => 'Ayam fillet crispy tanpa tulang digeprek dengan sambal korek bawang pedas level 1-5.',
                'image' => 'assets/images/products/geprek_sambal.jpg',
                'price' => 18000,
                'discount' => 2000,
                'discount_type' => 'amount',
                'is_recommended' => 1
            ],
            [
                'name' => 'Paket Geprek Mozarella Keju Melted',
                'category_id' => 1,
                'description' => 'Ayam geprek pedas diselimuti lelehan keju mozarella bakar gurih nikmat.',
                'image' => 'assets/images/products/geprek_mozza.jpg',
                'price' => 24000,
                'discount' => 15,
                'discount_type' => 'percent',
                'is_recommended' => 1
            ],
            [
                'name' => 'Sate Taichan Ayam Pedas (10 Tusuk)',
                'category_id' => 2,
                'description' => 'Daging ayam juicy dibakar gurih disajikan dengan sambal taichan cabe rawit pedas & jeruk nipis.',
                'image' => 'assets/images/products/sate_maranggi.jpg',
                'price' => 22000,
                'discount' => 0,
                'discount_type' => 'amount',
                'is_recommended' => 1
            ],
            [
                'name' => 'Es Jeruk Peras Murni',
                'category_id' => 7,
                'description' => 'Jeruk peras asli manis alami dingin menyegarkan.',
                'image' => 'assets/images/products/es_jeruk.jpg',
                'price' => 7000,
                'discount' => 0,
                'discount_type' => 'amount',
                'is_recommended' => 0
            ]
        ]
    ],
    [
        'vendor_name'   => 'Warung Seblak 33 Cicalengka',
        'vendor_email'  => 'seblak33.cicalengka@cicalengkago.com',
        'vendor_phone'  => '081222334403',
        'store_name'    => 'Warung Seblak 33 Cicalengka (Prasmanan & Ceker)',
        'module_id'     => 1,
        'logo'          => 'assets/images/stores/seblak33_logo.png',
        'cover_photo'   => 'assets/images/stores/seblak33_cover.jpg',
        'address'       => 'Jl. Raya Barat Cicalengka No. 33, Cicalengka',
        'latitude'      => -6.986000,
        'longitude'     => 107.836000,
        'delivery_time' => '10-20 mnt',
        'delivery_fee'  => 4000,
        'rating'        => 4.9,
        'reviews_count' => 740,
        'order_count'   => 1250,
        'products'      => [
            [
                'name' => 'Seblak Komplit Ceker, Cuanki & Siomay',
                'category_id' => 4,
                'description' => 'Seblak kuah kencur pedas merah merona dengan topping ceker empuk, batagor, cuanki lidah, dan telur.',
                'image' => 'assets/images/products/seblak_ceker_komplit.jpg',
                'price' => 17000,
                'discount' => 2000,
                'discount_type' => 'amount',
                'is_recommended' => 1
            ],
            [
                'name' => 'Seblak Cilok Telur Dumpling Keju',
                'category_id' => 4,
                'description' => 'Cilok kenyal gurih dengan isian dumpling keju meleleh disiram kuah seblak pedas gurih.',
                'image' => 'assets/images/products/seblak_cilok.jpg',
                'price' => 15000,
                'discount' => 0,
                'discount_type' => 'amount',
                'is_recommended' => 1
            ]
        ]
    ],
    [
        'vendor_name'   => 'Baso Bi Manah Cicalengka',
        'vendor_email'  => 'bimanah.cicalengka@cicalengkago.com',
        'vendor_phone'  => '081222334404',
        'store_name'    => 'Baso Bi Manah Cicalengka (Dekat Stasiun)',
        'module_id'     => 1,
        'logo'          => 'assets/images/stores/bimanah_logo.png',
        'cover_photo'   => 'assets/images/stores/bimanah_cover.jpg',
        'address'       => 'Jl. Stasiun Cicalengka No. 05, Cicalengka',
        'latitude'      => -6.981000,
        'longitude'     => 107.837500,
        'delivery_time' => '15-25 mnt',
        'delivery_fee'  => 5000,
        'rating'        => 4.9,
        'reviews_count' => 1100,
        'order_count'   => 1890,
        'products'      => [
            [
                'name' => 'Bakso Urat Jumbo Spesial Bi Manah',
                'category_id' => 5,
                'description' => 'Bakso daging sapi asli dengan urat kriuk kenyal, kuah kaldu sapi gurih pekat plus mie & sayur.',
                'image' => 'assets/images/products/bakso_urat_jumbo.jpg',
                'price' => 23000,
                'discount' => 3000,
                'discount_type' => 'amount',
                'is_recommended' => 1
            ],
            [
                'name' => 'Mie Kocok Kaki Sapi Cicalengka',
                'category_id' => 5,
                'description' => 'Mie pipih kuning berpadu potongan kikil kaki sapi empuk, tauge segar, dan kuah kaldu kental wangi daun seledri.',
                'image' => 'assets/images/products/mie_kocok_sapi.jpg',
                'price' => 25000,
                'discount' => 10,
                'discount_type' => 'percent',
                'is_recommended' => 1
            ]
        ]
    ],
    [
        'vendor_name'   => 'Martabak MR ZA Cicalengka',
        'vendor_email'  => 'martabakza.cicalengka@cicalengkago.com',
        'vendor_phone'  => '081222334405',
        'store_name'    => 'Martabak MR ZA Cicalengka',
        'module_id'     => 1,
        'logo'          => 'assets/images/stores/martabakza_logo.png',
        'cover_photo'   => 'assets/images/stores/martabakza_cover.jpg',
        'address'       => 'Jl. Cicalengka Raya No. 112, Cicalengka',
        'latitude'      => -6.985000,
        'longitude'     => 107.831000,
        'delivery_time' => '20-30 mnt',
        'delivery_fee'  => 5000,
        'rating'        => 4.8,
        'reviews_count' => 510,
        'order_count'   => 820,
        'products'      => [
            [
                'name' => 'Martabak Spesial Telur Bebek 3 Telur',
                'category_id' => 6,
                'description' => 'Kulit renyah gurih bersarang dengan isian daging sapi cincang bumbu rempah & daun bawang melimpah.',
                'image' => 'assets/images/products/martabak_telur_bebek.jpg',
                'price' => 38000,
                'discount' => 5000,
                'discount_type' => 'amount',
                'is_recommended' => 1
            ],
            [
                'name' => 'Martabak Manis Keju Coklat Wijen',
                'category_id' => 6,
                'description' => 'Adonan tebal lembut empuk dengan taburan keju cheddar parut melimpah, meses coklat premium, dan wijen sangrai.',
                'image' => 'assets/images/products/martabak_keju_coklat.jpg',
                'price' => 32000,
                'discount' => 10,
                'discount_type' => 'percent',
                'is_recommended' => 1
            ]
        ]
    ],
    [
        'vendor_name'   => 'Senandung Sore Coffee & Nature',
        'vendor_email'  => 'senandungsore.cicalengka@cicalengkago.com',
        'vendor_phone'  => '081222334406',
        'store_name'    => 'Senandung Sore Coffee & Nature',
        'module_id'     => 1,
        'logo'          => 'assets/images/stores/senandungsore_logo.png',
        'cover_photo'   => 'assets/images/stores/senandungsore_cover.jpg',
        'address'       => 'Jl. Curug Cinulang, Cicalengka, Kab. Bandung',
        'latitude'      => -6.992000,
        'longitude'     => 107.848000,
        'delivery_time' => '25-35 mnt',
        'delivery_fee'  => 6000,
        'rating'        => 4.9,
        'reviews_count' => 930,
        'order_count'   => 1540,
        'products'      => [
            [
                'name' => 'Kopi Susu Aren Senandung Sore (Es)',
                'category_id' => 7,
                'description' => 'Espresso house blend arabica dengan susu creamy dan sirup gula aren murni organik khas Curug Cinulang.',
                'image' => 'assets/images/products/kopi_senandung_sore.jpg',
                'price' => 20000,
                'discount' => 2000,
                'discount_type' => 'amount',
                'is_recommended' => 1
            ],
            [
                'name' => 'Matcha Green Tea Latte Iced',
                'category_id' => 7,
                'description' => 'Bubuk matcha jepang autentik berpadu fresh milk dingin creamy berbusa halus.',
                'image' => 'assets/images/products/matcha_latte.jpg',
                'price' => 22000,
                'discount' => 0,
                'discount_type' => 'amount',
                'is_recommended' => 1
            ]
        ]
    ],
    [
        'vendor_name'   => 'Ayam Bakar Panyileukan Cicalengka',
        'vendor_email'  => 'panyileukan.cicalengka@cicalengkago.com',
        'vendor_phone'  => '081222334407',
        'store_name'    => 'Ayam Bakar Panyileukan Cicalengka',
        'module_id'     => 1,
        'logo'          => 'assets/images/stores/panyileukan_logo.png',
        'cover_photo'   => 'assets/images/stores/panyileukan_cover.jpg',
        'address'       => 'Jl. Raya Cicalengka No. 70, Cicalengka',
        'latitude'      => -6.987500,
        'longitude'     => 107.839000,
        'delivery_time' => '20-30 mnt',
        'delivery_fee'  => 5000,
        'rating'        => 4.8,
        'reviews_count' => 420,
        'order_count'   => 710,
        'products'      => [
            [
                'name' => 'Paket Nasi Timbel Ayam Bakar Madu',
                'category_id' => 3,
                'description' => 'Nasi timbel dibungkus daun pisang wangi, ayam bakar bumbu kecap madu meresap, tahu, tempe & lalap sambal terasi.',
                'image' => 'assets/images/products/ayam_bakar_madu.jpg',
                'price' => 28000,
                'discount' => 3000,
                'discount_type' => 'amount',
                'is_recommended' => 1
            ]
        ]
    ],
    [
        'vendor_name'   => 'Barokah Sembako Mart Cicalengka',
        'vendor_email'  => 'barokahmart.cicalengka@cicalengkago.com',
        'vendor_phone'  => '081222334408',
        'store_name'    => 'Barokah Mart Sembako Cicalengka (GoMart)',
        'module_id'     => 2,
        'logo'          => 'assets/images/stores/sembako_logo.png',
        'cover_photo'   => 'assets/images/stores/sembako_cover.jpg',
        'address'       => 'Jl. Cicalengka Kulon No. 42, Cicalengka',
        'latitude'      => -6.985500,
        'longitude'     => 107.833000,
        'delivery_time' => '15-30 mnt',
        'delivery_fee'  => 5000,
        'rating'        => 4.9,
        'reviews_count' => 380,
        'order_count'   => 640,
        'products'      => [
            [
                'name' => 'Beras Pandan Wangi Super Cicalengka 5kg',
                'category_id' => 8,
                'description' => 'Beras pulen harum alami tanpa pemutih dan tanpa pengawet.',
                'image' => 'assets/images/products/beras_pandan.jpg',
                'price' => 74000,
                'discount' => 4000,
                'discount_type' => 'amount',
                'is_recommended' => 1
            ],
            [
                'name' => 'Minyak Goreng Sania Pouch 2 Liter',
                'category_id' => 8,
                'description' => 'Minyak goreng kelapa sawit bening berkualitas kaya vitamin E.',
                'image' => 'assets/images/products/minyak_sania.jpg',
                'price' => 36000,
                'discount' => 2000,
                'discount_type' => 'amount',
                'is_recommended' => 1
            ]
        ]
    ],
    [
        'vendor_name'   => 'Apotek Sehat Farma Cicalengka',
        'vendor_email'  => 'sehatfarma.cicalengka@cicalengkago.com',
        'vendor_phone'  => '081222334409',
        'store_name'    => 'Apotek Sehat Farma 24 Jam (GoMed)',
        'module_id'     => 3,
        'logo'          => 'assets/images/stores/apotek_logo.png',
        'cover_photo'   => 'assets/images/stores/apotek_cover.jpg',
        'address'       => 'Jl. Raya Timur Cicalengka No. 89, Cicalengka',
        'latitude'      => -6.983800,
        'longitude'     => 107.836500,
        'delivery_time' => '10-20 mnt',
        'delivery_fee'  => 4000,
        'rating'        => 5.0,
        'reviews_count' => 610,
        'order_count'   => 1020,
        'products'      => [
            [
                'name' => 'Enervon-C Multivitamin Strip 4 Tablet',
                'category_id' => 9,
                'description' => 'Suplemen vitamin C 500mg dan vitamin B kompleks untuk menjaga daya tahan tubuh.',
                'image' => 'assets/images/products/enervon_c.jpg',
                'price' => 8500,
                'discount' => 0,
                'discount_type' => 'amount',
                'is_recommended' => 1
            ]
        ]
    ]
];

$syncedStores = 0;
$syncedProducts = 0;

foreach ($gofoodStores as $sData) {
    // A. Check or Create Vendor User
    $vendor = Database::fetchOne("SELECT * FROM `users` WHERE `email` = ?", [$sData['vendor_email']]);
    if (!$vendor) {
        $vendorId = Database::insert('users', [
            'name'       => $sData['vendor_name'],
            'email'      => $sData['vendor_email'],
            'phone'      => $sData['vendor_phone'],
            'password'   => password_hash('password123', PASSWORD_DEFAULT),
            'role'       => 'vendor',
            'is_active'  => 1,
            'avatar'     => $sData['logo'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
    } else {
        $vendorId = $vendor['id'];
    }

    // B. Check or Insert/Update Store
    $store = Database::fetchOne("SELECT * FROM `stores` WHERE `name` = ? OR `vendor_id` = ?", [$sData['store_name'], $vendorId]);
    if ($store) {
        Database::execute("UPDATE `stores` SET 
            `name` = ?, `module_id` = ?, `zone_id` = 1, `phone` = ?, `email` = ?,
            `logo` = ?, `cover_photo` = ?, `address` = ?, `latitude` = ?, `longitude` = ?,
            `delivery_time` = ?, `delivery_fee` = ?, `rating` = ?, `reviews_count` = ?,
            `order_count` = ?, `is_open` = 1, `status` = 'approved'
            WHERE `id` = ?", [
            $sData['store_name'], $sData['module_id'], $sData['vendor_phone'], $sData['vendor_email'],
            $sData['logo'], $sData['cover_photo'], $sData['address'], $sData['latitude'], $sData['longitude'],
            $sData['delivery_time'], $sData['delivery_fee'], $sData['rating'], $sData['reviews_count'],
            $sData['order_count'], $store['id']
        ]);
        $storeId = $store['id'];
    } else {
        $storeId = Database::insert('stores', [
            'vendor_id'     => $vendorId,
            'module_id'     => $sData['module_id'],
            'zone_id'       => 1,
            'name'          => $sData['store_name'],
            'phone'         => $sData['vendor_phone'],
            'email'         => $sData['vendor_email'],
            'logo'          => $sData['logo'],
            'cover_photo'   => $sData['cover_photo'],
            'address'       => $sData['address'],
            'latitude'      => $sData['latitude'],
            'longitude'     => $sData['longitude'],
            'minimum_order' => 10000,
            'delivery_time' => $sData['delivery_time'],
            'delivery_fee'  => $sData['delivery_fee'],
            'tax_percent'   => 0.00,
            'is_open'       => 1,
            'status'        => 'approved',
            'rating'        => $sData['rating'],
            'reviews_count' => $sData['reviews_count'],
            'order_count'   => $sData['order_count'],
            'created_at'    => date('Y-m-d H:i:s')
        ]);
    }
    $syncedStores++;
    echo "✔ Resto Sinkron: {$sData['store_name']} (ID: $storeId)\n";

    // C. Synchronize Products
    foreach ($sData['products'] as $pData) {
        $prod = Database::fetchOne("SELECT * FROM `products` WHERE `store_id` = ? AND `name` = ?", [$storeId, $pData['name']]);
        if ($prod) {
            Database::execute("UPDATE `products` SET
                `module_id` = ?, `category_id` = ?, `description` = ?, `image` = ?,
                `price` = ?, `discount` = ?, `discount_type` = ?, `is_recommended` = ?, `status` = 1
                WHERE `id` = ?", [
                $sData['module_id'], $pData['category_id'], $pData['description'], $pData['image'],
                $pData['price'], $pData['discount'], $pData['discount_type'], $pData['is_recommended'],
                $prod['id']
            ]);
        } else {
            Database::insert('products', [
                'store_id'       => $storeId,
                'module_id'      => $sData['module_id'],
                'category_id'    => $pData['category_id'],
                'name'           => $pData['name'],
                'description'    => $pData['description'],
                'image'          => $pData['image'],
                'price'          => $pData['price'],
                'discount'       => $pData['discount'],
                'discount_type'  => $pData['discount_type'],
                'unit'           => 'porsi',
                'stock'          => 100,
                'is_veg'         => 0,
                'is_recommended' => $pData['is_recommended'],
                'status'         => 1,
                'order_count'    => rand(50, 450),
                'rating'         => 4.9,
                'reviews_count'  => rand(30, 200),
                'created_at'     => date('Y-m-d H:i:s')
            ]);
        }
        $syncedProducts++;
    }
}

// 5. Update Stores Count in modules
Database::execute("UPDATE `modules` m SET m.stores_count = (SELECT COUNT(*) FROM `stores` s WHERE s.module_id = m.id AND s.status = 'approved')");

echo "\n======================================================\n";
echo ">>> SINKRONISASI SUKSES! <<<\n";
echo "Jumlah Toko/Resto GoFood Terhubung: $syncedStores\n";
echo "Jumlah Menu & Produk Tersinkron: $syncedProducts\n";
echo "======================================================\n";
