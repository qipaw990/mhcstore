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
        
        $defaultHost = $config['host'] ?? null;
        $candidateHosts = (PHP_OS_FAMILY === 'Windows')
            ? array_unique(array_filter(['127.0.0.1', 'localhost', $defaultHost, 'cicalengkago_db', 'mariadb', 'host.docker.internal']))
            : array_unique(array_filter([$defaultHost, 'cicalengkago_db', 'mariadb', '172.17.0.1', 'host.docker.internal', '127.0.0.1', 'localhost']));

        $connected = false;
        $lastException = null;

        foreach ($candidateHosts as $host) {
            $dsn = "mysql:host={$host};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            
            // Retry loop for DB startup delay in Docker
            for ($attempt = 1; $attempt <= 2; $attempt++) {
                try {
                    $this->pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
                    $connected = true;
                    break 2; // Connected successfully
                } catch (PDOException $e) {
                    $lastException = $e;
                    if ($e->getCode() == 2002 && $attempt < 2) {
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
            }

            // Guarantee orders table columns allow dynamic payment methods without truncation
            try {
                $this->pdo->exec("ALTER TABLE `orders` MODIFY COLUMN `payment_method` VARCHAR(50) NOT NULL DEFAULT 'cod'");
                $this->pdo->exec("ALTER TABLE `orders` MODIFY COLUMN `payment_status` VARCHAR(30) NOT NULL DEFAULT 'unpaid'");
            } catch (Exception $e) {}

            // Guarantee chats table existence
            try {
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
            } catch (Exception $e) {}

            // Guarantee withdraw_requests table existence
            try {
                $this->pdo->exec("CREATE TABLE IF NOT EXISTS `withdraw_requests` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `withdraw_code` varchar(50) NOT NULL,
                  `user_id` bigint(20) unsigned NOT NULL,
                  `user_type` enum('vendor','delivery_man') NOT NULL,
                  `amount` decimal(14,2) NOT NULL,
                  `bank_name` varchar(50) NOT NULL,
                  `account_number` varchar(50) NOT NULL,
                  `account_holder` varchar(100) NOT NULL,
                  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
                  `admin_notes` varchar(255) DEFAULT NULL,
                  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
                  `processed_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `idx_wr_code` (`withdraw_code`),
                  KEY `idx_wr_user` (`user_id`, `user_type`),
                  KEY `idx_wr_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

                // Auto-heal orphaned withdrawal transactions if any
                $orphans = $this->pdo->query("
                    SELECT wt.*, w.user_id, w.user_type, u.name as user_name
                    FROM `wallet_transactions` wt
                    JOIN `wallets` w ON wt.wallet_id = w.id
                    LEFT JOIN `users` u ON w.user_id = u.id
                    LEFT JOIN `withdraw_requests` wr ON (wr.withdraw_code = wt.reference_id OR (wr.user_id = w.user_id AND wr.amount = wt.amount))
                    WHERE wt.category = 'withdrawal' AND wr.id IS NULL
                ")->fetchAll(PDO::FETCH_ASSOC);

                foreach ($orphans as $orphan) {
                    $code = (!empty($orphan['reference_id']) && str_starts_with($orphan['reference_id'], 'WD-')) 
                        ? $orphan['reference_id'] 
                        : ('WD-' . date('Ymd', strtotime($orphan['created_at'])) . '-' . strtoupper(substr(uniqid(), -5)));
                    
                    $desc = $orphan['description'] ?? '';
                    $bank = 'DANA';
                    $acc = '083153444251';
                    if (preg_match('/\((.+?)\s*-\s*(.+?)\)/', $desc, $matches)) {
                        $bank = trim($matches[1]);
                        $acc = trim($matches[2]);
                    }
                    $holder = $orphan['user_name'] ?: 'Mitra Driver';
                    $userType = in_array($orphan['user_type'], ['vendor', 'delivery_man']) ? $orphan['user_type'] : 'delivery_man';

                    $stmt = $this->pdo->prepare("
                        INSERT INTO `withdraw_requests` 
                        (`withdraw_code`, `user_id`, `user_type`, `amount`, `bank_name`, `account_number`, `account_holder`, `status`, `requested_at`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)
                    ");
                    $stmt->execute([
                        $code,
                        $orphan['user_id'],
                        $userType,
                        $orphan['amount'],
                        $bank,
                        $acc,
                        $holder,
                        $orphan['created_at'] ?: date('Y-m-d H:i:s')
                    ]);

                    // Sync total_withdrawn in wallets
                    $this->pdo->prepare("
                        UPDATE `wallets` SET `total_withdrawn` = (
                            SELECT COALESCE(SUM(amount), 0) FROM `withdraw_requests` WHERE `user_id` = ? AND `status` != 'rejected'
                        ) WHERE `user_id` = ?
                    ")->execute([$orphan['user_id'], $orphan['user_id']]);
                }
            } catch (Exception $e) {}

            // Guarantee topup_logs table existence
            try {
                $this->pdo->exec("CREATE TABLE IF NOT EXISTS `topup_logs` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `topup_code` varchar(64) NOT NULL,
                  `user_id` bigint(20) unsigned NOT NULL,
                  `amount` decimal(14,2) NOT NULL,
                  `payment_method` varchar(50) NOT NULL DEFAULT 'midtrans',
                  `payment_type` varchar(50) NOT NULL DEFAULT 'midtrans_snap',
                  `status` enum('pending','success','failed','canceled') NOT NULL DEFAULT 'pending',
                  `snap_token` varchar(255) DEFAULT NULL,
                  `notes` varchar(255) DEFAULT NULL,
                  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `idx_topup_code` (`topup_code`),
                  KEY `idx_topup_user` (`user_id`),
                  KEY `idx_topup_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

                // Auto-sync existing successful topup wallet transactions
                $orphansTopup = $this->pdo->query("
                    SELECT wt.*, w.user_id 
                    FROM `wallet_transactions` wt 
                    JOIN `wallets` w ON wt.wallet_id = w.id 
                    LEFT JOIN `topup_logs` tl ON (tl.topup_code = wt.reference_id OR (tl.user_id = w.user_id AND tl.amount = wt.amount))
                    WHERE wt.category = 'topup' AND tl.id IS NULL
                ")->fetchAll(PDO::FETCH_ASSOC);

                foreach ($orphansTopup as $opt) {
                    $code = (!empty($opt['reference_id']) && str_starts_with($opt['reference_id'], 'TOPUP-'))
                        ? $opt['reference_id']
                        : ('TOPUP-' . $opt['user_id'] . '-' . strtotime($opt['created_at']) . '-' . rand(100, 999));

                    $this->pdo->prepare("
                        INSERT IGNORE INTO `topup_logs`
                        (`topup_code`, `user_id`, `amount`, `payment_method`, `payment_type`, `status`, `notes`, `created_at`, `updated_at`)
                        VALUES (?, ?, ?, 'midtrans', 'midtrans_snap', 'success', 'Pengisian saldo CicalengkaPay berhasil', ?, ?)
                    ")->execute([
                        $code,
                        $opt['user_id'],
                        $opt['amount'],
                        $opt['created_at'] ?: date('Y-m-d H:i:s'),
                        $opt['created_at'] ?: date('Y-m-d H:i:s')
                    ]);
                }
            } catch (Exception $e) {}

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

    public static function raw(string $sql, array $params = []): bool
    {
        return self::execute($sql, $params);
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
