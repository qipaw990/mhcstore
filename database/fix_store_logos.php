<?php
$base = dirname(__DIR__);
$stores = [
    'ampera'        => "$base/public/assets/images/stores/ampera_cover.jpg",
    'seblak33'      => "$base/public/assets/images/stores/seblak33_cover.jpg",
    'bimanah'       => "$base/public/assets/images/stores/bimanah_cover.jpg",
    'martabakza'    => "$base/public/assets/images/stores/martabakza_cover.jpg",
    'senandungsore' => "$base/public/assets/images/stores/senandungsore_cover.jpg",
    'panyileukan'   => "$base/public/assets/images/stores/panyileukan_cover.jpg"
];
foreach ($stores as $k => $cover) {
    if (file_exists($cover)) {
        copy($cover, "$base/public/assets/images/stores/{$k}_logo.png");
    }
}
echo "Logos created successfully!\n";
