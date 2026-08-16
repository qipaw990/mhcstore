<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cart;
use App\Models\Product;
use App\Core\Database;
use Exception;

class CartController extends Controller
{
    private Cart $cartModel;
    private Product $productModel;

    public function __construct()
    {
        $this->cartModel = new Cart();
        $this->productModel = new Product();
    }

    public function add(): void
    {
        $data = $this->getPost();
        $productId = (int)($data['product_id'] ?? 0);
        $quantity = max(1, (int)($data['quantity'] ?? 1));
        $variationId = !empty($data['variation_id']) ? (int)$data['variation_id'] : null;
        $addons = is_array($data['addons'] ?? null) ? $data['addons'] : [];
        $notes = sanitize($data['item_notes'] ?? '');

        $product = $this->productModel->findWithDetails($productId);
        if (!$product) {
            $this->errorResponse('Produk tidak ditemukan.');
            return;
        }

        $userId = auth_id();
        $sessionId = session_id();

        // Check if cart has items from another store
        $currentCart = $this->cartModel->getUserCart($userId, $sessionId);
        if (!empty($currentCart['items']) && $currentCart['store_id'] != $product['store_id']) {
            if (empty($data['force_switch_store'])) {
                $this->json([
                    'success' => false,
                    'is_store_conflict' => true,
                    'message' => "Keranjang Anda sudah berisi item dari toko lain ({$currentCart['store']['name']}). Ganti dengan produk dari {$product['store_name']}?"
                ], 409);
                return;
            }
            // Clear previous store items if user agreed
            $this->cartModel->clearCart($userId, $sessionId);
        }

        // Calculate item base price + variation price
        $price = (float)$product['final_price'];
        if ($variationId) {
            $var = Database::fetchOne("SELECT price FROM product_variations WHERE id = ?", [$variationId]);
            if ($var) {
                $price = (float)$var['price'];
            }
        }

        // Calculate addons total
        $addonsJson = null;
        if (!empty($addons)) {
            $addonItems = [];
            foreach ($addons as $addonId) {
                $addonData = Database::fetchOne("SELECT id, name, price FROM product_addons WHERE id = ?", [(int)$addonId]);
                if ($addonData) {
                    $addonItems[] = [
                        'id'    => $addonData['id'],
                        'name'  => $addonData['name'],
                        'price' => (float)$addonData['price']
                    ];
                    $price += (float)$addonData['price'];
                }
            }
            $addonsJson = json_encode(['items' => $addonItems]);
        }

        // Insert to cart
        $cartId = $this->cartModel->create([
            'user_id'      => $userId,
            'session_id'   => $sessionId,
            'store_id'     => $product['store_id'],
            'product_id'   => $productId,
            'variation_id' => $variationId,
            'addons_json'  => $addonsJson,
            'quantity'     => $quantity,
            'price'        => $price,
            'item_notes'   => $notes
        ]);

        $updatedCart = $this->cartModel->getUserCart($userId, $sessionId);
        $this->successResponse('Produk berhasil ditambahkan ke keranjang!', [
            'cart_count' => $updatedCart['count'],
            'subtotal'   => $updatedCart['subtotal'],
            'subtotal_fmt' => format_rupiah($updatedCart['subtotal'])
        ]);
    }

    public function updateQty(): void
    {
        $data = $this->getPost();
        $cartId = (int)($data['cart_id'] ?? 0);
        $delta = (int)($data['delta'] ?? 0);

        $item = $this->cartModel->find($cartId);
        if (!$item) {
            $this->errorResponse('Item keranjang tidak ditemukan.');
            return;
        }

        $newQty = (int)$item['quantity'] + $delta;
        if ($newQty <= 0) {
            $this->cartModel->delete($cartId);
        } else {
            $this->cartModel->update($cartId, ['quantity' => $newQty]);
        }

        $userId = auth_id();
        $updatedCart = $this->cartModel->getUserCart($userId, session_id());
        $this->successResponse('Keranjang diperbarui', [
            'cart_count' => $updatedCart['count'],
            'subtotal'   => $updatedCart['subtotal'],
            'subtotal_fmt' => format_rupiah($updatedCart['subtotal'])
        ]);
    }

    public function remove(): void
    {
        $data = $this->getPost();
        $cartId = (int)($data['cart_id'] ?? 0);
        $this->cartModel->delete($cartId);

        $updatedCart = $this->cartModel->getUserCart(auth_id(), session_id());
        $this->successResponse('Item dihapus dari keranjang', [
            'cart_count' => $updatedCart['count'],
            'subtotal'   => $updatedCart['subtotal'],
            'subtotal_fmt' => format_rupiah($updatedCart['subtotal'])
        ]);
    }

    public function clear(): void
    {
        $this->cartModel->clearCart(auth_id(), session_id());
        $this->successResponse('Keranjang dikosongkan.');
    }

    public function viewCart(): void
    {
        $userId = auth_id();
        $cartSummary = $this->cartModel->getUserCart($userId, session_id());
        $this->view('customer.cart', [
            'title'        => 'Keranjang Belanja - CicalengkaGO',
            'cart_summary' => $cartSummary,
            'active_tab'   => 'cart'
        ], 'customer_layout');
    }
}
