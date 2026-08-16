<!-- GoFood Store Header Cover & Info -->
<div class="position-relative">
    <img src="<?= $baseUrl ?>/<?= htmlspecialchars($store['cover_photo'] ?? 'assets/images/stores/geprek_cover.jpg') ?>" alt="<?= htmlspecialchars($store['name']) ?>" style="width: 100%; height: 180px; object-fit: cover;">
    
    <div class="position-absolute top-0 start-0 p-3">
        <a href="<?= $baseUrl ?>" class="btn btn-light rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; border: 1px solid var(--gojek-border);">
            <i class="bi bi-arrow-left fs-5"></i>
        </a>
    </div>
</div>

<div class="p-3 bg-white border-bottom">
    <div class="d-flex align-items-start gap-3 mb-2">
        <img src="<?= $baseUrl ?>/<?= htmlspecialchars($store['logo'] ?? 'assets/images/stores/geprek_logo.png') ?>" alt="Logo" class="rounded-4 border shadow-sm" style="width: 64px; height: 64px; object-fit: cover; margin-top: -35px; background: white;">
        <div class="flex-grow-1">
            <h5 class="fw-bold mb-1" style="color: var(--gojek-charcoal);"><?= htmlspecialchars($store['name']) ?></h5>
            <div class="text-muted small mb-2" style="font-size: 11px;"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($store['address']) ?></div>
            
            <div class="d-flex align-items-center gap-2 small">
                <span class="text-warning fw-bold"><i class="bi bi-star-fill me-1"></i><?= number_format($store['rating'] ?? 5.0, 1) ?> <span class="text-muted fw-normal">(<?= $store['reviews_count'] ?? 0 ?> ulasan)</span></span>
                <span class="text-muted">•</span>
                <span class="text-muted"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($store['delivery_time'] ?? '20-30 mnt') ?></span>
                <span class="text-muted">•</span>
                <?php if ($store['is_open']): ?>
                    <span class="badge bg-success-subtle text-success fw-bold px-2 py-1">Buka</span>
                <?php else: ?>
                    <span class="badge bg-danger-subtle text-danger fw-bold px-2 py-1">Tutup</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Store Map Collapse Preview -->
    <div class="d-flex align-items-center justify-content-between pt-2 border-top">
        <button class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1 fw-bold" style="font-size: 11px; border-color: #EE2737; color: #EE2737;" type="button" data-bs-toggle="collapse" data-bs-target="#storeMapCollapse" aria-expanded="false">
            <i class="bi bi-map-fill me-1"></i> Lokasi Peta Resto
        </button>
        <a href="https://www.google.com/maps/dir/?api=1&destination=<?= (float)($store['latitude'] ?? -6.9835) ?>,<?= (float)($store['longitude'] ?? 107.8335) ?>" target="_blank" class="btn btn-light btn-sm text-dark rounded-pill px-3 py-1 fw-semibold border" style="font-size: 11px;">
            <i class="bi bi-compass text-danger me-1"></i> Petunjuk Arah
        </a>
    </div>

    <div class="collapse mt-2" id="storeMapCollapse">
        <div id="store-mini-map" style="width: 100%; height: 160px; border-radius: 12px;" class="border shadow-sm"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const sLat = <?= (float)($store['latitude'] ?? -6.9835) ?>;
    const sLng = <?= (float)($store['longitude'] ?? 107.8335) ?>;
    let sMapInit = false;

    const collapseEl = document.getElementById('storeMapCollapse');
    if (collapseEl) {
        collapseEl.addEventListener('shown.bs.collapse', function () {
            if (!sMapInit) {
                const sMap = L.map('store-mini-map', { zoomControl: false }).setView([sLat, sLng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap'
                }).addTo(sMap);

                const storeIcon = L.divIcon({
                    className: 'custom-pin',
                    html: '<div style="background:#EE2737;color:white;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2.5px solid white;box-shadow:0 3px 8px rgba(0,0,0,0.3);"><i class="bi bi-shop"></i></div>',
                    iconSize: [32, 32],
                    iconAnchor: [16, 16]
                });

                L.marker([sLat, sLng], { icon: storeIcon })
                    .bindPopup("<b><?= htmlspecialchars($store['name']) ?></b><br><small><?= htmlspecialchars($store['address']) ?></small>")
                    .addTo(sMap)
                    .openPopup();

                sMapInit = true;
            }
        });
    }
});
</script>

<!-- GoFood Product Catalog -->
<div class="p-3">
    <h6 class="fw-bold mb-3" style="color: var(--gojek-charcoal);"><i class="bi bi-egg-fried me-1 text-danger"></i> Daftar Menu Makanan & Minuman</h6>

    <?php if (empty($products)): ?>
        <div class="text-center py-4 text-muted small">Belum ada menu yang ditampilkan.</div>
    <?php else: ?>
        <div class="d-flex flex-column gap-3 mb-4">
            <?php foreach ($products as $prod): ?>
                <div class="p-3 bg-white rounded-4 border shadow-sm d-flex align-items-center justify-content-between gap-3">
                    <div class="flex-grow-1">
                        <div class="fw-bold mb-1" style="color: var(--gojek-charcoal); font-size: 14px;"><?= htmlspecialchars($prod['name']) ?></div>
                        <div class="text-muted small mb-2" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-size: 11px;">
                            <?= htmlspecialchars($prod['description']) ?>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold text-dark fs-6"><?= format_rupiah($prod['final_price']) ?></span>
                            <?php if ((float)$prod['discount'] > 0): ?>
                                <span class="text-muted text-decoration-line-through small" style="font-size: 11px;"><?= format_rupiah($prod['price']) ?></span>
                                <span class="badge bg-danger-subtle text-danger fw-bold" style="font-size: 10px;">Hemat <?= $prod['discount_type'] === 'percent' ? (int)$prod['discount'] . '%' : format_rupiah($prod['discount']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="position-relative flex-shrink-0 text-center">
                        <img src="<?= $baseUrl ?>/<?= htmlspecialchars($prod['image'] ?? 'assets/images/products/default.jpg') ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="rounded-3" style="width: 85px; height: 85px; object-fit: cover;">
                        <div class="mt-2">
                            <?php if ($store['is_open']): ?>
                                <button type="button" onclick="addToCart(<?= $prod['id'] ?>, 1)" class="btn btn-sm text-white fw-bold px-3 rounded-pill shadow-xs" style="background:#EE2737; font-size: 11px;">
                                    + Tambah
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-secondary disabled px-3 rounded-pill" style="font-size: 11px;">
                                    Tutup
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Store Reviews & Customer Testimonials Section -->
    <div class="bg-white rounded-4 border shadow-sm p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="bi bi-star-fill"></i>
                </div>
                <div>
                    <h6 class="fw-bold m-0 text-dark" style="font-size: 13.5px;">Ulasan & Rating Pembeli</h6>
                    <span class="text-muted" style="font-size: 11px;"><?= $store['reviews_count'] ?? 0 ?> ulasan dari pembeli</span>
                </div>
            </div>
            <div class="text-end">
                <div class="fw-bold text-dark fs-6"><?= number_format($store['rating'] ?? 5.0, 1) ?> <span class="text-warning">★</span></div>
            </div>
        </div>

        <?php if (empty($reviews)): ?>
            <div class="text-center py-3 bg-light rounded-3 text-muted small" style="font-size: 11.5px;">
                <i class="bi bi-chat-square-heart text-muted fs-4 mb-1 d-block"></i>
                Belum ada ulasan untuk toko ini. Jadilah yang pertama memberikan penilaian!
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-2.5">
                <?php foreach ($reviews as $rev): ?>
                    <div class="p-2.5 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-white text-dark d-flex align-items-center justify-content-center border" style="width: 28px; height: 28px; font-size: 11px; font-weight: 700;">
                                    <?= strtoupper(substr($rev['customer_name'] ?? 'P', 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark small" style="font-size: 12px;"><?= htmlspecialchars($rev['customer_name'] ?? 'Pembeli CicalengkaGO') ?></div>
                                    <div class="text-muted" style="font-size: 10px;"><?= date('d M Y', strtotime($rev['created_at'])) ?></div>
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
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
