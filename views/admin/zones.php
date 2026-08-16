<?php
    $countTotalZones = count($zones);
    $avgBaseCharge = count($zones) ? array_sum(array_column($zones, 'min_delivery_charge')) / count($zones) : 5000;
    $avgPerKm = count($zones) ? array_sum(array_column($zones, 'per_km_delivery_charge')) / count($zones) : 2500;
?>

<!-- Zones KPI Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-muted fw-semibold">Total Zona Operasional</small>
                    <h4 class="fw-black text-dark mb-0 mt-1"><?= $countTotalZones ?> Wilayah</h4>
                </div>
                <div style="width:42px;height:42px;border-radius:12px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#475569;font-size:20px;">
                    <i class="bi bi-geo-alt-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-success fw-semibold">Rata-rata Tarif Dasar</small>
                    <h4 class="fw-black text-success mb-0 mt-1"><?= format_rupiah($avgBaseCharge) ?></h4>
                </div>
                <div style="width:42px;height:42px;border-radius:12px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;color:#16a34a;font-size:20px;">
                    <i class="bi bi-cash-coin"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-primary fw-semibold">Tarif Tambahan per KM</small>
                    <h4 class="fw-black text-primary mb-0 mt-1"><?= format_rupiah($avgPerKm) ?> / Km</h4>
                </div>
                <div style="width:42px;height:42px;border-radius:12px;background:#eff6ff;display:flex;align-items:center;justify-content:center;color:#2563eb;font-size:20px;">
                    <i class="bi bi-speedometer2"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Header -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h5 class="fw-bold m-0"><i class="bi bi-geo-alt-fill text-primary me-2"></i>Zona Operasional & Polygon Radius Pengantaran</h5>
                    <small class="text-muted">Tentukan batasan wilayah jangkauan kurir CicalengkaGO dan konfigurasi tarif ongkir per KM.</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-primary rounded-pill px-3 fw-semibold" onclick="openFareSimulator()">
                        <i class="bi bi-calculator me-1"></i> Simulator Tarif
                    </button>
                    <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" onclick="openAddZoneModal()">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Zona Baru
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Zones Interactive Map -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold m-0"><i class="bi bi-map me-2 text-primary"></i>Peta Jangkauan Wilayah Aktif</h6>
                <span class="badge bg-primary-subtle text-primary">Kec. Cicalengka & Sekitarnya</span>
            </div>
            <div class="card-body p-0">
                <div id="zones-preview-map" style="width: 100%; height: 350px;"></div>
            </div>
        </div>
    </div>

    <!-- Zones Table List -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nama Zona</th>
                            <th>Titik Tengah (Lat, Lng)</th>
                            <th>Tarif Minimum</th>
                            <th>Tarif per KM</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($zones)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada zona pengantaran dibuat.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($zones as $z): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><i class="bi bi-geo-alt text-primary me-1"></i><?= htmlspecialchars($z['name']) ?></div>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= $z['center_latitude'] ?? -6.9840 ?>, <?= $z['center_longitude'] ?? 107.8340 ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold small text-success"><?= format_rupiah($z['min_delivery_charge'] ?? 5000) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold small text-primary"><?= format_rupiah($z['per_km_delivery_charge'] ?? 2500) ?> / Km</div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $z['status'] ? 'bg-success-subtle text-success' : 'bg-secondary' ?>">
                                            <?= $z['status'] ? 'AKTIF' : 'NON-AKTIF' ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <button class="btn btn-light btn-sm rounded-circle" style="width:34px;height:34px;" onclick='openEditZoneModal(<?= json_encode($z) ?>)'>
                                                <i class="bi bi-pencil text-primary"></i>
                                            </button>
                                            <button class="btn btn-light btn-sm rounded-circle" style="width:34px;height:34px;" onclick="deleteZone(<?= $z['id'] ?>, '<?= addslashes($z['name']) ?>')">
                                                <i class="bi bi-trash text-danger"></i>
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
    </div>
</div>

<!-- Modal: Zone Form (Create / Edit) -->
<div class="modal fade" id="zoneModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold" id="zoneModalTitle">Tambah Zona Wilayah Baru</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= $baseUrl ?>/admin/zones/save" method="POST">
                <input type="hidden" name="id" id="zoneId">
                <input type="hidden" name="coordinates_json" id="zoneCoordinatesJson" value="[]">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Nama Zona *</label>
                            <input type="text" name="name" id="zoneName" class="form-control rounded-3" placeholder="Contoh: Zona 1 - Cicalengka Pusat" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tarif Minimum Dasar (Rp)</label>
                            <input type="number" name="min_delivery_charge" id="zoneMinCharge" class="form-control rounded-3" value="5000" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tarif Tambahan per KM (Rp)</label>
                            <input type="number" name="per_km_delivery_charge" id="zonePerKmCharge" class="form-control rounded-3" value="2500" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Center Latitude</label>
                            <input type="number" step="any" name="center_latitude" id="zoneCenterLat" class="form-control rounded-3" value="-6.9840" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Center Longitude</label>
                            <input type="number" step="any" name="center_longitude" id="zoneCenterLng" class="form-control rounded-3" value="107.8340" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Simpan Zona</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Fare Simulator -->
<div class="modal fade" id="fareSimulatorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold"><i class="bi bi-calculator text-primary me-2"></i>Simulator Estimasi Ongkos Kirim</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Jarak Tempuh Pengantaran (Kilometer)</label>
                    <input type="number" id="simDistance" step="0.1" class="form-control rounded-3" value="2.5" oninput="calculateSimFare()">
                </div>
                <div class="p-3 bg-light rounded-3">
                    <div class="d-flex justify-content-between py-1 small">
                        <span class="text-muted">Tarif Dasar (1 Km Pertama):</span>
                        <span class="fw-semibold">Rp 5.000</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 small">
                        <span class="text-muted">Tarif Tambahan Jarak:</span>
                        <span class="fw-semibold" id="simExtraFare">Rp 3.750</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-top mt-2 fw-bold fs-5 text-primary">
                        <span>Total Ongkir:</span>
                        <span id="simTotalFare">Rp 8.750</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top py-3">
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold w-100" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
let zoneModal = null;
let zonesPreviewMap = null;

function initZonesPreviewMap() {
    zonesPreviewMap = L.map('zones-preview-map').setView([-6.9840, 107.8340], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(zonesPreviewMap);

    <?php foreach ($zones as $z): ?>
        <?php
            $coords = json_decode($z['coordinates_json'] ?? '[]', true);
            if (!empty($coords) && is_array($coords)):
        ?>
            const polyCoords_<?= $z['id'] ?> = <?= json_encode($coords) ?>;
            L.polygon(polyCoords_<?= $z['id'] ?>, {
                color: '#2563eb',
                fillColor: '#3b82f6',
                fillOpacity: 0.2,
                weight: 2
            }).bindPopup("<b>🗺️ <?= htmlspecialchars($z['name']) ?></b><br>Tarif Dasar: <?= format_rupiah($z['min_delivery_charge'] ?? 5000) ?>").addTo(zonesPreviewMap);
        <?php else: ?>
            L.circle([<?= (float)($z['center_latitude'] ?? -6.9840) ?>, <?= (float)($z['center_longitude'] ?? 107.8340) ?>], {
                radius: 4000,
                color: '#2563eb',
                fillColor: '#3b82f6',
                fillOpacity: 0.2,
                weight: 2
            }).bindPopup("<b>🗺️ <?= htmlspecialchars($z['name']) ?></b> (Radius 4 KM)").addTo(zonesPreviewMap);
        <?php endif; ?>
    <?php endforeach; ?>
}

function openAddZoneModal() {
    document.getElementById('zoneModalTitle').textContent = 'Tambah Zona Wilayah Baru';
    document.getElementById('zoneId').value = '';
    document.getElementById('zoneName').value = '';
    document.getElementById('zoneMinCharge').value = '5000';
    document.getElementById('zonePerKmCharge').value = '2500';
    document.getElementById('zoneCenterLat').value = '-6.9840';
    document.getElementById('zoneCenterLng').value = '107.8340';
    document.getElementById('zoneCoordinatesJson').value = '[]';
    
    zoneModal = new bootstrap.Modal(document.getElementById('zoneModal'));
    zoneModal.show();
}

function openEditZoneModal(z) {
    document.getElementById('zoneModalTitle').textContent = 'Edit Zona: ' + z.name;
    document.getElementById('zoneId').value = z.id;
    document.getElementById('zoneName').value = z.name;
    document.getElementById('zoneMinCharge').value = z.min_delivery_charge || 5000;
    document.getElementById('zonePerKmCharge').value = z.per_km_delivery_charge || 2500;
    document.getElementById('zoneCenterLat').value = z.center_latitude || -6.9840;
    document.getElementById('zoneCenterLng').value = z.center_longitude || 107.8340;
    document.getElementById('zoneCoordinatesJson').value = z.coordinates_json || '[]';

    zoneModal = new bootstrap.Modal(document.getElementById('zoneModal'));
    zoneModal.show();
}

function openFareSimulator() {
    new bootstrap.Modal(document.getElementById('fareSimulatorModal')).show();
    calculateSimFare();
}

function calculateSimFare() {
    const km = parseFloat(document.getElementById('simDistance').value) || 0;
    const base = 5000;
    const extra = Math.max(0, km - 1) * 2500;
    const total = base + extra;

    document.getElementById('simExtraFare').textContent = 'Rp ' + Math.round(extra).toLocaleString('id-ID');
    document.getElementById('simTotalFare').textContent = 'Rp ' + Math.round(total).toLocaleString('id-ID');
}

function deleteZone(id, name) {
    Swal.fire({
        title: 'Hapus Zona?',
        text: `Apakah Anda yakin ingin menghapus zona "${name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then(async (result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('id', id);
            const res = await fetch(`${window.BASE_URL}/admin/zones/delete`, { method: 'POST', body: fd });
            const json = await res.json();
            if (json.success) {
                Swal.fire('Terhapus!', json.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Gagal!', json.message, 'error');
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', initZonesPreviewMap);
</script>
