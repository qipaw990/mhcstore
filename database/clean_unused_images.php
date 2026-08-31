<?php
/**
 * CicalengkaGO - Clean Unused Store & Product Images
 * Scans public/uploads/ directories and removes orphan image files not referenced in MySQL database.
 * 
 * Usage:
 *   php database/clean_unused_images.php
 *   php database/clean_unused_images.php [HOST] [USER] [PASS] [DBNAME] [PORT]
 */

$host   = $argv[1] ?? getenv('DB_HOST') ?: '127.0.0.1';
$user   = $argv[2] ?? getenv('DB_USERNAME') ?: getenv('DB_USER') ?: 'root';
$pass   = $argv[3] ?? getenv('DB_PASSWORD') ?: getenv('DB_PASS') ?: '';
$dbname = $argv[4] ?? getenv('DB_DATABASE') ?: getenv('DB_NAME') ?: 'cicalengkago_db';
$port   = $argv[5] ?? getenv('DB_PORT') ?: '3306';

echo "=========================================================\n";
echo " 🧹 CicalengkaGO — Pembersihan Gambar Tidak Terpakai\n";
echo "=========================================================\n";
echo " Host     : {$host}:{$port}\n";
echo " Database : {$dbname}\n";
echo "=========================================================\n\n";

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "[✓] Koneksi ke MySQL berhasil!\n\n";

    // 1. Kumpulkan semua nama file gambar yang sedang aktif di Database
    $activeImageFiles = [];

    $collectImages = function($query, $column) use ($pdo, &$activeImageFiles) {
        try {
            $stmt = $pdo->query($query);
            while ($row = $stmt->fetch()) {
                $val = trim((string)($row[$column] ?? ''));
                if (!empty($val)) {
                    $base = basename($val);
                    $activeImageFiles[$base] = true;
                }
            }
        } catch (\Exception $e) {
            // Table or column might not exist, silently continue
        }
    };

    echo "🔍 Mengumpulkan daftar gambar aktif dari database...\n";

    // Tabel stores (logo & cover_photo)
    $collectImages("SELECT logo FROM stores", "logo");
    $collectImages("SELECT cover_photo FROM stores", "cover_photo");

    // Tabel items / products (image)
    $collectImages("SELECT image FROM items", "image");
    $collectImages("SELECT image FROM products", "image");

    // Tabel banners (image)
    $collectImages("SELECT image FROM banners", "image");

    // Tabel users (avatar)
    $collectImages("SELECT avatar FROM users", "avatar");

    // Tabel categories & modules (image & thumbnail)
    $collectImages("SELECT image FROM categories", "image");
    $collectImages("SELECT thumbnail FROM modules", "thumbnail");

    echo "   [✓] Ditemukan " . count($activeImageFiles) . " file gambar yang aktif digunakan di database.\n\n";

    // 2. Scan folder fisik public/uploads/
    $baseUploadDir = defined('PUBLIC_PATH') ? PUBLIC_PATH . '/uploads' : dirname(__DIR__) . '/public/uploads';
    if (!is_dir($baseUploadDir)) {
        $baseUploadDir = '/var/www/html/public/uploads';
    }

    if (!is_dir($baseUploadDir)) {
        echo "❌ Direktori uploads tidak ditemukan di: {$baseUploadDir}\n";
        exit(1);
    }

    $targetFolders = ['stores', 'products', 'banners', 'profiles', 'general'];
    $protectedFiles = ['.gitkeep', 'default.png', 'customer.png', 'driver.png', 'store.png', 'banner.png', 'placeholder.png', 'index.html'];

    $totalScanned = 0;
    $totalDeleted = 0;
    $totalFreedBytes = 0;

    echo "🧹 Memulai pemindaian dan pembersihan folder uploads:\n";

    foreach ($targetFolders as $folder) {
        $dirPath = $baseUploadDir . '/' . $folder;
        if (!is_dir($dirPath)) {
            continue;
        }

        echo "   📁 Memeriksa folder: uploads/{$folder}...\n";
        $files = scandir($dirPath);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || in_array(strtolower($file), $protectedFiles, true)) {
                continue;
            }

            $filePath = $dirPath . '/' . $file;
            if (!is_file($filePath)) {
                continue;
            }

            $totalScanned++;
            $fileSize = filesize($filePath) ?: 0;

            // Jika file tidak terdaftar di database, hapus!
            if (!isset($activeImageFiles[$file])) {
                if (@unlink($filePath)) {
                    $totalDeleted++;
                    $totalFreedBytes += $fileSize;
                    echo "      🗑️ Dihapus: uploads/{$folder}/{$file} (" . round($fileSize / 1024, 1) . " KB)\n";
                }
            }
        }
    }

    $freedMB = round($totalFreedBytes / (1024 * 1024), 2);
    echo "\n=========================================================\n";
    echo " ✨ PEMBERSIHAN SELESAI!\n";
    echo " 📊 Total File Diperiksa  : {$totalScanned} file\n";
    echo " 🗑️ File Sampah Dihapus   : {$totalDeleted} file\n";
    echo " 💾 Ruang Disk Dihemat    : {$freedMB} MB\n";
    echo "=========================================================\n";

} catch (\PDOException $e) {
    echo "❌ Error Database: " . $e->getMessage() . "\n";
    exit(1);
}
