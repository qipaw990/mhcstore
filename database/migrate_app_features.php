<?php
/**
 * CLI Migration Runner untuk tabel `app_features` (Fitur & Layanan Aplikasi Flutter)
 * Digunakan oleh update.sh di CasaOS / Docker.
 */

define('APP_PATH', dirname(__DIR__) . '/app');
require_once dirname(__DIR__) . '/app/core/Database.php';

use App\Core\Database;

echo "---------------------------------------------------------\n";
echo " CicalengkaGO — Migrasi Tabel app_features ke Database\n";
echo "---------------------------------------------------------\n";

try {
    $pdo = Database::getPdo();

    $sqlFile = __DIR__ . '/migrate_app_features.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("File {$sqlFile} tidak ditemukan!");
    }

    $sql = file_get_contents($sqlFile);

    // Jalankan query statement per statement
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($s) => !empty($s) && !preg_match('/^--/', $s)
    );

    foreach ($statements as $stmt) {
        if (!empty(trim($stmt))) {
            try {
                $pdo->exec($stmt);
            } catch (\Throwable $e) {
                // Abaikan jika error 1062 duplicate key saat re-migrasi
                if (strpos($e->getMessage(), '1062') === false) {
                    echo "[!] Warning: " . $e->getMessage() . "\n";
                }
            }
        }
    }

    // Verifikasi jumlah baris yang berhasil di-seed
    $count = $pdo->query("SELECT COUNT(*) FROM `app_features`")->fetchColumn();
    echo "[✓] Tabel `app_features` berhasil dimigrasi! Total {$count} fitur terdaftar.\n";
    echo "---------------------------------------------------------\n";
} catch (\Throwable $e) {
    echo "[X] Error migrasi app_features: " . $e->getMessage() . "\n";
    exit(1);
}
