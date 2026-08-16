<?php
/**
 * Safe File Upload Helper
 */

function upload_image(array $file, string $folder = 'general'): ?string
{
    if (!isset($file['tmp_name']) || empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $targetDir = PUBLIC_PATH . '/uploads/' . trim($folder, '/');
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedMimes)) {
        return null;
    }

    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        default      => 'jpg'
    };

    $filename = uniqid('img_', true) . '.' . $ext;
    $destination = $targetDir . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return 'uploads/' . trim($folder, '/') . '/' . $filename;
    }

    return null;
}
