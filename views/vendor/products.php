<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold m-0"><i class="bi bi-egg-fried me-1 text-primary"></i> Daftar Menu & Produk Toko</h6>
        <a href="<?= $baseUrl ?>/vendor/products/create" class="btn btn-primary btn-sm fw-bold">
            <i class="bi bi-plus-lg me-1"></i> Tambah Menu Baru
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Foto</th>
                    <th>Nama Menu</th>
                    <th>Kategori</th>
                    <th>Harga Jual</th>
                    <th>Diskon</th>
                    <th>Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Belum ada menu produk. Silakan tambah menu baru.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($p['image'] ?? 'assets/images/products/default.jpg') ?>" class="rounded-3" style="width: 48px; height: 48px; object-fit: cover;">
                            </td>
                            <td>
                                <div class="fw-bold small"><?= htmlspecialchars($p['name']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($p['description']) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($p['category_name'] ?? 'Umum') ?></span>
                            </td>
                            <td class="fw-bold text-primary"><?= format_rupiah($p['final_price']) ?></td>
                            <td>
                                <?php if ((float)$p['discount'] > 0): ?>
                                    <span class="badge bg-danger-subtle text-danger"><?= (int)$p['discount'] ?>%</span>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-success-subtle text-success"><?= $p['stock'] ?> <?= htmlspecialchars($p['unit']) ?></span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="<?= $baseUrl ?>/vendor/products/edit/<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary py-1 px-2" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button onclick="deleteProduct(<?= $p['id'] ?>)" class="btn btn-sm btn-outline-danger py-1 px-2" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
async function deleteProduct(id) {
    Swal.fire({
        title: 'Hapus Menu?',
        text: 'Menu ini akan dihapus dari etalase toko.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then(async (res) => {
        if (res.isConfirmed) {
            const fd = new FormData();
            fd.append('id', id);
            const resp = await fetch(window.BASE_URL + '/vendor/products/delete', { method: 'POST', body: fd });
            const data = await resp.json();
            if (data.success) {
                location.reload();
            }
        }
    });
}
</script>
