<?php
/**
 * Migration Script to fix and enhance chats table
 */
$host   = $argv[1] ?? getenv('DB_HOST') ?: '127.0.0.1';
$user   = $argv[2] ?? getenv('DB_USERNAME') ?: 'root';
$pass   = $argv[3] ?? getenv('DB_PASSWORD') ?: '';
$dbname = $argv[4] ?? getenv('DB_DATABASE') ?: 'cicalengkago_db';
$port   = $argv[5] ?? getenv('DB_PORT') ?: '3306';

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "[✓] Connected to MySQL database {$dbname}\n";

    // Check chats table columns
    $cols = $pdo->query("SHOW COLUMNS FROM `chats`")->fetchAll(PDO::FETCH_COLUMN);
    echo "[i] Current columns in `chats`: " . implode(', ', $cols) . "\n";

    // 1. Make order_id nullable if not already
    try {
        // Drop FK if exists
        $fks = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'chats' AND CONSTRAINT_SCHEMA = '{$dbname}' AND REFERENCED_TABLE_NAME = 'orders'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($fks as $fk) {
            $pdo->exec("ALTER TABLE `chats` DROP FOREIGN KEY `{$fk}`");
            echo "[+] Dropped old foreign key `{$fk}`\n";
        }
        $pdo->exec("ALTER TABLE `chats` MODIFY COLUMN `order_id` bigint(20) unsigned NULL DEFAULT NULL");
        echo "[+] Modified `order_id` to be NULLABLE\n";
    } catch (Exception $e) {
        echo "[!] Notice on modifying order_id: " . $e->getMessage() . "\n";
    }

    // 2. Add store_id if not exists
    if (!in_array('store_id', $cols)) {
        $pdo->exec("ALTER TABLE `chats` ADD COLUMN `store_id` bigint(20) unsigned NULL DEFAULT NULL AFTER `order_id`");
        echo "[+] Added column `store_id` to `chats`\n";
    } else {
        echo "[=] Column `store_id` already exists in `chats`\n";
    }

    // 3. Add indexes
    try {
        $pdo->exec("CREATE INDEX `idx_chat_store` ON `chats` (`store_id`)");
        echo "[+] Added index `idx_chat_store`\n";
    } catch (Exception $e) {
        echo "[=] Index `idx_chat_store` already exists\n";
    }

    try {
        $pdo->exec("CREATE INDEX `idx_chat_order_store` ON `chats` (`order_id`, `store_id`)");
        echo "[+] Added index `idx_chat_order_store`\n";
    } catch (Exception $e) {
        echo "[=] Index `idx_chat_order_store` already exists\n";
    }

    echo "\n[✓] Migration `chats` table complete!\n";
} catch (Exception $e) {
    echo "[x] Database error: " . $e->getMessage() . "\n";
}
