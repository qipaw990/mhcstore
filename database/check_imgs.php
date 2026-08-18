<?php
$config = require __DIR__ . '/../app/config/database.php';
$pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}", $config['username'], $config['password']);

$stmtS = $pdo->query("SELECT id, name, logo, cover_photo FROM stores ORDER BY id DESC LIMIT 5");
echo "=== LATEST STORES ===\n";
print_r($stmtS->fetchAll(PDO::FETCH_ASSOC));

$stmtP = $pdo->query("SELECT id, store_id, name, image FROM products ORDER BY id DESC LIMIT 20");
echo "=== LATEST PRODUCTS ===\n";
print_r($stmtP->fetchAll(PDO::FETCH_ASSOC));
