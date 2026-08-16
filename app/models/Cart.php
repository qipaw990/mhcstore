<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Cart extends Model
{
    protected string $table = 'carts';
    protected array $fillable = [
        'user_id', 'session_id', 'store_id', 'product_id', 'variation_id',
        'addons_json', 'quantity', 'price', 'item_notes'
    ];

    public function getUserCart(?int $userId, ?string $sessionId = null): array
    {
        $where = $userId ? "c.user_id = ?" : "c.session_id = ?";
        $param = $userId ?: $sessionId;

        $sql = "SELECT c.*, p.name as product_name, p.image as product_image, p.price as base_price,
                       s.name as store_name, s.logo as store_logo, s.delivery_fee, s.minimum_order,
                       pv.name as variation_name, pv.price as variation_price
                FROM `carts` c
                JOIN `products` p ON c.product_id = p.id
                JOIN `stores` s ON c.store_id = s.id
                LEFT JOIN `product_variations` pv ON c.variation_id = pv.id
                WHERE {$where}
                ORDER BY c.id DESC";

        $items = Database::query($sql, [$param]);
        $subtotal = 0;
        $storeId = null;
        $storeInfo = null;

        foreach ($items as &$item) {
            $item['addons'] = json_decode($item['addons_json'] ?? '[]', true) ?: [];
            $unitPrice = (float)$item['price'];
            $qty = (int)$item['quantity'];
            $item['item_total'] = $unitPrice * $qty;
            $subtotal += $item['item_total'];
            $storeId = $item['store_id'];
            if (!$storeInfo) {
                $storeInfo = [
                    'id' => $item['store_id'],
                    'name' => $item['store_name'],
                    'logo' => $item['store_logo'],
                    'delivery_fee' => (float)$item['delivery_fee'],
                    'minimum_order' => (float)$item['minimum_order']
                ];
            }
        }

        return [
            'items' => $items,
            'count' => count($items),
            'subtotal' => $subtotal,
            'store' => $storeInfo,
            'store_id' => $storeId
        ];
    }

    public function addItem(?int $userId, ?string $sessionId, int $productId, int $quantity = 1, ?float $price = null, ?int $variationId = null, array $addons = [], string $notes = ''): int|string
    {
        $prodModel = new Product();
        $product = $prodModel->findWithDetails($productId);
        if (!$product) {
            throw new \Exception("Produk tidak ditemukan");
        }

        $finalPrice = $price ?? (float)$product['final_price'];

        return $this->create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'store_id' => $product['store_id'],
            'product_id' => $productId,
            'variation_id' => $variationId,
            'addons_json' => !empty($addons) ? json_encode($addons) : null,
            'quantity' => $quantity,
            'price' => $finalPrice,
            'item_notes' => $notes
        ]);
    }

    public function clear(?int $userId, ?string $sessionId = null): bool
    {
        return $this->clearCart($userId, $sessionId);
    }

    public function clearCart(?int $userId, ?string $sessionId = null): bool
    {
        $where = $userId ? "`user_id` = ?" : "`session_id` = ?";
        $param = $userId ?: $sessionId;
        return Database::delete('carts', $where, [$param]);
    }
}
