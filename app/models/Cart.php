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
                       s.latitude as store_lat, s.longitude as store_lng, s.address as store_address,
                       pv.name as variation_name, pv.price as variation_price
                FROM `carts` c
                JOIN `products` p ON c.product_id = p.id
                JOIN `stores` s ON c.store_id = s.id
                LEFT JOIN `product_variations` pv ON c.variation_id = pv.id
                WHERE {$where}
                ORDER BY c.store_id ASC, c.id DESC";

        $rows = Database::query($sql, [$param]);

        // --- Build grouped-by-store structure ---
        $stores        = [];  // indexed by store_id
        $allItems      = [];  // flat list (backward compat)
        $grandSubtotal = 0.0;

        foreach ($rows as &$item) {
            $item['addons']     = json_decode($item['addons_json'] ?? '[]', true) ?: [];
            $unitPrice          = (float)$item['price'];
            $qty                = (int)$item['quantity'];
            $item['item_total'] = $unitPrice * $qty;

            $sid = (int)$item['store_id'];

            if (!isset($stores[$sid])) {
                $stores[$sid] = [
                    'store_id'      => $sid,
                    'name'          => $item['store_name'],
                    'logo'          => $item['store_logo'],
                    'delivery_fee'  => (float)$item['delivery_fee'],
                    'minimum_order' => (float)$item['minimum_order'],
                    'latitude'      => !empty($item['store_lat'])  ? (float)$item['store_lat']  : -6.9835,
                    'longitude'     => !empty($item['store_lng'])  ? (float)$item['store_lng']  : 107.8335,
                    'address'       => $item['store_address'] ?? '',
                    'items'         => [],
                    'subtotal'      => 0.0,
                    'item_count'    => 0,
                ];
            }

            $stores[$sid]['items'][]    = $item;
            $stores[$sid]['subtotal']  += $item['item_total'];
            $stores[$sid]['item_count']++;

            $grandSubtotal += $item['item_total'];
            $allItems[]     = $item;
        }

        $grandDelivery = array_sum(array_column($stores, 'delivery_fee'));

        // Backward-compat single-store fields (first store in list)
        $firstStore = !empty($stores) ? array_values($stores)[0] : null;
        $storeInfo  = $firstStore ? [
            'id'            => $firstStore['store_id'],
            'name'          => $firstStore['name'],
            'logo'          => $firstStore['logo'],
            'delivery_fee'  => $firstStore['delivery_fee'],
            'minimum_order' => $firstStore['minimum_order'],
            'latitude'      => $firstStore['latitude'],
            'longitude'     => $firstStore['longitude'],
            'address'       => $firstStore['address'],
        ] : null;

        return [
            // Multi-store grouped data (new)
            'stores'         => array_values($stores),
            'store_count'    => count($stores),
            'grand_subtotal' => $grandSubtotal,
            'grand_delivery' => $grandDelivery,
            'grand_total'    => $grandSubtotal + $grandDelivery,
            // Backward-compat flat data (existing code won't break)
            'items'          => $allItems,
            'count'          => count($allItems),
            'subtotal'       => $grandSubtotal,
            'store'          => $storeInfo,
            'store_id'       => $firstStore['store_id'] ?? null,
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

        // Check if exact same item already exists in cart for this user/session
        $whereUser = $userId ? "`user_id` = ?" : "`session_id` = ?";
        $paramUser = $userId ?: $sessionId;

        $varSql = $variationId ? "`variation_id` = ?" : "`variation_id` IS NULL";
        $notesSql = !empty($notes) ? "`item_notes` = ?" : "(`item_notes` IS NULL OR `item_notes` = '')";

        $sqlCheck = "SELECT id, quantity FROM `carts` WHERE {$whereUser} AND `product_id` = ? AND {$varSql} AND {$notesSql} LIMIT 1";
        $paramsCheck = [$paramUser, $productId];
        if ($variationId) $paramsCheck[] = $variationId;
        if (!empty($notes)) $paramsCheck[] = $notes;

        $existing = Database::fetchOne($sqlCheck, $paramsCheck);

        if ($existing) {
            $newQty = (int)$existing['quantity'] + $quantity;
            Database::execute("UPDATE `carts` SET `quantity` = ? WHERE `id` = ?", [$newQty, $existing['id']]);
            return $existing['id'];
        }

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
