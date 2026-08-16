<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Product extends Model
{
    protected string $table = 'products';
    protected array $fillable = [
        'store_id', 'module_id', 'category_id', 'name', 'description', 'image',
        'price', 'discount', 'discount_type', 'unit', 'stock', 'is_veg',
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
        return $products;
    }

    public function getByStore(int $storeId): array
    {
        $sql = "SELECT p.*, c.name as category_name
                FROM `products` p
                JOIN `categories` c ON p.category_id = c.id
                WHERE p.store_id = ? AND p.status = 1
                ORDER BY c.priority ASC, p.id DESC";
        $products = Database::query($sql, [$storeId]);
        foreach ($products as &$p) {
            $p['final_price'] = $this->calculateFinalPrice($p);
            $p['variations'] = Database::query("SELECT * FROM `product_variations` WHERE `product_id` = ?", [$p['id']]);
        }
        return $products;
    }

    public function search(string $query, ?int $moduleId = null): array
    {
        $params = ["%{$query}%", "%{$query}%"];
        $moduleSql = '';
        if ($moduleId) {
            $moduleSql = " AND p.module_id = ?";
            $params[] = $moduleId;
        }

        $sql = "SELECT p.*, s.name as store_name, s.is_open as store_is_open
                FROM `products` p
                JOIN `stores` s ON p.store_id = s.id
                WHERE p.status = 1 AND (p.name LIKE ? OR p.description LIKE ?) {$moduleSql}
                ORDER BY p.order_count DESC LIMIT 20";
        $products = Database::query($sql, $params);
        foreach ($products as &$p) {
            $p['final_price'] = $this->calculateFinalPrice($p);
        }
        return $products;
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
