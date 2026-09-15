<?php
/**
 * CLI Migration Runner untuk tabel `app_features` (Fitur & Layanan Aplikasi Flutter)
 * Digunakan oleh update.sh di CasaOS / Docker.
 */

if (!defined('APP_PATH')) {
    define('APP_PATH', dirname(__DIR__) . '/app');
}

echo "---------------------------------------------------------\n";
echo " CicalengkaGO — Migrasi Tabel app_features ke Database\n";
echo "---------------------------------------------------------\n";

try {
    $pdo = null;

    // 1. Coba lewat App\Core\Database jika tersedia
    try {
        require_once dirname(__DIR__) . '/app/core/Database.php';
        $pdo = \App\Core\Database::getPdo();
    } catch (\Throwable $e) {
        $pdo = null;
    }

    // 2. Fallback koneksi PDO manual jika Database.php gagal
    if (!$pdo) {
        $host   = getenv('DB_HOST') ?: 'cicalengkago_db';
        $dbname = getenv('DB_DATABASE') ?: getenv('DB_NAME') ?: 'cicalengkago';
        $user   = getenv('DB_USERNAME') ?: getenv('DB_USER') ?: 'cicalengka_user';
        $pass   = getenv('DB_PASSWORD') ?: getenv('DB_PASS') ?: 'cicalengka_pass';
        $port   = getenv('DB_PORT') ?: '3306';

        $candidateHosts = array_unique(array_filter([$host, 'cicalengkago_db', '127.0.0.1', 'localhost']));
        foreach ($candidateHosts as $h) {
            try {
                $dsn = "mysql:host={$h};port={$port};dbname={$dbname};charset=utf8mb4";
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                break;
            } catch (\Throwable $t) {
                // Coba dengan root password jika user biasa gagal
                try {
                    $pdo = new PDO("mysql:host={$h};port={$port};dbname={$dbname};charset=utf8mb4", 'root', 'rootpassword', [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]);
                    break;
                } catch (\Throwable $t2) {}
            }
        }
    }

    if (!$pdo) {
        throw new Exception("Tidak dapat terhubung ke MySQL database.");
    }

    $sqlFile = __DIR__ . '/migrate_app_features.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("File {$sqlFile} tidak ditemukan!");
    }

    $sql = file_get_contents($sqlFile);

    // Bersihkan semua komentar SQL (-- comment) dan (/* comment */)
    $sqlClean = preg_replace('/--.*$/m', '', $sql);
    $sqlClean = preg_replace('/\/\*.*?\*\//s', '', $sqlClean);

    // Pisahkan statement berdasarkan titik koma (;)
    $statements = array_filter(
        array_map('trim', explode(';', $sqlClean)),
        fn($s) => !empty($s)
    );

    $executed = 0;
    foreach ($statements as $stmt) {
        if (!empty(trim($stmt))) {
            try {
                $pdo->exec($stmt);
                $executed++;
            } catch (\Throwable $e) {
                // Abaikan jika duplicate key 1062
                if (strpos($e->getMessage(), '1062') === false) {
                    echo "[!] Notice: " . $e->getMessage() . "\n";
                }
            }
        }
    }

    // Verifikasi jumlah data yang ada di tabel
    $count = $pdo->query("SELECT COUNT(*) FROM `app_features`")->fetchColumn();
    echo "[✓] Migrasi selesai! {$executed} query dijalankan. Total {$count} fitur aktif di database.\n";
    echo "---------------------------------------------------------\n";

} catch (\Throwable $e) {
    echo "[X] Error migrasi app_features: " . $e->getMessage() . "\n";
    exit(1);
}
