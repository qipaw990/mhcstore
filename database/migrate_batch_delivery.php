<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=cicalengkago_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // orders table
    $cols = $pdo->query('SHOW COLUMNS FROM orders')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('delivery_batch_id', $cols)) {
        $pdo->exec('ALTER TABLE `orders`
            ADD COLUMN `delivery_batch_id` VARCHAR(24) NULL DEFAULT NULL,
            ADD COLUMN `pickup_sequence`   TINYINT UNSIGNED NOT NULL DEFAULT 1,
            ADD INDEX  `idx_batch` (`delivery_batch_id`)');
        echo "orders: columns added\n";
    } else {
        echo "orders: already exist\n";
    }

    // delivery_men table
    $cols2 = $pdo->query('SHOW COLUMNS FROM delivery_men')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('active_batch_id', $cols2)) {
        $pdo->exec('ALTER TABLE `delivery_men`
            ADD COLUMN `active_batch_id`  VARCHAR(24) NULL DEFAULT NULL,
            ADD COLUMN `active_order_ids` TEXT NULL DEFAULT NULL');
        echo "delivery_men: columns added\n";
    } else {
        echo "delivery_men: already exist\n";
    }

    echo "Migration done.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
