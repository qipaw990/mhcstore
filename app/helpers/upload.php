<?php
/**
 * Safe File Upload Helper
 * CicalengkaGO Multi-Vendor Delivery Platform
 */

function upload_image(array $file, string $folder = 'general'): ?string
{
    if (!isset($file['tmp_name']) || empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $folder = trim($folder, '/\\');
    $publicDir = defined('PUBLIC_PATH') ? PUBLIC_PATH : (defined('BASE_PATH') ? BASE_PATH . '/public' : __DIR__ . '/../../public');
    $targetDir = $publicDir . '/uploads/' . $folder;

    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0777, true);
        @chmod($targetDir, 0777);
    }

    if (!is_dir($targetDir) || !is_writable($targetDir)) {
        @chmod($targetDir, 0777);
        $parentDir = $publicDir . '/uploads';
        if (is_dir($parentDir) && !is_writable($parentDir)) {
            @chmod($parentDir, 0777);
        }
    }

    // MIME detection with multiple fallbacks
    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = (string)@finfo_file($finfo, $file['tmp_name']);
            @finfo_close($finfo);
        }
    }
    if (empty($mime) && function_exists('mime_content_type')) {
        $mime = (string)@mime_content_type($file['tmp_name']);
    }
    if (empty($mime)) {
        $mime = (string)($file['type'] ?? '');
    }

    $origExt = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml', 'image/x-png', 'image/pjpeg'];

    $isValid = in_array(strtolower($mime), $allowedMimes) || in_array($origExt, $allowedExts);
    if (!$isValid) {
        return null;
    }

    $ext = match (strtolower($mime)) {
        'image/jpeg', 'image/pjpeg' => 'jpg',
        'image/png', 'image/x-png'  => 'png',
        'image/webp'                => 'webp',
        'image/gif'                 => 'gif',
        'image/svg+xml'             => 'svg',
        default                     => in_array($origExt, $allowedExts) ? $origExt : 'jpg'
    };

    // Convert standard raster images to ultra-compact WebP (85%+ smaller file size)
    $outputExt = $ext;
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) && function_exists('imagewebp')) {
        $outputExt = 'webp';
    }

    $filename = uniqid('img_', true) . '.' . $outputExt;
    $destination = $targetDir . '/' . $filename;

    // Strategy 0: High-Performance Image Compression & Downscaling (GD)
    if ($ext !== 'svg' && compress_and_resize_image($file['tmp_name'], $destination, 1200, 1200, 80)) {
        @chmod($destination, 0664);
        return 'uploads/' . $folder . '/' . $filename;
    }

    // Strategy 1: move_uploaded_file fallback
    $fallbackDest = $targetDir . '/' . uniqid('img_', true) . '.' . $ext;
    if (@move_uploaded_file($file['tmp_name'], $fallbackDest)) {
        @chmod($fallbackDest, 0664);
        return 'uploads/' . $folder . '/' . basename($fallbackDest);
    }

    // Strategy 2: copy / file_put_contents fallback
    $fileData = @file_get_contents($file['tmp_name']);
    if ($fileData !== false && strlen($fileData) > 0) {
        if (@file_put_contents($fallbackDest, $fileData) !== false) {
            @chmod($fallbackDest, 0664);
            return 'uploads/' . $folder . '/' . basename($fallbackDest);
        }
    }

    return null;
}

/**
 * High-Performance Image Resizer and WebP/JPEG Compressor.
 */
function compress_and_resize_image(string $sourcePath, string $destinationPath, int $maxWidth = 1200, int $maxHeight = 1200, int $quality = 80): bool
{
    if (!extension_loaded('gd')) {
        return false;
    }

    $imageInfo = @getimagesize($sourcePath);
    if (!$imageInfo) {
        return false;
    }

    $origWidth  = (int)($imageInfo[0] ?? 0);
    $origHeight = (int)($imageInfo[1] ?? 0);
    $mimeType   = (string)($imageInfo['mime'] ?? '');

    if ($origWidth <= 0 || $origHeight <= 0) {
        return false;
    }

    // Fast stream loader based on mime type
    $srcImg = match ($mimeType) {
        'image/jpeg', 'image/pjpeg' => @imagecreatefromjpeg($sourcePath),
        'image/png', 'image/x-png'  => @imagecreatefrompng($sourcePath),
        'image/webp'                => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : null,
        'image/gif'                 => @imagecreatefromgif($sourcePath),
        default                     => null
    };

    if (!$srcImg) {
        $fileData = @file_get_contents($sourcePath);
        if ($fileData) {
            $srcImg = @imagecreatefromstring($fileData);
        }
    }

    if (!$srcImg) {
        return false;
    }

    $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight, 1.0);
    $newWidth  = (int)round($origWidth * $ratio);
    $newHeight = (int)round($origHeight * $ratio);

    $dstImg = imagecreatetruecolor($newWidth, $newHeight);
    $ext = strtolower(pathinfo($destinationPath, PATHINFO_EXTENSION));

    if (in_array($ext, ['png', 'webp', 'gif'], true)) {
        imagealphablending($dstImg, false);
        imagesavealpha($dstImg, true);
        $transparent = imagecolorallocatealpha($dstImg, 255, 255, 255, 127);
        imagefilledrectangle($dstImg, 0, 0, $newWidth, $newHeight, $transparent);
    } else {
        $white = imagecolorallocate($dstImg, 255, 255, 255);
        imagefilledrectangle($dstImg, 0, 0, $newWidth, $newHeight, $white);
    }

    imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

    $saved = false;
    if ($ext === 'webp' && function_exists('imagewebp')) {
        $saved = @imagewebp($dstImg, $destinationPath, $quality);
    } elseif ($ext === 'png') {
        $saved = @imagepng($dstImg, $destinationPath, 4);
    } else {
        $saved = @imagejpeg($dstImg, $destinationPath, $quality);
    }

    imagedestroy($srcImg);
    imagedestroy($dstImg);

    return $saved;
}

/**
 * Downloads a remote image from URL and saves it to local public/uploads directory.
 */
function download_and_save_image(string $url, string $folder = 'general'): string
{
    $url = trim($url);
    if (empty($url)) {
        return '';
    }

    // If it's already a local relative path (e.g. uploads/stores/img_xxx.jpg), return as is
    if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
        return $url;
    }

    // If it's hosted on our own server domain, return as is
    $host = parse_url($url, PHP_URL_HOST);
    if ($host && in_array(strtolower($host), ['cicago.store', 'localhost', '127.0.0.1'])) {
        return $url;
    }

    $folder = trim($folder, '/\\');
    $targetDir = PUBLIC_PATH . '/uploads/' . $folder;

    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0777, true);
        @chmod($targetDir, 0777);
    }

    // Download image using cURL
    $imgData = null;
    $contentType = '';
    
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        $imgData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $imgData = null;
        }
    }

    if (empty($imgData)) {
        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n",
                "timeout" => 10
            ],
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false
            ]
        ];
        $context = stream_context_create($opts);
        $imgData = @file_get_contents($url, false, $context);
    }

    if (empty($imgData)) {
        return $url; // Return original remote URL if download fails
    }

    // Determine extension
    $ext = 'jpg';
    if (str_contains($contentType, 'png')) $ext = 'png';
    elseif (str_contains($contentType, 'webp')) $ext = 'webp';
    elseif (str_contains($contentType, 'gif')) $ext = 'gif';
    else {
        $pathExt = pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION);
        if (in_array(strtolower($pathExt), ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $ext = strtolower($pathExt);
        }
    }

    $filename = uniqid('grab_', true) . '.' . $ext;
    $destination = $targetDir . '/' . $filename;

    if (@file_put_contents($destination, $imgData)) {
        @chmod($destination, 0664);
        @compress_and_resize_image($destination, $destination, 1200, 1200, 80);
        return 'uploads/' . $folder . '/' . $filename;
    }

    return $url;
}

