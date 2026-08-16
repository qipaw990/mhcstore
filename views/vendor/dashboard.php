<!-- Vendor Overview Cards -->
<div class="row g-3 mb-4">
    <!-- Store Status Switch Card -->
    <div class="col-12">
        <div class="stat-card justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon <?= $store['is_open'] ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>">
                    <i class="bi bi-shop"></i>
                </div>
                <div>
                    <h6 class="fw-bold m-0"><?= htmlspecialchars($store['name']) ?></h6>
                    <span class="badge <?= $store['is_open'] ? 'bg-success' : 'bg-danger' ?>">
                        <?= $store['is_open'] ? 'TOKO SEDANG BUKA (TERIMA ORDER)' : 'TOKO SEDANG TUTUP' ?>
                    </span>
                </div>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="storeSwitch" <?= $store['is_open'] ? 'checked' : '' ?> onchange="toggleStoreOpenStatus()" style="width: 54px; height: 28px; cursor: pointer;">
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon bg-primary-subtle text-primary">
                <i class="bi bi-wallet2"></i>
            </div>
            <div>
                <div class="text-muted small">Saldo Dompet Toko</div>
                <h5 class="fw-bold text-primary m-0"><?= format_rupiah($wallet['balance'] ?? 0) ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon bg-info-subtle text-info">
                <i class="bi bi-receipt"></i>
            </div>
            <div>
                <div class="text-muted small">Total Pesanan</div>
                <h5 class="fw-bold m-0"><?= $total_orders ?? 0 ?> Order</h5>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon bg-warning-subtle text-warning">
                <i class="bi bi-egg-fried"></i>
            </div>
            <div>
                <div class="text-muted small">Menu / Produk Aktif</div>
                <h5 class="fw-bold m-0"><?= $products_count ?? 0 ?> Item</h5>
            </div>
        </div>
    </div>

    <!-- Store Location GPS & Mini Map -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="fw-bold m-0"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Lokasi GPS Toko Anda (<?= htmlspecialchars($store['address'] ?? 'Cicalengka') ?>)</h6>
                    <small class="text-muted">Titik penjemputan pesanan oleh kurir CicalengkaGO.</small>
                </div>
                <a href="https://www.google.com/maps/search/?api=1&query=<?= (float)($store['latitude'] ?? -6.9835) ?>,<?= (float)($store['longitude'] ?? 107.8335) ?>" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1 fw-semibold" style="font-size: 12px;">
                    <i class="bi bi-compass me-1"></i> Buka di Google Maps
                </a>
            </div>
            <div class="card-body p-0">
                <div id="vendor-store-map" style="width: 100%; height: 200px;"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const sLat = <?= (float)($store['latitude'] ?? -6.9835) ?>;
    const sLng = <?= (float)($store['longitude'] ?? 107.8335) ?>;

    const vMap = L.map('vendor-store-map', { zoomControl: false }).setView([sLat, sLng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(vMap);

    const storeIcon = L.divIcon({
        className: 'custom-pin',
        html: '<div style="background:#ef4444;color:white;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid white;box-shadow:0 3px 8px rgba(0,0,0,0.3);"><i class="bi bi-shop"></i></div>',
        iconSize: [34, 34],
        iconAnchor: [17, 17]
    });

    L.marker([sLat, sLng], { icon: storeIcon })
        .addTo(vMap)
        .bindPopup("<b>🏪 <?= htmlspecialchars($store['name']) ?></b><br><small><?= htmlspecialchars($store['address'] ?? '') ?></small>")
        .openPopup();
});
</script>

<!-- Recent Orders Table -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold m-0"><i class="bi bi-clock-history me-1 text-primary"></i> Pesanan Terbaru</h6>
        <a href="<?= $baseUrl ?>/vendor/orders" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No. Order</th>
                    <th>Pelanggan</th>
                    <th>Item Menu</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Belum ada pesanan masuk.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $ord): ?>
                        <tr>
                            <td class="fw-bold text-primary">#<?= htmlspecialchars($ord['order_code']) ?></td>
                            <td>
                                <div class="fw-bold small"><?= htmlspecialchars($ord['customer_name']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($ord['customer_phone']) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= count($ord['items']) ?> Menu</span>
                            </td>
                            <td class="fw-bold"><?= format_rupiah($ord['total_amount']) ?></td>
                            <td>
                                <span class="badge bg-primary text-uppercase"><?= $ord['order_status'] ?></span>
                            </td>
                            <td>
                                <?php if ($ord['order_status'] === 'confirmed'): ?>
                                    <button onclick="updateStoreOrderStatus(<?= $ord['id'] ?>, 'processing')" class="btn btn-sm btn-warning fw-bold">
                                        Proses & Masak
                                    </button>
                                <?php elseif ($ord['order_status'] === 'processing'): ?>
                                    <button onclick="updateStoreOrderStatus(<?= $ord['id'] ?>, 'handover')" class="btn btn-sm btn-info text-white fw-bold">
                                        Siap Diambil Kurir
                                    </button>
                                <?php else: ?>
                                    <span class="small text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
async function toggleStoreOpenStatus() {
    try {
        const res = await fetch(window.BASE_URL + '/vendor/toggle-status', { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            Swal.fire('Berhasil', data.message, 'success').then(() => location.reload());
        }
    } catch (e) {
        console.error(e);
    }
}

async function updateStoreOrderStatus(orderId, status) {
    const fd = new FormData();
    fd.append('order_id', orderId);
    fd.append('status', status);

    try {
        const res = await fetch(window.BASE_URL + '/vendor/orders/update-status', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();
        if (data.success) {
            Swal.fire('Berhasil', data.message, 'success').then(() => location.reload());
        }
    } catch (e) {
        console.error(e);
    }
}
</script>
