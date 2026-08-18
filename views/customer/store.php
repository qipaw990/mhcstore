<!-- GoFood Store Header Cover & Floating Back Button -->
<div class="position-relative">
    <img src="<?= $baseUrl ?>/<?= htmlspecialchars($store['cover_photo'] ?? 'assets/images/stores/geprek_cover.jpg') ?>" alt="<?= htmlspecialchars($store['name']) ?>" style="width: 100%; height: 120px; object-fit: cover;">
    <div class="position-absolute top-0 start-0" style="padding: 10px;">
        <a href="<?= $baseUrl ?>" class="btn btn-light rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: rgba(255, 255, 255, 0.92); backdrop-filter: blur(8px); border: 1px solid rgba(226, 232, 240, 0.8); padding: 0;">
            <i class="bi bi-arrow-left text-dark" style="font-size: 14px;"></i>
        </a>
    </div>
</div>

<!-- Store Main Information Floating Card -->
<div style="padding: 0 12px !important;">
    <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px !important; margin-top: -24px; position: relative; z-index: 2; padding: 14px 16px !important; margin-bottom: 16px !important; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);">
        <div style="display: flex; align-items: flex-start; gap: 12px !important; margin-bottom: 10px !important;">
            <img src="<?= $baseUrl ?>/<?= htmlspecialchars($store['logo'] ?? 'assets/images/stores/geprek_logo.png') ?>" alt="Logo" style="width: 48px; height: 48px; object-fit: cover; border-radius: 12px !important; flex-shrink: 0; background: #FFFFFF; border: 1px solid #E2E8F0;">
            <div style="flex-grow: 1; min-width: 0;">
                <h6 style="font-size: 14px; font-weight: 800; color: #0F172A; margin: 0 0 3px 0 !important; line-height: 1.3; letter-spacing: -0.2px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?= htmlspecialchars($store['name']) ?></h6>
                <div style="font-size: 10.5px; color: #64748B; margin-bottom: 6px !important; display: flex; align-items: center; gap: 4px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                    <i class="bi bi-geo-alt-fill text-danger flex-shrink-0" style="font-size: 11px;"></i>
                    <span style="text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?= htmlspecialchars($store['address']) ?></span>
                </div>
                
                <div style="display: flex; align-items: center; gap: 8px !important; font-size: 10px; color: #64748B; flex-wrap: wrap;">
                    <span style="color: #D97706; font-weight: 700; display: flex; align-items: center; gap: 3px; flex-shrink: 0;">
                        <i class="bi bi-star-fill" style="font-size: 10px;"></i> <?= number_format((float)($store['rating'] ?? 5.0), 1) ?> 
                        <span style="color: #94A3B8; font-weight: 400;">(<?= (int)($store['reviews_count'] ?? 0) ?> ulasan)</span>
                    </span>
                    <span style="color: #CBD5E1;">•</span>
                    <span style="display: flex; align-items: center; gap: 3px; flex-shrink: 0;"><i class="bi bi-clock me-0.5"></i><?= htmlspecialchars($store['delivery_time'] ?? '15-25 mnt') ?></span>
                    <span style="color: #CBD5E1;">•</span>
                    <?php if ($store['is_open']): ?>
                        <span class="badge bg-success-subtle text-success fw-bold rounded-pill flex-shrink-0" style="font-size: 9px; padding: 2px 7px;"><i class="bi bi-door-open-fill me-0.5"></i> Buka</span>
                    <?php else: ?>
                        <span class="badge bg-danger-subtle text-danger fw-bold rounded-pill flex-shrink-0" style="font-size: 9px; padding: 2px 7px;"><i class="bi bi-door-closed-fill me-0.5"></i> Tutup</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Store Map Collapse & Navigation Actions -->
        <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid #F1F5F9; padding-top: 10px !important; margin-top: 10px !important; gap: 10px !important;">
            <button class="btn btn-outline-danger btn-sm rounded-pill fw-bold" style="font-size: 11px !important; padding: 5px 12px !important; border: 1px solid #EE2737; color: #EE2737; flex: 1; display: flex; align-items: center; justify-content: center; gap: 4px;" type="button" data-bs-toggle="collapse" data-bs-target="#storeMapCollapse" aria-expanded="false">
                <i class="bi bi-map-fill" style="font-size: 11px;"></i> Peta Resto
            </button>
            <a href="https://www.google.com/maps/dir/?api=1&destination=<?= (float)($store['latitude'] ?? -6.9835) ?>,<?= (float)($store['longitude'] ?? 107.8335) ?>" target="_blank" class="btn btn-light btn-sm text-dark rounded-pill fw-semibold border" style="font-size: 11px !important; padding: 5px 12px !important; border-color: #CBD5E1; background: #F8FAFC; color: #1E293B; flex: 1; display: flex; align-items: center; justify-content: center; gap: 4px; text-decoration: none;">
                <i class="bi bi-compass text-danger" style="font-size: 11px;"></i> Petunjuk Arah
            </a>
        </div>

        <div class="collapse" id="storeMapCollapse" style="margin-top: 10px;">
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

                const storeSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 46" width="28" height="40">
                  <defs>
                    <linearGradient id="ssg" x1="0%" y1="0%" x2="100%" y2="100%">
                      <stop offset="0%" stop-color="#f87171"/>
                      <stop offset="100%" stop-color="#b91c1c"/>
                    </linearGradient>
                  </defs>
                  <path d="M16 2C9 2 3 8 3 15c0 10 13 29 13 29S29 25 29 15C29 8 23 2 16 2z" fill="url(#ssg)" stroke="white" stroke-width="2"/>
                  <path d="M9 14 L9 12 Q9 10 16 10 Q23 10 23 12 L23 14 Q19.5 17 16 16 Q12.5 17 9 14z" fill="white"/>
                  <rect x="11" y="14.5" width="10" height="6" rx="0.5" fill="white" opacity="0.25"/>
                  <rect x="13" y="15" width="6" height="5.5" fill="white"/>
                  <rect x="14.5" y="16" width="3" height="4.5" fill="#b91c1c"/>
                </svg>`;
                const storeIcon = L.icon({
                    iconUrl: 'data:image/svg+xml,' + encodeURIComponent(storeSvg),
                    iconSize: [28, 40],
                    iconAnchor: [14, 40],
                    popupAnchor: [0, -40]
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
<div style="padding: 0 12px 16px 12px !important;">
    <div style="margin-top: 14px !important; margin-bottom: 12px !important; padding: 0 2px;">
        <h6 style="font-size: 12.5px; font-weight: 800; color: #0F172A; margin: 0; display: flex; align-items: center; gap: 6px;">
            <i class="bi bi-egg-fried text-danger" style="font-size: 14px;"></i> Daftar Menu Makanan & Minuman
        </h6>
    </div>

    <?php if (empty($products)): ?>
        <div style="text-align: center; padding: 14px; background: #F8FAFC; border-radius: 12px; border: 1px solid #E2E8F0; color: #64748B; font-size: 11px;">Belum ada menu yang ditampilkan.</div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 12px !important; margin-bottom: 16px !important;">
            <?php foreach ($products as $prod):
                $isOutOfStock = ((int)($prod['stock'] ?? 0) <= 0);
            ?>
                <div style="background: #FFFFFF; border: 1px solid <?= $isOutOfStock ? '#FEE2E2' : '#E2E8F0' ?>; border-radius: 14px !important; padding: 12px 14px !important; display: flex; align-items: center; justify-content: space-between; gap: 12px !important; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02); overflow: hidden; <?= $isOutOfStock ? 'opacity: 0.72;' : '' ?>">
                    <div style="flex-grow: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px !important;">
                            <div style="font-size: 12.5px; font-weight: 700; color: #0F172A; line-height: 1.3; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;" title="<?= htmlspecialchars($prod['name']) ?>"><?= htmlspecialchars($prod['name']) ?></div>
                            <?php if ($isOutOfStock): ?>
                                <span style="font-size: 8.5px; padding: 2px 6px; border-radius: 10px; background: #FEE2E2; color: #DC2626; font-weight: 700; white-space: nowrap; flex-shrink: 0;">Stok Habis</span>
                            <?php endif; ?>
                        </div>
                        <div style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-size: 10px; color: #64748B; line-height: 1.4; margin-bottom: 8px !important;">
                            <?= htmlspecialchars($prod['description']) ?>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px !important; flex-wrap: wrap;">
                            <span style="font-size: 13px; font-weight: 800; color: #0F172A;"><?= format_rupiah($prod['final_price']) ?></span>
                            <?php if (!empty($prod['discount']) && (float)$prod['discount'] > 0 && (float)$prod['price'] > (float)$prod['final_price']): ?>
                                <span style="font-size: 10px; color: #94A3B8; text-decoration: line-through; margin-right: 4px;"><?= format_rupiah($prod['price']) ?></span>
                                <span style="font-size: 9px; padding: 2px 6px; border-radius: 4px; background: #FEE2E2; color: #DC2626; font-weight: 700;">-<?= $prod['discount_type'] === 'percent' ? (int)$prod['discount'] . '%' : format_rupiah($prod['discount']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="flex-shrink: 0; text-align: center;">
                        <img src="<?= $baseUrl ?>/<?= htmlspecialchars($prod['image'] ?? 'assets/images/products/default.jpg') ?>" alt="<?= htmlspecialchars($prod['name']) ?>" style="width: 70px; height: 70px; object-fit: cover; border-radius: 12px !important; margin-bottom: 6px !important; border: 1px solid #F1F5F9;">
                        <div>
                            <?php if (!$store['is_open']): ?>
                                <button type="button" class="btn btn-sm btn-secondary disabled" style="font-size: 10px; padding: 3px 10px !important; border-radius: 20px !important;">Tutup</button>
                            <?php elseif ($isOutOfStock): ?>
                                <button type="button" class="btn btn-sm disabled" style="font-size: 10px; padding: 3px 10px !important; border-radius: 20px !important; background:#FEE2E2; color:#DC2626; border: none; font-weight:700;">Habis</button>
                            <?php else: ?>
                                <button type="button" onclick="addToCart(<?= $prod['id'] ?>, 1)" style="background: linear-gradient(135deg, #EE2737, #C61524); color: #FFFFFF; font-size: 11px !important; font-weight: 700; padding: 5px 14px !important; border-radius: 20px !important; border: none; box-shadow: 0 2px 6px rgba(238, 39, 55, 0.25); display: inline-flex; align-items: center; gap: 4px; cursor: pointer;">
                                    <i class="bi bi-plus-lg" style="font-size: 11px;"></i> Tambah
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Store Reviews & Customer Testimonials Section -->
    <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 14px !important; padding: 14px !important; margin-bottom: 16px !important; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px !important;">
            <div style="display: flex; align-items: center; gap: 8px !important;">
                <div style="width: 28px; height: 28px; border-radius: 50%; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 12px;">
                    <i class="bi bi-star-fill"></i>
                </div>
                <div>
                    <h6 style="font-size: 12px; font-weight: 700; color: #0F172A; margin: 0;">Ulasan & Rating Pembeli</h6>
                    <span style="font-size: 9.5px; color: #64748B;"><?= $store['reviews_count'] ?? 0 ?> ulasan dari pelanggan</span>
                </div>
            </div>
            <div style="flex-shrink: 0; text-align: right;">
                <div style="font-size: 12.5px; font-weight: 800; color: #0F172A;"><?= number_format($store['rating'] ?? 5.0, 1) ?> <span style="color: #D97706;">★</span></div>
            </div>
        </div>

        <?php if (empty($reviews)): ?>
            <div style="text-align: center; padding: 12px; background: #F8FAFC; border-radius: 10px; border: 1px solid #F1F5F9; color: #64748B; font-size: 10px;">
                <i class="bi bi-chat-square-heart text-muted d-block mb-1" style="font-size: 18px;"></i>
                Belum ada ulasan untuk toko ini.
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 8px !important;">
                <?php foreach ($reviews as $rev): ?>
                    <div style="padding: 10px; background: #F8FAFC; border-radius: 10px; border: 1px solid #E2E8F0;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 4px !important;">
                            <div style="display: flex; align-items: center; gap: 6px !important;">
                                <div style="width: 22px; height: 22px; border-radius: 50%; background: #FFFFFF; color: #0F172A; display: flex; align-items: center; justify-content: center; border: 1px solid #CBD5E1; flex-shrink: 0; font-size: 9px; font-weight: 700;">
                                    <?= strtoupper(substr($rev['customer_name'] ?? 'P', 0, 1)) ?>
                                </div>
                                <div>
                                    <div style="font-size: 10.5px; font-weight: 700; color: #0F172A;"><?= htmlspecialchars($rev['customer_name'] ?? 'Pembeli CicalengkaGO') ?></div>
                                    <div style="font-size: 9px; color: #94A3B8;"><?= date('d M Y', strtotime($rev['created_at'])) ?></div>
                                </div>
                            </div>
                            <div style="color: #D97706; font-weight: 700; flex-shrink: 0; font-size: 9px;">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <i class="bi <?= $s <= (int)$rev['rating'] ? 'bi-star-fill' : 'bi-star' ?>" style="font-size: 9.5px;"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php if (!empty($rev['comment'])): ?>
                            <div style="font-size: 10px; color: #334155; margin-top: 4px; font-style: italic; line-height: 1.4;">
                                "<?= htmlspecialchars($rev['comment']) ?>"
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
