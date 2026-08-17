<!-- Product Catalog Header -->
<div class="mb-3">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div>
            <h6 class="fw-bold m-0 text-dark" style="font-size: 15px;">
                <i class="bi bi-egg-fried text-danger me-1"></i> Katalog Menu (<?= count($products) ?>)
            </h6>
            <span class="text-muted" style="font-size: 11px;">Kelola stok & menu etalase toko Anda</span>
        </div>
        <a href="<?= $baseUrl ?>/vendor/products/create" class="vnd-action-btn red py-1.5 px-3" style="font-size: 11.5px; border-radius: 20px; flex: initial;">
            <i class="bi bi-plus-lg me-1"></i> Tambah Menu
        </a>
    </div>

    <!-- Product Search Bar -->
    <div class="position-relative">
        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted" style="font-size: 13px;"></i>
        <input type="text" id="productSearchInput" onkeyup="searchProductCards()" placeholder="Cari nama menu..." class="form-control form-control-sm rounded-pill ps-5 bg-white border" style="font-size: 12.5px; padding-top: 8px; padding-bottom: 8px; border-color: #CBD5E1;">
    </div>
</div>

<!-- Product Cards List -->
<?php if (empty($products)): ?>
    <div class="text-center py-5 bg-white rounded-4 border p-4 shadow-xs">
        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 54px; height: 54px;">
            <i class="bi bi-egg-fried text-muted fs-3"></i>
        </div>
        <h6 class="fw-bold text-dark">Belum Ada Menu Terdaftar</h6>
        <p class="small text-muted mb-3">Tambahkan menu pertama Anda agar pembeli bisa memesan di CicalengkaGO.</p>
        <a href="<?= $baseUrl ?>/vendor/products/create" class="btn btn-danger btn-sm rounded-pill px-4 fw-bold" style="background:#EE2737;">
            <i class="bi bi-plus-circle me-1"></i> Tambah Menu Sekarang
        </a>
    </div>
<?php else: ?>
    <div class="d-flex flex-column gap-2.5" id="productListContainer">
        <?php foreach ($products as $p): 
            $isOutOfStock = ((int)$p['stock'] <= 0);
            $isActive = !empty($p['status']);
        ?>
            <div class="vnd-card p-3 product-node" data-name="<?= strtolower(htmlspecialchars($p['name'])) ?>">
                <div class="d-flex gap-3 align-items-center">
                    <img src="<?= $baseUrl ?>/<?= htmlspecialchars($p['image'] ?? 'assets/images/products/default.jpg') ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="rounded-3 border flex-shrink-0" style="width: 68px; height: 68px; object-fit: cover;">
                    
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex align-items-start justify-content-between gap-1">
                            <div>
                                <h6 class="fw-bold m-0 text-dark text-truncate" style="font-size: 13.5px; max-width: 170px;">
                                    <?= htmlspecialchars($p['name']) ?>
                                </h6>
                                <span class="badge bg-light text-muted border my-1" style="font-size: 9.5px;">
                                    <?= htmlspecialchars($p['category_name'] ?? 'Umum') ?>
                                </span>
                            </div>
                            <div class="d-flex gap-1 flex-shrink-0">
                                <a href="<?= $baseUrl ?>/vendor/products/edit/<?= $p['id'] ?>" class="btn btn-light btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center border shadow-xs" style="width: 30px; height: 30px;" title="Edit">
                                    <i class="bi bi-pencil text-primary" style="font-size: 12.5px;"></i>
                                </a>
                                <button onclick="deleteProduct(<?= $p['id'] ?>)" class="btn btn-light btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center border shadow-xs" style="width: 30px; height: 30px;" title="Hapus">
                                    <i class="bi bi-trash text-danger" style="font-size: 12.5px;"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Price & Discount -->
                        <div class="d-flex align-items-center gap-1.5 mt-0.5">
                            <span class="fw-extrabold text-danger" style="font-size: 13.5px;">
                                <?= format_rupiah($p['final_price']) ?>
                            </span>
                            <?php if ((float)$p['discount'] > 0): ?>
                                <span class="text-muted text-decoration-line-through" style="font-size: 10.5px;"><?= format_rupiah($p['price']) ?></span>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 8.5px;"><?= (int)$p['discount'] ?>% OFF</span>
                            <?php endif; ?>
                        </div>

                        <!-- Instant Stock Switch Bar -->
                        <div class="d-flex align-items-center justify-content-between pt-2 mt-1.5 border-top" style="font-size: 11px;">
                            <span class="fw-semibold <?= $isOutOfStock ? 'text-danger' : 'text-success' ?>">
                                <i class="bi <?= $isOutOfStock ? 'bi-x-circle-fill' : 'bi-check-circle-fill' ?> me-0.5"></i>
                                <?= $isOutOfStock ? 'Stok Habis' : 'Tersedia (' . $p['stock'] . ')' ?>
                            </span>
                            
                            <div class="form-check form-switch m-0 d-flex align-items-center gap-1">
                                <label class="form-check-label text-muted small" style="font-size: 10px;" for="stockSwitch<?= $p['id'] ?>">Stok</label>
                                <input class="form-check-input" type="checkbox" role="switch" id="stockSwitch<?= $p['id'] ?>" <?= !$isOutOfStock ? 'checked' : '' ?> onchange="toggleStockStatus(<?= $p['id'] ?>)" style="cursor:pointer; width: 34px; height: 18px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
function searchProductCards() {
    const q = document.getElementById('productSearchInput').value.toLowerCase();
    const nodes = document.querySelectorAll('.product-node');
    nodes.forEach(n => {
        if (n.dataset.name.includes(q)) {
            n.classList.remove('d-none');
        } else {
            n.classList.add('d-none');
        }
    });
}

async function toggleStockStatus(id) {
    const fd = new FormData();
    fd.append('id', id);
    fd.append('field', 'stock');

    try {
        const res = await fetch(window.BASE_URL + '/vendor/products/toggle-status', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();
        if (data.success) {
            showVendorToast(data.message || 'Status stok diperbarui', 'success');
        } else {
            showVendorToast(data.message || 'Gagal mengubah stok', 'warning');
        }
    } catch(e) {
        console.error(e);
    }
}

async function deleteProduct(id) {
    Swal.fire({
        title: 'Hapus Menu?',
        text: 'Menu ini akan dihapus dari etalase toko.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EE2737',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then(async (res) => {
        if (res.isConfirmed) {
            const fd = new FormData();
            fd.append('id', id);
            const resp = await fetch(window.BASE_URL + '/vendor/products/delete', { method: 'POST', body: fd });
            const data = await resp.json();
            if (data.success) {
                showVendorToast('Menu berhasil dihapus', 'success');
                setTimeout(() => location.reload(), 1000);
            }
        }
    });
}
</script>
