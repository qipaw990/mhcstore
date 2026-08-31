<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Product extends Model
{
    protected string $table = 'products';
    protected array $fillable = [
        'store_id', 'module_id', 'category_id', 'name', 'barcode', 'description', 'image',
        'price', 'hpp', 'discount', 'discount_type', 'unit', 'stock', 'is_veg',
        'is_recommended', 'status', 'order_count', 'rating', 'reviews_count'
    ];

    public function findWithDetails(int $id): ?array
    {
        $sql = "SELECT p.*, s.name as store_name, s.logo as store_logo, s.is_open as store_is_open,
                       s.delivery_time, s.delivery_fee, c.name as category_name, m.name as module_name
                FROM `products` p
                JOIN `stores` s ON p.store_id = s.id
                JOIN `categories` c ON p.category_id = c.id
                JOIN `modules` m ON p.module_id = m.id
                WHERE p.id = ? LIMIT 1";
        $product = Database::fetchOne($sql, [$id]);
        if ($product) {
            $product['final_price'] = $this->calculateFinalPrice($product);
            $product['variations'] = Database::query("SELECT * FROM `product_variations` WHERE `product_id` = ?", [$id]);
            $product['addons'] = Database::query("SELECT * FROM `product_addons` WHERE `store_id` = ? AND `status` = 1", [$product['store_id']]);
            $singleArr = [$product];
            $this->attachStoreStatus($singleArr);
            $product = $singleArr[0];
        }
        return $product;
    }

    public function getRecommended(int $limit = 8): array
    {
        $sql = "SELECT p.*, s.name as store_name, s.is_open as store_is_open
                FROM `products` p
                JOIN `stores` s ON p.store_id = s.id
                WHERE p.status = 1 AND s.status = 'approved' AND p.is_recommended = 1
                ORDER BY s.is_open DESC, p.order_count DESC
                LIMIT {$limit}";
        $products = Database::query($sql);
        foreach ($products as &$p) {
            $p['final_price'] = $this->calculateFinalPrice($p);
        }
        unset($p);
        $this->attachStoreStatus($products);
        return $products;
    }

    public function getByStore(int $storeId): array
    {
        // Ambil semua produk aktif + produk stock habis (agar customer lihat badge Habis)
        $sql = "SELECT p.*, c.name as category_name
                FROM `products` p
                LEFT JOIN `categories` c ON p.category_id = c.id
                WHERE p.store_id = ?
                ORDER BY p.status DESC, p.stock DESC, c.priority ASC, p.id DESC";
        $products = Database::query($sql, [$storeId]);
        $storeAddons = Database::query("SELECT * FROM `product_addons` WHERE `store_id` = ? AND `status` = 1 ORDER BY `price` ASC", [$storeId]);
        foreach ($products as &$p) {
            $p['final_price'] = $this->calculateFinalPrice($p);
            $p['variations'] = Database::query("SELECT * FROM `product_variations` WHERE `product_id` = ? ORDER BY `price` ASC", [$p['id']]);
            $p['addons'] = $storeAddons;
        }
        unset($p);
        $this->attachStoreStatus($products);
        return $products;
    }

    public function search(string $query, ?int $moduleId = null): array
    {
        $params = ["%{$query}%", "%{$query}%", "%{$query}%", "%{$query}%"];
        $moduleSql = '';
        if ($moduleId) {
            $moduleSql = " AND p.module_id = ?";
            $params[] = $moduleId;
        }

        $sql = "SELECT p.*, s.name as store_name, s.is_open as store_is_open
                FROM `products` p
                JOIN `stores` s ON p.store_id = s.id
                LEFT JOIN `categories` c ON p.category_id = c.id
                WHERE p.status = 1 AND s.status = 'approved' AND (p.name LIKE ? OR p.description LIKE ? OR s.name LIKE ? OR c.name LIKE ?) {$moduleSql}
                ORDER BY s.is_open DESC, p.order_count DESC LIMIT 30";
        $products = Database::query($sql, $params);
        foreach ($products as &$p) {
            $p['final_price'] = $this->calculateFinalPrice($p);
        }
        unset($p);
        $this->attachStoreStatus($products);
        return $products;
    }

    public function attachStoreStatus(array &$products): void
    {
        $storeStatusCache = [];
        foreach ($products as &$p) {
            $stId = (int)($p['store_id'] ?? 0);
            if ($stId > 0) {
                if (!isset($storeStatusCache[$stId])) {
                    $st = Database::fetchOne("SELECT * FROM `stores` WHERE id = ? LIMIT 1", [$stId]);
                    if ($st) {
                        if (function_exists('attach_store_schedule_data')) {
                            attach_store_schedule_data($st, true);
                        }
                        $isCurrentlyOpen = !empty($st['is_currently_open']) || (isset($st['is_open']) && ($st['is_open'] == 1 || $st['is_open'] === true || $st['is_open'] === '1' || $st['is_open'] === 'true'));
                        if (isset($st['is_open']) && ($st['is_open'] === 0 || $st['is_open'] === '0' || $st['is_open'] === false)) {
                            $isCurrentlyOpen = false;
                        }
                        $storeStatusCache[$stId] = $isCurrentlyOpen;
                    } else {
                        $storeStatusCache[$stId] = false;
                    }
                }
                $p['store_is_open'] = $storeStatusCache[$stId] ? 1 : 0;
                $p['is_store_open'] = $storeStatusCache[$stId];
                $p['is_currently_open'] = $storeStatusCache[$stId];
            }
        }
        unset($p);
    }

    public function calculateFinalPrice(array $product): float
    {
        $price = (float)$product['price'];
        $discount = (float)$product['discount'];
        if ($discount <= 0) return $price;

        if ($product['discount_type'] === 'percent') {
            return max(0, $price - ($price * ($discount / 100)));
        }
        return max(0, $price - $discount);
    }
}
