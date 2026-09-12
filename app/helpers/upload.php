<?php
/**
 * Safe File Upload Helper v2 — with Base64 JSON fallback & structured logging.
 *
 * Kenapa butuh base64 fallback? Karena Frontend (Flutter / PWA / React) kadang
 * mengirim file sebagai `data:image/jpeg;base64,xxx...` di JSON body
 * (bukan multipart/form-data), sehingga $_FILES kosong dan upload
 * di-skip SILENTLY — inilah PENYEBAB UTAMA "foto tidak tersimpan".
 */
use App\Core\Database;

/**
 * Helper: Pastikan folder upload ada & writable. Return absolute targetDir.
 */
function _upload_ensure_dir(string $folder): string
{
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
    if (!is_dir($targetDir)) {
        error_log("[UPLOAD] Tidak bisa membuat / akses folder: {$targetDir}");
    }
    return $targetDir;
}

/**
 * Helper: Log upload failure ke tabel upload_logs (auto-create).
 * Karena banyak @(error-suppress) — ini cara agar kita punya visibility.
 */
function _upload_log(string $level, string $folder, string $fieldName, string $message, ?int $userId = null): void
{
    try {
        static $tableEnsured = false;
        if (!$tableEnsured) {
            @Database::execute("
                CREATE TABLE IF NOT EXISTS `upload_logs` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    `level` VARCHAR(16) NOT NULL DEFAULT 'info',
                    `folder` VARCHAR(64) NOT NULL DEFAULT '',
                    `field_name` VARCHAR(128) NOT NULL DEFAULT '',
                    `user_id` INT UNSIGNED NULL,
                    `message` VARCHAR(500) NOT NULL DEFAULT '',
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    KEY `idx_field_folder` (`field_name`,`folder`),
                    KEY `idx_created_at` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            $tableEnsured = true;
        }
        @Database::insert('upload_logs', [
            'level'      => $level,
            'folder'     => $folder,
            'field_name' => $fieldName,
            'user_id'    => $userId,
            'message'    => substr($message, 0, 500),
        ]);
    } catch (\Throwable $e) { /* logging gagal tidak boleh halt */ }

    if ($level !== 'info') {
        error_log("[UPLOAD][{$level}] folder={$folder} field={$fieldName} uid=" . ($userId ?? 'null') . " | " . substr($message, 0, 500));
    }
}

/**
 * Helper: Tentukan extension berdasarkan MIME (validasi aman).
 */
function _upload_resolve_ext(string $mime, string $origExt = ''): ?string
{
    $allowedExts  = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/x-png', 'image/pjpeg'];

    $safeOrig = strtolower(trim($origExt, '.'));
    if (!in_array($safeOrig, $allowedExts, true)) $safeOrig = '';
    $safeMime = strtolower($mime);

    if (!in_array($safeMime, $allowedMimes, true) && $safeOrig === '') {
        return null; // benar-benar invalid
    }

    return match ($safeMime) {
        'image/jpeg', 'image/pjpeg' => 'jpg',
        'image/png', 'image/x-png'  => 'png',
        'image/webp'                => 'webp',
        'image/gif'                 => 'gif',
        default                     => $safeOrig !== '' ? $safeOrig : 'jpg'
    };
}

/**
 * Helper: Simpan binary gambar ke file — jalankan strategy resize/move sama seperti upload_image.
 * Return relative path jika sukses.
 */
function _upload_save_binary(string $binary, string $folder, string $ext, ?int $userId = null, string $fieldName = 'unknown'): ?string
{
    if (strlen($binary) < 100) {
        _upload_log('error', $folder, $fieldName, "Binary terlalu kecil (byte < 100), dianggap corrupt.", $userId);
        return null;
    }

    $targetDir = _upload_ensure_dir($folder);
    if (!is_dir($targetDir) || !is_writable($targetDir)) {
        _upload_log('critical', $folder, $fieldName, "Folder {$targetDir} tidak writable / tidak ada (cek permission).", $userId);
        return null;
    }

    // Convert output ke webp jika didukung
    $outputExt = $ext;
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) && function_exists('imagewebp')) {
        $outputExt = 'webp';
    }

    $filename = uniqid('img_', true) . '.' . $outputExt;
    $destination = $targetDir . '/' . $filename;

    // Strategy 0: Tulis binary ke temp file → compress_and_resize_image
    $tmpName = @tempnam(sys_get_temp_dir(), 'ccgup_');
    if ($tmpName !== false) {
        @file_put_contents($tmpName, $binary);
        if (compress_and_resize_image($tmpName, $destination, 1200, 1200, 80)) {
            @chmod($destination, 0664);
            @unlink($tmpName);
            _upload_log('success', $folder, $fieldName, "Berhasil (strategy compress-resize): uploads/{$folder}/{$filename}", $userId);
            return 'uploads/' . $folder . '/' . $filename;
        }
        // Strategy 0 gagal → coba pindahkan file tmp ke destination
        $altDest = $targetDir . '/' . uniqid('img_', true) . '.' . $ext;
        if (@rename($tmpName, $altDest)) {
            @chmod($altDest, 0664);
            _upload_log('success', $folder, $fieldName, "Berhasil (strategy rename tmp): uploads/{$folder}/" . basename($altDest), $userId);
            return 'uploads/' . $folder . '/' . basename($altDest);
        }
        @unlink($tmpName);
    }

    // Strategy 1: file_put_contents langsung
    $altDest = $targetDir . '/' . uniqid('img_', true) . '.' . $ext;
    if (@file_put_contents($altDest, $binary) !== false) {
        @chmod($altDest, 0664);
        _upload_log('success', $folder, $fieldName, "Berhasil (strategy file_put_contents): uploads/{$folder}/" . basename($altDest), $userId);
        return 'uploads/' . $folder . '/' . basename($altDest);
    }

    _upload_log('error', $folder, $fieldName, "SEMUA strategy simpan file gagal (compress/tmp/file_put_contents).", $userId);
    return null;
}

/**
 * Upload via $_FILES (multipart/form-data) — dengan logging & validasi.
 * @param array  $file  Element $_FILES['field']
 * @param string $folder
 * @param string $fieldName Nama field (untuk logging)
 * @param ?int   $userId
 * @return ?string Relative path (uploads/folder/xxx.webp) atau null jika gagal
 */
function upload_image(array $file, string $folder = 'general', string $fieldName = 'image', ?int $userId = null): ?string
{
    if (!isset($file['tmp_name']) || empty($file['tmp_name']) || !isset($file['error'])) {
        _upload_log('warning', $folder, $fieldName, "Upload tidak valid (tmp_name kosong / error tidak set).", $userId);
        return null;
    }

    if ((int)($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $errMap = [
            UPLOAD_ERR_INI_SIZE   => 'File melebihi upload_max_filesize php.ini',
            UPLOAD_ERR_FORM_SIZE  => 'File melebihi MAX_FILE_SIZE form',
            UPLOAD_ERR_PARTIAL    => 'Upload sebagian (terputus)',
            UPLOAD_ERR_NO_FILE    => 'Tidak ada file diupload',
            UPLOAD_ERR_NO_TMP_DIR => 'PHP tmp folder tidak ditemukan',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis disk',
            UPLOAD_ERR_EXTENSION  => 'Diblok extension PHP',
        ];
        $msg = $errMap[(int)$file['error']] ?? ("Kode error: " . (int)$file['error']);
        _upload_log('error', $folder, $fieldName, "UPLOAD_ERR — {$msg}", $userId);
        return null;
    }

    $targetDir = _upload_ensure_dir($folder);
    if (!is_dir($targetDir) || !is_writable($targetDir)) {
        _upload_log('critical', $folder, $fieldName, "Folder {$targetDir} tidak writable / tidak ada.", $userId);
        return null;
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
    $ext = _upload_resolve_ext($mime, $origExt);
    if ($ext === null) {
        _upload_log('warning', $folder, $fieldName, "MIME/ext tidak diizinkan. mime={$mime} ext={$origExt}", $userId);
        return null;
    }

    $outputExt = $ext;
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) && function_exists('imagewebp')) {
        $outputExt = 'webp';
    }

    $filename = uniqid('img_', true) . '.' . $outputExt;
    $destination = $targetDir . '/' . $filename;

    // Strategy 0: High-Performance Image Compression & Downscaling (GD)
    if (compress_and_resize_image($file['tmp_name'], $destination, 1200, 1200, 80)) {
        @chmod($destination, 0664);
        _upload_log('success', $folder, $fieldName, "Berhasil (compress-resize GD): uploads/{$folder}/{$filename}", $userId);
        return 'uploads/' . $folder . '/' . $filename;
    }
    _upload_log('warning', $folder, $fieldName, "Strategy 0 (GD compress) gagal — lanjut fallback 1.", $userId);

    // Strategy 1: move_uploaded_file fallback
    $fallbackDest = $targetDir . '/' . uniqid('img_', true) . '.' . $ext;
    if (@move_uploaded_file($file['tmp_name'], $fallbackDest)) {
        @chmod($fallbackDest, 0664);
        _upload_log('success', $folder, $fieldName, "Berhasil (move_uploaded_file): uploads/{$folder}/" . basename($fallbackDest), $userId);
        return 'uploads/' . $folder . '/' . basename($fallbackDest);
    }

    // Strategy 2: copy / file_put_contents fallback
    $fileData = @file_get_contents($file['tmp_name']);
    if ($fileData !== false && strlen($fileData) > 0) {
        if (@file_put_contents($fallbackDest, $fileData) !== false) {
            @chmod($fallbackDest, 0664);
            _upload_log('success', $folder, $fieldName, "Berhasil (file_put_contents tmp): uploads/{$folder}/" . basename($fallbackDest), $userId);
            return 'uploads/' . $folder . '/' . basename($fallbackDest);
        }
    }

    _upload_log('error', $folder, $fieldName, "SEMUA strategy (compress/move/file_put) GAGAL. Cek disk space & permission.", $userId);
    return null;
}

/**
 * Upload dari string Base64 (format `data:image/jpeg;base64,xxx`) atau raw binary string.
 * Sering digunakan oleh Flutter / React yang mengirim file via JSON body.
 *
 * @param string $payload  data:image/xxx;base64,xxxx atau binary raw atau https:// URL
 * @param string $folder
 * @param string $fieldName Nama field (untuk logging)
 * @param ?int $userId
 * @return ?string Relative path jika sukses
 */
function upload_image_from_base64(string $payload, string $folder = 'general', string $fieldName = 'image_base64', ?int $userId = null): ?string
{
    $payload = trim($payload);
    if (empty($payload)) return null;

    // Jika ternyata URL (http/https), coba download terlebih dahulu
    if (str_starts_with(strtolower($payload), 'http://') || str_starts_with(strtolower($payload), 'https://')) {
        $saved = download_and_save_image($payload, $folder);
        if (!empty($saved) && (str_starts_with($saved, 'uploads/') || file_exists((_upload_ensure_dir('..')) . '/../' . $saved))) {
            _upload_log('success', $folder, $fieldName, "Berhasil via download_and_save_image: {$saved}", $userId);
            return $saved;
        }
    }

    // Jika sudah relative path (uploads/folder/xxx.webp) → biarkan (tidak perlu upload ulang)
    if (preg_match('#^uploads/\S+\.(jpg|jpeg|png|webp|gif)$#i', $payload) ||
        preg_match('#^assets/images/\S+\.(jpg|jpeg|png|webp|gif)$#i', $payload)) {
        return $payload;
    }

    $binary = null;
    $mimeHint = '';
    $origExt = '';

    // Format: data:image/png;base64,xxxxxxx
    if (stripos($payload, 'data:image/') === 0) {
        if (preg_match('#^data:(image/[a-z0-9\.\-\+]+);base64,(.+)$#is', $payload, $matches)) {
            $mimeHint = strtolower($matches[1]);
            $b64Part = str_replace([' ', "\t", "\n", "\r"], '', $matches[2]);
            $bin = base64_decode($b64Part, true);
            if ($bin !== false && strlen($bin) > 100) {
                $binary = $bin;
            }
        }
        // Jika hanya `data:image/png,xxx` (tanpa base64) -> raw urlencode
        if ($binary === null && preg_match('#^data:(image/[a-z0-9\.\-\+]+),(.+)$#is', $payload, $matches)) {
            $mimeHint = strtolower($matches[1]);
            $binary = rawurldecode($matches[2]);
            if (strlen($binary) < 100) $binary = null;
        }
    }

    // Fallback: coba decode seluruh string sebagai base64 (mobile app terkadang kirim pure b64 tanpa header)
    if ($binary === null) {
        $b64Clean = preg_replace('#^[^A-Za-z0-9+/=]+#', '', rtrim($payload));
        if (preg_match('#^[A-Za-z0-9+/=]+$#', $b64Clean) && strlen($b64Clean) > 200) {
            $bin = @base64_decode($b64Clean, true);
            if ($bin !== false && strlen($bin) > 200) {
                // Cek magic byte untuk MIME
                if (str_starts_with($bin, "\xFF\xD8\xFF")) {
                    $mimeHint = 'image/jpeg';
                } elseif (str_starts_with($bin, "\x89PNG\r\n\x1a\n")) {
                    $mimeHint = 'image/png';
                } elseif (str_starts_with($bin, 'RIFF') && str_contains(substr($bin, 0, 16), 'WEBP')) {
                    $mimeHint = 'image/webp';
                } elseif (str_starts_with($bin, 'GIF87a') || str_starts_with($bin, 'GIF89a')) {
                    $mimeHint = 'image/gif';
                } else {
                    _upload_log('warning', $folder, $fieldName, "Base64 di-decode tapi magic byte tidak dikenali (len=" . strlen($bin) . ").", $userId);
                }
                $binary = $bin;
            }
        }
    }

    if ($binary === null || strlen($binary) < 200) {
        _upload_log('warning', $folder, $fieldName, "Payload bukan base64 image valid (len < 200). PayloadLen=" . strlen($payload), $userId);
        return null;
    }

    // Tentukan ext & simpan
    $ext = _upload_resolve_ext($mimeHint, $origExt);
    if ($ext === null) {
        // Mungkin image valid tapi mime detect belum, coba infer dari magic bytes fallback
        if (str_starts_with($binary, "\xFF\xD8\xFF")) $ext = 'jpg';
        elseif (str_starts_with($binary, "\x89PNG")) $ext = 'png';
        elseif (str_starts_with($binary, 'RIFF')) $ext = 'webp';
        elseif (str_starts_with($binary, 'GIF8')) $ext = 'gif';
    }
    if ($ext === null) {
        _upload_log('warning', $folder, $fieldName, "Tidak bisa menentukan extension mime={$mimeHint}.", $userId);
        return null;
    }

    _upload_log('info', $folder, $fieldName, "Mendeteksi base64 valid mime={$mimeHint} ext={$ext} binaryLen=" . strlen($binary), $userId);
    return _upload_save_binary($binary, $folder, $ext, $userId, $fieldName);
}

/**
 * SMART UPLOAD — solusi satu-untuk-semua.
 *
 * Urutan pengecekan (yang pertama valid, yang dipakai):
 *   1. $_FILES[$fieldName] → panggil upload_image()
 *   2. Jika ada alias list → cek $_FILES[alias] juga (misal 'logo' / 'store_logo')
 *   3. $body[$fieldName] → jika `data:image...;base64,...` → panggil upload_image_from_base64()
 *   4. $body[alias] → sama cek base64-nya
 *   5. Semua gagal → return $defaultExisting
 *
 * Ini adalah function YANG SEHARUSNYA dipanggil di semua controller.
 *
 * @param string       $fieldName   Nama field utama (misal 'image')
 * @param string       $folder      Target folder (products/stores/ktp/profiles)
 * @param string|null  $defaultExisting Default value jika tidak ada upload baru (misal existing image di DB)
 * @param array        $aliasList   Daftar alias nama field (misal ['store_logo','storeLogo','merchant_logo'])
 * @param array|null   $body        Body request (array dari getPost() / json_decode)
 * @param int|null     $userId
 * @return string|null Path relative upload (uploads/...) atau defaultExisting
 */
function smart_upload_field(
    string  $fieldName,
    string  $folder,
    ?string $defaultExisting = null,
    array   $aliasList = [],
    ?array  $body = null,
    ?int    $userId = null
): ?string
{
    $allNames = array_merge([$fieldName], $aliasList);

    // === 1 & 2: Cek $_FILES (multipart form) ===
    foreach ($allNames as $fn) {
        if (isset($_FILES[$fn]) && is_array($_FILES[$fn])) {
            $err = (int)($_FILES[$fn]['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($err === UPLOAD_ERR_OK && !empty($_FILES[$fn]['tmp_name'])) {
                $result = upload_image($_FILES[$fn], $folder, $fn, $userId);
                if ($result !== null && $result !== '') {
                    return $result;
                }
                // Kalau upload_image return null (gd library tak ada / permission)
                // coba method primitive langsung
                $targetDir = _upload_ensure_dir($folder);
                $ext = strtolower(pathinfo($_FILES[$fn]['name'] ?? 'img.jpg', PATHINFO_EXTENSION));
                $safeExt = in_array($ext, ['jpg','jpeg','png','webp','gif'], true) ? $ext : 'jpg';
                $fname = uniqid('img_smart_', true) . '.' . $safeExt;
                $dest = $targetDir . '/' . $fname;
                if (@move_uploaded_file($_FILES[$fn]['tmp_name'], $dest)) {
                    @chmod($dest, 0664);
                    _upload_log('success', $folder, $fn, "Berhasil (smart_upload primitive move): uploads/{$folder}/{$fname}", $userId);
                    return 'uploads/' . $folder . '/' . $fname;
                }
            } elseif ($err !== UPLOAD_ERR_NO_FILE) {
                _upload_log('warning', $folder, $fn, "\$_FILES ada tapi error code {$err}.", $userId);
            }
        }
    }

    // === 3 & 4: Cek body JSON (base64 / URL / existing path) ===
    if ($body !== null && is_array($body)) {
        foreach ($allNames as $fn) {
            if (isset($body[$fn]) && is_string($body[$fn]) && trim($body[$fn]) !== '') {
                $val = trim($body[$fn]);

                // Explicit opt-out: user kirim string '__KEEP__' / 'null' → jangan upload apa-apa
                if ($val === '__KEEP__' || $val === 'same' || $val === 'keep') {
                    return $defaultExisting;
                }

                // Jika user kirim path existing (misal 'uploads/stores/xxx.webp') biarkan, TAPI jika
                // defaultExisting berbeda → user bermaksud mengganti, jadi biarkan return ini.
                if (preg_match('#^uploads/\S+\.(jpg|jpeg|png|webp|gif)$#i', $val) ||
                    preg_match('#^assets/images/\S+\.(jpg|jpeg|png|webp|gif)$#i', $val)) {
                    return $val;
                }

                $result = upload_image_from_base64($val, $folder, $fn, $userId);
                if ($result !== null && $result !== '') {
                    return $result;
                }
            }
        }
    }

    // === 5: Tidak ada upload baru ===
    return $defaultExisting;
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

