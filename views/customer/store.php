<!-- GoFood Store Header Cover & Floating Back Button -->
<div class="position-relative">
    <img src="<?= $baseUrl ?>/<?= htmlspecialchars($store['cover_photo'] ?? 'assets/images/stores/geprek_cover.jpg') ?>" alt="<?= htmlspecialchars($store['name']) ?>" style="width: 100%; height: 135px; object-fit: cover;">
    <div class="position-absolute top-0 start-0 p-3">
        <a href="<?= $baseUrl ?>" class="btn btn-light rounded-circle shadow-md d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: rgba(255, 255, 255, 0.92); backdrop-filter: blur(8px); border: 1px solid rgba(226, 232, 240, 0.8);">
            <i class="bi bi-arrow-left text-dark" style="font-size: 16px;"></i>
        </a>
    </div>
</div>

<!-- Store Main Information Floating Card -->
<div class="px-3">
    <div class="bg-white border shadow-sm p-3.5 mb-3.5 overflow-hidden" style="border-radius: 18px; border-color: #E2E8F0 !important; margin-top: -24px; position: relative; z-index: 2; padding: 16px !important;">
        <div class="d-flex align-items-start gap-3 mb-2.5">
            <img src="<?= $baseUrl ?>/<?= htmlspecialchars($store['logo'] ?? 'assets/images/stores/geprek_logo.png') ?>" alt="Logo" class="rounded-3 border shadow-2xs flex-shrink-0" style="width: 52px; height: 52px; object-fit: cover; border-radius: 14px !important; background: white; border-color: #E2E8F0 !important;">
            <div class="flex-grow-1 min-w-0">
                <h6 class="fw-extrabold mb-1 text-truncate text-dark" style="font-size: 15px; letter-spacing: -0.3px;"><?= htmlspecialchars($store['name']) ?></h6>
                <div class="text-muted text-truncate d-flex align-items-center gap-1" style="font-size: 11px;">
                    <i class="bi bi-geo-alt-fill text-danger flex-shrink-0"></i>
                    <span class="text-truncate"><?= htmlspecialchars($store['address']) ?></span>
                </div>
                
                <div class="d-flex align-items-center gap-2 mt-1.5 flex-wrap" style="font-size: 10.5px;">
                    <span class="text-warning fw-bold d-flex align-items-center gap-1 flex-shrink-0">
                        <i class="bi bi-star-fill"></i> <?= !empty($store['reviews_count']) && (int)$store['reviews_count'] > 0 ? number_format($store['rating'], 1) : '0.0' ?> 
                        <span class="text-muted fw-normal">(<?= (int)($store['reviews_count'] ?? 0) ?> ulasan)</span>
                    </span>
                    <span class="text-muted">•</span>
                    <span class="text-muted flex-shrink-0"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($store['delivery_time'] ?? '15-25 mnt') ?></span>
                    <span class="text-muted">•</span>
                    <?php if ($store['is_open']): ?>
                        <span class="badge bg-success-subtle text-success fw-bold px-2 py-0.5 rounded-pill flex-shrink-0" style="font-size: 9px;"><i class="bi bi-door-open-fill me-0.5"></i> Buka</span>
                    <?php else: ?>
                        <span class="badge bg-danger-subtle text-danger fw-bold px-2 py-0.5 rounded-pill flex-shrink-0" style="font-size: 9px;"><i class="bi bi-door-closed-fill me-0.5"></i> Tutup</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Store Map Collapse & Navigation Actions -->
        <div class="d-flex align-items-center justify-content-between pt-2.5 border-top gap-2" style="border-color: #F1F5F9 !important;">
            <button class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1.5 fw-bold flex-grow-1 d-flex align-items-center justify-content-center gap-1" style="font-size: 11px; border-color: #EE2737; color: #EE2737;" type="button" data-bs-toggle="collapse" data-bs-target="#storeMapCollapse" aria-expanded="false">
                <i class="bi bi-map-fill me-0.5"></i> Peta Resto
            </button>
            <a href="https://www.google.com/maps/dir/?api=1&destination=<?= (float)($store['latitude'] ?? -6.9835) ?>,<?= (float)($store['longitude'] ?? 107.8335) ?>" target="_blank" class="btn btn-light btn-sm text-dark rounded-pill px-3 py-1.5 fw-semibold border flex-grow-1 d-flex align-items-center justify-content-center gap-1" style="font-size: 11px; border-color: #E2E8F0; background: #F8FAFC;">
                <i class="bi bi-compass text-danger me-0.5"></i> Petunjuk Arah
            </a>
        </div>

        <div class="collapse mt-2.5" id="storeMapCollapse">
            <div id="store-mini-map" style="width: 100%; height: 140px; border-radius: 12px;" class="border shadow-2xs"></div>
        </div>
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
                    html: '<div style="background:#EE2737;color:white;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.3);font-size:12px;"><i class="bi bi-shop"></i></div>',
                    iconSize: [28, 28],
                    iconAnchor: [14, 14]
                });

                L.marker([sLat, sLng], { icon: storeIcon })
                    .bindPopup("<b style='font-size:11px;'><?= htmlspecialchars($store['name']) ?></b><br><small style='font-size:9.5px;'><?= htmlspecialchars($store['address']) ?></small>")
                    .addTo(sMap)
                    .openPopup();

                sMapInit = true;
            }
        });
    }
});
</script>

<!-- GoFood Product Catalog -->
<div class="px-3 pb-3">
    <div class="d-flex align-items-center justify-content-between mb-3 px-0.5">
        <h6 class="fw-extrabold m-0 text-dark d-flex align-items-center gap-1.5" style="font-size: 13.5px; letter-spacing: -0.3px;">
            <i class="bi bi-egg-fried text-danger fs-6"></i> Daftar Menu Makanan & Minuman
        </h6>
    </div>

    <?php if (empty($products)): ?>
        <div class="text-center py-4 text-muted bg-light rounded-4 border p-4" style="font-size: 11.5px;">Belum ada menu yang ditampilkan.</div>
    <?php else: ?>
        <div class="d-flex flex-column gap-3 mb-3.5">
            <?php foreach ($products as $prod): ?>
                <div class="p-3.5 bg-white border shadow-2xs d-flex align-items-center justify-content-between gap-3 overflow-hidden" style="border-radius: 16px; border-color: #E2E8F0 !important; padding: 14px 16px !important;">
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold mb-1 text-truncate text-dark" style="font-size: 13px; letter-spacing: -0.2px;" title="<?= htmlspecialchars($prod['name']) ?>"><?= htmlspecialchars($prod['name']) ?></div>
                        <div class="text-muted" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-size: 10.5px; line-height: 1.4; margin-bottom: 8px;">
                            <?= htmlspecialchars($prod['description']) ?>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="fw-extrabold text-dark" style="font-size: 13.5px; color: #0F172A;"><?= format_rupiah($prod['final_price']) ?></span>
                            <?php if (!empty($prod['discount']) && (float)$prod['discount'] > 0 && (float)$prod['price'] > (float)$prod['final_price']): ?>
                                <span class="text-muted text-decoration-line-through me-1" style="font-size: 10.5px; color: #94A3B8;"><?= format_rupiah($prod['price']) ?></span>
                                <span class="badge bg-danger-subtle text-danger fw-bold" style="font-size: 9px; padding: 2.5px 6px; border-radius: 6px;">-<?= $prod['discount_type'] === 'percent' ? (int)$prod['discount'] . '%' : format_rupiah($prod['discount']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="position-relative flex-shrink-0 text-center">
                        <img src="<?= $baseUrl ?>/<?= htmlspecialchars($prod['image'] ?? 'assets/images/products/default.jpg') ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="rounded-3 shadow-2xs mb-2" style="width: 76px; height: 76px; object-fit: cover; border-radius: 14px !important;">
                        <div>
                            <?php if ($store['is_open']): ?>
                                <button type="button" onclick="addToCart(<?= $prod['id'] ?>, 1)" class="btn btn-sm text-white fw-bold px-3 py-1.5 rounded-pill shadow-2xs d-inline-flex align-items-center gap-1" style="background: linear-gradient(135deg, #EE2737, #C61524); font-size: 11px;">
                                    <i class="bi bi-plus-lg"></i> Tambah
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-secondary disabled px-3 py-1.5 rounded-pill" style="font-size: 10.5px;">
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
    <div class="bg-white border shadow-2xs p-3.5 mb-3" style="border-radius: 16px; border-color: #E2E8F0 !important; padding: 16px !important;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; font-size: 14px;">
                    <i class="bi bi-star-fill"></i>
                </div>
                <div>
                    <h6 class="fw-bold m-0 text-dark" style="font-size: 12.5px;">Ulasan & Rating Pembeli</h6>
                    <span class="text-muted" style="font-size: 10px;"><?= $store['reviews_count'] ?? 0 ?> ulasan dari pelanggan</span>
                </div>
            </div>
            <div class="text-end flex-shrink-0">
                <div class="fw-extrabold text-dark" style="font-size: 14px;"><?= number_format($store['rating'] ?? 5.0, 1) ?> <span class="text-warning">★</span></div>
            </div>
        </div>

        <?php if (empty($reviews)): ?>
            <div class="text-center py-3 bg-light rounded-3 text-muted" style="font-size: 10.5px;">
                <i class="bi bi-chat-square-heart text-muted fs-5 mb-1 d-block"></i>
                Belum ada ulasan untuk toko ini.
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-2">
                <?php foreach ($reviews as $rev): ?>
                    <div class="p-2.5 bg-light rounded-3 border" style="border-color: #E2E8F0 !important;">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-white text-dark d-flex align-items-center justify-content-center border flex-shrink-0" style="width: 24px; height: 24px; font-size: 10px; font-weight: 700; border-color: #CBD5E1 !important;">
                                    <?= strtoupper(substr($rev['customer_name'] ?? 'P', 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark" style="font-size: 10.5px;"><?= htmlspecialchars($rev['customer_name'] ?? 'Pembeli CicalengkaGO') ?></div>
                                    <div class="text-muted" style="font-size: 9px;"><?= date('d M Y', strtotime($rev['created_at'])) ?></div>
                                </div>
                            </div>
                            <div class="text-warning fw-bold flex-shrink-0" style="font-size: 9.5px;">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <i class="bi <?= $s <= (int)$rev['rating'] ? 'bi-star-fill' : 'bi-star' ?>" style="font-size: 10px;"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php if (!empty($rev['comment'])): ?>
                            <div class="text-dark mt-1 ps-0.5 fst-italic" style="font-size: 10px; line-height: 1.4;">
                                "<?= htmlspecialchars($rev['comment']) ?>"
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
