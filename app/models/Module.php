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

    public function activeModules(?int $zoneId = null): array
    {
        $zoneCondition = $zoneId ? " AND (s.zone_id = " . (int)$zoneId . " OR s.zone_id IS NULL OR s.zone_id = 0)" : "";
        $sql = "
            SELECT m.*,
                   (
                       SELECT COUNT(*) 
                       FROM `stores` s 
                       WHERE s.module_id = m.id 
                         AND s.status = 'approved' 
                         AND (s.is_open = 1 OR s.is_open = '1')
                         {$zoneCondition}
                   ) AS stores_count
            FROM `modules` m
            WHERE m.status = 1
            ORDER BY m.id ASC
        ";
        $modules = Database::query($sql);

        foreach ($modules as &$mod) {
            $mod['stores_count'] = (int)($mod['stores_count'] ?? 0);
        }
        unset($mod);

        return $modules;
    }

    public function getActive(?int $zoneId = null): array
    {
        return $this->activeModules($zoneId);
    }

    public static function syncStoresCount(?int $moduleId = null): void
    {
        try {
            if ($moduleId) {
                Database::query("
                    UPDATE `modules` m
                    SET `stores_count` = (
                        SELECT COUNT(*) 
                        FROM `stores` s 
                        WHERE s.module_id = m.id 
                          AND s.status = 'approved' 
                          AND (s.is_open = 1 OR s.is_open = '1')
                    )
                    WHERE m.id = ?
                ", [$moduleId]);
            } else {
                Database::query("
                    UPDATE `modules` m
                    SET `stores_count` = (
                        SELECT COUNT(*) 
                        FROM `stores` s 
                        WHERE s.module_id = m.id 
                          AND s.status = 'approved' 
                          AND (s.is_open = 1 OR s.is_open = '1')
                    )
                ");
            }
        } catch (\Throwable $e) {
            // Silently catch if table schema or execution fails
        }
    }
}
