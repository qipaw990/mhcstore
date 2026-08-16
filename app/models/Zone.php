<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Zone extends Model
{
    protected string $table = 'zones';
    protected array $fillable = [
        'name', 'coordinates_json', 'status', 'min_delivery_charge', 'per_km_delivery_charge',
        'center_latitude', 'center_longitude'
    ];

    public function activeZones(): array
    {
        return Database::query("SELECT * FROM `zones` WHERE `status` = 1 ORDER BY `name` ASC");
    }
}
