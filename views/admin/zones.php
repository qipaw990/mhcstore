<?php
    $countTotalZones = count($zones);
    $avgBaseCharge = count($zones) ? array_sum(array_column($zones, 'min_delivery_charge')) / count($zones) : 5000;
    $avgPerKm = count($zones) ? array_sum(array_column($zones, 'per_km_delivery_charge')) / count($zones) : 2500;
?>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm p-3 mb-4 d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-check-circle-fill text-success fs-5"></i>
        <div><?= htmlspecialchars($_SESSION['success']) ?></div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm p-3 mb-4 d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
        <div><?= htmlspecialchars($_SESSION['error']) ?></div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

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
                    <h5 class="fw-bold m-0"><i class="bi bi-geo-alt-fill text-primary me-2"></i>Zona Operasional & Editor Polygon Leaflet</h5>
                    <small class="text-muted">Atur batasan cakupan wilayah pengantaran kurir secara interaktif dengan peta Leaflet (geser titik polygon / radius).</small>
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
                <div class="d-flex align-items-center gap-2">
                    <h6 class="fw-bold m-0"><i class="bi bi-map me-2 text-primary"></i>Peta Jangkauan Wilayah Aktif</h6>
                    <span class="badge bg-primary-subtle text-primary">Live Polygon View</span>
                </div>
                <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Klik pada area polygon untuk melihat detail zona</small>
            </div>
            <div class="card-body p-0 position-relative">
                <div id="zones-preview-map" style="width: 100%; height: 380px;"></div>
            </div>
        </div>
    </div>

    <!-- Zones Table List -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold m-0"><i class="bi bi-list-columns-reverse me-2 text-primary"></i>Daftar Zona Terdaftar</h6>
                <span class="badge bg-light text-dark border"><?= count($zones) ?> Wilayah</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Zona</th>
                            <th>Titik Pusat (Lat, Lng)</th>
                            <th>Cakupan Polygon</th>
                            <th>Tarif Minimum</th>
                            <th>Tarif per KM</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($zones)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Belum ada zona pengantaran dibuat. Klik tombol "Tambah Zona Baru" di atas.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($zones as $z): ?>
                                <?php 
                                    $parsedCoords = json_decode($z['coordinates_json'] ?? '[]', true);
                                    $pointCount = is_array($parsedCoords) ? count($parsedCoords) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($z['name']) ?></div>
                                    </td>
                                    <td>
                                        <small class="text-muted font-monospace"><?= number_format((float)($z['center_latitude'] ?? -6.9840), 4) ?>, <?= number_format((float)($z['center_longitude'] ?? 107.8340), 4) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($pointCount > 0): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                <i class="bi bi-polygon me-1"></i> <?= $pointCount ?> Titik Sudut
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                <i class="bi bi-circle me-1"></i> Radius Lingkaran Default
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold small text-success"><?= format_rupiah($z['min_delivery_charge'] ?? 5000) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold small text-primary"><?= format_rupiah($z['per_km_delivery_charge'] ?? 2500) ?> / Km</div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $z['status'] ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary' ?>">
                                            <?= $z['status'] ? 'AKTIF' : 'NON-AKTIF' ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <button class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1 fw-bold" onclick="openEditZoneModal(<?= htmlspecialchars(json_encode($z, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)">
                                                <i class="bi bi-pencil-square me-1"></i> Edit Cakupan
                                            </button>
                                            <button class="btn btn-light btn-sm rounded-circle text-danger" style="width:34px;height:34px;" onclick="deleteZone(<?= $z['id'] ?>, '<?= addslashes($z['name']) ?>')" title="Hapus Zona">
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
    </div>
</div>

<!-- Modal: Interactive Leaflet Zone Editor (Create / Edit) -->
<div class="modal fade" id="zoneModal" tabindex="-1" aria-labelledby="zoneModalTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom py-3 px-4 bg-light rounded-top-4">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:36px;height:36px;border-radius:10px;background:#EE2737;display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;">
                        <i class="bi bi-pin-map-fill"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold text-dark m-0" id="zoneModalTitle">Edit Cakupan Wilayah Pengantaran</h6>
                        <small class="text-muted" style="font-size:11px;">Sesuaikan titik koordinat polygon atau radius jangkauan pengiriman</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="<?= $baseUrl ?>/admin/zones/save" method="POST" id="zoneForm">
                <input type="hidden" name="id" id="zoneId">
                <input type="hidden" name="coordinates_json" id="zoneCoordinatesJson" value="[]">
                
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <!-- Left Column: Zone Form Settings -->
                        <div class="col-lg-4">
                            <div class="p-3 bg-light rounded-4 border mb-3">
                                <h6 class="fw-bold text-dark mb-3" style="font-size: 13px;">
                                    <i class="bi bi-sliders text-danger me-1"></i> 1. Informasi & Tarif Zona
                                </h6>
                                
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Nama Zona Wilayah *</label>
                                    <input type="text" name="name" id="zoneName" class="form-control rounded-3" placeholder="Contoh: Zona 1 - Cicalengka Raya" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Tarif Minimum Dasar (Rp)</label>
                                    <input type="number" name="min_delivery_charge" id="zoneMinCharge" class="form-control rounded-3" value="5000" min="0" step="500" required>
                                    <small class="text-muted" style="font-size: 10px;">Biaya ongkir untuk jarak 1 KM pertama.</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Tarif Tambahan per KM (Rp)</label>
                                    <input type="number" name="per_km_delivery_charge" id="zonePerKmCharge" class="form-control rounded-3" value="2500" min="0" step="500" required>
                                    <small class="text-muted" style="font-size: 10px;">Biaya tambahan untuk setiap kilometer berikutnya.</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Status Zona</label>
                                    <select name="status" id="zoneStatus" class="form-select rounded-3">
                                        <option value="1">Aktif (Menerima Pengantaran)</option>
                                        <option value="0">Non-Aktif (Ditutup Sementara)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="p-3 bg-light rounded-4 border">
                                <h6 class="fw-bold text-dark mb-2" style="font-size: 13px;">
                                    <i class="bi bi-geo-fill text-primary me-1"></i> 2. Titik Pusat Zona (Center)
                                </h6>
                                <small class="text-muted d-block mb-3" style="font-size: 11px;">
                                    Tarik pin merah (📍) di peta untuk memindahkan titik pusat zona secara otomatis.
                                </small>
                                
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold text-muted" style="font-size: 11px;">Center Lat</label>
                                        <input type="number" step="any" name="center_latitude" id="zoneCenterLat" class="form-control form-control-sm rounded-3 font-monospace" value="-6.9840" required onchange="onCenterInputChanged()">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold text-muted" style="font-size: 11px;">Center Lng</label>
                                        <input type="number" step="any" name="center_longitude" id="zoneCenterLng" class="form-control form-control-sm rounded-3 font-monospace" value="107.8340" required onchange="onCenterInputChanged()">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Interactive Leaflet Map Editor -->
                        <div class="col-lg-8">
                            <div class="card border rounded-4 shadow-sm overflow-hidden h-100 d-flex flex-column">
                                <!-- Map Toolbar -->
                                <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="btn-group btn-group-sm rounded-pill p-1 bg-light border" role="group">
                                            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold" id="btnModeDrag" onclick="setEditorMode('drag')">
                                                <i class="bi bi-arrows-move me-1"></i> Geser Titik
                                            </button>
                                            <button type="button" class="btn btn-sm btn-light rounded-pill px-3 fw-semibold text-dark" id="btnModeAdd" onclick="setEditorMode('add')">
                                                <i class="bi bi-plus-circle me-1"></i> Tambah Titik
                                            </button>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <div class="dropdown">
                                            <button class="btn btn-outline-secondary btn-sm rounded-pill dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown">
                                                <i class="bi bi-circle me-1"></i> Buat Radius Bulat
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                                <li><a class="dropdown-item small" href="javascript:void(0)" onclick="generateRadiusPolygon(3)">Radius 3 KM (~Kecamatan Cicalengka)</a></li>
                                                <li><a class="dropdown-item small" href="javascript:void(0)" onclick="generateRadiusPolygon(5)">Radius 5 KM (Cicalengka Raya)</a></li>
                                                <li><a class="dropdown-item small" href="javascript:void(0)" onclick="generateRadiusPolygon(8)">Radius 8 KM (Cicalengka - Nagreg - Cikancung)</a></li>
                                                <li><a class="dropdown-item small" href="javascript:void(0)" onclick="generateRadiusPolygon(12)">Radius 12 KM (Bandung Timur Luas)</a></li>
                                            </ul>
                                        </div>

                                        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill fw-semibold" onclick="resetToCicalengkaDefault()" title="Kembali ke polygon default Cicalengka">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Default Cicalengka
                                        </button>
                                    </div>
                                </div>

                                <!-- Leaflet Map Container -->
                                <div class="position-relative flex-grow-1" style="min-height: 420px;">
                                    <div id="zone-editor-map" style="width: 100%; height: 100%; min-height: 420px;"></div>
                                </div>

                                <!-- Map Info Bar & Instructions -->
                                <div class="card-footer bg-light border-top p-2.5 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-1.5 px-2.5" id="polyVertexBadge">
                                            <i class="bi bi-vector-pen me-1"></i> <strong>4</strong> Titik Sudut
                                        </span>
                                        <span class="small text-muted" id="editorModeInstruction" style="font-size: 11px;">
                                            <i class="bi bi-hand-index-thumb text-primary me-1"></i> Mode Geser: Tarik titik sudut lingkaran putih/biru untuk mengubah batas cakupan.
                                        </span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none p-0" onclick="clearAllPolygonPoints()">
                                        <i class="bi bi-trash me-1"></i> Hapus Semua Titik
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer border-top py-3 px-4 bg-white rounded-bottom-4 d-flex align-items-center justify-content-between">
                    <small class="text-muted">
                        <i class="bi bi-shield-check text-success me-1"></i> Cakupan polygon ini langsung diterapkan ke validasi order & penugasan kurir.
                    </small>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                            <i class="bi bi-check-circle-fill me-1"></i> Simpan Cakupan Zona
                        </button>
                    </div>
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
let zoneModalInstance = null;
let zonesPreviewMap = null;
let zoneEditorMap = null;

// Editor state
let editorMode = 'drag'; // 'drag' or 'add'
let editorCenterMarker = null;
let editorPolygon = null;
let editorVertexMarkers = [];
let currentPolygonCoords = []; // Array of [lat, lng]

const DEFAULT_CICALENGKA_COORDS = [
    [-6.955741, 107.827375],
    [-6.961527, 107.860682],
    [-7.023885, 107.901530],
    [-7.030012, 107.797852],
    [-6.972775, 107.754607],
    [-6.955071, 107.804043]
];

// 1. Initialize Preview Map
function initZonesPreviewMap() {
    const previewContainer = document.getElementById('zones-preview-map');
    if (!previewContainer) return;

    zonesPreviewMap = L.map('zones-preview-map').setView([-6.9840, 107.8340], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(zonesPreviewMap);

    const allBounds = [];

    <?php foreach ($zones as $z): ?>
        <?php
            $rawCoords = json_decode($z['coordinates_json'] ?? '[]', true);
            $normCoords = [];
            if (!empty($rawCoords) && is_array($rawCoords)) {
                foreach ($rawCoords as $pt) {
                    if (is_array($pt) && isset($pt['lat'], $pt['lng'])) {
                        $normCoords[] = [(float)$pt['lat'], (float)$pt['lng']];
                    } elseif (is_array($pt) && count($pt) >= 2) {
                        $normCoords[] = [(float)$pt[0], (float)$pt[1]];
                    }
                }
            }
        ?>
        <?php if (!empty($normCoords)): ?>
            (function() {
                const coords_<?= $z['id'] ?> = <?= json_encode($normCoords) ?>;
                const poly = L.polygon(coords_<?= $z['id'] ?>, {
                    color: '#2563eb',
                    fillColor: '#3b82f6',
                    fillOpacity: 0.25,
                    weight: 2
                }).bindPopup(`
                    <div style="min-width: 180px;">
                        <h6 class="fw-bold text-dark mb-1">🗺️ <?= htmlspecialchars($z['name']) ?></h6>
                        <small class="text-muted d-block mb-2">Tarif Dasar: <?= format_rupiah($z['min_delivery_charge'] ?? 5000) ?> • +<?= format_rupiah($z['per_km_delivery_charge'] ?? 2500) ?>/km</small>
                        <button class="btn btn-primary btn-sm rounded-pill w-100 fw-bold" onclick='openEditZoneModal(<?= json_encode($z) ?>)'>
                            <i class="bi bi-pencil-square me-1"></i> Edit Cakupan
                        </button>
                    </div>
                `).addTo(zonesPreviewMap);

                // Add center pin
                L.marker([<?= (float)($z['center_latitude'] ?? -6.9840) ?>, <?= (float)($z['center_longitude'] ?? 107.8340) ?>], {
                    title: "<?= htmlspecialchars($z['name']) ?>"
                }).bindPopup("<b>Titik Pusat <?= htmlspecialchars($z['name']) ?></b>").addTo(zonesPreviewMap);

                coords_<?= $z['id'] ?>.forEach(pt => allBounds.push(pt));
            })();
        <?php else: ?>
            (function() {
                const circle = L.circle([<?= (float)($z['center_latitude'] ?? -6.9840) ?>, <?= (float)($z['center_longitude'] ?? 107.8340) ?>], {
                    radius: 4000,
                    color: '#2563eb',
                    fillColor: '#3b82f6',
                    fillOpacity: 0.2,
                    weight: 2
                }).bindPopup("<b>🗺️ <?= htmlspecialchars($z['name']) ?></b> (Radius Default 4 KM)").addTo(zonesPreviewMap);
            })();
        <?php endif; ?>
    <?php endforeach; ?>

    if (allBounds.length > 0) {
        zonesPreviewMap.fitBounds(allBounds, { padding: [30, 30] });
    }
}

// 2. Initialize / Update Interactive Zone Editor Map
function initZoneEditorMap(centerLat, centerLng, initialCoords) {
    if (!zoneEditorMap) {
        zoneEditorMap = L.map('zone-editor-map').setView([centerLat, centerLng], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(zoneEditorMap);

        // Click on map to add vertex in 'add' mode
        zoneEditorMap.on('click', function(e) {
            if (editorMode === 'add') {
                currentPolygonCoords.push([e.latlng.lat, e.latlng.lng]);
                renderEditorPolygon();
            }
        });
    } else {
        zoneEditorMap.setView([centerLat, centerLng], 14);
    }

    // Set Center Marker
    if (editorCenterMarker) {
        zoneEditorMap.removeLayer(editorCenterMarker);
    }

    // Create custom red pin for center
    const centerIcon = L.divIcon({
        className: 'custom-center-pin',
        html: `<div style="background:#EE2737;color:white;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 8px rgba(0,0,0,0.3);border:2px solid white;font-size:16px;">📍</div>`,
        iconSize: [32, 32],
        iconAnchor: [16, 16]
    });

    editorCenterMarker = L.marker([centerLat, centerLng], {
        draggable: true,
        icon: centerIcon,
        title: "Titik Pusat Zona (Geser untuk memindahkan)"
    }).addTo(zoneEditorMap);

    editorCenterMarker.bindPopup("<b>📍 Titik Pusat Zona</b><br><small class='text-muted'>Geser untuk mengubah titik koordinat pusat zona</small>");

    editorCenterMarker.on('drag', function(e) {
        const pos = e.target.getLatLng();
        document.getElementById('zoneCenterLat').value = pos.lat.toFixed(6);
        document.getElementById('zoneCenterLng').value = pos.lng.toFixed(6);
    });

    // Parse Initial Polygon Coordinates
    currentPolygonCoords = [];
    if (Array.isArray(initialCoords) && initialCoords.length >= 3) {
        initialCoords.forEach(pt => {
            if (Array.isArray(pt)) {
                currentPolygonCoords.push([parseFloat(pt[0]), parseFloat(pt[1])]);
            } else if (pt && typeof pt === 'object' && pt.lat && pt.lng) {
                currentPolygonCoords.push([parseFloat(pt.lat), parseFloat(pt.lng)]);
            }
        });
    }

    if (currentPolygonCoords.length < 3) {
        // Fallback default Cicalengka
        currentPolygonCoords = JSON.parse(JSON.stringify(DEFAULT_CICALENGKA_COORDS));
    }

    renderEditorPolygon();

    if (currentPolygonCoords.length > 0) {
        zoneEditorMap.fitBounds(currentPolygonCoords, { padding: [40, 40] });
    }
}

// 3. Render Editor Polygon & Draggable Vertex Markers
function renderEditorPolygon() {
    if (!zoneEditorMap) return;

    // Remove existing polygon & vertex markers
    if (editorPolygon) {
        zoneEditorMap.removeLayer(editorPolygon);
    }
    editorVertexMarkers.forEach(m => zoneEditorMap.removeLayer(m));
    editorVertexMarkers = [];

    if (currentPolygonCoords.length >= 3) {
        editorPolygon = L.polygon(currentPolygonCoords, {
            color: '#2563eb',
            fillColor: '#3b82f6',
            fillOpacity: 0.25,
            weight: 3,
            dashArray: '4, 4'
        }).addTo(zoneEditorMap);
    }

    // Add draggable handle marker for every vertex
    currentPolygonCoords.forEach((pt, index) => {
        const vertexIcon = L.divIcon({
            className: 'custom-vertex-handle',
            html: `<div style="background:#2563eb;width:16px;height:16px;border-radius:50%;border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.35);cursor:move;"></div>`,
            iconSize: [16, 16],
            iconAnchor: [8, 8]
        });

        const vMarker = L.marker([pt[0], pt[1]], {
            draggable: true,
            icon: vertexIcon,
            title: `Titik #${index + 1} (Tarik untuk menggeser, klik untuk hapus)`
        }).addTo(zoneEditorMap);

        // Drag vertex handle
        vMarker.on('drag', function(e) {
            const pos = e.target.getLatLng();
            currentPolygonCoords[index] = [pos.lat, pos.lng];
            if (editorPolygon) {
                editorPolygon.setLatLngs(currentPolygonCoords);
            }
            syncCoordinatesToJsonField();
        });

        // Click vertex to delete if more than 3 vertices
        vMarker.on('click', function() {
            if (currentPolygonCoords.length > 3) {
                currentPolygonCoords.splice(index, 1);
                renderEditorPolygon();
            } else {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'warning',
                    title: 'Polygon minimal harus memiliki 3 titik sudut!',
                    showConfirmButton: false,
                    timer: 2000
                });
            }
        });

        editorVertexMarkers.push(vMarker);
    });

    syncCoordinatesToJsonField();
}

// 4. Sync coordinates array to hidden input JSON field & badge
function syncCoordinatesToJsonField() {
    const formatted = currentPolygonCoords.map(pt => ({
        lat: Number(pt[0].toFixed(6)),
        lng: Number(pt[1].toFixed(6))
    }));

    document.getElementById('zoneCoordinatesJson').value = JSON.stringify(formatted);
    const badge = document.getElementById('polyVertexBadge');
    if (badge) {
        badge.innerHTML = `<i class="bi bi-vector-pen me-1"></i> <strong>${currentPolygonCoords.length}</strong> Titik Sudut`;
    }
}

// 5. Switch Editor Mode (Drag vs Add)
function setEditorMode(mode) {
    editorMode = mode;
    const btnDrag = document.getElementById('btnModeDrag');
    const btnAdd = document.getElementById('btnModeAdd');
    const instruction = document.getElementById('editorModeInstruction');

    if (mode === 'drag') {
        btnDrag.className = 'btn btn-sm btn-primary rounded-pill px-3 fw-bold';
        btnAdd.className = 'btn btn-sm btn-light rounded-pill px-3 fw-semibold text-dark';
        instruction.innerHTML = '<i class="bi bi-arrows-move text-primary me-1"></i> Mode Geser: Tarik titik sudut lingkaran biru/putih untuk menyesuaikan batas cakupan.';
    } else {
        btnAdd.className = 'btn btn-sm btn-primary rounded-pill px-3 fw-bold';
        btnDrag.className = 'btn btn-sm btn-light rounded-pill px-3 fw-semibold text-dark';
        instruction.innerHTML = '<i class="bi bi-plus-circle text-primary me-1"></i> Mode Tambah: Klik di mana saja pada peta untuk menambahkan titik sudut baru ke polygon.';
    }
}

// 6. Generate Radius Polygon (N-sided circle polygon around center)
function generateRadiusPolygon(radiusKm) {
    const centerLat = parseFloat(document.getElementById('zoneCenterLat').value) || -6.9840;
    const centerLng = parseFloat(document.getElementById('zoneCenterLng').value) || 107.8340;
    
    // Generate 12-point regular polygon
    const points = [];
    const earthRadiusKm = 6371.0;
    const numPoints = 12;

    for (let i = 0; i < numPoints; i++) {
        const bearing = (i * 360 / numPoints) * (Math.PI / 180);
        const latRad = centerLat * (Math.PI / 180);
        const lngRad = centerLng * (Math.PI / 180);
        const distRatio = radiusKm / earthRadiusKm;

        const newLatRad = Math.asin(Math.sin(latRad) * Math.cos(distRatio) + Math.cos(latRad) * Math.sin(distRatio) * Math.cos(bearing));
        const newLngRad = lngRad + Math.atan2(Math.sin(bearing) * Math.sin(distRatio) * Math.cos(latRad), Math.cos(distRatio) - Math.sin(latRad) * Math.sin(newLatRad));

        points.push([
            Number((newLatRad * (180 / Math.PI)).toFixed(6)),
            Number((newLngRad * (180 / Math.PI)).toFixed(6))
        ]);
    }

    currentPolygonCoords = points;
    renderEditorPolygon();
    if (zoneEditorMap) {
        zoneEditorMap.fitBounds(currentPolygonCoords, { padding: [30, 30] });
    }

    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: `Cakupan Radius ${radiusKm} KM berhasil dibuat!`,
        showConfirmButton: false,
        timer: 2000
    });
}

// 7. Reset to Default Cicalengka
function resetToCicalengkaDefault() {
    document.getElementById('zoneCenterLat').value = '-6.9840';
    document.getElementById('zoneCenterLng').value = '107.8340';
    if (editorCenterMarker) {
        editorCenterMarker.setLatLng([-6.9840, 107.8340]);
    }
    currentPolygonCoords = JSON.parse(JSON.stringify(DEFAULT_CICALENGKA_COORDS));
    renderEditorPolygon();
    if (zoneEditorMap) {
        zoneEditorMap.fitBounds(currentPolygonCoords, { padding: [30, 30] });
    }
}

// 8. Clear all points
function clearAllPolygonPoints() {
    currentPolygonCoords = [];
    if (editorPolygon) {
        zoneEditorMap.removeLayer(editorPolygon);
        editorPolygon = null;
    }
    editorVertexMarkers.forEach(m => zoneEditorMap.removeLayer(m));
    editorVertexMarkers = [];
    syncCoordinatesToJsonField();
    setEditorMode('add');
}

// 9. Manual Center Lat/Lng input change
function onCenterInputChanged() {
    const lat = parseFloat(document.getElementById('zoneCenterLat').value);
    const lng = parseFloat(document.getElementById('zoneCenterLng').value);
    if (!isNaN(lat) && !isNaN(lng) && editorCenterMarker && zoneEditorMap) {
        editorCenterMarker.setLatLng([lat, lng]);
        zoneEditorMap.panTo([lat, lng]);
    }
}

// 10. Open Add Zone Modal
function openAddZoneModal() {
    document.getElementById('zoneModalTitle').textContent = 'Tambah Zona Wilayah & Batas Polygon Baru';
    document.getElementById('zoneId').value = '';
    document.getElementById('zoneName').value = '';
    document.getElementById('zoneMinCharge').value = '5000';
    document.getElementById('zonePerKmCharge').value = '2500';
    document.getElementById('zoneCenterLat').value = '-6.9840';
    document.getElementById('zoneCenterLng').value = '107.8340';
    document.getElementById('zoneStatus').value = '1';
    
    setEditorMode('drag');

    const modalEl = document.getElementById('zoneModal');
    zoneModalInstance = new bootstrap.Modal(modalEl);
    zoneModalInstance.show();

    setTimeout(() => {
        initZoneEditorMap(-6.9840, 107.8340, DEFAULT_CICALENGKA_COORDS);
        if (zoneEditorMap) {
            zoneEditorMap.invalidateSize();
        }
    }, 300);
}

// 11. Open Edit Zone Modal
function openEditZoneModal(z) {
    document.getElementById('zoneModalTitle').textContent = 'Edit Cakupan Zona: ' + z.name;
    document.getElementById('zoneId').value = z.id;
    document.getElementById('zoneName').value = z.name;
    document.getElementById('zoneMinCharge').value = z.min_delivery_charge || 5000;
    document.getElementById('zonePerKmCharge').value = z.per_km_delivery_charge || 2500;
    document.getElementById('zoneCenterLat').value = z.center_latitude || -6.9840;
    document.getElementById('zoneCenterLng').value = z.center_longitude || 107.8340;
    document.getElementById('zoneStatus').value = (z.status !== undefined && z.status !== null) ? z.status : '1';

    let initialCoords = [];
    try {
        initialCoords = JSON.parse(z.coordinates_json || '[]');
    } catch(e) {
        initialCoords = [];
    }

    setEditorMode('drag');

    const modalEl = document.getElementById('zoneModal');
    zoneModalInstance = new bootstrap.Modal(modalEl);
    zoneModalInstance.show();

    const centerLat = parseFloat(z.center_latitude) || -6.9840;
    const centerLng = parseFloat(z.center_longitude) || 107.8340;

    setTimeout(() => {
        initZoneEditorMap(centerLat, centerLng, initialCoords);
        if (zoneEditorMap) {
            zoneEditorMap.invalidateSize();
        }
    }, 300);
}

// 12. Modal shown event to invalidate Leaflet map size
document.getElementById('zoneModal').addEventListener('shown.bs.modal', function() {
    if (zoneEditorMap) {
        zoneEditorMap.invalidateSize();
        if (currentPolygonCoords.length > 0) {
            zoneEditorMap.fitBounds(currentPolygonCoords, { padding: [40, 40] });
        }
    }
});

// 13. Simulator and Delete Helpers
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
        title: 'Hapus Zona Pengantaran?',
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
