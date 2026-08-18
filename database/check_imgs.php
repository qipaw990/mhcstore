<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=cicalengkago_db;charset=utf8mb4', 'root', '');
$s = $pdo->query('SELECT id, name, logo, cover_photo FROM stores WHERE name LIKE "%Berayan%" LIMIT 1')->fetch(PDO::FETCH_ASSOC);
print_r($s);

if ($s) {
    $prods = $pdo->query("SELECT id, name, image FROM products WHERE store_id = {$s['id']}")->fetchAll(PDO::FETCH_ASSOC);
    print_r($prods);
}
