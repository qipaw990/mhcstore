<div class="row g-4">
    <!-- Header -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h5 class="fw-bold m-0"><i class="bi bi-images text-primary me-2"></i>Manajemen Banner Promo & Carousel Slider</h5>
                    <small class="text-muted">Kelola spanduk promo di beranda aplikasi PWA Customer CicalengkaGO.</small>
                </div>
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" onclick="openAddBannerModal()">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Banner Baru
                </button>
            </div>
        </div>
    </div>

    <!-- Banner Grid Cards -->
    <div class="col-12">
        <div class="row g-3">
            <?php if (empty($banners)): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted">
                        <i class="bi bi-images fs-1 d-block mb-2 text-secondary"></i>
                        Belum ada banner promo dibuat.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($banners as $b): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                            <div style="height: 140px; background: #e2e8f0; position: relative;">
                                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($b['image']) ?>" alt="<?= htmlspecialchars($b['title']) ?>" class="w-100 h-100 object-fit-cover" onerror="this.src='https://placehold.co/600x250/ee2737/ffffff?text=Promo+CicalengkaGO'">
                                <span class="badge bg-dark bg-opacity-75 text-white position-absolute top-0 start-0 m-2 px-2 py-1 small">
                                    Urutan: #<?= $b['priority'] ?>
                                </span>
                                <span class="badge bg-primary position-absolute top-0 end-0 m-2 px-2 py-1 small">
                                    <?= htmlspecialchars($b['module_name'] ?? 'Semua Modul') ?>
                                </span>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($b['title']) ?></h6>
                                <small class="text-muted mb-3">Tipe: <?= strtoupper($b['banner_type']) ?> • Target: <?= strtoupper($b['target_type']) ?></small>
                                
                                <div class="mt-auto pt-2 border-top d-flex align-items-center justify-content-between">
                                    <span class="badge <?= $b['status'] ? 'bg-success-subtle text-success' : 'bg-secondary' ?>">
                                        <?= $b['status'] ? 'AKTIF TAMPIL' : 'NON-AKTIF' ?>
                                    </span>
                                    <button class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="deleteBanner(<?= $b['id'] ?>, '<?= addslashes($b['title']) ?>')">
                                        <i class="bi bi-trash me-1"></i> Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: Add Banner -->
<div class="modal fade" id="bannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold">Tambah Banner Promo Baru</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= $baseUrl ?>/admin/banners/save" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Judul Banner / Promo *</label>
                            <input type="text" name="title" class="form-control rounded-3" placeholder="Contoh: Diskon Kuliner Murah 50%" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Terkait Modul</label>
                            <select name="module_id" class="form-select rounded-3">
                                <option value="">-- Semua Modul --</option>
                                <?php foreach ($modules as $m): ?>
                                    <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Prioritas Urutan</label>
                            <input type="number" name="priority" class="form-control rounded-3" value="1" min="1">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Upload File Gambar (JPG/PNG/WebP)</label>
                            <input type="file" name="image" class="form-control rounded-3" accept="image/*">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Atau URL Gambar Banner</label>
                            <input type="text" name="image_url" class="form-control rounded-3" placeholder="assets/images/banners/banner1.jpg">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tipe Target Klik</label>
                            <select name="target_type" class="form-select rounded-3">
                                <option value="store">Halaman Toko</option>
                                <option value="category">Kategori</option>
                                <option value="url">Link Eksternal</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">ID / Value Target</label>
                            <input type="text" name="target_id" class="form-control rounded-3" value="1">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Simpan Banner</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddBannerModal() {
    new bootstrap.Modal(document.getElementById('bannerModal')).show();
}

function deleteBanner(id, title) {
    Swal.fire({
        title: 'Hapus Banner?',
        text: `Apakah Anda yakin ingin menghapus banner "${title}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then(async (result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('id', id);
            const res = await fetch(`${window.BASE_URL}/admin/banners/delete`, { method: 'POST', body: fd });
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
