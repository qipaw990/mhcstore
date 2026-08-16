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
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";

        try {
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());
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
