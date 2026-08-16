<?php
$databases = ['cicago', 'cicalengkago_db'];

try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
    foreach ($databases as $db) {
        echo "=== DATABASE: $db ===" . PHP_EOL;
        try {
            $pdo->exec("USE `$db`");
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($tables as $table) {
                $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                echo "  $table: $count rows" . PHP_EOL;
            }
        } catch (Exception $e) {
            echo "  Error: " . $e->getMessage() . PHP_EOL;
        }
        echo PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Connection Error: ' . $e->getMessage();
}
