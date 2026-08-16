<?php
/**
 * Base Model Class
 */

namespace App\Core;

abstract class Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $fillable = [];

    public function all(string $orderBy = 'id DESC'): array
    {
        return Database::query("SELECT * FROM `{$this->table}` ORDER BY {$orderBy}");
    }

    public function find($id): ?array
    {
        return Database::fetchOne("SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ? LIMIT 1", [$id]);
    }

    public function where(string $column, $value, string $operator = '='): array
    {
        return Database::query("SELECT * FROM `{$this->table}` WHERE `{$column}` {$operator} ?", [$value]);
    }

    public function firstWhere(string $column, $value, string $operator = '='): ?array
    {
        return Database::fetchOne("SELECT * FROM `{$this->table}` WHERE `{$column}` {$operator} ? LIMIT 1", [$value]);
    }

    public function create(array $data): int|string
    {
        if (!empty($this->fillable)) {
            $data = array_intersect_key($data, array_flip($this->fillable));
        }
        return Database::insert($this->table, $data);
    }

    public function update($id, array $data): bool
    {
        if (!empty($this->fillable)) {
            $data = array_intersect_key($data, array_flip($this->fillable));
        }
        return Database::update($this->table, $data, "`{$this->primaryKey}` = ?", [$id]);
    }

    public function delete($id): bool
    {
        return Database::delete($this->table, "`{$this->primaryKey}` = ?", [$id]);
    }

    public function count(string $where = '1', array $params = []): int
    {
        $res = Database::fetchOne("SELECT COUNT(*) as total FROM `{$this->table}` WHERE {$where}", $params);
        return (int)($res['total'] ?? 0);
    }
}
