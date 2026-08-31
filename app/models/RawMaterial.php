<?php
namespace App\Models;

use App\Core\Database;
use Exception;

class RawMaterial
{
    private static bool $tablesChecked = false;

    public function __construct()
    {
        self::ensureTablesExist();
    }

    /**
     * Auto-create tables & columns on the fly if they don't exist yet
     */
    public static function ensureTablesExist(): void
    {
        if (self::$tablesChecked) {
            return;
        }

        try {
            // 1. Table raw_materials
            Database::query("CREATE TABLE IF NOT EXISTS `raw_materials` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `store_id` bigint(20) unsigned NOT NULL,
                `name` varchar(255) NOT NULL,
                `unit` varchar(50) NOT NULL DEFAULT 'gr',
                `price_per_unit` decimal(15,2) NOT NULL DEFAULT 0.00,
                `stock_qty` decimal(15,2) NOT NULL DEFAULT 0.00,
                `description` text DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT current_timestamp(),
                `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `idx_rm_store` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // 2. Table product_raw_materials
            Database::query("CREATE TABLE IF NOT EXISTS `product_raw_materials` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `product_id` bigint(20) unsigned NOT NULL,
                `raw_material_id` bigint(20) unsigned NOT NULL,
                `qty_used` decimal(15,4) NOT NULL DEFAULT 0.0000,
                `created_at` timestamp NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `idx_prm_product` (`product_id`),
                KEY `idx_prm_material` (`raw_material_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // 3. Pastikan kolom hpp ada di tabel products
            $cols = Database::query("SHOW COLUMNS FROM `products` LIKE 'hpp'");
            if (empty($cols)) {
                Database::query("ALTER TABLE `products` ADD COLUMN `hpp` decimal(15,2) NOT NULL DEFAULT 0.00 AFTER `price`");
            }

            // 4. Pastikan kolom variation_id ada di tabel product_raw_materials
            $colsPrm = Database::query("SHOW COLUMNS FROM `product_raw_materials` LIKE 'variation_id'");
            if (empty($colsPrm)) {
                Database::query("ALTER TABLE `product_raw_materials` ADD COLUMN `variation_id` bigint(20) unsigned NULL DEFAULT NULL AFTER `product_id`, ADD KEY `idx_prm_variation` (`variation_id`)");
            }

            // 5. Pastikan kolom hpp ada di tabel product_variations
            $colsVar = Database::query("SHOW COLUMNS FROM `product_variations` LIKE 'hpp'");
            if (empty($colsVar)) {
                Database::query("ALTER TABLE `product_variations` ADD COLUMN `hpp` decimal(15,2) NOT NULL DEFAULT 0.00 AFTER `price`");
            }

            self::$tablesChecked = true;
        } catch (Exception $e) {
            // Log or silence to prevent crashing if user has limited DDL permissions
            error_log("[RawMaterial AutoMigrate] " . $e->getMessage());
        }
    }

    // ── CRUD Bahan Baku per Toko ──────────────────────────────────────────────

    public function getByStore(int $storeId): array
    {
        self::ensureTablesExist();
        return Database::query(
            "SELECT * FROM `raw_materials` WHERE `store_id` = ? ORDER BY `name` ASC",
            [$storeId]
        );
    }

    public function find(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM `raw_materials` WHERE `id` = ? LIMIT 1",
            [$id]
        );
    }

    public function save(array $data): int|false
    {
        if (!empty($data['id'])) {
            $id = (int)$data['id'];
            Database::query(
                "UPDATE `raw_materials` SET `name`=?, `unit`=?, `price_per_unit`=?, `stock_qty`=?, `description`=?, `updated_at`=NOW()
                 WHERE `id`=? AND `store_id`=?",
                [$data['name'], $data['unit'], $data['price_per_unit'], $data['stock_qty'] ?? 0, $data['description'] ?? null, $id, $data['store_id']]
            );
            return $id;
        }
        Database::query(
            "INSERT INTO `raw_materials` (`store_id`,`name`,`unit`,`price_per_unit`,`stock_qty`,`description`) VALUES (?,?,?,?,?,?)",
            [$data['store_id'], $data['name'], $data['unit'], $data['price_per_unit'], $data['stock_qty'] ?? 0, $data['description'] ?? null]
        );
        return (int)Database::getPdo()->lastInsertId();
    }

    public function delete(int $id, int $storeId): bool
    {
        // Hapus dari resep produk juga
        Database::query("DELETE FROM `product_raw_materials` WHERE `raw_material_id` = ?", [$id]);
        Database::query("DELETE FROM `raw_materials` WHERE `id` = ? AND `store_id` = ?", [$id, $storeId]);
        return true;
    }

    // ── Resep Produk (Product Recipe) ────────────────────────────────────────

    public function getProductRecipe(int $productId): array
    {
        self::ensureTablesExist();

        // 1. Resep Dasar (Base Recipe: variation_id IS NULL / 0)
        $baseRecipe = Database::query(
            "SELECT prm.*, rm.name as material_name, rm.unit, rm.price_per_unit,
                    (prm.qty_used * rm.price_per_unit) as cost
             FROM `product_raw_materials` prm
             JOIN `raw_materials` rm ON prm.raw_material_id = rm.id
             WHERE prm.product_id = ? AND (prm.variation_id IS NULL OR prm.variation_id = 0)
             ORDER BY rm.name ASC",
            [$productId]
        );

        // 2. Variasi Produk beserta resep masing-masing varian
        $variations = Database::query(
            "SELECT * FROM `product_variations` WHERE `product_id` = ? ORDER BY `id` ASC",
            [$productId]
        );

        foreach ($variations as &$var) {
            $varId = (int)$var['id'];
            $varRecipe = Database::query(
                "SELECT prm.*, rm.name as material_name, rm.unit, rm.price_per_unit,
                        (prm.qty_used * rm.price_per_unit) as cost
                 FROM `product_raw_materials` prm
                 JOIN `raw_materials` rm ON prm.raw_material_id = rm.id
                 WHERE prm.product_id = ? AND prm.variation_id = ?
                 ORDER BY rm.name ASC",
                [$productId, $varId]
            );
            $var['recipe'] = $varRecipe;
            $varHpp = array_sum(array_column($varRecipe, 'cost'));
            if ($varHpp > 0) {
                $var['hpp'] = (float)$varHpp;
            } else {
                $var['hpp'] = (float)($var['hpp'] ?? 0);
            }
        }
        unset($var);

        return [
            'base_recipe' => $baseRecipe,
            'variations'  => $variations,
        ];
    }

    /**
     * Simpan resep produk dan variasi produk
     * @param int        $productId
     * @param array      $baseIngredients [['raw_material_id' => 1, 'qty_used' => 100], ...]
     * @param array|null $variations      [['id' => 1, 'name' => 'Large', 'price' => 25000, 'stock' => 100, 'ingredients' => [...]], ...]
     * @return array     Ringkasan HPP dasar dan HPP variasi
     */
    public function saveProductRecipe(int $productId, array $baseIngredients, ?array $variations = null): array
    {
        self::ensureTablesExist();

        // 1. Simpan Resep Dasar
        Database::query("DELETE FROM `product_raw_materials` WHERE `product_id` = ? AND (`variation_id` IS NULL OR `variation_id` = 0)", [$productId]);

        $baseHpp = 0.0;
        foreach ($baseIngredients as $item) {
            $rmId    = (int)($item['raw_material_id'] ?? 0);
            $qtyUsed = (float)($item['qty_used'] ?? 0);
            if ($rmId <= 0 || $qtyUsed <= 0) continue;

            Database::query(
                "INSERT INTO `product_raw_materials` (`product_id`, `variation_id`, `raw_material_id`, `qty_used`) VALUES (?, NULL, ?, ?)",
                [$productId, $rmId, $qtyUsed]
            );

            $rm = Database::fetchOne("SELECT `price_per_unit` FROM `raw_materials` WHERE `id`=?", [$rmId]);
            if ($rm) {
                $baseHpp += $qtyUsed * (float)$rm['price_per_unit'];
            }
        }

        // Update HPP di tabel products
        Database::query("UPDATE `products` SET `hpp` = ? WHERE `id` = ?", [$baseHpp, $productId]);

        // 2. Simpan Variasi & Resep Bahan Baku Tiap Varian jika dikirimkan
        $savedVariations = [];
        if (is_array($variations)) {
            $keptVarIds = [];

            foreach ($variations as $varData) {
                $vName  = trim($varData['name'] ?? '');
                if (empty($vName)) continue;

                $vPrice = (float)($varData['price'] ?? 0);
                $vStock = (int)($varData['stock'] ?? 100);
                $vId    = (int)($varData['id'] ?? 0);

                // Insert / Update Product Variation
                if ($vId > 0) {
                    $exist = Database::fetchOne("SELECT id FROM `product_variations` WHERE `id` = ? AND `product_id` = ?", [$vId, $productId]);
                    if ($exist) {
                        Database::query(
                            "UPDATE `product_variations` SET `name` = ?, `price` = ?, `stock` = ? WHERE `id` = ?",
                            [$vName, $vPrice, $vStock, $vId]
                        );
                    } else {
                        Database::query(
                            "INSERT INTO `product_variations` (`product_id`, `name`, `price`, `stock`) VALUES (?, ?, ?, ?)",
                            [$productId, $vName, $vPrice, $vStock]
                        );
                        $vId = (int)Database::getPdo()->lastInsertId();
                    }
                } else {
                    Database::query(
                        "INSERT INTO `product_variations` (`product_id`, `name`, `price`, `stock`) VALUES (?, ?, ?, ?)",
                        [$productId, $vName, $vPrice, $vStock]
                    );
                    $vId = (int)Database::getPdo()->lastInsertId();
                }

                $keptVarIds[] = $vId;

                // Simpan Racikan Bahan Baku untuk Varian Ini
                Database::query("DELETE FROM `product_raw_materials` WHERE `product_id` = ? AND `variation_id` = ?", [$productId, $vId]);

                $varIngredients = (array)($varData['ingredients'] ?? []);
                $varHpp = 0.0;

                foreach ($varIngredients as $vItem) {
                    $rmId    = (int)($vItem['raw_material_id'] ?? 0);
                    $qtyUsed = (float)($vItem['qty_used'] ?? 0);
                    if ($rmId <= 0 || $qtyUsed <= 0) continue;

                    Database::query(
                        "INSERT INTO `product_raw_materials` (`product_id`, `variation_id`, `raw_material_id`, `qty_used`) VALUES (?, ?, ?, ?)",
                        [$productId, $vId, $rmId, $qtyUsed]
                    );

                    $rm = Database::fetchOne("SELECT `price_per_unit` FROM `raw_materials` WHERE `id`=?", [$rmId]);
                    if ($rm) {
                        $varHpp += $qtyUsed * (float)$rm['price_per_unit'];
                    }
                }

                // Update HPP untuk variasi ini
                Database::query("UPDATE `product_variations` SET `hpp` = ? WHERE `id` = ?", [$varHpp, $vId]);

                $savedVariations[] = [
                    'id'    => $vId,
                    'name'  => $vName,
                    'price' => $vPrice,
                    'stock' => $vStock,
                    'hpp'   => $varHpp,
                ];
            }

            // Hapus variasi yang dihapus oleh merchant dari resep
            if (!empty($keptVarIds)) {
                $inClause = implode(',', array_map('intval', $keptVarIds));
                Database::query("DELETE FROM `product_raw_materials` WHERE `product_id` = ? AND `variation_id` IS NOT NULL AND `variation_id` NOT IN ($inClause)", [$productId]);
                Database::query("DELETE FROM `product_variations` WHERE `product_id` = ? AND `id` NOT IN ($inClause)", [$productId]);
            } else if (empty($variations)) {
                // Semua variasi dihapus
                Database::query("DELETE FROM `product_raw_materials` WHERE `product_id` = ? AND `variation_id` IS NOT NULL", [$productId]);
                Database::query("DELETE FROM `product_variations` WHERE `product_id` = ?", [$productId]);
            }
        }

        return [
            'base_hpp'   => $baseHpp,
            'variations' => $savedVariations,
        ];
    }

    /**
     * Hitung ulang HPP dari resep yang tersimpan (tanpa merubah resep)
     */
    public function recalculateHpp(int $productId): float
    {
        $row = Database::fetchOne(
            "SELECT SUM(prm.qty_used * rm.price_per_unit) as total
             FROM `product_raw_materials` prm
             JOIN `raw_materials` rm ON prm.raw_material_id = rm.id
             WHERE prm.product_id = ? AND (prm.variation_id IS NULL OR prm.variation_id = 0)",
            [$productId]
        );
        $hpp = (float)($row['total'] ?? 0);
        Database::query("UPDATE `products` SET `hpp` = ? WHERE `id` = ?", [$hpp, $productId]);
        return $hpp;
    }
}
