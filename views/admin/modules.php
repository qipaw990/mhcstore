<div class="row g-4">
    <!-- Header -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h5 class="fw-bold m-0"><i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i>Modul Bisnis Multi-Vendor Platform</h5>
                    <small class="text-muted">Konfigurasi modul layanan (CicaFood, CicaMart, CicaSend, CicaMed, dll).</small>
                </div>
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" onclick="openAddModuleModal()">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Modul Baru
                </button>
            </div>
        </div>
    </div>

    <!-- Modules Cards / Grid -->
    <div class="col-12">
        <div class="row g-3">
            <?php foreach ($modules as $m): ?>
                <?php
                    $mIcon = $m['icon'] ?? 'box';
                    $mIconClass = (str_starts_with($mIcon, 'bi-') || str_starts_with($mIcon, 'bi ')) ? $mIcon : 'bi-' . $mIcon;
                ?>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div style="width: 52px; height: 52px; border-radius: 16px; background: <?= htmlspecialchars($m['theme_color'] ?? '#2563eb') ?>15; color: <?= htmlspecialchars($m['theme_color'] ?? '#2563eb') ?>; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                                <i class="bi <?= htmlspecialchars($mIconClass) ?>"></i>
                            </div>
                            <span class="badge <?= $m['status'] ? 'bg-success-subtle text-success' : 'bg-secondary' ?>">
                                <?= $m['status'] ? 'AKTIF' : 'NON-AKTIF' ?>
                            </span>
                        </div>
                        <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($m['name']) ?></h6>
                        <span class="badge bg-light text-primary border mb-2 align-self-start">Tipe: <?= strtoupper($m['module_type']) ?></span>
                        <p class="small text-muted mb-3"><?= htmlspecialchars($m['description'] ?: 'Layanan pemesanan ' . $m['name']) ?></p>

                        <div class="mt-auto pt-3 border-top d-flex align-items-center justify-content-between">
                            <span class="small text-muted fw-semibold"><b><?= $m['store_count'] ?? 0 ?></b> Toko Mitra</span>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-light btn-sm rounded-circle" style="width:34px;height:34px;" onclick='openEditModuleModal(<?= json_encode($m) ?>)'>
                                    <i class="bi bi-pencil text-primary"></i>
                                </button>
                                <button type="button" class="btn btn-light btn-sm rounded-circle" style="width:34px;height:34px;" onclick="toggleModuleStatus(<?= $m['id'] ?>, <?= $m['status'] ? 0 : 1 ?>)">
                                    <i class="bi bi-power <?= $m['status'] ? 'text-warning' : 'text-success' ?>"></i>
                                </button>
                                <button type="button" class="btn btn-light btn-sm rounded-circle" style="width:34px;height:34px;" onclick="deleteModule(<?= $m['id'] ?>, '<?= addslashes($m['name']) ?>')">
                                    <i class="bi bi-trash text-danger"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Modal: Module Form (Create / Edit) -->
<div class="modal fade" id="moduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold" id="moduleModalTitle">Tambah Modul Bisnis Baru</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= $baseUrl ?>/admin/modules/save" method="POST">
                <input type="hidden" name="id" id="modId">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Nama Modul *</label>
                            <input type="text" name="name" id="modName" class="form-control rounded-3" placeholder="Contoh: CicaFood Kuliner" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tipe Sistem Modul</label>
                            <select name="module_type" id="modType" class="form-select rounded-3">
                                <option value="food">Food (Kuliner Resto)</option>
                                <option value="grocery">Grocery (Pasar & Mart)</option>
                                <option value="pharmacy">Pharmacy (Apotek Med)</option>
                                <option value="ecommerce">Ecommerce (Toko Online)</option>
                                <option value="parcel">Parcel (Kirim Paket / Logistik)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Icon Bootstrap</label>
                            <input type="text" name="icon" id="modIcon" class="form-control rounded-3" placeholder="bi-egg-fried" value="bi-box">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Warna Tema (Hex Code)</label>
                            <input type="color" name="theme_color" id="modColor" class="form-control form-control-color w-100 rounded-3" value="#2563eb">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Deskripsi Singkat</label>
                            <textarea name="description" id="modDesc" class="form-control rounded-3" rows="2" placeholder="Layanan pesan antar makanan tercepat"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Simpan Modul</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let moduleModal = null;

function openAddModuleModal() {
    document.getElementById('moduleModalTitle').textContent = 'Tambah Modul Bisnis Baru';
    document.getElementById('modId').value = '';
    document.getElementById('modName').value = '';
    document.getElementById('modType').value = 'food';
    document.getElementById('modIcon').value = 'bi-box';
    document.getElementById('modColor').value = '#2563eb';
    document.getElementById('modDesc').value = '';
    
    moduleModal = new bootstrap.Modal(document.getElementById('moduleModal'));
    moduleModal.show();
}

function openEditModuleModal(m) {
    document.getElementById('moduleModalTitle').textContent = 'Edit Modul: ' + m.name;
    document.getElementById('modId').value = m.id;
    document.getElementById('modName').value = m.name;
    document.getElementById('modType').value = m.module_type;
    document.getElementById('modIcon').value = m.icon || 'bi-box';
    document.getElementById('modColor').value = m.theme_color || '#2563eb';
    document.getElementById('modDesc').value = m.description || '';

    moduleModal = new bootstrap.Modal(document.getElementById('moduleModal'));
    moduleModal.show();
}

async function toggleModuleStatus(id, newStatus) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('status', newStatus);

    const res = await fetch(`${window.BASE_URL}/admin/modules/toggle-status`, { method: 'POST', body: formData });
    const json = await res.json();
    if (json.success) {
        Swal.fire({ icon: 'success', title: 'Berhasil', text: json.message }).then(() => location.reload());
    } else {
        Swal.fire({ icon: 'error', title: 'Gagal', text: json.message });
    }
}

function deleteModule(id, name) {
    Swal.fire({
        title: 'Hapus Modul?',
        text: `Apakah Anda yakin ingin menghapus modul "${name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then(async (result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('id', id);
            const res = await fetch(`${window.BASE_URL}/admin/modules/delete`, { method: 'POST', body: fd });
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
