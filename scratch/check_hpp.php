<?php
$pdo = new PDO('mysql:host=localhost;dbname=cicalengkago', 'root', '');
$stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'hpp'");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($cols)) {
    $pdo->exec("ALTER TABLE products ADD COLUMN hpp DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'HPP per unit' AFTER price");
    echo "MIGRATED: kolom hpp berhasil ditambahkan!\n";
} else {
    echo "OK: kolom hpp sudah ada => " . json_encode($cols[0]) . "\n";
}
// Cek juga beberapa produk
$rows = $pdo->query("SELECT id, name, price, hpp FROM products LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
echo "Sample products:\n";
foreach ($rows as $r) {
    echo "  [{$r['id']}] {$r['name']} - Harga: {$r['price']}, HPP: {$r['hpp']}\n";
}
