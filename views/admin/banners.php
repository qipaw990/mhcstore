<div class="row g-4">
    <!-- Header -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h5 class="fw-bold m-0"><i class="bi bi-images text-danger me-2"></i>Manajemen Banner Promo & Carousel Slider</h5>
                    <small class="text-muted">Kelola spanduk promo di beranda aplikasi Mobile Customer CicalengkaGO.</small>
                </div>
                <button type="button" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm" onclick="openAddBannerModal()">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Banner Baru
                </button>
            </div>
        </div>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="col-12">
            <div class="alert alert-success alert-dismissible fade show rounded-3 small" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show rounded-3 small" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- Banner Grid Cards -->
    <div class="col-12">
        <div class="row g-3">
            <?php if (empty($banners)): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted">
                        <i class="bi bi-images fs-1 d-block mb-2 text-secondary"></i>
                        Belum ada banner promo dibuat. Klik tombol <strong>Tambah Banner Baru</strong> di atas.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($banners as $b): ?>
                    <?php
                        $imgSrc = $b['image'];
                        if (!empty($imgSrc) && !str_starts_with($imgSrc, 'http://') && !str_starts_with($imgSrc, 'https://')) {
                            $imgSrc = $baseUrl . '/' . ltrim($imgSrc, '/');
                        }
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                            <div style="height: 145px; background: #f1f5f9; position: relative;">
                                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($b['title']) ?>" class="w-100 h-100 object-fit-cover" onerror="this.src='https://placehold.co/720x300/fee2e2/dc2626?text=Promo+CicalengkaGO'">
                                <span class="badge bg-dark bg-opacity-75 text-white position-absolute top-0 start-0 m-2 px-2 py-1 small">
                                    Urutan: #<?= $b['priority'] ?>
                                </span>
                                <span class="badge bg-danger position-absolute top-0 end-0 m-2 px-2 py-1 small">
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
                            <label class="form-label small fw-bold">Upload File Gambar Banner (Rekomendasi: 1080x450 px atau 720x300 px)</label>
                            <input type="file" name="image" id="bannerFileInput" class="form-control rounded-3" accept="image/jpeg,image/png,image/webp" onchange="previewBannerImage(this)">
                            <div class="mt-2 d-none" id="previewContainer">
                                <img id="bannerPreviewImg" src="" class="img-fluid rounded-3 border w-100" style="max-height: 145px; object-fit: cover;">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Atau Gunakan Link / URL Gambar</label>
                            <input type="text" name="image_url" class="form-control rounded-3" placeholder="https://... atau assets/images/banners/banner1.jpg">
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
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">Simpan Banner</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddBannerModal() {
    document.getElementById('previewContainer').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('bannerModal')).show();
}

function previewBannerImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('bannerPreviewImg').src = e.target.result;
            document.getElementById('previewContainer').classList.remove('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function deleteBanner(id, title) {
    if (!confirm(`Apakah Anda yakin ingin menghapus banner "${title}"?`)) {
        return;
    }
    const fd = new FormData();
    fd.append('id', id);
    fetch(`<?= $baseUrl ?>/admin/banners/delete`, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(json => {
            if (json.success) {
                location.reload();
            } else {
                alert(json.message || 'Gagal menghapus banner.');
            }
        })
        .catch(() => alert('Terjadi kesalahan jaringan.'));
}
</script>
