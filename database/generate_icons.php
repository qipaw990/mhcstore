<?php
/**
 * PWA Icon & Placeholder Generator
 */

$sizes = [72, 96, 128, 144, 192, 512];
$iconsDir = __DIR__ . '/../public/assets/icons';
if (!is_dir($iconsDir)) {
    mkdir($iconsDir, 0755, true);
}

foreach ($sizes as $size) {
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);
    
    // Background blue
    $bgColor = imagecolorallocate($img, 37, 99, 235); // #2563eb
    $white = imagecolorallocate($img, 255, 255, 255);
    $accent = imagecolorallocate($img, 251, 191, 36); // Gold

    imagefilledrectangle($img, 0, 0, $size, $size, $bgColor);

    // Inner circle
    imagefilledellipse($img, $size / 2, $size / 2, $size * 0.85, $size * 0.85, $accent);
    imagefilledellipse($img, $size / 2, $size / 2, $size * 0.78, $size * 0.78, $bgColor);

    // Draw stylized "C" and "GO"
    $fontSize = max(3, (int)($size / 4));
    $text = "CGO";
    if ($size >= 128) {
        $text = "CICAGO";
    }
    
    // Fallback built-in font rendering
    $font = 5;
    $fw = imagefontwidth($font) * strlen($text);
    $fh = imagefontheight($font);
    $x = ($size - $fw) / 2;
    $y = ($size - $fh) / 2;
    imagestring($img, $font, $x, $y, $text, $white);

    imagepng($img, $iconsDir . "/icon-{$size}.png");
    imagedestroy($img);
}

echo "Icons generated successfully in {$iconsDir}\n";
