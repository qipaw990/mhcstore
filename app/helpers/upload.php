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
    $targetDir = PUBLIC_PATH . '/uploads/' . $folder;

    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0777, true);
        @chmod($targetDir, 0777);
    }

    if (!is_dir($targetDir) || !is_writable($targetDir)) {
        @chmod($targetDir, 0777);
        // If still not writable, try parent directory permissions
        $parentDir = PUBLIC_PATH . '/uploads';
        if (is_dir($parentDir) && !is_writable($parentDir)) {
            @chmod($parentDir, 0777);
        }
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

    if (@move_uploaded_file($file['tmp_name'], $destination)) {
        @chmod($destination, 0664);
        return 'uploads/' . $folder . '/' . $filename;
    }

    return null;
}
