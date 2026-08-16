<?php
// Download high-resolution food and store images from Unsplash

$productDir = dirname(__DIR__) . '/public/assets/images/products';
$storeDir = dirname(__DIR__) . '/public/assets/images/stores';
$bannerDir = dirname(__DIR__) . '/public/assets/images/banners';

if (!is_dir($productDir)) mkdir($productDir, 0777, true);
if (!is_dir($storeDir)) mkdir($storeDir, 0777, true);
if (!is_dir($bannerDir)) mkdir($bannerDir, 0777, true);

$downloads = [
    // Products
    "$productDir/nasi_liwet_ampera.jpg"   => "https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=600&q=80",
    "$productDir/gurame_goreng.jpg"       => "https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?auto=format&fit=crop&w=600&q=80",
    "$productDir/geprek_sambal.jpg"       => "https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?auto=format&fit=crop&w=600&q=80",
    "$productDir/geprek_mozza.jpg"        => "https://images.unsplash.com/photo-1562967914-608f82629710?auto=format&fit=crop&w=600&q=80",
    "$productDir/sate_maranggi.jpg"       => "https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=600&q=80",
    "$productDir/seblak_ceker_komplit.jpg"=> "https://images.unsplash.com/photo-1569718212165-3a8278d5f624?auto=format&fit=crop&w=600&q=80",
    "$productDir/seblak_cilok.jpg"        => "https://images.unsplash.com/photo-1565299585323-38d6b0865b47?auto=format&fit=crop&w=600&q=80",
    "$productDir/bakso_urat_jumbo.jpg"    => "https://images.unsplash.com/photo-1547928576-a4a33237cbc3?auto=format&fit=crop&w=600&q=80",
    "$productDir/mie_kocok_sapi.jpg"      => "https://images.unsplash.com/photo-1612927601601-6638404737ce?auto=format&fit=crop&w=600&q=80",
    "$productDir/martabak_telur_bebek.jpg"=> "https://images.unsplash.com/photo-1604908176997-125f25cc6f3d?auto=format&fit=crop&w=600&q=80",
    "$productDir/martabak_keju_coklat.jpg"=> "https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=600&q=80",
    "$productDir/kopi_senandung_sore.jpg" => "https://images.unsplash.com/photo-1517256064527-09c73fc73e38?auto=format&fit=crop&w=600&q=80",
    "$productDir/matcha_latte.jpg"        => "https://images.unsplash.com/photo-1536256263959-770b48d82b0a?auto=format&fit=crop&w=600&q=80",
    "$productDir/ayam_bakar_madu.jpg"     => "https://images.unsplash.com/photo-1598515214211-89d3c73ae83b?auto=format&fit=crop&w=600&q=80",
    "$productDir/beras_pandan.jpg"        => "https://images.unsplash.com/photo-1586201375761-83865001e31c?auto=format&fit=crop&w=600&q=80",
    "$productDir/minyak_sania.jpg"        => "https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?auto=format&fit=crop&w=600&q=80",
    "$productDir/es_jeruk.jpg"            => "https://images.unsplash.com/photo-1613478223719-2ab802602423?auto=format&fit=crop&w=600&q=80",
    "$productDir/es_teh.jpg"              => "https://images.unsplash.com/photo-1556679343-c7306c1976bc?auto=format&fit=crop&w=600&q=80",
    "$productDir/sop_iga.jpg"             => "https://images.unsplash.com/photo-1547592166-23ac45744acd?auto=format&fit=crop&w=600&q=80",
    "$productDir/enervon_c.jpg"           => "https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?auto=format&fit=crop&w=600&q=80",
    "$productDir/pashmina.jpg"            => "https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=600&q=80",
    "$productDir/default.jpg"             => "https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=600&q=80",

    // Stores Covers
    "$storeDir/ampera_cover.jpg"          => "https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=600&q=80",
    "$storeDir/geprek_cover.jpg"          => "https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=600&q=80",
    "$storeDir/maranggi_cover.jpg"        => "https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=600&q=80",
    "$storeDir/seblak33_cover.jpg"        => "https://images.unsplash.com/photo-1552611052-33e04de081de?auto=format&fit=crop&w=600&q=80",
    "$storeDir/bimanah_cover.jpg"         => "https://images.unsplash.com/photo-1565299585323-38d6b0865b47?auto=format&fit=crop&w=600&q=80",
    "$storeDir/martabakza_cover.jpg"      => "https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=600&q=80",
    "$storeDir/senandungsore_cover.jpg"   => "https://images.unsplash.com/photo-1445116572660-2384184870d0?auto=format&fit=crop&w=600&q=80",
    "$storeDir/panyileukan_cover.jpg"     => "https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=600&q=80",
    "$storeDir/sembako_cover.jpg"         => "https://images.unsplash.com/photo-1578916171728-46686eac8d58?auto=format&fit=crop&w=600&q=80",
    "$storeDir/apotek_cover.jpg"          => "https://images.unsplash.com/photo-1586015555751-63bb77f4322a?auto=format&fit=crop&w=600&q=80",
    "$storeDir/fashion_cover.jpg"         => "https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=600&q=80",

    // Banners
    "$bannerDir/banner1.jpg"              => "https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=900&q=80",
    "$bannerDir/banner2.jpg"              => "https://images.unsplash.com/photo-1526367790999-0150786686a2?auto=format&fit=crop&w=900&q=80",
    "$bannerDir/banner3.jpg"              => "https://images.unsplash.com/photo-1559526324-4b87b5e36e44?auto=format&fit=crop&w=900&q=80"
];

$ctx = stream_context_create([
    'http' => [
        'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n",
        'timeout' => 10
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$success = 0;
foreach ($downloads as $path => $url) {
    echo "Downloading " . basename($path) . "... ";
    $data = @file_get_contents($url, false, $ctx);
    if ($data !== false && strlen($data) > 1000) {
        file_put_contents($path, $data);
        echo "OK (" . strlen($data) . " bytes)\n";
        $success++;
    } else {
        echo "FAILED\n";
    }
}

echo "\nFinished downloading $success / " . count($downloads) . " real photographic assets from Unsplash!\n";
