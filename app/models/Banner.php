<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Banner extends Model
{
    protected string $table = 'banners';
    protected array $fillable = [
        'module_id', 'zone_id', 'title', 'image', 'banner_type',
        'target_type', 'target_id', 'status', 'priority'
    ];

    public function getActiveBanners(?int $moduleId = null): array
    {
        $params = [];
        $moduleSql = '';
        if ($moduleId) {
            $moduleSql = " AND (module_id = ? OR module_id IS NULL OR module_id = 0)";
            $params[] = $moduleId;
        }

        return Database::query("SELECT * FROM `banners` WHERE `status` = 1 {$moduleSql} ORDER BY `priority` ASC", $params);
    }
}
