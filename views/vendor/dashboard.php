<!-- Store Open/Close Status Hero Card -->
<div class="vnd-status-card <?= $store['is_open'] ? 'is-open' : 'is-closed' ?> mb-3">
    <div class="d-flex align-items-center gap-2.5">
        <span class="merchant-status-indicator <?= $store['is_open'] ? 'online' : 'offline' ?>"></span>
        <div>
            <div class="fw-bold text-dark" style="font-size: 13.5px; letter-spacing: -0.2px;"><?= htmlspecialchars($store['name']) ?></div>
            <span class="badge <?= $store['is_open'] ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?> rounded-pill px-2.5 py-0.5 mt-0.5" style="font-size: 9.5px; font-weight: 700;">
                <i class="bi <?= $store['is_open'] ? 'bi-check-circle-fill' : 'bi-dash-circle-fill' ?> me-1"></i>
                <?= $store['is_open'] ? 'TOKO BUKA (MENERIMA PESANAN)' : 'TOKO TUTUP SEMENTARA' ?>
            </span>
        </div>
    </div>
    <div class="form-check form-switch m-0 flex-shrink-0">
        <input class="form-check-input" type="checkbox" role="switch" id="storeSwitch" <?= $store['is_open'] ? 'checked' : '' ?> onchange="toggleStoreOpenStatus()" style="width: 48px; height: 24px; cursor: pointer;">
    </div>
</div>

<!-- Merchant Main Stat Grid -->
<div class="row g-2.5 mb-3">
    <!-- Card 1: Saldo Dompet Toko -->
    <div class="col-6">
        <a href="<?= $baseUrl ?>/vendor/wallet" class="vnd-stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div class="vnd-stat-icon green">
                    <i class="bi bi-wallet2"></i>
                </div>
                <span class="badge bg-light text-muted border fw-semibold" style="font-size: 9px;">Dompet <i class="bi bi-chevron-right"></i></span>
            </div>
            <div>
                <div class="vnd-stat-value text-success"><?= format_rupiah($wallet['balance'] ?? 0) ?></div>
                <div class="vnd-stat-label">Saldo Siap Ditarik</div>
            </div>
        </a>
    </div>

    <!-- Card 2: Total Pesanan Masuk -->
    <div class="col-6">
        <a href="<?= $baseUrl ?>/vendor/orders" class="vnd-stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div class="vnd-stat-icon red">
                    <i class="bi bi-receipt"></i>
                </div>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-0.5" style="font-size: 9px; font-weight: 700;">
                    <?= $stats['delivered_count'] ?? 0 ?> Selesai
                </span>
            </div>
            <div>
                <div class="vnd-stat-value"><?= (int)($stats['total_orders'] ?? $total_orders ?? 0) ?> <span style="font-size: 11px; font-weight: 600;" class="text-muted">Pesanan</span></div>
                <div class="vnd-stat-label">Total Order Masuk</div>
            </div>
        </a>
    </div>

    <!-- Card 3: Total Omzet / Pendapatan Selesai -->
    <div class="col-6">
        <div class="vnd-stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div class="vnd-stat-icon blue">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0.5" style="font-size: 9px; font-weight: 700;">Bersih 90%</span>
            </div>
            <div>
                <div class="vnd-stat-value"><?= format_rupiah($stats['total_revenue'] ?? 0) ?></div>
                <div class="vnd-stat-label">Total Omzet Pesanan</div>
            </div>
        </div>
    </div>

    <!-- Card 4: Rating & Ulasan Pelanggan -->
    <div class="col-6">
        <a href="#customer-reviews-section" class="vnd-stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div class="vnd-stat-icon amber">
                    <i class="bi bi-star-fill text-warning"></i>
                </div>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-0.5" style="font-size: 9px; font-weight: 700;">
                    <?= $store['reviews_count'] ?? 0 ?> Ulasan
                </span>
            </div>
            <div>
                <div class="vnd-stat-value">
                    <?= !empty($store['reviews_count']) && (int)$store['reviews_count'] > 0 ? number_format($store['rating'], 1) : '0.0' ?>
                    <span style="font-size: 13px; color: #F59E0B;">★</span>
                </div>
                <div class="vnd-stat-label">Rating Toko Anda</div>
            </div>
        </a>
    </div>
</div>

<!-- Today's Order Pipeline Breakdown -->
<div class="vnd-card mb-3">
    <div class="vnd-card-header">
        <h6 class="vnd-card-title">
            <i class="bi bi-calendar2-check-fill text-danger"></i> Ringkasan Status Order Toko
        </h6>
        <span class="badge bg-light text-dark border px-2 py-1 rounded-pill" style="font-size: 10px; font-weight: 600;">
            Hari Ini: <strong><?= $stats['today_orders'] ?? 0 ?> Order</strong>
        </span>
    </div>

    <div class="row g-2">
        <div class="col-3">
            <div class="vnd-pipeline-chip pending">
                <div class="chip-num"><?= $stats['pending_count'] ?? 0 ?></div>
                <div class="chip-label">Menunggu</div>
            </div>
        </div>
        <div class="col-3">
            <div class="vnd-pipeline-chip cooking">
                <div class="chip-num"><?= $stats['processing_count'] ?? 0 ?></div>
                <div class="chip-label">Dimasak</div>
            </div>
        </div>
        <div class="col-3">
            <div class="vnd-pipeline-chip delivery">
                <div class="chip-num"><?= $stats['on_the_way_count'] ?? 0 ?></div>
                <div class="chip-label">Diantar</div>
            </div>
        </div>
        <div class="col-3">
            <div class="vnd-pipeline-chip done">
                <div class="chip-num"><?= $stats['delivered_count'] ?? 0 ?></div>
                <div class="chip-label">Selesai</div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Action Strip -->
<div class="d-flex gap-2 mb-3">
    <a href="<?= $baseUrl ?>/vendor/products/create" class="vnd-action-btn red">
        <i class="bi bi-plus-circle-fill"></i> Tambah Menu Baru
    </a>
    <a href="<?= $baseUrl ?>/vendor/products" class="vnd-action-btn light">
        <i class="bi bi-egg-fried text-warning"></i> Menu (<?= $products_count ?? 0 ?>)
    </a>
    <a href="<?= $baseUrl ?>/vendor/wallet" class="vnd-action-btn dark">
        <i class="bi bi-cash-stack text-success"></i> Tarik Dana
    </a>
</div>

<!-- Customer Reviews & Rating Section -->
<div class="vnd-card mb-3" id="customer-reviews-section">
    <div class="vnd-card-header">
        <h6 class="vnd-card-title">
            <i class="bi bi-chat-heart-fill text-warning"></i> Ulasan & Rating Pembeli
        </h6>
        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1 rounded-pill fw-bold" style="font-size: 10px;">
            <?= !empty($store['reviews_count']) && (int)$store['reviews_count'] > 0 ? number_format($store['rating'], 1) : '0.0' ?> ★ (<?= (int)($store['reviews_count'] ?? 0) ?>)
        </span>
    </div>

    <?php if (empty($reviews)): ?>
        <div class="text-center py-3 bg-light rounded-3 text-muted small">
            <i class="bi bi-star text-muted fs-3 mb-1 d-block"></i>
            <div class="fw-semibold text-dark mb-0.5">Belum Ada Ulasan Pembeli</div>
            <div style="font-size: 11px;">Ulasan dan rating dari pembeli setelah pesanan selesai akan tampil di sini.</div>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-2">
            <?php foreach ($reviews as $rev): ?>
                <div class="p-2.5 bg-light rounded-3 border">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-white text-dark d-flex align-items-center justify-content-center border fw-bold" style="width: 28px; height: 28px; font-size: 12px;">
                                <?= strtoupper(substr($rev['customer_name'] ?? 'P', 0, 1)) ?>
                            </div>
                            <div>
                                <div class="fw-bold text-dark small" style="font-size: 12px;"><?= htmlspecialchars($rev['customer_name'] ?? 'Pembeli CicalengkaGO') ?></div>
                                <div class="text-muted" style="font-size: 10px;">
                                    <?= date('d M Y, H:i', strtotime($rev['created_at'])) ?>
                                    <?php if (!empty($rev['order_code'])): ?>
                                        • #<?= htmlspecialchars($rev['order_code']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="text-warning small fw-bold">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <i class="bi <?= $s <= (int)$rev['rating'] ? 'bi-star-fill' : 'bi-star' ?>" style="font-size: 11px;"></i>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <?php if (!empty($rev['comment'])): ?>
                        <div class="text-dark small mt-1 ps-1 fst-italic" style="font-size: 11.5px;">
                            "<?= htmlspecialchars($rev['comment']) ?>"
                        </div>
                    <?php else: ?>
                        <div class="text-muted small mt-1 ps-1" style="font-size: 10.5px;">
                            (Memberikan rating <?= (int)$rev['rating'] ?> bintang)
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Recent Active Orders Section -->
<div class="mb-3">
    <div class="d-flex align-items-center justify-content-between mb-2.5">
        <h6 class="vnd-section-title">
            <i class="bi bi-bell-fill text-danger fs-6"></i> Pesanan Terbaru Masuk
        </h6>
        <a href="<?= $baseUrl ?>/vendor/orders" class="text-danger fw-bold text-decoration-none small" style="font-size: 11.5px;">
            Lihat Semua (<?= count($orders) ?>) <i class="bi bi-arrow-right"></i>
        </a>
    </div>

    <?php if (empty($orders)): ?>
        <div class="text-center py-4 bg-white rounded-4 border p-3 shadow-xs">
            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 48px; height: 48px;">
                <i class="bi bi-inbox text-muted fs-4"></i>
            </div>
            <div class="fw-bold small text-dark">Belum Ada Pesanan Masuk</div>
            <div class="text-muted" style="font-size: 11px;">Pesanan baru dari pembeli akan otomatis muncul di sini secara real-time.</div>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-2.5">
            <?php foreach ($orders as $ord): ?>
                <div class="vnd-order-card">
                    <!-- Order Header -->
                    <div class="vnd-order-header">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-white text-dark fw-extrabold" style="font-size: 11px;">#<?= htmlspecialchars($ord['order_code']) ?></span>
                            <span class="text-white-50" style="font-size: 11px;"><i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($ord['created_at'])) ?></span>
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

                    <div class="vnd-order-body">
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
                                <span class="badge bg-light text-muted border" style="font-size: 9px;"><?= strtoupper($ord['payment_method']) ?></span>
                            </div>
                        </div>

                        <!-- Ordered Items Preview -->
                        <div class="vnd-order-items">
                            <?php foreach ($ord['items'] as $it): ?>
                                <div class="vnd-order-item-row">
                                    <span class="fw-bold text-dark"><?= $it['quantity'] ?>x <?= htmlspecialchars($it['product_name']) ?></span>
                                    <span class="text-muted small"><?= format_rupiah($it['total_price'] ?? (($it['price'] ?? 0) * $it['quantity'])) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Order Notes if any -->
                        <?php if (!empty($ord['order_notes'])): ?>
                            <div class="p-2 rounded-3 bg-warning-subtle text-warning-emphasis small mb-2" style="font-size: 11px; border-left: 3px solid #F59E0B;">
                                <i class="bi bi-sticky-fill me-1"></i> <strong>Catatan:</strong> <?= htmlspecialchars($ord['order_notes']) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Action Button -->
                        <?php if ($ord['order_status'] === 'confirmed' || $ord['order_status'] === 'pending'): ?>
                            <button onclick="updateStoreOrderStatus(<?= $ord['id'] ?>, 'processing')" class="btn btn-warning text-dark fw-bold w-100 py-2 rounded-3 shadow-xs border-0" style="font-size: 12px;">
                                <i class="bi bi-fire me-1"></i> Terima & Masak Pesanan
                            </button>
                        <?php elseif ($ord['order_status'] === 'processing'): ?>
                            <button onclick="updateStoreOrderStatus(<?= $ord['id'] ?>, 'handover')" class="btn btn-info text-white fw-bold w-100 py-2 rounded-3 shadow-xs border-0" style="font-size: 12px;">
                                <i class="bi bi-box-seam-fill me-1"></i> Pesanan Siap Diambil Kurir
                            </button>
                        <?php elseif ($ord['order_status'] === 'handover'): ?>
                            <div class="p-2 bg-light rounded-3 text-center small text-muted border">
                                <i class="bi bi-bicycle text-primary me-1"></i> Menunggu kurir mengambil pesanan
                            </div>
                        <?php elseif ($ord['order_status'] === 'delivered'): ?>
                            <div class="p-2 bg-success-subtle text-success rounded-3 text-center small fw-bold border border-success-subtle">
                                <i class="bi bi-check-circle-fill me-1"></i> Pesanan Telah Selesai (Dana Masuk ke Saldo)
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Customer Ratings & Review History Card -->
<div class="vnd-card mb-3" id="customer-reviews-section">
    <div class="vnd-card-header d-flex align-items-center justify-content-between">
        <h6 class="vnd-card-title">
            <i class="bi bi-star-fill text-warning"></i> Rating & Ulasan Pelanggan Toko
        </h6>
        <div class="d-flex align-items-center gap-1.5 bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1 rounded-pill" style="font-size: 11px; font-weight: 700;">
            <span style="font-size: 13px;">⭐ <?= !empty($store['reviews_count']) && (int)$store['reviews_count'] > 0 ? number_format($store['rating'], 1) : '5.0' ?></span>
            <span class="text-muted fw-normal" style="font-size: 10px;">(<?= $store['reviews_count'] ?? count($reviews ?? []) ?> Ulasan)</span>
        </div>
    </div>

    <div class="vnd-card-body p-0">
        <?php if (empty($reviews)): ?>
            <div class="text-center py-4 px-3">
                <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 44px; height: 44px; font-size: 20px;">
                    <i class="bi bi-star"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1" style="font-size: 12px;">Belum Ada Ulasan Toko</h6>
                <p class="text-muted mb-0" style="font-size: 10.5px; max-width: 280px; margin: 0 auto;">
                    Ulasan dan penilaian bintang dari pelanggan setelah pesanan selesai akan otomatis ditampilkan di sini.
                </p>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column divide-y">
                <?php foreach ($reviews as $rev): ?>
                    <div class="p-3 border-bottom">
                        <div class="d-flex align-items-center justify-content-between mb-1.5">
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($rev['customer_avatar'] ?? 'assets/images/users/customer.png') ?>" alt="Customer" class="rounded-circle border" style="width: 32px; height: 32px; object-fit: cover;">
                                <div>
                                    <div class="fw-bold text-dark" style="font-size: 11.5px;"><?= htmlspecialchars($rev['customer_name'] ?? 'Pelanggan CicalengkaGO') ?></div>
                                    <div class="text-muted" style="font-size: 9.5px;">Order #<?= htmlspecialchars($rev['order_code'] ?? '-') ?> • <?= date('d M Y, H:i', strtotime($rev['created_at'])) ?></div>
                                </div>
                            </div>
                            <div class="text-warning fw-bold d-flex align-items-center gap-1" style="font-size: 11px;">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <i class="bi <?= $s <= (int)$rev['rating'] ? 'bi-star-fill' : 'bi-star text-muted opacity-50' ?>"></i>
                                <?php endfor; ?>
                                <span class="ms-1 text-dark fw-extrabold" style="font-size: 11px;"><?= (int)$rev['rating'] ?>.0</span>
                            </div>
                        </div>
                        <?php if (!empty($rev['comment'])): ?>
                            <div class="p-2.5 bg-light rounded-3 text-dark mt-2 border" style="font-size: 11px; line-height: 1.4;">
                                <i class="bi bi-chat-quote-fill text-muted me-1"></i> "<?= htmlspecialchars($rev['comment']) ?>"
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Store Location GPS & Mini Map Card -->
<div class="vnd-card overflow-hidden p-0 mb-3">
    <div class="p-3 bg-white d-flex align-items-center justify-content-between border-bottom">
        <div class="d-flex align-items-center gap-1.5">
            <i class="bi bi-geo-alt-fill text-danger"></i>
            <h6 class="fw-bold m-0 text-dark" style="font-size: 13px;">Titik GPS Toko Anda</h6>
        </div>
        <a href="https://www.google.com/maps/search/?api=1&query=<?= (float)($store['latitude'] ?? -6.9835) ?>,<?= (float)($store['longitude'] ?? 107.8335) ?>" target="_blank" class="btn btn-outline-danger btn-sm rounded-pill px-2.5 py-0.5" style="font-size: 10.5px;">
            <i class="bi bi-compass me-0.5"></i> Google Maps
        </a>
    </div>
    <div>
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

    const storeSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 46" width="32" height="46">
      <defs>
        <linearGradient id="vsg" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#f87171"/>
          <stop offset="100%" stop-color="#b91c1c"/>
        </linearGradient>
      </defs>
      <path d="M16 2C9 2 3 8 3 15c0 10 13 29 13 29S29 25 29 15C29 8 23 2 16 2z" fill="url(#vsg)" stroke="white" stroke-width="2"/>
      <path d="M9 14 L9 12 Q9 10 16 10 Q23 10 23 12 L23 14 Q19.5 17 16 16 Q12.5 17 9 14z" fill="white"/>
      <rect x="11" y="14.5" width="10" height="6" rx="0.5" fill="white" opacity="0.25"/>
      <rect x="13" y="15" width="6" height="5.5" fill="white"/>
      <rect x="14.5" y="16" width="3" height="4.5" fill="#b91c1c"/>
    </svg>`;
    const storeIcon = L.icon({
        iconUrl: 'data:image/svg+xml,' + encodeURIComponent(storeSvg),
        iconSize: [32, 46],
        iconAnchor: [16, 46],
        popupAnchor: [0, -46]
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
            showVendorToast(data.message, 'success');
            setTimeout(() => location.reload(), 1200);
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
            showVendorToast(data.message, 'success');
            setTimeout(() => location.reload(), 1200);
        }
    } catch (e) {
        console.error(e);
    }
}
</script>
