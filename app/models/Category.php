<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Category extends Model
{
    protected string $table = 'categories';
    protected array $fillable = ['module_id', 'parent_id', 'name', 'image', 'icon', 'priority', 'status'];

    public function getByModule(int $moduleId): array
    {
        return Database::query("SELECT * FROM `categories` WHERE `module_id` = ? AND `status` = 1 ORDER BY `priority` ASC", [$moduleId]);
    }
}
