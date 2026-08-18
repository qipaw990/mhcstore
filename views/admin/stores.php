<?php
    $countTotalStores = count($stores);
    $countOpenStores = count(array_filter($stores, fn($s) => !empty($s['is_open'])));
    $countClosedStores = count(array_filter($stores, fn($s) => empty($s['is_open'])));
    $countSuspendedStores = count(array_filter($stores, fn($s) => $s['status'] === 'suspended'));
?>

<!-- Stores KPI Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-muted fw-semibold">Total Toko Mitra</small>
                    <h4 class="fw-black text-dark mb-0 mt-1"><?= $countTotalStores ?></h4>
                </div>
                <div style="width:42px;height:42px;border-radius:12px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#475569;font-size:20px;">
                    <i class="bi bi-shop"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-success fw-semibold">Mitra Buka</small>
                    <h4 class="fw-black text-success mb-0 mt-1"><?= $countOpenStores ?></h4>
                </div>
                <div style="width:42px;height:42px;border-radius:12px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;color:#16a34a;font-size:20px;">
                    <i class="bi bi-door-open-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-secondary fw-semibold">Mitra Tutup</small>
                    <h4 class="fw-black text-secondary mb-0 mt-1"><?= $countClosedStores ?></h4>
                </div>
                <div style="width:42px;height:42px;border-radius:12px;background:#f8fafc;display:flex;align-items:center;justify-content:center;color:#64748b;font-size:20px;">
                    <i class="bi bi-door-closed-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-danger fw-semibold">Mitra Suspended</small>
                    <h4 class="fw-black text-danger mb-0 mt-1"><?= $countSuspendedStores ?></h4>
                </div>
                <div style="width:42px;height:42px;border-radius:12px;background:#fef2f2;display:flex;align-items:center;justify-content:center;color:#ef4444;font-size:20px;">
                    <i class="bi bi-shield-x"></i>
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
                        <input type="text" id="storeSearchInput" onkeyup="filterStoreTable()" class="form-control bg-light border-start-0 rounded-end-pill" placeholder="Cari Nama Toko, Modul, Alamat...">
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" id="bulkDeleteBtn" class="btn btn-outline-danger rounded-pill px-3 fw-bold btn-sm" style="display: none;" onclick="bulkDeleteStores()">
                        <i class="bi bi-trash3 me-1"></i> Hapus Terpilih (<span id="selectedStoreCount">0</span>)
                    </button>
                    <?php if (!empty($stores)): ?>
                        <button type="button" class="btn btn-danger rounded-pill px-3 fw-bold btn-sm" onclick="deleteAllStores()">
                            <i class="bi bi-eraser-fill me-1"></i> Kosongkan Semua Toko
                        </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold btn-sm" onclick="openAddStoreModal()">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Mitra Toko
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stores Table List -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="storesTable">
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="ps-3">
                                <input type="checkbox" class="form-check-input" id="selectAllStoresCheck" onchange="toggleSelectAllStores(this)" style="cursor: pointer;">
                            </th>
                            <th>Toko / Merchant</th>
                            <th>Modul Bisnis</th>
                            <th>Pemilik (Vendor)</th>
                            <th>Kontak & Alamat</th>
                            <th>Total Menu</th>
                            <th>Buka / Tutup</th>
                            <th>Status Akun</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stores)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">Belum ada data toko terdaftar.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stores as $s): ?>
                                <tr>
                                    <td class="ps-3">
                                        <input type="checkbox" class="form-check-input store-select-checkbox" value="<?= $s['id'] ?>" onchange="updateBulkDeleteState()" style="cursor: pointer;">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div style="width: 44px; height: 44px; border-radius: 12px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 20px; color: #2563eb; overflow: hidden;">
                                                <i class="bi bi-shop"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold small text-dark"><?= htmlspecialchars($s['name']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($s['zone_name'] ?? 'Zona Cicalengka') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($s['module_name']) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold small text-dark"><?= htmlspecialchars($s['vendor_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($s['vendor_email']) ?></small>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold"><?= htmlspecialchars($s['phone'] ?: '-') ?></div>
                                        <small class="text-muted d-inline-block text-truncate" style="max-width: 160px;"><?= htmlspecialchars($s['address']) ?></small>
                                    </td>
                                    <td>
                                        <a href="<?= $baseUrl ?>/admin/products?store_id=<?= $s['id'] ?>" class="badge bg-light text-primary border text-decoration-none px-2 py-1">
                                            <?= $s['product_count'] ?? 0 ?> Produk <i class="bi bi-arrow-right-short"></i>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" <?= $s['is_open'] ? 'checked' : '' ?> onchange="toggleStoreOpen(<?= $s['id'] ?>, this.checked ? 1 : 0)" style="cursor: pointer;">
                                            <span class="small ms-1 <?= $s['is_open'] ? 'text-success fw-bold' : 'text-muted' ?>">
                                                <?= $s['is_open'] ? 'Buka' : 'Tutup' ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $s['status'] === 'approved' ? 'bg-success-subtle text-success' : ($s['status'] === 'suspended' ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning') ?>">
                                            <?= strtoupper($s['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-sm rounded-pill px-3 dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                                Aksi
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                                <li>
                                                    <a class="dropdown-item py-2 small" href="javascript:void(0)" onclick='openEditStoreModal(<?= json_encode($s) ?>)'>
                                                        <i class="bi bi-pencil-square text-primary me-2"></i> Edit Data Toko
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item py-2 small" href="<?= $baseUrl ?>/admin/products?store_id=<?= $s['id'] ?>">
                                                        <i class="bi bi-box-seam text-info me-2"></i> Kelola Menu & Produk
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item py-2 small" href="javascript:void(0)" onclick="toggleStoreApproval(<?= $s['id'] ?>, '<?= $s['status'] === 'approved' ? 'suspended' : 'approved' ?>')">
                                                        <i class="bi bi-power <?= $s['status'] === 'approved' ? 'text-warning' : 'text-success' ?> me-2"></i>
                                                        <?= $s['status'] === 'approved' ? 'Suspend Toko' : 'Aktifkan (Approve)' ?>
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item py-2 small text-danger" href="javascript:void(0)" onclick="deleteStore(<?= $s['id'] ?>, '<?= addslashes($s['name']) ?>')">
                                                        <i class="bi bi-trash me-2"></i> Hapus Toko
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

<!-- Modal: Store Form (Create / Edit) -->
<div class="modal fade" id="storeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold" id="storeModalTitle">Tambah Mitra Toko Baru</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= $baseUrl ?>/admin/stores/save" method="POST">
                <input type="hidden" name="id" id="storeId">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nama Toko / Resto *</label>
                            <input type="text" name="name" id="storeName" class="form-control rounded-3" placeholder="Contoh: Rumah Makan Padang Sederhana" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Modul Bisnis *</label>
                            <select name="module_id" id="storeModuleId" class="form-select rounded-3" required>
                                <?php foreach ($modules as $m): ?>
                                    <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?> (<?= $m['module_type'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nomor Telepon Toko *</label>
                            <input type="text" name="phone" id="storePhone" class="form-control rounded-3" placeholder="08xxxxxxxx" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Email Toko</label>
                            <input type="email" name="email" id="storeEmail" class="form-control rounded-3" placeholder="toko@cicalengkago.id">
                        </div>

                        <!-- Vendor Account Info (for new stores) -->
                        <div id="vendorAccountSection" class="col-12 p-3 bg-light rounded-3 mt-2">
                            <div class="fw-bold small mb-2 text-primary"><i class="bi bi-person-badge me-1"></i> Akun Login Pemilik Toko (Vendor)</div>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <input type="text" name="vendor_name" id="vendorName" class="form-control form-control-sm rounded-3" placeholder="Nama Pemilik Toko">
                                </div>
                                <div class="col-md-4">
                                    <input type="email" name="vendor_email" id="vendorEmail" class="form-control form-control-sm rounded-3" placeholder="Email Login Vendor">
                                </div>
                                <div class="col-md-4">
                                    <input type="password" name="vendor_password" id="vendorPassword" class="form-control form-control-sm rounded-3" placeholder="Password (default: 123456)">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Alamat Lengkap *</label>
                            <textarea name="address" id="storeAddress" class="form-control rounded-3" rows="2" placeholder="Jl. Raya Cicalengka No. ..." required></textarea>
                        </div>

                        <!-- Interactive Map Pin Picker -->
                        <div class="col-12">
                            <label class="form-label small fw-bold d-flex justify-content-between">
                                <span><i class="bi bi-pin-map-fill text-danger me-1"></i> Titik Lokasi Peta (Klik atau Geser Pin di Peta)</span>
                                <span class="text-muted small">Kecamatan Cicalengka</span>
                            </label>
                            <div id="store-picker-map" style="width: 100%; height: 220px; border-radius: 12px; border: 1px solid #cbd5e1;"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Zona Pengantaran</label>
                            <select name="zone_id" id="storeZoneId" class="form-select rounded-3">
                                <?php foreach ($zones as $z): ?>
                                    <option value="<?= $z['id'] ?>"><?= htmlspecialchars($z['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Latitude GPS *</label>
                            <input type="number" step="any" name="latitude" id="storeLat" class="form-control rounded-3" value="-6.9840" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Longitude GPS *</label>
                            <input type="number" step="any" name="longitude" id="storeLng" class="form-control rounded-3" value="107.8340" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Minimal Order (Rp)</label>
                            <input type="number" name="minimum_order" id="storeMinOrder" class="form-control rounded-3" value="10000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Estimasi Pengolahan</label>
                            <input type="text" name="delivery_time" id="storeTime" class="form-control rounded-3" value="20-30 Menit">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Status Toko</label>
                            <select name="status" id="storeStatus" class="form-select rounded-3">
                                <option value="approved">Approved (Aktif)</option>
                                <option value="pending">Pending</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Simpan Data Toko</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let storeModal = null;
let pickerMap = null;
let pickerMarker = null;

function initStorePickerMap(lat, lng) {
    if (!pickerMap) {
        pickerMap = L.map('store-picker-map').setView([lat, lng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(pickerMap);
        pickerMarker = L.marker([lat, lng], { draggable: true }).addTo(pickerMap);
        
        pickerMarker.on('dragend', function (e) {
            const position = pickerMarker.getLatLng();
            document.getElementById('storeLat').value = position.lat.toFixed(6);
            document.getElementById('storeLng').value = position.lng.toFixed(6);
        });

        pickerMap.on('click', function(e) {
            pickerMarker.setLatLng(e.latlng);
            document.getElementById('storeLat').value = e.latlng.lat.toFixed(6);
            document.getElementById('storeLng').value = e.latlng.lng.toFixed(6);
        });
    } else {
        pickerMap.setView([lat, lng], 15);
        pickerMarker.setLatLng([lat, lng]);
        setTimeout(() => pickerMap.invalidateSize(), 300);
    }
}

function filterStoreTable() {
    const input = document.getElementById('storeSearchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#storesTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(input) ? '' : 'none';
    });
}

function openAddStoreModal() {
    document.getElementById('storeModalTitle').textContent = 'Tambah Mitra Toko Baru';
    document.getElementById('storeId').value = '';
    document.getElementById('storeName').value = '';
    document.getElementById('storePhone').value = '';
    document.getElementById('storeEmail').value = '';
    document.getElementById('storeAddress').value = 'Kec. Cicalengka, Kab. Bandung';
    document.getElementById('storeLat').value = '-6.9840';
    document.getElementById('storeLng').value = '107.8340';
    document.getElementById('vendorAccountSection').style.display = 'block';
    
    storeModal = new bootstrap.Modal(document.getElementById('storeModal'));
    storeModal.show();

    setTimeout(() => initStorePickerMap(-6.9840, 107.8340), 400);
}

function openEditStoreModal(store) {
    document.getElementById('storeModalTitle').textContent = 'Edit Data Mitra Toko #' + store.id;
    document.getElementById('storeId').value = store.id;
    document.getElementById('storeName').value = store.name;
    document.getElementById('storeModuleId').value = store.module_id;
    document.getElementById('storeZoneId').value = store.zone_id || 1;
    document.getElementById('storePhone').value = store.phone || '';
    document.getElementById('storeEmail').value = store.email || '';
    document.getElementById('storeAddress').value = store.address || '';
    const lat = parseFloat(store.latitude || -6.9840);
    const lng = parseFloat(store.longitude || 107.8340);
    document.getElementById('storeLat').value = lat;
    document.getElementById('storeLng').value = lng;
    document.getElementById('storeMinOrder').value = store.minimum_order || 10000;
    document.getElementById('storeTime').value = store.delivery_time || '20-30 Menit';
    document.getElementById('storeStatus').value = store.status || 'approved';
    document.getElementById('vendorAccountSection').style.display = 'none';

    storeModal = new bootstrap.Modal(document.getElementById('storeModal'));
    storeModal.show();

    setTimeout(() => initStorePickerMap(lat, lng), 400);
}

async function toggleStoreOpen(storeId, isOpen) {
    const formData = new FormData();
    formData.append('store_id', storeId);
    formData.append('is_open', isOpen);

    const res = await fetch(`${window.BASE_URL}/admin/stores/toggle-open`, { method: 'POST', body: formData });
    const json = await res.json();
    if (json.success) {
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: json.message, showConfirmButton: false, timer: 2000 });
    } else {
        Swal.fire({ icon: 'error', title: 'Gagal', text: json.message });
    }
}

async function toggleStoreApproval(storeId, newStatus) {
    const formData = new FormData();
    formData.append('store_id', storeId);
    formData.append('status', newStatus);

    const res = await fetch(`${window.BASE_URL}/admin/stores/update-status`, { method: 'POST', body: formData });
    const json = await res.json();
    if (json.success) {
        Swal.fire({ icon: 'success', title: 'Berhasil', text: json.message }).then(() => location.reload());
    } else {
        Swal.fire({ icon: 'error', title: 'Gagal', text: json.message });
    }
}

function deleteStore(id, name) {
    Swal.fire({
        title: 'Hapus Toko?',
        text: `Apakah Anda yakin ingin menghapus toko "${name}"? Semua produk terkait juga akan terhapus.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Ya, Hapus Toko!',
        cancelButtonText: 'Batal'
    }).then(async (result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('id', id);
            const res = await fetch(`${window.BASE_URL}/admin/stores/delete`, { method: 'POST', body: fd });
            const json = await res.json();
            if (json.success) {
                Swal.fire('Terhapus!', json.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Gagal!', json.message, 'error');
            }
        }
    });
}

function toggleSelectAllStores(masterCheck) {
    const checkboxes = document.querySelectorAll('.store-select-checkbox');
    checkboxes.forEach(cb => cb.checked = masterCheck.checked);
    updateBulkDeleteState();
}

function updateBulkDeleteState() {
    const selected = document.querySelectorAll('.store-select-checkbox:checked');
    const bulkBtn = document.getElementById('bulkDeleteBtn');
    const countSpan = document.getElementById('selectedStoreCount');
    
    if (selected.length > 0) {
        bulkBtn.style.display = 'inline-block';
        countSpan.textContent = selected.length;
    } else {
        bulkBtn.style.display = 'none';
        countSpan.textContent = '0';
    }
}

function bulkDeleteStores() {
    const selected = Array.from(document.querySelectorAll('.store-select-checkbox:checked')).map(cb => cb.value);
    if (selected.length === 0) {
        Swal.fire('Perhatian', 'Pilih setidaknya satu toko yang ingin dihapus.', 'info');
        return;
    }

    Swal.fire({
        title: `Hapus ${selected.length} Toko Terpilih?`,
        text: `Semua toko yang Anda centang beserta seluruh menu produknya akan dihapus permanen!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: `Ya, Hapus ${selected.length} Toko!`,
        cancelButtonText: 'Batal'
    }).then(async (result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('ids', selected.join(','));
            const res = await fetch(`${window.BASE_URL}/admin/stores/bulk-delete`, { method: 'POST', body: fd });
            const json = await res.json();
            if (json.success) {
                Swal.fire('Terhapus!', json.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Gagal!', json.message, 'error');
            }
        }
    });
}

function deleteAllStores() {
    Swal.fire({
        title: '⚠️ KOSONGKAN SELURUH TOKO MITRA?',
        text: 'Tindakan ini akan menghapus SEMUA toko mitra, menu produk, dan akun vendor dari sistem! Data yang terhapus tidak dapat dikembalikan.',
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Ya, Kosongkan Seluruh Data Toko!',
        cancelButtonText: 'Batal'
    }).then(async (result) => {
        if (result.isConfirmed) {
            const res = await fetch(`${window.BASE_URL}/admin/stores/delete-all`, { method: 'POST' });
            const json = await res.json();
            if (json.success) {
                Swal.fire('Berhasil Dikersihkan!', json.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Gagal!', json.message, 'error');
            }
        }
    });
}
</script>
