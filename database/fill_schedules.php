<?php
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
require_once APP_PATH . '/autoload.php';

try {
    $pdo = \App\Core\Database::getPdo();
} catch (\Throwable $e) {
    try {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=cicalengkago_db;charset=utf8mb4', 'root', '');
    } catch (\Throwable $e2) {
        die("Connection error: " . $e2->getMessage());
    }
}

$stores = $pdo->query('SELECT id, name FROM stores')->fetchAll(PDO::FETCH_ASSOC);
$isCli = (php_sapi_name() === 'cli');
$nl = $isCli ? "\n" : "<br>";

echo "Populating store schedules for all " . count($stores) . " stores..." . $nl;

foreach ($stores as $s) {
    $sid = $s['id'];
    for ($d = 0; $d <= 6; $d++) {
        $stmt = $pdo->prepare('SELECT id FROM store_schedules WHERE store_id = ? AND day_of_week = ?');
        $stmt->execute([$sid, $d]);
        if (!$stmt->fetch()) {
            $ins = $pdo->prepare("INSERT INTO store_schedules (store_id, day_of_week, opening_time, closing_time) VALUES (?, ?, '08:00:00', '22:00:00')");
            $ins->execute([$sid, $d]);
        }
    }
}

echo "✅ Success! All stores now have complete opening and closing times." . $nl;
