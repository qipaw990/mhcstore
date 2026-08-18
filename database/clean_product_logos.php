<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=cicalengkago_db;charset=utf8mb4', 'root', '');
$stmt = $pdo->query("UPDATE products SET image = NULL WHERE image LIKE '%merchants%' OR image LIKE '%hero%' OR image LIKE '%logo%'");
echo "Success: Cleaned " . $stmt->rowCount() . " product records that had store logos.\n";
