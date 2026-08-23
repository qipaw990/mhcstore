<?php
    $countTotalProds = count($products);
    $countActiveProds = count(array_filter($products, fn($p) => !empty($p['status'])));
    $countDiscountProds = count(array_filter($products, fn($p) => $p['discount'] > 0));
    $countLowStockProds = count(array_filter($products, fn($p) => $p['stock'] < 10));
?>

<!-- Products KPI Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-muted fw-semibold">Total Menu & Produk</small>
                    <h4 class="fw-black text-dark mb-0 mt-1"><?= $countTotalProds ?></h4>
                </div>
                <div style="width:42px;height:42px;border-radius:12px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#475569;font-size:20px;">
                    <i class="bi bi-box-seam"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-success fw-semibold">Produk Aktif Jual</small>
                    <h4 class="fw-black text-success mb-0 mt-1"><?= $countActiveProds ?></h4>
                </div>
                <div style="width:42px;height:42px;border-radius:12px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;color:#16a34a;font-size:20px;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-danger fw-semibold">Produk Diskon Promo</small>
                    <h4 class="fw-black text-danger mb-0 mt-1"><?= $countDiscountProds ?></h4>
                </div>
                <div style="width:42px;height:42px;border-radius:12px;background:#fef2f2;display:flex;align-items:center;justify-content:center;color:#ef4444;font-size:20px;">
                    <i class="bi bi-tag-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-warning fw-semibold">Stok Menipis (&lt; 10)</small>
                    <h4 class="fw-black text-warning mb-0 mt-1"><?= $countLowStockProds ?></h4>
                </div>
                <div style="width:42px;height:42px;border-radius:12px;background:#fffbeb;display:flex;align-items:center;justify-content:center;color:#d97706;font-size:20px;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Header & Action Bar -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3 flex-grow-1" style="max-width: 400px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 rounded-start-pill"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="productSearchInput" onkeyup="filterProductTable()" class="form-control bg-light border-start-0 rounded-end-pill" placeholder="Cari Menu, Toko, Modul...">
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <form method="GET" action="<?= $baseUrl ?>/admin/products" class="d-flex align-items-center gap-2">
                        <select name="store_id" class="form-select form-select-sm rounded-pill px-3" onchange="this.form.submit()">
                            <option value="0">-- Semua Mitra Toko --</option>
                            <?php foreach ($stores as $st): ?>
                                <option value="<?= $st['id'] ?>" <?= ($store_filter ?? 0) == $st['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($st['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold" onclick="openAddProductModal()">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Produk
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Table List -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="productsTable">
                    <thead>
                        <tr>
                            <th>Produk / Menu</th>
                            <th>Mitra Toko</th>
                            <th>Kategori / Modul</th>
                            <th>Harga Jual</th>
                            <th>Diskon</th>
                            <th>Stok</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">Belum ada produk untuk toko ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <?php $prodImg = asset_url($p['image'] ?? '', 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=100&q=80'); ?>
                                            <img src="<?= $prodImg ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="rounded-3 border shadow-2xs object-fit-cover" style="width: 44px; height: 44px; flex-shrink: 0;" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=100&q=80';">
                                            <div>
                                                <div class="fw-bold small text-dark"><?= htmlspecialchars($p['name']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($p['unit'] ?: 'Item') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-semibold small text-dark"><?= htmlspecialchars($p['store_name']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($p['module_name']) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold small text-dark"><?= format_rupiah($p['price']) ?></div>
                                    </td>
                                    <td>
                                        <?php if ($p['discount'] > 0): ?>
                                            <span class="badge bg-danger-subtle text-danger">
                                                <?= $p['discount_type'] === 'percent' ? $p['discount'] . '%' : format_rupiah($p['discount']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge <?= $p['stock'] < 10 ? 'bg-danger-subtle text-danger' : 'bg-light text-dark border' ?>">
                                                <?= $p['stock'] ?> <?= htmlspecialchars($p['unit'] ?: 'Pcs') ?>
                                            </span>
                                            <button class="btn btn-sm btn-link text-primary p-0" title="Ubah Stok Cepat" onclick="quickUpdateStock(<?= $p['id'] ?>, '<?= addslashes($p['name']) ?>', <?= $p['stock'] ?>)">
                                                <i class="bi bi-pencil-square" style="font-size: 13px;"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $p['status'] ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= $p['status'] ? 'AKTIF' : 'NON-AKTIF' ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-sm rounded-pill px-3 dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                                Aksi
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                                <li>
                                                    <a class="dropdown-item py-2 small" href="javascript:void(0)" onclick='openEditProductModal(<?= json_encode($p) ?>)'>
                                                        <i class="bi bi-pencil-square text-primary me-2"></i> Edit Produk
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item py-2 small" href="javascript:void(0)" onclick="quickUpdateStock(<?= $p['id'] ?>, '<?= addslashes($p['name']) ?>', <?= $p['stock'] ?>)">
                                                        <i class="bi bi-boxes text-info me-2"></i> Atur Jumlah Stok
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item py-2 small" href="javascript:void(0)" onclick="toggleProductStatus(<?= $p['id'] ?>, <?= $p['status'] ? 0 : 1 ?>)">
                                                        <i class="bi bi-power <?= $p['status'] ? 'text-warning' : 'text-success' ?> me-2"></i>
                                                        <?= $p['status'] ? 'Nonaktifkan Produk' : 'Aktifkan Produk' ?>
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item py-2 small text-danger" href="javascript:void(0)" onclick="deleteProduct(<?= $p['id'] ?>, '<?= addslashes($p['name']) ?>')">
                                                        <i class="bi bi-trash me-2"></i> Hapus Produk
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Product Form (Create / Edit) -->
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold" id="productModalTitle">Tambah Produk Baru</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= $baseUrl ?>/admin/products/save" method="POST">
                <input type="hidden" name="id" id="prodId">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Pilih Mitra Toko *</label>
                            <select name="store_id" id="prodStoreId" class="form-select rounded-3" required>
                                <?php foreach ($stores as $st): ?>
                                    <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Nama Menu / Produk *</label>
                            <input type="text" name="name" id="prodName" class="form-control rounded-3" placeholder="Contoh: Paket Ayam Bakar Komplit" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Harga Jual (Rp) *</label>
                            <input type="number" name="price" id="prodPrice" class="form-control rounded-3" placeholder="25000" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Diskon Potongan</label>
                            <input type="number" name="discount" id="prodDiscount" class="form-control rounded-3" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Satuan Unit</label>
                            <input type="text" name="unit" id="prodUnit" class="form-control rounded-3" value="Porsi">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Jumlah Stok</label>
                            <input type="number" name="stock" id="prodStock" class="form-control rounded-3" value="100">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Deskripsi Produk</label>
                            <textarea name="description" id="prodDesc" class="form-control rounded-3" rows="2" placeholder="Komposisi, rasa, variasi porsi..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let productModal = null;

function filterProductTable() {
    const input = document.getElementById('productSearchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#productsTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(input) ? '' : 'none';
    });
}

function openAddProductModal() {
    document.getElementById('productModalTitle').textContent = 'Tambah Produk / Menu Baru';
    document.getElementById('prodId').value = '';
    document.getElementById('prodName').value = '';
    document.getElementById('prodPrice').value = '';
    document.getElementById('prodDiscount').value = '0';
    document.getElementById('prodUnit').value = 'Porsi';
    document.getElementById('prodStock').value = '100';
    document.getElementById('prodDesc').value = '';
    
    productModal = new bootstrap.Modal(document.getElementById('productModal'));
    productModal.show();
}

function openEditProductModal(p) {
    document.getElementById('productModalTitle').textContent = 'Edit Menu #' + p.id;
    document.getElementById('prodId').value = p.id;
    document.getElementById('prodStoreId').value = p.store_id;
    document.getElementById('prodName').value = p.name;
    document.getElementById('prodPrice').value = p.price;
    document.getElementById('prodDiscount').value = p.discount || 0;
    document.getElementById('prodUnit').value = p.unit || 'Porsi';
    document.getElementById('prodStock').value = p.stock || 100;
    document.getElementById('prodDesc').value = p.description || '';

    productModal = new bootstrap.Modal(document.getElementById('productModal'));
    productModal.show();
}

function quickUpdateStock(id, name, currentStock) {
    Swal.fire({
        title: 'Update Stok Menu',
        text: `Atur jumlah ketersediaan stok untuk "${name}":`,
        input: 'number',
        inputValue: currentStock,
        showCancelButton: true,
        confirmButtonText: 'Simpan Stok',
        cancelButtonText: 'Batal',
        inputValidator: (value) => {
            if (!value || parseInt(value) < 0) {
                return 'Jumlah stok tidak boleh kosong atau negatif!';
            }
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('id', id);
            fd.append('stock', result.value);
            const res = await fetch(`${window.BASE_URL}/admin/products/update-stock`, { method: 'POST', body: fd });
            const json = await res.json();
            if (json.success) {
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: json.message, showConfirmButton: false, timer: 1500 }).then(() => location.reload());
            } else {
                Swal.fire('Gagal!', json.message, 'error');
            }
        }
    });
}

async function toggleProductStatus(id, newStatus) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('status', newStatus);

    const res = await fetch(`${window.BASE_URL}/admin/products/toggle-status`, { method: 'POST', body: formData });
    const json = await res.json();
    if (json.success) {
        Swal.fire({ icon: 'success', title: 'Berhasil', text: json.message }).then(() => location.reload());
    } else {
        Swal.fire({ icon: 'error', title: 'Gagal', text: json.message });
    }
}

function deleteProduct(id, name) {
    Swal.fire({
        title: 'Hapus Produk?',
        text: `Apakah Anda yakin ingin menghapus produk "${name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then(async (result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('id', id);
            const res = await fetch(`${window.BASE_URL}/admin/products/delete`, { method: 'POST', body: fd });
            const json = await res.json();
            if (json.success) {
                Swal.fire('Terhapus!', json.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Gagal!', json.message, 'error');
            }
        }
    });
}
</script>
