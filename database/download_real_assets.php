<?php
/**
 * Instant Real-Photo Visual Assets Downloader for CicalengkaGO
 * Downloads high-res curated photography for banners, products, stores, and avatars.
 */

$assets = [
    // BANNERS (1200x500 16:9 widescreen)
    'banners/banner1.jpg' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1000&q=80', // Delicious Food Promo
    'banners/banner2.jpg' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1000&q=80', // Fresh Supermarket Mart
    'banners/banner3.jpg' => 'https://images.unsplash.com/photo-1617347454431-f49d7ff5c3b1?auto=format&fit=crop&w=1000&q=80', // Express Delivery Courier

    // PRODUCTS (800x800 square HD)
    'products/geprek_sambal.jpg' => 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?auto=format&fit=crop&w=800&q=80', // Crispy Fried Chicken Sambal
    'products/geprek_mozza.jpg'  => 'https://images.unsplash.com/photo-1562967914-608f82629710?auto=format&fit=crop&w=800&q=80', // Mozzarella Chicken
    'products/sate_maranggi.jpg' => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=800&q=80', // Grilled Beef Satay Skewers
    'products/sop_iga.jpg'       => 'https://images.unsplash.com/photo-1547592166-23ac45744acd?auto=format&fit=crop&w=800&q=80', // Steaming Beef Ribs Soup
    'products/es_jeruk.jpg'      => 'https://images.unsplash.com/photo-1613478223719-2ab802602423?auto=format&fit=crop&w=800&q=80', // Iced Fresh Orange Juice
    'products/es_teh.jpg'        => 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?auto=format&fit=crop&w=800&q=80', // Iced Sweet Jasmine Tea
    'products/beras_pandan.jpg'  => 'https://images.unsplash.com/photo-1586201375761-83865001e31c?auto=format&fit=crop&w=800&q=80', // Premium Rice Grain
    'products/minyak_sania.jpg'  => 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?auto=format&fit=crop&w=800&q=80', // Premium Cooking Oil
    'products/enervon_c.jpg'     => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?auto=format&fit=crop&w=800&q=80', // Multivitamin & Pharmacy
    'products/pashmina.jpg'      => 'https://images.unsplash.com/photo-1601924994987-69e26d50dc26?auto=format&fit=crop&w=800&q=80', // Elegant Scarf / Fashion
    'products/default.jpg'       => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?auto=format&fit=crop&w=800&q=80', // Default Gourmet

    // STORE COVERS (1000x500 HD Cover)
    'stores/geprek_cover.jpg'  => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=900&q=80', // Modern Resto Interior
    'stores/maranggi_cover.jpg'=> 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80', // Cozy Eatery Dining
    'stores/sembako_cover.jpg' => 'https://images.unsplash.com/photo-1578916171728-46686eac8d58?auto=format&fit=crop&w=900&q=80', // Modern Supermarket Store
    'stores/apotek_cover.jpg'  => 'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?auto=format&fit=crop&w=900&q=80', // Clean Bright Pharmacy
    'stores/fashion_cover.jpg' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=900&q=80', // Trendy Fashion Boutique

    // STORE LOGOS (400x400 Avatar)
    'stores/geprek_logo.png'  => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=400&q=80',
    'stores/maranggi_logo.png'=> 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=400&q=80',
    'stores/sembako_logo.png' => 'https://images.unsplash.com/photo-1583258292688-d0213dc5a3a8?auto=format&fit=crop&w=400&q=80',
    'stores/apotek_logo.png'  => 'https://images.unsplash.com/photo-1576602976047-174e57a47881?auto=format&fit=crop&w=400&q=80',
    'stores/fashion_logo.png' => 'https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?auto=format&fit=crop&w=400&q=80',

    // USERS (400x400 Profile photos)
    'users/admin.png'    => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=300&q=80',
    'users/customer.png' => 'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?auto=format&fit=crop&w=300&q=80',
    'users/driver.png'   => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=300&q=80',
    'users/vendor1.png'  => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=300&q=80',
    'users/vendor2.png'  => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=300&q=80',
    'users/default.png'  => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=300&q=80',
];

$baseDir = __DIR__ . '/../public/assets/images/';

echo "Starting Fast Asset Download...\n";

foreach ($assets as $relPath => $url) {
    $targetFile = $baseDir . $relPath;
    $targetDir = dirname($targetFile);
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    echo "Downloading {$relPath}... ";
    
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 8,
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n"
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);

    $content = @file_get_contents($url, false, $ctx);
    if ($content !== false && strlen($content) > 1000) {
        file_put_contents($targetFile, $content);
        echo "OK (" . round(strlen($content) / 1024) . " KB)\n";
    } else {
        echo "FAILED (Skipped/Retaining existing)\n";
    }
}

echo "\nAll Real Visual Assets Processed Successfully!\n";
