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

    /** Simple in-request cache so we don't hit DB repeatedly for the same zone. */
    private static array $_cache = [];

    public function activeZones(): array
    {
        return Database::query("SELECT * FROM `zones` WHERE `status` = 1 ORDER BY `name` ASC");
    }

    /**
     * Return tariff for a zone.
     * Falls back to sensible defaults (Rp 5.000 min, Rp 2.500/km) if zone not found.
     *
     * @return array{min_delivery_charge: float, per_km_delivery_charge: float}
     */
    public static function getZoneTariff(int $zoneId): array
    {
        if (isset(self::$_cache[$zoneId])) {
            return self::$_cache[$zoneId];
        }

        $zone = Database::fetchOne(
            "SELECT `min_delivery_charge`, `per_km_delivery_charge` FROM `zones` WHERE `id` = ? LIMIT 1",
            [$zoneId]
        );

        $tariff = [
            'min_delivery_charge'    => (float)($zone['min_delivery_charge']    ?? 5000.00),
            'per_km_delivery_charge' => (float)($zone['per_km_delivery_charge'] ?? 2500.00),
        ];

        self::$_cache[$zoneId] = $tariff;
        return $tariff;
    }

    /**
     * Return full detail of a zone including name, tariff, and polygon coordinates.
     */
    public static function getZoneDetail(int $zoneId): array
    {
        $zone = Database::fetchOne(
            "SELECT * FROM `zones` WHERE `id` = ? LIMIT 1",
            [$zoneId]
        );

        if (!$zone) {
            $zone = Database::fetchOne("SELECT * FROM `zones` WHERE `status` = 1 ORDER BY `id` ASC LIMIT 1") ?: [];
        }

        $rawCoords = $zone['coordinates_json'] ?? $zone['coordinates'] ?? '[]';
        $decoded = is_string($rawCoords) ? json_decode($rawCoords, true) : $rawCoords;

        $coordsList = [];
        if (isset($decoded['coordinates']) && is_array($decoded['coordinates'])) {
            $coordsList = $decoded['coordinates'][0] ?? [];
        } elseif (is_array($decoded)) {
            $coordsList = $decoded;
        }

        // Normalize to array of [lat, lng] pairs
        $normalized = [];
        foreach ($coordsList as $pt) {
            if (is_array($pt)) {
                if (isset($pt['lat']) && isset($pt['lng'])) {
                    $normalized[] = [(float)$pt['lat'], (float)$pt['lng']];
                } elseif (isset($pt[0]) && isset($pt[1])) {
                    $normalized[] = [(float)$pt[0], (float)$pt[1]];
                }
            } elseif (is_object($pt) && isset($pt->lat, $pt->lng)) {
                $normalized[] = [(float)$pt->lat, (float)$pt->lng];
            }
        }

        if (count($normalized) < 3) {
            // Server exact polygon for Zona Cicalengka Raya
            $normalized = [
                [-6.955741, 107.827375],
                [-6.961527, 107.860682],
                [-7.023885, 107.901530],
                [-7.030012, 107.797852],
                [-6.972775, 107.754607],
                [-6.955071, 107.804043]
            ];
        }

        return [
            'id'                     => (int)($zone['id'] ?? 1),
            'name'                   => $zone['name'] ?? 'Zona Cicalengka Raya',
            'min_delivery_charge'    => (float)($zone['min_delivery_charge'] ?? 5000.00),
            'per_km_delivery_charge' => (float)($zone['per_km_delivery_charge'] ?? 2500.00),
            'center_latitude'        => (float)($zone['center_latitude'] ?? -6.983340),
            'center_longitude'       => (float)($zone['center_longitude'] ?? 107.833900),
            'polygon_coordinates'    => $normalized,
        ];
    }
}
