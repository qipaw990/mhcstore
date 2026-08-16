<?php
/**
 * Core Database Wrapper
 * Singleton PDO instance with safe query helpers and ACID transactions
 */

namespace App\Core;

use PDO;
use PDOException;
use Exception;

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $config = require APP_PATH . '/config/database.php';
        
        $candidateHosts = array_unique(array_filter([
            $config['host'] ?? null,
            'cicalengkago_db',
            'mariadb',
            '172.17.0.1',
            'host.docker.internal',
            '127.0.0.1',
            'localhost'
        ]));

        $connected = false;
        $lastException = null;

        foreach ($candidateHosts as $host) {
            $dsn = "mysql:host={$host};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            
            // Retry loop for DB startup delay in Docker
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                try {
                    $this->pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
                    $connected = true;
                    break 2; // Connected successfully
                } catch (PDOException $e) {
                    $lastException = $e;
                    if ($e->getCode() == 2002 && $attempt < 3) {
                        sleep(1);
                    } else {
                        break; // Try next host candidate
                    }
                }
            }
        }

        if (!$connected) {
            die("Database Connection Error: " . ($lastException ? $lastException->getMessage() : "Unable to connect to database"));
        }

        $this->autoMigrateIfNeeded();
    }

    private function autoMigrateIfNeeded(): void
    {
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'modules'");
            $tableExists = $stmt ? $stmt->fetch() : false;

            if (!$tableExists) {
                $root = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);
                $schemaFile = $root . '/database/schema.sql';
                $seedersFile = $root . '/database/seeders.sql';

                if (file_exists($schemaFile)) {
                    $sql = file_get_contents($schemaFile);
                    $this->pdo->exec($sql);
                }

                if (file_exists($seedersFile)) {
                    $sql = file_get_contents($seedersFile);
                    $this->pdo->exec($sql);
                }
                // Ensure orders table columns allow dynamic payment methods without truncation
                try {
                    $this->pdo->exec("ALTER TABLE `orders` MODIFY COLUMN `payment_method` VARCHAR(50) NOT NULL DEFAULT 'cod'");
                    $this->pdo->exec("ALTER TABLE `orders` MODIFY COLUMN `payment_status` VARCHAR(30) NOT NULL DEFAULT 'unpaid'");
                    
                    // Guarantee chats table existence
                    $this->pdo->exec("CREATE TABLE IF NOT EXISTS `chats` (
                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                      `order_id` bigint(20) unsigned NOT NULL,
                      `sender_id` bigint(20) unsigned NOT NULL,
                      `receiver_id` bigint(20) unsigned NOT NULL DEFAULT 0,
                      `message` text NOT NULL,
                      `file` varchar(255) DEFAULT NULL,
                      `is_read` tinyint(1) NOT NULL DEFAULT 0,
                      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                      PRIMARY KEY (`id`),
                      KEY `idx_chat_order` (`order_id`),
                      KEY `idx_chat_pair` (`sender_id`,`receiver_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
                } catch (Exception $e) {
                    // Ignore if already modified or restricted
                }
            }
        } catch (Exception $e) {
            error_log("Auto Migration Error: " . $e->getMessage());
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function getPdo(): PDO
    {
        return self::getInstance()->pdo;
    }

    public static function query(string $sql, array $params = []): array
    {
        $stmt = self::getPdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::getPdo()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function execute(string $sql, array $params = []): bool
    {
        $stmt = self::getPdo()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function insert(string $table, array $data): int|string
    {
        $columns = implode('`, `', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO `{$table}` (`{$columns}`) VALUES ({$placeholders})";
        
        $stmt = self::getPdo()->prepare($sql);
        $stmt->execute(array_values($data));
        return self::getPdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): bool
    {
        $fields = [];
        $values = [];
        foreach ($data as $col => $val) {
            $fields[] = "`{$col}` = ?";
            $values[] = $val;
        }
        $fieldsSql = implode(', ', $fields);
        $sql = "UPDATE `{$table}` SET {$fieldsSql} WHERE {$where}";
        
        $params = array_merge($values, $whereParams);
        $stmt = self::getPdo()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(string $table, string $where, array $params = []): bool
    {
        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        $stmt = self::getPdo()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function beginTransaction(): bool
    {
        return self::getPdo()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::getPdo()->commit();
    }

    public static function rollBack(): bool
    {
        return self::getPdo()->rollBack();
    }

    public static function transaction(callable $callback)
    {
        self::beginTransaction();
        try {
            $result = $callback(self::getPdo());
            self::commit();
            return $result;
        } catch (Exception $e) {
            self::rollBack();
            throw $e;
        }
    }
}
