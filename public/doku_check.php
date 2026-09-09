<?php
/**
 * Diagnosa DOKU - Hapus file ini setelah digunakan!
 * Akses: https://cicago.store/doku_check.php?key=cek2024doku
 */

$pass = $_GET['key'] ?? '';
if ($pass !== 'cek2024doku') {
    die('Access denied. Tambahkan ?key=cek2024doku di URL');
}

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

try {
    $dbConfig = require APP_PATH . '/config/database.php';
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h2>✅ Koneksi DB Berhasil</h2>";
    echo "<h3>Setting DOKU di Database:</h3><table border='1' cellpadding='8' style='border-collapse:collapse'>";
    echo "<tr><th>Key</th><th>Value</th></tr>";

    $rows = $pdo->query("SELECT key_name, value_text FROM business_settings WHERE key_name LIKE 'doku%' ORDER BY key_name")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $val = $r['value_text'];
        if (strpos($r['key_name'], 'secret') !== false && strlen($val) > 8) {
            $val = substr($val, 0, 6) . str_repeat('*', strlen($val) - 6);
        }
        $isEmpty = empty($r['value_text']) ? ' ⚠️ KOSONG!' : '';
        echo "<tr><td><strong>{$r['key_name']}</strong></td><td>{$val}{$isEmpty}</td></tr>";
    }
    echo "</table>";

    echo "<h3>Test Koneksi ke DOKU API:</h3>";
    $clientId  = $pdo->query("SELECT value_text FROM business_settings WHERE key_name = 'doku_client_id' LIMIT 1")->fetchColumn();
    $secretKey = $pdo->query("SELECT value_text FROM business_settings WHERE key_name = 'doku_secret_key' LIMIT 1")->fetchColumn();
    $env       = $pdo->query("SELECT value_text FROM business_settings WHERE key_name = 'doku_environment' LIMIT 1")->fetchColumn();

    if (empty($clientId) || empty($secretKey)) {
        echo "<p style='color:red'>❌ <strong>Client ID atau Secret Key KOSONG!</strong> Ini penyebab error DOKU.</p>";
        echo "<p>👉 Isi di Admin Panel → Settings → Payment Gateway DOKU</p>";
    } else {
        $baseUrl = ($env === 'production') ? 'https://api.doku.com' : 'https://api-sandbox.doku.com';
        echo "<p>✅ Credentials tersedia. Environment: <strong>{$env}</strong></p>";
        echo "<p>Base URL: <code>{$baseUrl}</code></p>";

        $ch = curl_init($baseUrl . '/checkout/v1/payment');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode > 0) {
            echo "<p>✅ DOKU API reachable (HTTP {$httpCode})</p>";
        } else {
            echo "<p style='color:red'>❌ Tidak bisa terhubung ke DOKU API</p>";
        }
    }

} catch (Exception $e) {
    echo "<h2 style='color:red'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</h2>";
}

echo "<br><hr><small style='color:gray'>Hapus file ini (public/doku_check.php) setelah selesai diagnosa!</small>";
