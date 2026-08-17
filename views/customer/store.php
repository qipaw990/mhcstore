<!-- GoFood Store Header Cover & Floating Back Button -->
<div class="position-relative">
    <img src="<?= $baseUrl ?>/<?= htmlspecialchars($store['cover_photo'] ?? 'assets/images/stores/geprek_cover.jpg') ?>" alt="<?= htmlspecialchars($store['name']) ?>" style="width: 100%; height: 110px; object-fit: cover;">
    <div class="position-absolute top-0 start-0 p-2.5">
        <a href="<?= $baseUrl ?>" class="btn btn-light rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; background: rgba(255, 255, 255, 0.92); backdrop-filter: blur(8px); border: 1px solid rgba(226, 232, 240, 0.8); padding: 0;">
            <i class="bi bi-arrow-left text-dark" style="font-size: 13px;"></i>
        </a>
    </div>
</div>

<!-- Store Main Information Floating Card -->
<div class="px-2.5">
    <div class="bg-white border shadow-2xs overflow-hidden" style="border-radius: 14px !important; border-color: #E2E8F0 !important; margin-top: -20px; position: relative; z-index: 2; padding: 12px !important; margin-bottom: 10px !important;">
        <div class="d-flex align-items-start gap-2.5 mb-2">
            <img src="<?= $baseUrl ?>/<?= htmlspecialchars($store['logo'] ?? 'assets/images/stores/geprek_logo.png') ?>" alt="Logo" class="border shadow-2xs flex-shrink-0" style="width: 44px; height: 44px; object-fit: cover; border-radius: 10px !important; background: white; border-color: #E2E8F0 !important;">
            <div class="flex-grow-1 min-w-0">
                <h6 class="fw-bold mb-1 text-truncate text-dark" style="font-size: 13px; letter-spacing: -0.2px; line-height: 1.25;"><?= htmlspecialchars($store['name']) ?></h6>
                <div class="text-muted text-truncate d-flex align-items-center gap-1" style="font-size: 10px;">
                    <i class="bi bi-geo-alt-fill text-danger flex-shrink-0" style="font-size: 10px;"></i>
                    <span class="text-truncate"><?= htmlspecialchars($store['address']) ?></span>
                </div>
                
                <div class="d-flex align-items-center gap-1.5 mt-1 flex-wrap" style="font-size: 9.5px;">
                    <span class="text-warning fw-bold d-flex align-items-center gap-1 flex-shrink-0">
                        <i class="bi bi-star-fill" style="font-size: 9.5px;"></i> <?= !empty($store['reviews_count']) && (int)$store['reviews_count'] > 0 ? number_format($store['rating'], 1) : '0.0' ?> 
                        <span class="text-muted fw-normal">(<?= (int)($store['reviews_count'] ?? 0) ?> ulasan)</span>
                    </span>
                    <span class="text-muted">•</span>
                    <span class="text-muted flex-shrink-0"><i class="bi bi-clock me-0.5"></i><?= htmlspecialchars($store['delivery_time'] ?? '15-25 mnt') ?></span>
                    <span class="text-muted">•</span>
                    <?php if ($store['is_open']): ?>
                        <span class="badge bg-success-subtle text-success fw-bold px-1.5 py-0.5 rounded-pill flex-shrink-0" style="font-size: 8.5px;"><i class="bi bi-door-open-fill me-0.5"></i> Buka</span>
                    <?php else: ?>
                        <span class="badge bg-danger-subtle text-danger fw-bold px-1.5 py-0.5 rounded-pill flex-shrink-0" style="font-size: 8.5px;"><i class="bi bi-door-closed-fill me-0.5"></i> Tutup</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Store Map Collapse & Navigation Actions -->
        <div class="d-flex align-items-center justify-content-between pt-2 border-top gap-2" style="border-color: #F1F5F9 !important; margin-top: 8px;">
            <button class="btn btn-outline-danger btn-sm rounded-pill fw-bold flex-grow-1 d-flex align-items-center justify-content-center gap-1" style="font-size: 10px !important; padding: 4px 10px !important; border-color: #EE2737; color: #EE2737;" type="button" data-bs-toggle="collapse" data-bs-target="#storeMapCollapse" aria-expanded="false">
                <i class="bi bi-map-fill me-0.5" style="font-size: 10px;"></i> Peta Resto
            </button>
            <a href="https://www.google.com/maps/dir/?api=1&destination=<?= (float)($store['latitude'] ?? -6.9835) ?>,<?= (float)($store['longitude'] ?? 107.8335) ?>" target="_blank" class="btn btn-light btn-sm text-dark rounded-pill fw-semibold border flex-grow-1 d-flex align-items-center justify-content-center gap-1" style="font-size: 10px !important; padding: 4px 10px !important; border-color: #E2E8F0; background: #F8FAFC;">
                <i class="bi bi-compass text-danger me-0.5" style="font-size: 10px;"></i> Petunjuk Arah
            </a>
        </div>

        <div class="collapse mt-2" id="storeMapCollapse">
            <div id="store-mini-map" style="width: 100%; height: 130px; border-radius: 10px;" class="border shadow-2xs"></div>
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
                    html: '<div style="background:#EE2737;color:white;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.3);font-size:10px;"><i class="bi bi-shop"></i></div>',
                    iconSize: [24, 24],
                    iconAnchor: [12, 12]
                });

                L.marker([sLat, sLng], { icon: storeIcon })
                    .bindPopup("<b style='font-size:10px;'><?= htmlspecialchars($store['name']) ?></b><br><small style='font-size:9px;'><?= htmlspecialchars($store['address']) ?></small>")
                    .addTo(sMap)
                    .openPopup();

                sMapInit = true;
            }
        });
    }
});
</script>

<!-- GoFood Product Catalog -->
<div class="px-2.5 pb-3">
    <div class="d-flex align-items-center justify-content-between mb-2 mt-1 px-0.5">
        <h6 class="fw-bold m-0 text-dark d-flex align-items-center gap-1.5" style="font-size: 11.5px; letter-spacing: -0.2px;">
            <i class="bi bi-egg-fried text-danger" style="font-size: 13px;"></i> Daftar Menu Makanan & Minuman
        </h6>
    </div>

    <?php if (empty($products)): ?>
        <div class="text-center py-3 text-muted bg-light rounded-3 border p-3" style="font-size: 10.5px;">Belum ada menu yang ditampilkan.</div>
    <?php else: ?>
        <div class="d-flex flex-column gap-2 mb-2.5">
            <?php foreach ($products as $prod): ?>
                <div class="bg-white border shadow-2xs d-flex align-items-center justify-content-between gap-2.5 overflow-hidden" style="border-radius: 12px !important; border-color: #E2E8F0 !important; padding: 10px 12px !important;">
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold text-truncate text-dark" style="font-size: 11.5px; letter-spacing: -0.2px; margin-bottom: 2px;" title="<?= htmlspecialchars($prod['name']) ?>"><?= htmlspecialchars($prod['name']) ?></div>
                        <div class="text-muted" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-size: 9.5px; line-height: 1.35; margin-bottom: 6px;">
                            <?= htmlspecialchars($prod['description']) ?>
                        </div>
                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                            <span class="fw-bold text-dark" style="font-size: 11.5px; color: #0F172A;"><?= format_rupiah($prod['final_price']) ?></span>
                            <?php if (!empty($prod['discount']) && (float)$prod['discount'] > 0 && (float)$prod['price'] > (float)$prod['final_price']): ?>
                                <span class="text-muted text-decoration-line-through" style="font-size: 9px; color: #94A3B8;"><?= format_rupiah($prod['price']) ?></span>
                                <span class="badge bg-danger-subtle text-danger fw-bold" style="font-size: 8px; padding: 1.5px 5px; border-radius: 4px;">-<?= $prod['discount_type'] === 'percent' ? (int)$prod['discount'] . '%' : format_rupiah($prod['discount']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="position-relative flex-shrink-0 text-center">
                        <img src="<?= $baseUrl ?>/<?= htmlspecialchars($prod['image'] ?? 'assets/images/products/default.jpg') ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="shadow-2xs mb-1.5" style="width: 62px; height: 62px; object-fit: cover; border-radius: 10px !important;">
                        <div>
                            <?php if ($store['is_open']): ?>
                                <button type="button" onclick="addToCart(<?= $prod['id'] ?>, 1)" class="btn btn-sm text-white fw-bold shadow-2xs d-inline-flex align-items-center justify-content-center gap-1" style="background: linear-gradient(135deg, #EE2737, #C61524); font-size: 10px !important; padding: 3px 10px !important; border-radius: 20px !important;">
                                    <i class="bi bi-plus-lg" style="font-size: 10px;"></i> Tambah
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-secondary disabled rounded-pill" style="font-size: 9.5px; padding: 2.5px 8px !important;">
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
    <div class="bg-white border shadow-2xs" style="border-radius: 12px !important; border-color: #E2E8F0 !important; padding: 12px !important; margin-bottom: 10px !important;">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 26px; height: 26px; font-size: 11px;">
                    <i class="bi bi-star-fill"></i>
                </div>
                <div>
                    <h6 class="fw-bold m-0 text-dark" style="font-size: 11.5px;">Ulasan & Rating Pembeli</h6>
                    <span class="text-muted" style="font-size: 9px;"><?= $store['reviews_count'] ?? 0 ?> ulasan dari pelanggan</span>
                </div>
            </div>
            <div class="text-end flex-shrink-0">
                <div class="fw-bold text-dark" style="font-size: 12px;"><?= number_format($store['rating'] ?? 5.0, 1) ?> <span class="text-warning">★</span></div>
            </div>
        </div>

        <?php if (empty($reviews)): ?>
            <div class="text-center py-2.5 bg-light rounded-3 text-muted" style="font-size: 9.5px; border: 1px solid #F1F5F9;">
                <i class="bi bi-chat-square-heart text-muted fs-6 mb-0.5 d-block"></i>
                Belum ada ulasan untuk toko ini.
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-1.5">
                <?php foreach ($reviews as $rev): ?>
                    <div class="p-2 bg-light rounded-3 border" style="border-color: #E2E8F0 !important;">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div class="d-flex align-items-center gap-1.5">
                                <div class="rounded-circle bg-white text-dark d-flex align-items-center justify-content-center border flex-shrink-0" style="width: 20px; height: 20px; font-size: 9px; font-weight: 700; border-color: #CBD5E1 !important;">
                                    <?= strtoupper(substr($rev['customer_name'] ?? 'P', 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark" style="font-size: 10px;"><?= htmlspecialchars($rev['customer_name'] ?? 'Pembeli CicalengkaGO') ?></div>
                                    <div class="text-muted" style="font-size: 8.5px;"><?= date('d M Y', strtotime($rev['created_at'])) ?></div>
                                </div>
                            </div>
                            <div class="text-warning fw-bold flex-shrink-0" style="font-size: 8.5px;">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <i class="bi <?= $s <= (int)$rev['rating'] ? 'bi-star-fill' : 'bi-star' ?>" style="font-size: 9px;"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php if (!empty($rev['comment'])): ?>
                            <div class="text-dark mt-1 ps-0.5 fst-italic" style="font-size: 9.5px; line-height: 1.35;">
                                "<?= htmlspecialchars($rev['comment']) ?>"
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
