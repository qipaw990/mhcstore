<?php
// Generator for CicalengkaGO authentic Red-themed PNG icons and logos

function drawCicalengkaGoIconRed($size, $outputPath) {
    $img = imagecreatetruecolor($size, $size);
    imagealphablending($img, false);
    imagesavealpha($img, true);

    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);

    imagealphablending($img, true);

    // Red Theme Colors
    $red1 = [255, 56, 72];    // #FF3848 (Vibrant Ruby Red)
    $red2 = [198, 21, 36];    // #C61524 (Deep Crimson Red)
    $white = imagecolorallocate($img, 255, 255, 255);

    // 1. Draw rounded squircle background
    $padding = (int)($size * 0.05);
    $rad = (int)($size * 0.24);
    $w = $size - (2 * $padding);
    $h = $size - (2 * $padding);

    // Gradient fill inside squircle
    for ($y = $padding; $y <= $size - $padding; $y++) {
        $factor = ($y - $padding) / max(1, $h);
        $r = (int)($red1[0] + ($red2[0] - $red1[0]) * $factor);
        $g = (int)($red1[1] + ($red2[1] - $red1[1]) * $factor);
        $b = (int)($red1[2] + ($red2[2] - $red1[2]) * $factor);
        $col = imagecolorallocate($img, $r, $g, $b);

        $x1 = $padding;
        $x2 = $size - $padding;
        
        if ($y < $padding + $rad) {
            $dy = ($padding + $rad) - $y;
            $dx = (int)sqrt(max(0, $rad * $rad - $dy * $dy));
            $x1 = $padding + $rad - $dx;
            $x2 = $size - $padding - $rad + $dx;
        } else if ($y > $size - $padding - $rad) {
            $dy = $y - ($size - $padding - $rad);
            $dx = (int)sqrt(max(0, $rad * $rad - $dy * $dy));
            $x1 = $padding + $rad - $dx;
            $x2 = $size - $padding - $rad + $dx;
        }

        imageline($img, $x1, $y, $x2, $y, $col);
    }

    // 2. Draw 'C' Swoosh Ring
    $cx = (int)($size * 0.5);
    $cy = (int)($size * 0.5);
    $outerR = (int)($size * 0.28);

    imagesetthickness($img, max(2, (int)($size * 0.10)));
    imagearc($img, $cx, $cy, (int)($outerR * 2), (int)($outerR * 2), 48, 312, $white);

    // 3. Draw Center Dot
    $dotR = (int)($size * 0.095);
    imagefilledellipse($img, $cx, $cy, $dotR * 2, $dotR * 2, $white);

    // 4. Draw Speed Arrow at top right of C
    $arrowX = (int)($cx + $outerR * cos(deg2rad(48)));
    $arrowY = (int)($cy - $outerR * sin(deg2rad(48)));
    $arrowSize = (int)($size * 0.08);

    $points = [
        $arrowX - $arrowSize, $arrowY - (int)($arrowSize * 0.7),
        $arrowX + (int)($arrowSize * 1.1), $arrowY,
        $arrowX - $arrowSize, $arrowY + (int)($arrowSize * 0.7),
    ];
    imagefilledpolygon($img, $points, $white);

    imagepng($img, $outputPath, 9);
    imagedestroy($img);
}

$iconsDir = dirname(__DIR__) . '/public/assets/icons';
$imagesDir = dirname(__DIR__) . '/public/assets/images';

if (!is_dir($iconsDir)) mkdir($iconsDir, 0777, true);
if (!is_dir($imagesDir)) mkdir($imagesDir, 0777, true);

$sizes = [72, 96, 128, 144, 192, 512];
foreach ($sizes as $s) {
    drawCicalengkaGoIconRed($s, "$iconsDir/icon-$s.png");
    echo "✔ Generated red icon-{$s}.png\n";
}

drawCicalengkaGoIconRed(256, "$imagesDir/logo-icon.png");
drawCicalengkaGoIconRed(192, "$imagesDir/logo.png");
copy("$iconsDir/icon-192.png", "$iconsDir/favicon.png");

echo "\nAll red-themed CicalengkaGO icons & logos created successfully!\n";
