<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Module extends Model
{
    protected string $table = 'modules';
    protected array $fillable = [
        'name', 'module_type', 'icon', 'thumbnail', 'theme_color', 'description', 'status', 'stores_count'
    ];

    public function activeModules(): array
    {
        return Database::query("SELECT * FROM `modules` WHERE `status` = 1 ORDER BY `id` ASC");
    }

    public function getActive(): array
    {
        return $this->activeModules();
    }
}
