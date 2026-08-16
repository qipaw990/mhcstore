<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
    $dbs = $pdo->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($dbs as $db) echo $db . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
