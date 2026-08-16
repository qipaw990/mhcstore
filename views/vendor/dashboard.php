<!-- Store Open/Close Status Hero Card -->
<div class="merchant-hero-status mb-3">
    <div class="d-flex align-items-center gap-3">
        <span class="merchant-status-indicator <?= $store['is_open'] ? 'online' : 'offline' ?>"></span>
        <div>
            <div class="fw-bold text-dark" style="font-size: 13.5px;"><?= htmlspecialchars($store['name']) ?></div>
            <span class="badge <?= $store['is_open'] ? 'bg-success' : 'bg-danger' ?>" style="font-size: 10px; font-weight: 700;">
                <i class="bi <?= $store['is_open'] ? 'bi-check-circle-fill' : 'bi-dash-circle-fill' ?> me-1"></i>
                <?= $store['is_open'] ? 'TOKO BUKA (TERIMA PESANAN)' : 'TOKO TUTUP SEMENTARA' ?>
            </span>
        </div>
    </div>
    <div class="form-check form-switch m-0">
        <input class="form-check-input" type="checkbox" role="switch" id="storeSwitch" <?= $store['is_open'] ? 'checked' : '' ?> onchange="toggleStoreOpenStatus()" style="width: 52px; height: 26px; cursor: pointer;">
    </div>
</div>

<!-- Merchant Quick Stat Grid -->
<div class="merchant-stat-grid mb-3">
    <!-- Saldo -->
    <a href="<?= $baseUrl ?>/vendor/wallet" class="merchant-stat-box text-decoration-none">
        <div class="d-flex justify-content-between align-items-start">
            <div class="merchant-stat-icon bg-success-subtle text-success">
                <i class="bi bi-wallet2"></i>
            </div>
            <i class="bi bi-chevron-right text-muted" style="font-size: 11px;"></i>
        </div>
        <div>
            <div class="merchant-stat-val text-success"><?= format_rupiah($wallet['balance'] ?? 0) ?></div>
            <div class="merchant-stat-lbl">Saldo Dompet Toko</div>
        </div>
    </a>

    <!-- Total Orders -->
    <a href="<?= $baseUrl ?>/vendor/orders" class="merchant-stat-box text-decoration-none">
        <div class="d-flex justify-content-between align-items-start">
            <div class="merchant-stat-icon bg-danger-subtle text-danger">
                <i class="bi bi-receipt"></i>
            </div>
            <i class="bi bi-chevron-right text-muted" style="font-size: 11px;"></i>
        </div>
        <div>
            <div class="merchant-stat-val"><?= $total_orders ?? 0 ?> <span style="font-size: 13px; font-weight: 600;">Pesanan</span></div>
            <div class="merchant-stat-lbl">Semua Order</div>
        </div>
    </a>

    <!-- Active Menu Items -->
    <a href="<?= $baseUrl ?>/vendor/products" class="merchant-stat-box text-decoration-none">
        <div class="d-flex justify-content-between align-items-start">
            <div class="merchant-stat-icon bg-warning-subtle text-warning">
                <i class="bi bi-egg-fried"></i>
            </div>
            <i class="bi bi-chevron-right text-muted" style="font-size: 11px;"></i>
        </div>
        <div>
            <div class="merchant-stat-val"><?= $products_count ?? 0 ?> <span style="font-size: 13px; font-weight: 600;">Item</span></div>
            <div class="merchant-stat-lbl">Menu Aktif</div>
        </div>
    </a>

    <!-- Rating -->
    <div class="merchant-stat-box">
        <div class="d-flex justify-content-between align-items-start">
            <div class="merchant-stat-icon bg-primary-subtle text-primary">
                <i class="bi bi-star-fill text-warning"></i>
            </div>
            <span class="badge bg-light text-muted border" style="font-size: 9px;">Mitra</span>
        </div>
        <div>
            <div class="merchant-stat-val"><?= number_format($store['rating'] ?? 5.0, 1) ?> <span style="font-size: 12px; color: #F59E0B;">★</span></div>
            <div class="merchant-stat-lbl"><?= $store['reviews_count'] ?? 0 ?> Ulasan Pelanggan</div>
        </div>
    </div>
</div>

<!-- Quick Action Strip -->
<div class="d-flex gap-2 mb-3">
    <a href="<?= $baseUrl ?>/vendor/products/create" class="btn btn-danger btn-sm rounded-pill fw-bold px-3 py-2 flex-grow-1 d-flex align-items-center justify-content-center gap-1.5 shadow-sm" style="background:#EE2737; font-size: 12px; border:none;">
        <i class="bi bi-plus-circle-fill"></i> Tambah Menu Baru
    </a>
    <a href="<?= $baseUrl ?>/vendor/orders" class="btn btn-dark btn-sm rounded-pill fw-bold px-3 py-2 flex-grow-1 d-flex align-items-center justify-content-center gap-1.5 shadow-sm" style="font-size: 12px;">
        <i class="bi bi-clock-history"></i> Kelola Pesanan
    </a>
</div>

<!-- Recent Active Orders Section -->
<div class="mb-4">
    <div class="d-flex align-items-center justify-content-between mb-2.5">
        <div class="d-flex align-items-center gap-1.5">
            <i class="bi bi-bell-fill text-danger fs-6"></i>
            <h6 class="fw-bold m-0 text-dark" style="font-size: 13.5px;">Pesanan Terbaru Masuk</h6>
        </div>
        <a href="<?= $baseUrl ?>/vendor/orders" class="text-danger fw-bold text-decoration-none small" style="font-size: 11.5px;">
            Lihat Semua <i class="bi bi-arrow-right"></i>
        </a>
    </div>

    <?php if (empty($orders)): ?>
        <div class="text-center py-4 bg-white rounded-4 border p-3 shadow-xs">
            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 48px; height: 48px;">
                <i class="bi bi-inbox text-muted fs-4"></i>
            </div>
            <div class="fw-bold small text-dark">Belum Ada Pesanan Masuk</div>
            <div class="text-muted" style="font-size: 11px;">Pesanan baru dari pembeli akan otomatis muncul di sini.</div>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-2.5">
            <?php foreach ($orders as $ord): ?>
                <div class="merchant-order-card">
                    <!-- Order Header -->
                    <div class="merchant-order-header">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-danger-subtle text-danger fw-extrabold" style="font-size: 11px;">#<?= htmlspecialchars($ord['order_code']) ?></span>
                            <span class="text-muted" style="font-size: 11px;"><i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($ord['created_at'])) ?></span>
                        </div>
                        <?php
                        $statusBadge = match($ord['order_status']) {
                            'pending' => 'bg-warning text-dark',
                            'confirmed' => 'bg-primary text-white',
                            'processing' => 'bg-info text-white',
                            'handover' => 'bg-purple text-white',
                            'picked_up' => 'bg-indigo text-white',
                            'delivered' => 'bg-success text-white',
                            default => 'bg-secondary text-white'
                        };
                        ?>
                        <span class="badge <?= $statusBadge ?> text-uppercase rounded-pill" style="font-size: 9.5px; font-weight: 700;">
                            <?= $ord['order_status'] ?>
                        </span>
                    </div>

                    <!-- Customer & Courier Info -->
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="bi bi-person-fill text-dark" style="font-size: 15px;"></i>
                            </div>
                            <div>
                                <div class="fw-bold small text-dark"><?= htmlspecialchars($ord['customer_name']) ?></div>
                                <div class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($ord['customer_phone']) ?></div>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-danger" style="font-size: 13.5px;"><?= format_rupiah($ord['total_amount']) ?></div>
                            <span class="badge bg-light text-muted border" style="font-size: 9px;"><?= $ord['payment_method'] ?></span>
                        </div>
                    </div>

                    <!-- Ordered Items Preview -->
                    <div class="merchant-order-items">
                        <?php foreach ($ord['items'] as $it): ?>
                            <div class="merchant-order-item-row">
                                <span class="fw-bold text-dark"><?= $it['quantity'] ?>x <?= htmlspecialchars($it['product_name']) ?></span>
                                <span class="text-muted small"><?= format_rupiah($it['unit_price'] * $it['quantity']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Order Notes if any -->
                    <?php if (!empty($ord['order_notes'])): ?>
                        <div class="merchant-notes-box">
                            <i class="bi bi-sticky-fill me-1"></i> <strong>Catatan:</strong> <?= htmlspecialchars($ord['order_notes']) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Action Button -->
                    <?php if ($ord['order_status'] === 'confirmed' || $ord['order_status'] === 'pending'): ?>
                        <button onclick="updateStoreOrderStatus(<?= $ord['id'] ?>, 'processing')" class="merchant-btn-action bg-warning text-dark">
                            <i class="bi bi-fire"></i> Terima & Masak Pesanan
                        </button>
                    <?php elseif ($ord['order_status'] === 'processing'): ?>
                        <button onclick="updateStoreOrderStatus(<?= $ord['id'] ?>, 'handover')" class="merchant-btn-action bg-info text-white">
                            <i class="bi bi-box-seam-fill"></i> Pesanan Siap Diambil Kurir
                        </button>
                    <?php elseif ($ord['order_status'] === 'handover'): ?>
                        <div class="p-2 bg-light rounded-3 text-center small text-muted border">
                            <i class="bi bi-bicycle text-primary me-1"></i> Menunggu kurir mengambil pesanan
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Store Location GPS & Mini Map Card -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
    <div class="card-header bg-white border-0 py-2.5 px-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-1.5">
            <i class="bi bi-geo-alt-fill text-danger"></i>
            <h6 class="fw-bold m-0 text-dark" style="font-size: 13px;">Titik GPS Toko Anda</h6>
        </div>
        <a href="https://www.google.com/maps/search/?api=1&query=<?= (float)($store['latitude'] ?? -6.9835) ?>,<?= (float)($store['longitude'] ?? 107.8335) ?>" target="_blank" class="btn btn-outline-danger btn-sm rounded-pill px-2.5 py-0.5" style="font-size: 10.5px;">
            <i class="bi bi-compass me-0.5"></i> Google Maps
        </a>
    </div>
    <div class="card-body p-0">
        <div id="vendor-store-map" style="width: 100%; height: 160px;"></div>
    </div>
    <div class="p-2.5 bg-white text-muted small border-top" style="font-size: 11px;">
        <i class="bi bi-info-circle me-1 text-primary"></i> Kurir CicalengkaGO akan menuju titik ini saat menjemput pesanan toko Anda.
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const sLat = <?= (float)($store['latitude'] ?? -6.9835) ?>;
    const sLng = <?= (float)($store['longitude'] ?? 107.8335) ?>;

    const vMap = L.map('vendor-store-map', { zoomControl: false, attributionControl: false }).setView([sLat, sLng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(vMap);

    const storeIcon = L.divIcon({
        className: 'custom-pin',
        html: '<div style="background:#EE2737;color:white;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid white;box-shadow:0 3px 8px rgba(0,0,0,0.3);"><i class="bi bi-shop fs-6"></i></div>',
        iconSize: [32, 32],
        iconAnchor: [16, 16]
    });

    L.marker([sLat, sLng], { icon: storeIcon })
        .addTo(vMap)
        .bindPopup("<b>🏪 <?= htmlspecialchars($store['name']) ?></b>")
        .openPopup();
});

async function toggleStoreOpenStatus() {
    try {
        const res = await fetch(window.BASE_URL + '/vendor/toggle-status', { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            Swal.fire({
                title: 'Status Toko Berubah',
                text: data.message,
                icon: 'success',
                timer: 1800,
                showConfirmButton: false
            }).then(() => location.reload());
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
            Swal.fire({
                title: 'Berhasil!',
                text: data.message,
                icon: 'success',
                timer: 1800,
                showConfirmButton: false
            }).then(() => location.reload());
        }
    } catch (e) {
        console.error(e);
    }
}
</script>
