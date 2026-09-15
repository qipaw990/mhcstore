<?php
namespace App\Models;

use App\Core\Database;

/**
 * AppFeature — Model untuk tabel app_features.
 * Menyimpan semua fitur/layanan yang tampil di Flutter app
 * dan dikelola dari admin panel.
 */
class AppFeature
{
    /**
     * Cek apakah tabel app_features sudah ada di database.
     */
    public static function tableExists(): bool
    {
        try {
            return (bool)Database::fetchOne(
                "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'app_features' LIMIT 1"
            );
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Pastikan tabel app_features ada, jika belum otomatis lakukan migrasi.
     */
    public static function ensureTableExists(): void
    {
        if (!self::tableExists()) {
            try {
                self::migrate();
            } catch (\Throwable $e) {
                error_log('[AppFeature] Auto-migration error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Ambil semua fitur yang aktif, dikelompokkan per feature_type.
     * @return array<string, array<mixed>>
     */
    public static function getAllGrouped(): array
    {
        $grouped = [
            'service_grid'  => [],
            'filter_chip'   => [],
            'trending_chip' => [],
            'quick_action'  => [],
            'home_section'  => [],
        ];

        try {
            self::ensureTableExists();

            $rows = Database::query(
                "SELECT * FROM `app_features` WHERE `is_active` = 1 ORDER BY `sort_order` ASC, `id` ASC"
            ) ?: [];

            foreach ($rows as $row) {
                $type = $row['feature_type'] ?? '';
                if (isset($grouped[$type])) {
                    $grouped[$type][] = $row;
                }
            }
        } catch (\Throwable $e) {
            error_log('[AppFeature] Error getAllGrouped: ' . $e->getMessage());
        }

        return $grouped;
    }

    /**
     * Ambil semua fitur (aktif dan nonaktif) untuk admin panel.
     */
    public static function getAll(): array
    {
        try {
            self::ensureTableExists();
            return Database::query(
                "SELECT * FROM `app_features` ORDER BY `feature_type`, `sort_order` ASC, `id` ASC"
            ) ?: [];
        } catch (\Throwable $e) {
            error_log('[AppFeature] Error getAll: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil fitur per tipe.
     */
    public static function getByType(string $type, bool $activeOnly = true): array
    {
        try {
            self::ensureTableExists();
            $where = $activeOnly ? 'WHERE `feature_type` = ? AND `is_active` = 1' : 'WHERE `feature_type` = ?';
            return Database::query(
                "SELECT * FROM `app_features` {$where} ORDER BY `sort_order` ASC, `id` ASC",
                [$type]
            ) ?: [];
        } catch (\Throwable $e) {
            error_log('[AppFeature] Error getByType: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Simpan fitur (create or update).
     */
    public static function save(array $data): int|bool
    {
        $id = isset($data['id']) ? (int)$data['id'] : 0;

        $fields = [
            'feature_type' => $data['feature_type'] ?? 'service_grid',
            'name'         => $data['name'] ?? '',
            'icon'         => $data['icon'] ?? null,
            'icon_type'    => $data['icon_type'] ?? 'emoji',
            'color'        => $data['color'] ?: null,
            'bg_color'     => $data['bg_color'] ?: null,
            'action_type'  => $data['action_type'] ?? 'search',
            'action_value' => $data['action_value'] ?? null,
            'sort_order'   => (int)($data['sort_order'] ?? 0),
            'is_active'    => isset($data['is_active']) ? (int)(bool)$data['is_active'] : 1,
        ];

        if ($id > 0) {
            Database::update('app_features', $fields, '`id` = ?', [$id]);
            return $id;
        }

        return Database::insert('app_features', $fields);
    }

    /**
     * Hapus fitur berdasarkan ID.
     */
    public static function delete(int $id): bool
    {
        return Database::execute("DELETE FROM `app_features` WHERE `id` = ?", [$id]);
    }

    /**
     * Toggle aktif/nonaktif.
     */
    public static function toggleActive(int $id): array
    {
        Database::execute(
            "UPDATE `app_features` SET `is_active` = NOT `is_active` WHERE `id` = ?",
            [$id]
        );
        $row = Database::fetchOne("SELECT `is_active` FROM `app_features` WHERE `id` = ?", [$id]);
        return ['is_active' => (bool)($row['is_active'] ?? false)];
    }

    /**
     * Reorder fitur — terima array of {id, sort_order}.
     */
    public static function reorder(array $items): void
    {
        foreach ($items as $item) {
            $itemId    = (int)($item['id'] ?? 0);
            $sortOrder = (int)($item['sort_order'] ?? 0);
            if ($itemId > 0) {
                Database::execute(
                    "UPDATE `app_features` SET `sort_order` = ? WHERE `id` = ?",
                    [$sortOrder, $itemId]
                );
            }
        }
    }

    /**
     * Jalankan migration SQL untuk membuat & seed tabel.
     */
    public static function migrate(): bool
    {
        $sqlFile = dirname(__DIR__, 2) . '/database/migrate_app_features.sql';
        if (!file_exists($sqlFile)) {
            return false;
        }

        $sql = file_get_contents($sqlFile);
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($s) => !empty($s) && !preg_match('/^--/', $s)
        );

        foreach ($statements as $stmt) {
            if (!empty(trim($stmt))) {
                try {
                    Database::execute($stmt);
                } catch (\Throwable $e) {
                    // Ignore duplicate key errors during re-migration
                    if (strpos($e->getMessage(), '1062') === false) {
                        throw $e;
                    }
                }
            }
        }

        return true;
    }
}
