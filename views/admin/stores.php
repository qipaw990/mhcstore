<?php
    $countTotalStores = $total_stores ?? count($stores);
    $countOpenStores = $total_open_stores ?? 0;
    $countClosedStores = $total_closed_stores ?? 0;
    $countSuspendedStores = $total_suspended_stores ?? 0;
?>

<!-- Stores KPI Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-muted fw-semibold">Total Toko Mitra</small>
                    <h4 class="fw-black text-dark mb-0 mt-1"><?= number_format($countTotalStores) ?></h4>
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
                    <h4 class="fw-black text-success mb-0 mt-1"><?= number_format($countOpenStores) ?></h4>
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
                    <h4 class="fw-black text-secondary mb-0 mt-1"><?= number_format($countClosedStores) ?></h4>
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
                    <h4 class="fw-black text-danger mb-0 mt-1"><?= number_format($countSuspendedStores) ?></h4>
                </div>
                <div style="width:42px;height:42px;border-radius:12px;background:#fef2f2;display:flex;align-items:center;justify-content:center;color:#ef4444;font-size:20px;">
                    <i class="bi bi-x-circle-fill"></i>
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
                    <form method="GET" action="<?= $baseUrl ?>/admin/stores" class="input-group input-group-sm w-100">
                        <span class="input-group-text bg-light border-end-0 rounded-start-pill"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" class="form-control bg-light border-start-0 rounded-end-pill" placeholder="Cari Nama Toko, Modul, Alamat... (Tekan Enter)">
                    </form>
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
                                            <?php $storeLogo = asset_url($s['logo'] ?? '', 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=100&q=80'); ?>
                                            <img src="<?= $storeLogo ?>" alt="<?= htmlspecialchars($s['name']) ?>" class="rounded-3 border shadow-2xs object-fit-cover" style="width: 44px; height: 44px; flex-shrink: 0;" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=100&q=80';">
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
                                        <?php if ($s['status'] === 'approved'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                <i class="bi bi-check-circle-fill me-1"></i> AKTIF (APPROVED)
                                            </span>
                                        <?php elseif ($s['status'] === 'pending'): ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                                <i class="bi bi-hourglass-split me-1"></i> REVIEW ADMIN (PENDING)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                                <i class="bi bi-slash-circle-fill me-1"></i> SUSPENDED
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex align-items-center justify-content-end gap-1.5">
                                            <?php if ($s['status'] === 'pending'): ?>
                                                <button type="button" class="btn btn-success btn-sm rounded-pill px-2.5 py-1 fw-bold shadow-2xs" onclick="toggleStoreApproval(<?= $s['id'] ?>, 'approved')" title="Setujui dan Aktifkan Toko">
                                                    <i class="bi bi-check-lg me-1"></i> Setujui
                                                </button>
                                            <?php endif; ?>
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
                                                            <?= $s['status'] === 'approved' ? 'Suspend Toko' : 'Setujui & Aktifkan Toko' ?>
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
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Stores Table Pagination Footer -->
            <?php if (($total_pages ?? 1) > 1): ?>
                <div class="card-footer bg-white border-top py-2.5 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <small class="text-muted fw-semibold">
                        Menampilkan Halaman <span class="text-dark fw-bold"><?= $current_page ?></span> dari <span class="text-dark fw-bold"><?= $total_pages ?></span> (<span class="text-primary fw-bold"><?= number_format($total_stores ?? 0) ?></span> Total Toko)
                    </small>
                    <nav aria-label="Stores pagination">
                        <ul class="pagination pagination-sm m-0 gap-1">
                            <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link px-2.5 py-1 rounded-2 border text-decoration-none small fw-semibold <?= ($current_page <= 1) ? 'text-muted bg-light' : 'text-dark bg-white' ?>" href="?page=<?= max(1, $current_page - 1) ?>&search=<?= urlencode($search ?? '') ?>">
                                    <i class="bi bi-chevron-left me-1"></i> Prev
                                </a>
                            </li>
                            <?php 
                                $startP = max(1, $current_page - 2);
                                $endP = min($total_pages, $current_page + 2);
                                for ($p = $startP; $p <= $endP; $p++): 
                            ?>
                                <li class="page-item">
                                    <a class="page-link px-2.5 py-1 rounded-2 border text-decoration-none small fw-bold <?= ($p == $current_page) ? 'bg-primary text-white border-primary' : 'bg-white text-dark' ?>" href="?page=<?= $p ?>&search=<?= urlencode($search ?? '') ?>">
                                        <?= $p ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link px-2.5 py-1 rounded-2 border text-decoration-none small fw-semibold <?= ($current_page >= $total_pages) ? 'text-muted bg-light' : 'text-dark bg-white' ?>" href="?page=<?= min($total_pages, $current_page + 1) ?>&search=<?= urlencode($search ?? '') ?>">
                                    Next <i class="bi bi-chevron-right ms-1"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
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

                        <!-- Interactive Map Pin Picker with Location Calibration -->
                        <div class="col-12">
                            <div class="p-3 bg-light rounded-4 border">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                    <div>
                                        <div class="fw-bold small text-dark"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Kalibrasi Titik Lokasi Peta Toko</div>
                                        <small class="text-muted" style="font-size: 11px;">Akurasi titik koordinat penjemputan pesanan oleh driver</small>
                                    </div>
                                    <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill fw-bold" onclick="calibrateCurrentGPS()" title="Deteksi lokasi GPS perangkat saya sekarang">
                                            <i class="bi bi-crosshair me-1"></i> <span id="gpsBtnLabel">Kalibrasi GPS Saya</span>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill fw-bold" onclick="calibrateFromAddress()" title="Cari koordinat berdasarkan teks alamat di atas">
                                            <i class="bi bi-geo me-1"></i> Dari Alamat
                                        </button>
                                    </div>
                                </div>

                                <!-- Search & Quick Calibration Toolbar -->
                                <div class="row g-2 mb-2">
                                    <div class="col-12">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                            <input type="text" id="mapSearchInput" class="form-control border-start-0" placeholder="Ketik nama jalan, tempat, atau patokan di Cicalengka..." onkeydown="if(event.key==='Enter'){event.preventDefault();searchLocationOnMap();}">
                                            <button type="button" class="btn btn-dark fw-bold px-3" onclick="searchLocationOnMap()"><i class="bi bi-search me-1"></i> Cari</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Preset Chips Cicalengka -->
                                <div class="d-flex align-items-center gap-1 flex-wrap mb-2">
                                    <small class="text-muted fw-bold me-1" style="font-size: 10.5px;">Patokan Cepat:</small>
                                    <button type="button" class="badge bg-white text-dark border text-decoration-none shadow-2xs py-1.5 px-2" onclick="setPresetLocation(-6.983935, 107.834015, 'Alun-Alun Cicalengka')">🏛️ Alun-Alun Cicalengka</button>
                                    <button type="button" class="badge bg-white text-dark border text-decoration-none shadow-2xs py-1.5 px-2" onclick="setPresetLocation(-6.982230, 107.838520, 'Stasiun KAI Cicalengka')">🚆 Stasiun Cicalengka</button>
                                    <button type="button" class="badge bg-white text-dark border text-decoration-none shadow-2xs py-1.5 px-2" onclick="setPresetLocation(-6.978050, 107.842010, 'RSUD Cicalengka')">🏥 RSUD Cicalengka</button>
                                    <button type="button" class="badge bg-white text-dark border text-decoration-none shadow-2xs py-1.5 px-2" onclick="setPresetLocation(-6.981500, 107.835500, 'Pasar Cicalengka')">🏬 Pasar Cicalengka</button>
                                    <button type="button" class="badge bg-white text-dark border text-decoration-none shadow-2xs py-1.5 px-2" onclick="setPresetLocation(-6.984500, 107.833500, 'Masjid Agung Cicalengka')">🕌 Masjid Agung</button>
                                    <button type="button" class="badge bg-white text-dark border text-decoration-none shadow-2xs py-1.5 px-2" onclick="setPresetLocation(-6.989000, 107.845000, 'Bypass Cicalengka')">🛣️ Bypass Cicalengka</button>
                                    <button type="button" class="badge bg-white text-dark border text-decoration-none shadow-2xs py-1.5 px-2" onclick="setPresetLocation(-7.005000, 107.828000, 'Cikancung')">🏞️ Cikancung</button>
                                    <button type="button" class="badge bg-white text-dark border text-decoration-none shadow-2xs py-1.5 px-2" onclick="setPresetLocation(-7.028000, 107.892000, 'Nagreg Simpang')">🌄 Nagreg</button>
                                </div>

                                <div id="store-picker-map" style="width: 100%; height: 240px; border-radius: 12px; border: 1px solid #cbd5e1; z-index: 1;"></div>
                                
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-2" style="font-size: 11px;">
                                    <div class="text-muted" id="calibrationStatusBadge">
                                        <i class="bi bi-info-circle text-primary me-1"></i> Klik atau geser pin merah di peta untuk menyetel titik akurat.
                                    </div>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-primary fw-bold" onclick="reverseGeocodeCurrentPoint()">
                                        <i class="bi bi-arrow-repeat me-1"></i> Sinkronkan Nama Jalan ke Alamat
                                    </button>
                                </div>
                            </div>
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
                            <input type="number" step="any" name="latitude" id="storeLat" class="form-control rounded-3" value="-6.9840" required onchange="onManualCoordsChange()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Longitude GPS *</label>
                            <input type="number" step="any" name="longitude" id="storeLng" class="form-control rounded-3" value="107.8340" required onchange="onManualCoordsChange()">
                        </div>

                        <!-- Review Dokumen Pendaftaran (KTP, Logo, Foto Toko) -->
                        <div class="col-12" id="storeDocsSection">
                            <div class="p-3 bg-light rounded-4 border">
                                <div class="fw-bold small text-dark mb-2"><i class="bi bi-images me-1 text-primary"></i> Dokumen Pendaftaran Toko (KTP, Logo, Foto Toko)</div>
                                <div class="row g-2 text-center">
                                    <div class="col-4">
                                        <small class="fw-bold d-block text-muted mb-1" style="font-size: 10.5px;">Foto KTP Pemilik</small>
                                        <div id="previewKtpBox" class="border rounded-3 p-1 bg-white shadow-2xs" style="height: 85px; display: flex; align-items: center; justify-content: center; cursor: pointer;" title="Klik untuk memperbesar">
                                            <img id="storeModalKtp" src="" class="img-fluid rounded-2 object-fit-cover w-100 h-100" style="display:none;" onclick="window.open(this.src, '_blank')">
                                            <span id="noKtpText" class="text-muted small" style="font-size: 10.5px;"><i class="bi bi-card-heading d-block fs-5 text-secondary"></i> Tidak ada KTP</span>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <small class="fw-bold d-block text-muted mb-1" style="font-size: 10.5px;">Logo Toko</small>
                                        <div class="border rounded-3 p-1 bg-white shadow-2xs" style="height: 85px; display: flex; align-items: center; justify-content: center; cursor: pointer;" title="Klik untuk memperbesar">
                                            <img id="storeModalLogo" src="" class="img-fluid rounded-2 object-fit-cover w-100 h-100" style="display:none;" onclick="window.open(this.src, '_blank')">
                                            <span id="noLogoText" class="text-muted small" style="font-size: 10.5px;"><i class="bi bi-image d-block fs-5 text-secondary"></i> Default Logo</span>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <small class="fw-bold d-block text-muted mb-1" style="font-size: 10.5px;">Foto Toko / Banner</small>
                                        <div class="border rounded-3 p-1 bg-white shadow-2xs" style="height: 85px; display: flex; align-items: center; justify-content: center; cursor: pointer;" title="Klik untuk memperbesar">
                                            <img id="storeModalCover" src="" class="img-fluid rounded-2 object-fit-cover w-100 h-100" style="display:none;" onclick="window.open(this.src, '_blank')">
                                            <span id="noCoverText" class="text-muted small" style="font-size: 10.5px;"><i class="bi bi-camera d-block fs-5 text-secondary"></i> Default Foto</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
let pickerRadar = null;

function initStorePickerMap(lat, lng) {
    const defaultLat = parseFloat(lat) || -6.9840;
    const defaultLng = parseFloat(lng) || 107.8340;

    if (!pickerMap) {
        pickerMap = L.map('store-picker-map').setView([defaultLat, defaultLng], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(pickerMap);
        
        pickerMarker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(pickerMap);

        // Radar Delivery Radius Circle (5000m)
        pickerRadar = L.circle([defaultLat, defaultLng], {
            radius: 5000,
            color: '#EE2737',
            weight: 2,
            fillColor: '#EE2737',
            fillOpacity: 0.12,
            dashArray: '6, 6'
        }).addTo(pickerMap);
        
        pickerMarker.on('drag', function (e) {
            const position = pickerMarker.getLatLng();
            if (pickerRadar) pickerRadar.setLatLng(position);
        });

        pickerMarker.on('dragend', function (e) {
            const position = pickerMarker.getLatLng();
            if (pickerRadar) pickerRadar.setLatLng(position);
            updateCoordsFields(position.lat, position.lng, 'Pin Digeser Manual');
        });

        pickerMap.on('click', function(e) {
            pickerMarker.setLatLng(e.latlng);
            if (pickerRadar) pickerRadar.setLatLng(e.latlng);
            updateCoordsFields(e.latlng.lat, e.latlng.lng, 'Titik Peta Dipilih');
        });
    } else {
        pickerMap.setView([defaultLat, defaultLng], 14);
        pickerMarker.setLatLng([defaultLat, defaultLng]);
        if (pickerRadar) pickerRadar.setLatLng([defaultLat, defaultLng]);
        setTimeout(() => pickerMap.invalidateSize(), 300);
    }
    updateCoordsFields(defaultLat, defaultLng, 'Inisialisasi');
}

function updateCoordsFields(lat, lng, sourceLabel = '') {
    const latFixed = parseFloat(lat).toFixed(6);
    const lngFixed = parseFloat(lng).toFixed(6);
    document.getElementById('storeLat').value = latFixed;
    document.getElementById('storeLng').value = lngFixed;
    
    const badge = document.getElementById('calibrationStatusBadge');
    if (badge) {
        badge.innerHTML = `<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle-fill me-1"></i> Terkalibrasi: ${latFixed}, ${lngFixed} (${sourceLabel || 'Akurat'})</span>`;
    }
}

function onManualCoordsChange() {
    const lat = parseFloat(document.getElementById('storeLat').value);
    const lng = parseFloat(document.getElementById('storeLng').value);
    if (!isNaN(lat) && !isNaN(lng) && pickerMap && pickerMarker) {
        pickerMarker.setLatLng([lat, lng]);
        pickerMap.setView([lat, lng], 16);
        updateCoordsFields(lat, lng, 'Input Manual');
    }
}

// ── 1. KALIBRASI GPS REAL-TIME PERANGKAT ──
function calibrateCurrentGPS() {
    const btnLabel = document.getElementById('gpsBtnLabel');
    if (!navigator.geolocation) {
        Swal.fire({ icon: 'warning', title: 'GPS Tidak Didukung', text: 'Browser tidak mendukung deteksi geolokasi.' });
        return;
    }

    if (btnLabel) btnLabel.textContent = 'Mendeteksi GPS...';
    navigator.geolocation.getCurrentPosition(
        function (pos) {
            if (btnLabel) btnLabel.textContent = 'Kalibrasi GPS Saya';
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            const accuracy = Math.round(pos.coords.accuracy || 10);
            
            if (pickerMap && pickerMarker) {
                pickerMarker.setLatLng([lat, lng]);
                pickerMap.setView([lat, lng], 17);
            }
            updateCoordsFields(lat, lng, `GPS Akurat ±${accuracy}m`);
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: `GPS Terkalibrasi (Akurasi ±${accuracy}m)`,
                showConfirmButton: false,
                timer: 2500
            });
        },
        function (err) {
            if (btnLabel) btnLabel.textContent = 'Kalibrasi GPS Saya';
            Swal.fire({ icon: 'error', title: 'Gagal Akses GPS', text: 'Pastikan izin lokasi telah diizinkan di browser.' });
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}

// ── 2. PRESET LOKASI PATOKAN CICALENGKA ──
function setPresetLocation(lat, lng, label) {
    if (pickerMap && pickerMarker) {
        pickerMarker.setLatLng([lat, lng]);
        pickerMap.setView([lat, lng], 16);
    }
    updateCoordsFields(lat, lng, label);
    
    // Auto update address if still default
    const addrField = document.getElementById('storeAddress');
    if (addrField && (addrField.value.trim() === '' || addrField.value.includes('Kec. Cicalengka'))) {
        addrField.value = `${label}, Kec. Cicalengka, Kab. Bandung`;
    }
}

// ── 3. CARI LOKASI DARI INPUT PENCARIAN ──
async function searchLocationOnMap() {
    const query = document.getElementById('mapSearchInput').value.trim();
    if (!query) {
        Swal.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Ketik nama lokasi terlebih dahulu', showConfirmButton: false, timer: 2000 });
        return;
    }

    const fullQuery = query.toLowerCase().includes('cicalengka') || query.toLowerCase().includes('bandung') 
        ? query 
        : `${query}, Cicalengka, Bandung`;

    try {
        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(fullQuery)}&limit=1`;
        const res = await fetch(url, { headers: { 'Accept-Language': 'id' } });
        const data = await res.json();
        
        if (data && data.length > 0) {
            const lat = parseFloat(data[0].lat);
            const lng = parseFloat(data[0].lon);
            if (pickerMap && pickerMarker) {
                pickerMarker.setLatLng([lat, lng]);
                pickerMap.setView([lat, lng], 16);
            }
            updateCoordsFields(lat, lng, data[0].display_name.split(',')[0]);
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: `Ditemukan: ${data[0].display_name.split(',')[0]}`, showConfirmButton: false, timer: 2500 });
        } else {
            Swal.fire({ icon: 'info', title: 'Lokasi Tidak Ditemukan', text: 'Coba gunakan kata kunci patokan lain di sekitar Cicalengka.' });
        }
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal melakukan pencarian geocoding.' });
    }
}

// ── 4. KALIBRASI DARI TEKS ALAMAT ──
async function calibrateFromAddress() {
    const addr = document.getElementById('storeAddress').value.trim();
    if (!addr) {
        Swal.fire({ icon: 'info', title: 'Alamat Kosong', text: 'Isi alamat toko terlebih dahulu pada kolom Alamat Lengkap.' });
        return;
    }
    document.getElementById('mapSearchInput').value = addr;
    await searchLocationOnMap();
}

// ── 5. REVERSE GEOCODE: KOORDINAT KE NAMA JALAN ──
async function reverseGeocodeCurrentPoint() {
    const lat = document.getElementById('storeLat').value;
    const lng = document.getElementById('storeLng').value;
    if (!lat || !lng) return;

    try {
        const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`;
        const res = await fetch(url, { headers: { 'Accept-Language': 'id' } });
        const data = await res.json();
        if (data && data.display_name) {
            document.getElementById('storeAddress').value = data.display_name;
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Alamat toko diperbarui dari titik koordinat!', showConfirmButton: false, timer: 2000 });
        }
    } catch (e) {}
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
    document.getElementById('storeDocsSection').style.display = 'none';
    
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
    document.getElementById('storeDocsSection').style.display = 'block';

    // Populate Document Previews
    const imgBase = window.BASE_URL ? window.BASE_URL + '/' : '/';
    
    // KTP Preview
    const ktpImg = document.getElementById('storeModalKtp');
    const noKtp = document.getElementById('noKtpText');
    if (store.identity_image && store.identity_image.trim() !== '') {
        const ktpSrc = store.identity_image.startsWith('http') ? store.identity_image : imgBase + store.identity_image;
        ktpImg.src = ktpSrc;
        ktpImg.style.display = 'block';
        if (noKtp) noKtp.style.display = 'none';
    } else {
        ktpImg.style.display = 'none';
        if (noKtp) noKtp.style.display = 'block';
    }

    // Logo Preview
    const logoImg = document.getElementById('storeModalLogo');
    const noLogo = document.getElementById('noLogoText');
    if (store.logo && store.logo.trim() !== '') {
        const logoSrc = store.logo.startsWith('http') ? store.logo : imgBase + store.logo;
        logoImg.src = logoSrc;
        logoImg.style.display = 'block';
        if (noLogo) noLogo.style.display = 'none';
    } else {
        logoImg.style.display = 'none';
        if (noLogo) noLogo.style.display = 'block';
    }

    // Cover Photo Preview
    const coverImg = document.getElementById('storeModalCover');
    const noCover = document.getElementById('noCoverText');
    if (store.cover_photo && store.cover_photo.trim() !== '') {
        const coverSrc = store.cover_photo.startsWith('http') ? store.cover_photo : imgBase + store.cover_photo;
        coverImg.src = coverSrc;
        coverImg.style.display = 'block';
        if (noCover) noCover.style.display = 'none';
    } else {
        coverImg.style.display = 'none';
        if (noCover) noCover.style.display = 'block';
    }

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
