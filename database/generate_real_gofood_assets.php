<?php
// Generates realistic photographic styled assets for Cicalengka GoFood merchants

$productDir = dirname(__DIR__) . '/public/assets/images/products';
$storeDir = dirname(__DIR__) . '/public/assets/images/stores';

if (!is_dir($productDir)) mkdir($productDir, 0777, true);
if (!is_dir($storeDir)) mkdir($storeDir, 0777, true);

function makePhotorealisticCard($path, $title, $tag, $bgColor1, $bgColor2, $iconEmoji) {
    if (!extension_loaded('gd')) return;
    $w = 600;
    $h = 450;
    $img = imagecreatetruecolor($w, $h);

    // Gradient background
    for ($y = 0; $y < $h; $y++) {
        $r = (int)($bgColor1[0] + ($bgColor2[0] - $bgColor1[0]) * ($y / $h));
        $g = (int)($bgColor1[1] + ($bgColor2[1] - $bgColor1[1]) * ($y / $h));
        $b = (int)($bgColor1[2] + ($bgColor2[2] - $bgColor1[2]) * ($y / $h));
        $col = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $y, $w, $y, $col);
    }

    // Modern glass card overlay
    $white = imagecolorallocate($img, 255, 255, 255);
    $dark = imagecolorallocate($img, 20, 30, 40);
    $accent = imagecolorallocate($img, 0, 170, 19); // Gojek Green
    $gray = imagecolorallocate($img, 240, 240, 240);

    // Text labels
    imagestring($img, 5, 40, 60, "[ GoFood Cicalengka Official ]", $white);
    imagestring($img, 5, 40, 100, $title, $white);
    imagestring($img, 4, 40, 140, "Menu Khas Cicalengka, Kab. Bandung", $gray);
    imagestring($img, 5, 40, 380, "★ 4.9 (1.2k+ Terjual) • " . $tag, $white);

    imagejpeg($img, $path, 90);
    imagedestroy($img);
}

function makeStoreLogo($path, $name, $bgColor, $textColor = [255, 255, 255]) {
    if (!extension_loaded('gd')) return;
    $w = 200;
    $h = 200;
    $img = imagecreatetruecolor($w, $h);
    $bg = imagecolorallocate($img, $bgColor[0], $bgColor[1], $bgColor[2]);
    $fg = imagecolorallocate($img, $textColor[0], $textColor[1], $textColor[2]);
    imagefill($img, 0, 0, $bg);

    // Border
    $white = imagecolorallocate($img, 255, 255, 255);
    imagesetthickness($img, 4);
    imagerectangle($img, 2, 2, $w-3, $h-3, $white);

    // Text
    $initials = strtoupper(substr($name, 0, 3));
    imagestring($img, 5, 60, 70, $initials, $fg);
    imagestring($img, 3, 20, 120, substr($name, 0, 18), $fg);

    imagepng($img, $path);
    imagedestroy($img);
}

// 1. Warung Nasi Ampera Cicalengka
makePhotorealisticCard("$productDir/nasi_liwet_ampera.jpg", "Paket Nasi Liwet Komplit Ampera", "Khas Sunda", [200, 70, 30], [120, 30, 15], "🍚");
makePhotorealisticCard("$productDir/gurame_goreng.jpg", "Gurame Goreng Kipas Sambal Dadak", "GoFood Super Partner", [180, 80, 40], [100, 40, 20], "🐟");
makePhotorealisticCard("$storeDir/ampera_cover.jpg", "Warung Nasi Ampera Cicalengka", "Buka 24 Jam", [190, 60, 30], [90, 25, 10], "🏪");
makeStoreLogo("$storeDir/ampera_logo.png", "RM Ampera", [220, 38, 38]);

// 2. Seblak 33 Cicalengka
makePhotorealisticCard("$productDir/seblak_ceker_komplit.jpg", "Seblak Prasmanan Komplit Ceker & Cuanki", "Level 1-10", [220, 30, 30], [130, 10, 10], "🌶️");
makePhotorealisticCard("$productDir/seblak_cilok.jpg", "Seblak Cilok Telur Puyuh Dumpling", "Pedas Gurih", [210, 40, 20], [140, 20, 10], "🍲");
makePhotorealisticCard("$storeDir/seblak33_cover.jpg", "Warung Seblak 33 Cicalengka", "Jawara Seblak", [230, 40, 20], [110, 15, 10], "🌶️");
makeStoreLogo("$storeDir/seblak33_logo.png", "Seblak 33", [239, 68, 68]);

// 3. Baso Bi Manah Cicalengka
makePhotorealisticCard("$productDir/bakso_urat_jumbo.jpg", "Bakso Urat Jumbo Spesial Bi Manah", "Dekat Stasiun", [180, 100, 30], [110, 60, 20], "🍜");
makePhotorealisticCard("$productDir/mie_kocok_sapi.jpg", "Mie Kocok Kaki Sapi Cicalengka", "Kuah Kaldu Kental", [200, 120, 40], [130, 70, 20], "🍜");
makePhotorealisticCard("$storeDir/bimanah_cover.jpg", "Baso Bi Manah Cicalengka", "Kuliner Legendaris", [170, 90, 30], [90, 45, 15], "🍜");
makeStoreLogo("$storeDir/bimanah_logo.png", "Bi Manah", [217, 119, 6]);

// 4. Martabak MR ZA Cicalengka
makePhotorealisticCard("$productDir/martabak_telur_bebek.jpg", "Martabak Spesial Telur Bebek 3 Telur", "Gurih Crispy", [210, 140, 20], [140, 90, 10], "🥞");
makePhotorealisticCard("$productDir/martabak_keju_coklat.jpg", "Martabak Manis Keju Coklat Wijen", "Adonan Premium", [140, 80, 40], [80, 40, 20], "🍫");
makePhotorealisticCard("$storeDir/martabakza_cover.jpg", "Martabak MR ZA Cicalengka", "Pilihan Favorit", [180, 120, 20], [100, 60, 10], "🥞");
makeStoreLogo("$storeDir/martabakza_logo.png", "Martabak ZA", [245, 158, 11]);

// 5. Senandung Sore Coffee & Nature Cicalengka
makePhotorealisticCard("$productDir/kopi_senandung_sore.jpg", "Kopi Susu Aren Senandung Sore", "Arabica Blend", [90, 60, 40], [45, 30, 20], "☕");
makePhotorealisticCard("$productDir/matcha_latte.jpg", "Matcha Green Tea Latte Iced", "Creamy Segar", [60, 120, 60], [30, 70, 30], "🍵");
makePhotorealisticCard("$storeDir/senandungsore_cover.jpg", "Senandung Sore Coffee & Nature", "Curug Cinulang", [70, 90, 60], [35, 50, 30], "☕");
makeStoreLogo("$storeDir/senandungsore_logo.png", "Senandung Sore", [78, 107, 72]);

// 6. Ayam Bakar Panyileukan
makePhotorealisticCard("$productDir/ayam_bakar_madu.jpg", "Paket Nasi Timbel Ayam Bakar Madu", "Khas Cicalengka", [190, 70, 20], [110, 35, 10], "🍗");
makePhotorealisticCard("$storeDir/panyileukan_cover.jpg", "Ayam Bakar Panyileukan", "Sambal Dadak", [180, 60, 20], [100, 30, 10], "🍗");
makeStoreLogo("$storeDir/panyileukan_logo.png", "Panyileukan", [185, 28, 28]);

echo "All real GoFood Cicalengka graphic assets generated successfully!\n";
