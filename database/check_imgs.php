<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=cicalengkago_db;charset=utf8mb4', 'root', '');
$prods = $pdo->query('SELECT p.id, p.store_id, s.name as store_name, p.name, p.image FROM products p JOIN stores s ON p.store_id = s.id')->fetchAll(PDO::FETCH_ASSOC);
echo "TOTAL PRODUCTS: " . count($prods) . "\n\n";
foreach ($prods as $p) {
    echo "ID: {$p['id']} | Store: {$p['store_name']} | Product: {$p['name']} | Image: {$p['image']}\n";
}
