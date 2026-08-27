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

        // Validasi stok sebelum tambah ke keranjang
        if ((int)($product['stock'] ?? 0) <= 0) {
            $this->errorResponse('Maaf, stok produk "' . $product['name'] . '" sedang habis.');
            return;
        }
        if ($quantity > (int)($product['stock'] ?? 0)) {
            $quantity = (int)$product['stock'];
        }

        $userId = auth_id();
        $sessionId = session_id();

        // Multi-store cart: no longer block adding from a different store

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
        $productId = (int)($data['product_id'] ?? 0);
        $quantity = isset($data['quantity']) ? (int)$data['quantity'] : null;
        $delta = (int)($data['delta'] ?? 0);

        $userId = auth_id();
        $sessionId = session_id();

        if ($cartId <= 0 && $productId > 0) {
            $findItem = Database::fetchOne("SELECT id, quantity FROM cart WHERE product_id = ? AND ((user_id IS NOT NULL AND user_id = ?) OR session_id = ?) ORDER BY id DESC LIMIT 1", [$productId, $userId, $sessionId]);
            if ($findItem) {
                $cartId = (int)$findItem['id'];
            }
        }

        $item = $this->cartModel->find($cartId);
        if (!$item) {
            $this->errorResponse('Item keranjang tidak ditemukan.');
            return;
        }

        if ($quantity !== null) {
            $newQty = $quantity;
        } else {
            $newQty = (int)$item['quantity'] + $delta;
        }

        if ($newQty <= 0) {
            $this->cartModel->delete($cartId);
        } else {
            $this->cartModel->update($cartId, ['quantity' => $newQty]);
        }

        $updatedCart = $this->cartModel->getUserCart($userId, $sessionId);
        $this->successResponse('Keranjang diperbarui', [
            'cart'       => $updatedCart,
            'cart_count' => $updatedCart['count'],
            'subtotal'   => $updatedCart['subtotal'],
            'subtotal_fmt' => format_rupiah($updatedCart['subtotal'])
        ]);
    }

    public function remove(): void
    {
        $data = $this->getPost();
        $cartId = (int)($data['cart_id'] ?? 0);
        $productId = (int)($data['product_id'] ?? 0);

        $userId = auth_id();
        $sessionId = session_id();

        if ($cartId <= 0 && $productId > 0) {
            $findItem = Database::fetchOne("SELECT id FROM cart WHERE product_id = ? AND ((user_id IS NOT NULL AND user_id = ?) OR session_id = ?) ORDER BY id DESC LIMIT 1", [$productId, $userId, $sessionId]);
            if ($findItem) {
                $cartId = (int)$findItem['id'];
            }
        }

        if ($cartId > 0) {
            $this->cartModel->delete($cartId);
        }

        $updatedCart = $this->cartModel->getUserCart($userId, $sessionId);
        $this->successResponse('Item dihapus dari keranjang', [
            'cart'       => $updatedCart,
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

        if ($this->isJsonRequest()) {
            $this->successResponse('Data keranjang belanja', $cartSummary);
            return;
        }

        $this->view('customer.cart', [
            'title'        => 'Keranjang Belanja - CicalengkaGO',
            'cart_summary' => $cartSummary,
            'active_tab'   => 'cart'
        ], 'customer_layout');
    }
}
