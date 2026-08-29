<?php
$active_tab = 'banners';
$activeCount = 0;
foreach ($banners as $b) {
    if (!empty($b['status'])) $activeCount++;
}
$inactiveCount = count($banners) - $activeCount;
?>

<div class="row g-4">
    <!-- Header & Summary Stats -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h4 class="fw-bold m-0 text-dark">
                        <i class="bi bi-images text-danger me-2"></i>Manajemen Banner Promo & Carousel
                    </h4>
                    <p class="text-muted small mb-0 mt-1">Kelola spanduk promo di beranda aplikasi Mobile Customer CicalengkaGO.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-light px-3 py-2 rounded-3 border text-center d-none d-md-block">
                        <span class="text-muted small d-block" style="font-size: 11px;">Total Banner</span>
                        <strong class="text-dark fs-6"><?= count($banners) ?></strong>
                    </div>
                    <div class="bg-success-subtle px-3 py-2 rounded-3 border border-success-subtle text-center d-none d-md-block">
                        <span class="text-success small d-block" style="font-size: 11px;">Aktif Tampil</span>
                        <strong class="text-success fs-6"><?= $activeCount ?></strong>
                    </div>
                    <button type="button" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm" onclick="openAddBannerModal()">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Banner Baru
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
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
                    <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted bg-white">
                        <i class="bi bi-images fs-1 d-block mb-3 text-secondary"></i>
                        <h6 class="fw-bold text-dark">Belum ada banner promo dibuat.</h6>
                        <p class="small text-muted mb-3">Tambahkan banner promo untuk menarik pelanggan di beranda aplikasi.</p>
                        <div>
                            <button type="button" class="btn btn-danger btn-sm rounded-pill px-4 fw-bold" onclick="openAddBannerModal()">
                                <i class="bi bi-plus-lg me-1"></i> Tambah Banner Sekarang
                            </button>
                        </div>
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
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 bg-white">
                            <!-- Image Display with Aspect Ratio 2.4:1 -->
                            <div style="height: 155px; background: #f1f5f9; position: relative; overflow: hidden;">
                                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($b['title']) ?>" class="w-100 h-100 object-fit-cover" onerror="this.src='https://placehold.co/720x300/fee2e2/dc2626?text=Promo+CicalengkaGO'">
                                
                                <span class="badge bg-dark bg-opacity-75 text-white position-absolute top-0 start-0 m-2 px-2 py-1 small rounded-3">
                                    <i class="bi bi-sort-numeric-down me-1"></i>Urutan #<?= $b['priority'] ?>
                                </span>
                                
                                <span class="badge bg-danger position-absolute top-0 end-0 m-2 px-2 py-1 small rounded-3">
                                    <?= htmlspecialchars($b['module_name'] ?? 'Semua Modul') ?>
                                </span>
                            </div>

                            <!-- Card Body -->
                            <div class="card-body p-3 d-flex flex-column">
                                <h6 class="fw-bold text-dark mb-1 text-truncate" title="<?= htmlspecialchars($b['title']) ?>">
                                    <?= htmlspecialchars($b['title']) ?>
                                </h6>
                                
                                <div class="d-flex align-items-center gap-2 mb-3 small text-muted">
                                    <span><i class="bi bi-bullseye me-1 text-danger"></i>Target: <strong><?= strtoupper($b['target_type']) ?> #<?= htmlspecialchars($b['target_id'] ?? '1') ?></strong></span>
                                </div>
                                
                                <!-- Actions Footer -->
                                <div class="mt-auto pt-2 border-top d-flex align-items-center justify-content-between gap-2">
                                    <!-- Status Toggle Button -->
                                    <button type="button" class="btn btn-sm <?= $b['status'] ? 'btn-success' : 'btn-secondary' ?> rounded-pill px-3 py-1" onclick="toggleStatus(<?= $b['id'] ?>)" style="font-size: 11px; font-weight: 700;">
                                        <i class="bi <?= $b['status'] ? 'bi-check-circle-fill' : 'bi-pause-circle-fill' ?> me-1"></i>
                                        <?= $b['status'] ? 'AKTIF' : 'NON-AKTIF' ?>
                                    </button>

                                    <div class="d-flex gap-1">
                                        <!-- Edit Button -->
                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="openEditBannerModal(<?= htmlspecialchars(json_encode($b)) ?>)">
                                            <i class="bi bi-pencil me-1"></i>Edit
                                        </button>
                                        
                                        <!-- Delete Button -->
                                        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-2" onclick="deleteBanner(<?= $b['id'] ?>, '<?= addslashes($b['title']) ?>')" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: Add / Edit Banner -->
<div class="modal fade" id="bannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark" id="modalBannerTitle">Tambah Banner Promo Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= $baseUrl ?>/admin/banners/save" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="bannerIdInput" value="">
                <input type="hidden" name="image_base64" id="bannerBase64Input" value="">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Judul Banner -->
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-dark">Judul Banner / Promo *</label>
                            <input type="text" name="title" id="bannerTitleInput" class="form-control rounded-3" placeholder="Contoh: Diskon Kuliner Murah 50%" required>
                        </div>

                        <!-- Prioritas Urutan -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Prioritas Urutan</label>
                            <input type="number" name="priority" id="bannerPriorityInput" class="form-control rounded-3" value="1" min="1">
                        </div>

                        <!-- Modul Terkait -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Terkait Modul</label>
                            <select name="module_id" id="bannerModuleSelect" class="form-select rounded-3">
                                <option value="">-- Semua Modul (Beranda Utama) --</option>
                                <?php foreach ($modules as $m): ?>
                                    <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Tipe Target Klik -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Tipe Target Klik</label>
                            <select name="target_type" id="bannerTargetTypeSelect" class="form-select rounded-3" onchange="handleTargetTypeChange()">
                                <option value="store">Halaman Toko / Resto</option>
                                <option value="category">Kategori Menu</option>
                                <option value="url">Link URL Eksternal</option>
                            </select>
                        </div>

                        <!-- ID / Value Target Dinamis -->
                        <div class="col-12" id="targetStoreContainer">
                            <label class="form-label small fw-bold text-dark">Pilih Toko / Resto Tujuan</label>
                            <select id="targetStoreSelect" class="form-select rounded-3" onchange="document.getElementById('targetIdInput').value = this.value">
                                <?php foreach ($stores as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 d-none" id="targetCategoryContainer">
                            <label class="form-label small fw-bold text-dark">Pilih Kategori Tujuan</label>
                            <select id="targetCategorySelect" class="form-select rounded-3" onchange="document.getElementById('targetIdInput').value = this.value">
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 d-none" id="targetUrlContainer">
                            <label class="form-label small fw-bold text-dark">Masukkan Link URL</label>
                            <input type="text" id="targetUrlInput" class="form-control rounded-3" placeholder="https://..." oninput="document.getElementById('targetIdInput').value = this.value">
                        </div>

                        <input type="hidden" name="target_id" id="targetIdInput" value="1">

                        <!-- Upload Gambar Banner -->
                        <div class="col-12">
                            <div class="p-3 bg-light rounded-4 border">
                                <label class="form-label small fw-bold text-dark d-flex justify-content-between">
                                    <span>File Gambar Banner Promo</span>
                                    <span class="badge bg-danger-subtle text-danger">Ukuran Pas: 1080x450 px atau 720x300 px</span>
                                </label>
                                
                                <input type="file" name="image" id="bannerFileInput" class="form-control rounded-3 bg-white" accept="image/jpeg,image/png,image/webp,image/gif" onchange="previewBannerImage(this)">
                                
                                <div class="mt-3 text-center" id="previewContainer">
                                    <div class="position-relative d-inline-block w-100">
                                        <img id="bannerPreviewImg" src="https://placehold.co/720x300/fee2e2/dc2626?text=Pilih+Gambar+Banner" class="img-fluid rounded-4 border w-100 shadow-sm" style="max-height: 180px; object-fit: cover;">
                                    </div>
                                    <div class="text-muted small mt-1" style="font-size: 11px;">Pratinjau Banner Live (Rasio 2.4 : 1)</div>
                                </div>
                            </div>
                        </div>

                        <!-- Atau URL Gambar Manual -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">Atau URL Gambar Eksternal (Opsional)</label>
                            <input type="text" name="image_url" id="bannerImageUrlInput" class="form-control rounded-3 small" placeholder="https://... atau assets/images/banners/banner1.jpg">
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
function handleTargetTypeChange() {
    var type = document.getElementById('bannerTargetTypeSelect').value;
    var storeBox = document.getElementById('targetStoreContainer');
    var catBox = document.getElementById('targetCategoryContainer');
    var urlBox = document.getElementById('targetUrlContainer');
    var targetIdInput = document.getElementById('targetIdInput');

    storeBox.classList.add('d-none');
    catBox.classList.add('d-none');
    urlBox.classList.add('d-none');

    if (type === 'store') {
        storeBox.classList.remove('d-none');
        targetIdInput.value = document.getElementById('targetStoreSelect').value || '1';
    } else if (type === 'category') {
        catBox.classList.remove('d-none');
        targetIdInput.value = document.getElementById('targetCategorySelect').value || '1';
    } else {
        urlBox.classList.remove('d-none');
        targetIdInput.value = document.getElementById('targetUrlInput').value || 'https://';
    }
}

function openAddBannerModal() {
    document.getElementById('modalBannerTitle').innerText = 'Tambah Banner Promo Baru';
    document.getElementById('bannerIdInput').value = '';
    document.getElementById('bannerTitleInput').value = '';
    document.getElementById('bannerPriorityInput').value = '1';
    document.getElementById('bannerModuleSelect').value = '';
    document.getElementById('bannerTargetTypeSelect').value = 'store';
    document.getElementById('bannerImageUrlInput').value = '';
    document.getElementById('bannerBase64Input').value = '';
    document.getElementById('bannerFileInput').value = '';
    document.getElementById('bannerPreviewImg').src = 'https://placehold.co/720x300/fee2e2/dc2626?text=Pilih+Gambar+Banner';
    handleTargetTypeChange();
    new bootstrap.Modal(document.getElementById('bannerModal')).show();
}

function openEditBannerModal(banner) {
    document.getElementById('modalBannerTitle').innerText = 'Edit Banner: ' + banner.title;
    document.getElementById('bannerIdInput').value = banner.id;
    document.getElementById('bannerTitleInput').value = banner.title;
    document.getElementById('bannerPriorityInput').value = banner.priority;
    document.getElementById('bannerModuleSelect').value = banner.module_id || '';
    document.getElementById('bannerTargetTypeSelect').value = banner.target_type || 'store';
    document.getElementById('bannerImageUrlInput').value = banner.image || '';
    document.getElementById('bannerBase64Input').value = '';
    document.getElementById('bannerFileInput').value = '';
    
    var imgSrc = banner.image;
    if (imgSrc && !imgSrc.startsWith('http://') && !imgSrc.startsWith('https://')) {
        imgSrc = '<?= $baseUrl ?>/' + imgSrc.replace(/^\//, '');
    }
    document.getElementById('bannerPreviewImg').src = imgSrc || 'https://placehold.co/720x300/fee2e2/dc2626?text=Promo+CicalengkaGO';
    
    handleTargetTypeChange();
    if (banner.target_type === 'store') {
        document.getElementById('targetStoreSelect').value = banner.target_id;
    } else if (banner.target_type === 'category') {
        document.getElementById('targetCategorySelect').value = banner.target_id;
    } else {
        document.getElementById('targetUrlInput').value = banner.target_id;
    }
    document.getElementById('targetIdInput').value = banner.target_id;

    new bootstrap.Modal(document.getElementById('bannerModal')).show();
}

function previewBannerImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('bannerPreviewImg').src = e.target.result;
            document.getElementById('bannerBase64Input').value = e.target.result; // Guaranteed client-side upload payload
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function toggleStatus(id) {
    const fd = new FormData();
    fd.append('id', id);
    fetch(`<?= $baseUrl ?>/admin/banners/toggle-status`, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(json => {
            if (json.success) {
                location.reload();
            } else {
                alert(json.message || 'Gagal mengubah status banner.');
            }
        })
        .catch(() => alert('Terjadi kesalahan jaringan.'));
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
