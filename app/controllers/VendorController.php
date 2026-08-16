<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Store;
use App\Models\Order;
use App\Models\Product;
use App\Models\Category;
use App\Models\Wallet;
use App\Core\Database;
use Exception;

class VendorController extends Controller
{
    private Store $storeModel;
    private Order $orderModel;
    private Product $productModel;
    private Wallet $walletModel;

    public function __construct()
    {
        $this->storeModel = new Store();
        $this->orderModel = new Order();
        $this->productModel = new Product();
        $this->walletModel = new Wallet();
    }

    public function dashboard(): void
    {
        $userId = auth_id();
        $store = $this->storeModel->findByVendorId($userId);

        if (!$store) {
            $this->view('vendor.setup_store', ['title' => 'Daftarkan Toko Anda'], 'vendor_layout');
            return;
        }

        $orders = $this->orderModel->getStoreOrders($store['id']);
        $productsCount = $this->productModel->count('store_id = ?', [$store['id']]);
        $wallet = $this->walletModel->getOrCreate($userId, 'vendor');

        $this->view('vendor.dashboard', [
            'title'          => 'Dashboard Mitra Toko - ' . $store['name'],
            'store'          => $store,
            'orders'         => array_slice($orders, 0, 10),
            'total_orders'   => count($orders),
            'products_count' => $productsCount,
            'wallet'         => $wallet,
            'active_tab'     => 'dashboard'
        ], 'vendor_layout');
    }

    public function toggleStoreStatus(): void
    {
        $userId = auth_id();
        $store = $this->storeModel->findByVendorId($userId);
        if (!$store) {
            $this->errorResponse('Toko tidak ditemukan');
            return;
        }

        $newStatus = $store['is_open'] ? 0 : 1;
        $this->storeModel->update($store['id'], ['is_open' => $newStatus]);

        $this->successResponse($newStatus ? 'Toko Anda sekarang BUKA untuk menerima pesanan.' : 'Toko Anda sekarang TUTUP.', [
            'is_open' => $newStatus
        ]);
    }

    public function orders(): void
    {
        $userId = auth_id();
        $store = $this->storeModel->findByVendorId($userId);
        $orders = $store ? $this->orderModel->getStoreOrders($store['id']) : [];

        $this->view('vendor.orders', [
            'title'      => 'Manajemen Pesanan - CicalengkaGO Vendor',
            'store'      => $store,
            'orders'     => $orders,
            'active_tab' => 'orders'
        ], 'vendor_layout');
    }

    public function updateOrderStatus(): void
    {
        $data = $this->getPost();
        $orderId = (int)($data['order_id'] ?? 0);
        $status = sanitize($data['status'] ?? '');

        $order = $this->orderModel->find($orderId);
        if (!$order) {
            $this->errorResponse('Pesanan tidak ditemukan.');
            return;
        }

        $updateData = ['order_status' => $status];
        if ($status === 'processing') {
            $updateData['processing_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'handover') {
            $updateData['handover_at'] = date('Y-m-d H:i:s');
        }

        Database::update('orders', $updateData, 'id = ?', [$orderId]);
        $this->successResponse("Status pesanan #{$order['order_code']} diperbarui menjadi {$status}.");
    }

    public function products(): void
    {
        $userId = auth_id();
        $store = $this->storeModel->findByVendorId($userId);
        $products = $store ? $this->productModel->getByStore($store['id']) : [];

        $this->view('vendor.products', [
            'title'      => 'Katalog Produk & Menu - ' . ($store['name'] ?? ''),
            'store'      => $store,
            'products'   => $products,
            'active_tab' => 'products'
        ], 'vendor_layout');
    }

    public function productForm(?int $id = null): void
    {
        $userId = auth_id();
        $store = $this->storeModel->findByVendorId($userId);
        if (!$store) {
            $this->redirect('vendor');
            return;
        }

        $product = $id ? $this->productModel->findWithDetails($id) : null;
        $categories = (new Category())->getByModule($store['module_id']);

        $this->view('vendor.product_form', [
            'title'      => $id ? 'Edit Menu / Produk' : 'Tambah Menu / Produk Baru',
            'store'      => $store,
            'product'    => $product,
            'categories' => $categories,
            'active_tab' => 'products'
        ], 'vendor_layout');
    }

    public function saveProduct(): void
    {
        $userId = auth_id();
        $store = $this->storeModel->findByVendorId($userId);
        if (!$store) {
            $this->errorResponse('Toko tidak ditemukan.');
            return;
        }

        $data = $this->getPost();
        $id = !empty($data['id']) ? (int)$data['id'] : null;

        $imagePath = $data['existing_image'] ?? 'assets/images/products/default.jpg';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploaded = upload_image($_FILES['image'], 'products');
            if ($uploaded) {
                $imagePath = $uploaded;
            }
        }

        $productData = [
            'store_id'       => $store['id'],
            'module_id'      => $store['module_id'],
            'category_id'    => (int)($data['category_id'] ?? 1),
            'name'           => sanitize($data['name']),
            'description'    => sanitize($data['description'] ?? ''),
            'image'          => $imagePath,
            'price'          => (float)($data['price'] ?? 0),
            'discount'       => (float)($data['discount'] ?? 0),
            'discount_type'  => $data['discount_type'] ?? 'percent',
            'unit'           => sanitize($data['unit'] ?? 'porsi'),
            'stock'          => (int)($data['stock'] ?? 100),
            'is_veg'         => !empty($data['is_veg']) ? 1 : 0,
            'is_recommended' => !empty($data['is_recommended']) ? 1 : 0,
            'status'         => 1
        ];

        if ($id) {
            $this->productModel->update($id, $productData);
        } else {
            $id = $this->productModel->create($productData);
        }

        $this->redirect('vendor/products');
    }

    public function deleteProduct(): void
    {
        $data = $this->getPost();
        $id = (int)($data['id'] ?? 0);
        $this->productModel->delete($id);
        $this->successResponse('Produk berhasil dihapus.');
    }

    public function wallet(): void
    {
        $userId = auth_id();
        $wallet = $this->walletModel->getOrCreate($userId, 'vendor');
        $transactions = $this->walletModel->getTransactions($userId, 50);

        $this->view('vendor.wallet', [
            'title'        => 'Dompet & Pendapatan Toko',
            'wallet'       => $wallet,
            'transactions' => $transactions,
            'active_tab'   => 'wallet'
        ], 'vendor_layout');
    }
}
