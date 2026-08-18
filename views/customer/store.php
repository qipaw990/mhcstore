<!-- GoFood Store Header Cover & Floating Back Button -->
<div class="position-relative">
    <img src="<?= asset_url($store['cover_photo'] ?? null, 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800&q=80') ?>" alt="<?= htmlspecialchars($store['name']) ?>" style="width: 100%; height: 130px; object-fit: cover;" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800&q=80';">
    <div class="position-absolute top-0 start-0" style="padding: 10px;">
        <a href="<?= $baseUrl ?>" class="btn btn-light rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(8px); border: 1px solid rgba(226, 232, 240, 0.8); padding: 0;">
            <i class="bi bi-arrow-left text-dark" style="font-size: 15px;"></i>
        </a>
    </div>
</div>

<!-- Store Main Information Floating Card -->
<div style="padding: 0 12px !important;">
    <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px !important; margin-top: -24px; position: relative; z-index: 2; padding: 14px 16px !important; margin-bottom: 14px !important; box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);">
        <div style="display: flex; align-items: flex-start; gap: 12px !important; margin-bottom: 10px !important;">
            <img src="<?= asset_url($store['logo'] ?? null, 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=300&q=80') ?>" alt="Logo" style="width: 52px; height: 52px; object-fit: cover; border-radius: 12px !important; flex-shrink: 0; background: #FFFFFF; border: 1px solid #E2E8F0;" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=300&q=80';">
            <div style="flex-grow: 1; min-width: 0;">
                <div class="d-flex align-items-center gap-1.5">
                    <h6 style="font-size: 14.5px; font-weight: 800; color: #0F172A; margin: 0 !important; line-height: 1.3; letter-spacing: -0.2px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?= htmlspecialchars($store['name']) ?></h6>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill flex-shrink-0" style="font-size: 8.5px; padding: 2px 6px;"><i class="bi bi-patch-check-fill me-0.5"></i> Resto Resmi</span>
                </div>
                <div style="font-size: 10.5px; color: #64748B; margin-top: 3px; margin-bottom: 6px !important; display: flex; align-items: center; gap: 4px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                    <i class="bi bi-geo-alt-fill text-danger flex-shrink-0" style="font-size: 11px;"></i>
                    <span style="text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?= htmlspecialchars($store['address']) ?></span>
                </div>
                
                <div style="display: flex; align-items: center; gap: 6px !important; font-size: 10px; color: #64748B; flex-wrap: wrap;">
                    <span style="color: #D97706; font-weight: 700; display: flex; align-items: center; gap: 3px; flex-shrink: 0;">
                        <i class="bi bi-star-fill" style="font-size: 10px;"></i> <?= number_format((float)($store['rating'] ?? 5.0), 1) ?> 
                        <span style="color: #94A3B8; font-weight: 400;">(<?= (int)($store['reviews_count'] ?? 0) ?> ulasan)</span>
                    </span>
                    <span style="color: #CBD5E1;">•</span>
                    <span style="display: flex; align-items: center; gap: 3px; flex-shrink: 0;"><i class="bi bi-truck me-0.5 text-primary"></i><?= htmlspecialchars($store['delivery_time'] ?? '15-25 mnt') ?></span>
                    <span style="color: #CBD5E1;">•</span>
                    <span class="badge bg-light text-dark border rounded-pill flex-shrink-0" style="font-size: 9px; padding: 2px 7px;" title="Jam Operasional Toko">
                        <i class="bi bi-clock-history text-danger me-0.5"></i> <?= htmlspecialchars($store['operating_hours'] ?? '08:00 - 22:00') ?>
                    </span>
                    <span style="color: #CBD5E1;">•</span>
                    <?php if (!empty($store['is_open'])): ?>
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
                <i class="bi bi-map-fill" style="font-size: 11px;"></i> Lokasi Resto
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

<?php
// Extract unique categories from product list
$categoriesInStore = [];
if (!empty($products)) {
    foreach ($products as $p) {
        $cName = trim($p['category_name'] ?? 'Menu Utama');
        if (!in_array($cName, $categoriesInStore)) {
            $categoriesInStore[] = $cName;
        }
    }
}
?>

<!-- Search Bar inside Store & Sticky Category Pills -->
<div class="px-3 sticky-top bg-white py-2 shadow-2xs" style="z-index: 10; top: 0;">
    <div class="position-relative mb-2">
        <input type="text" id="storeMenuSearchInput" class="form-control rounded-pill border ps-4 pe-4 py-1.5" style="font-size: 11.5px; background: #F8FAFC;" placeholder="Cari menu di <?= htmlspecialchars($store['name']) ?>...">
        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-2.5 text-muted" style="font-size: 12px;"></i>
    </div>

    <!-- Category Pills Filter -->
    <div class="d-flex align-items-center gap-1.5 overflow-x-auto pb-1" id="storeCatPillsContainer" style="scrollbar-width: none;">
        <button type="button" class="btn btn-sm btn-dark rounded-pill px-3 py-1 font-monospace fw-bold store-cat-btn active" data-cat="all" style="font-size: 10px; flex-shrink: 0;">
            Semua (<?= count($products) ?>)
        </button>
        <?php foreach ($categoriesInStore as $cName): ?>
            <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 py-1 text-dark store-cat-btn" data-cat="<?= htmlspecialchars($cName) ?>" style="font-size: 10px; flex-shrink: 0; font-weight: 600;">
                <?= htmlspecialchars($cName) ?>
            </button>
        <?php endforeach; ?>
    </div>
</div>

<!-- GoFood Product Catalog -->
<div style="padding: 10px 12px 24px 12px !important;">
    <?php if (empty($products)): ?>
        <div style="text-align: center; padding: 20px; background: #F8FAFC; border-radius: 12px; border: 1px solid #E2E8F0; color: #64748B; font-size: 11px;">Belum ada menu yang ditampilkan.</div>
    <?php else: ?>
        <div id="storeProductsList" style="display: flex; flex-direction: column; gap: 12px !important; margin-bottom: 16px !important;">
            <?php foreach ($products as $prod):
                $isOutOfStock = ((int)($prod['stock'] ?? 0) <= 0);
                $cName = trim($prod['category_name'] ?? 'Menu Utama');
            ?>
                <div class="store-product-item-card" data-category="<?= htmlspecialchars($cName) ?>" data-name="<?= strtolower(htmlspecialchars($prod['name'])) ?>" style="background: #FFFFFF; border: 1px solid <?= $isOutOfStock ? '#FEE2E2' : '#E2E8F0' ?>; border-radius: 14px !important; padding: 12px 14px !important; display: flex; align-items: center; justify-content: space-between; gap: 12px !important; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02); overflow: hidden; <?= $isOutOfStock ? 'opacity: 0.72;' : '' ?>">
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

                    <div style="flex-shrink: 0; text-align: center; width: 76px;">
                        <img src="<?= asset_url($prod['image'] ?? null, asset_url($store['cover_photo'] ?? null, 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&q=80')) ?>" alt="<?= htmlspecialchars($prod['name']) ?>" style="width: 76px; height: 76px; object-fit: cover; border-radius: 12px !important; margin-bottom: 4px !important; border: 1px solid #F1F5F9;" onerror="this.onerror=null; this.src='<?= asset_url($store['cover_photo'] ?? null, 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&q=80') ?>';">
                        <div>
                            <?php if (!$store['is_open']): ?>
                                <button type="button" class="btn btn-sm btn-secondary disabled w-100" style="font-size: 10px; padding: 3px 0 !important; border-radius: 20px !important;">Tutup</button>
                            <?php elseif ($isOutOfStock): ?>
                                <button type="button" class="btn btn-sm disabled w-100" style="font-size: 10px; padding: 3px 0 !important; border-radius: 20px !important; background:#FEE2E2; color:#DC2626; border: none; font-weight:700;">Habis</button>
                            <?php else: ?>
                                <button type="button" class="gofood-btn-add-block m-0" onclick="addToCart(<?= $prod['id'] ?>, 1)">
                                    <i class="bi bi-plus-lg" style="font-size: 10px;"></i> Tambah
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

<!-- Interactive JS Filtering for Store Menu & Categories -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('storeMenuSearchInput');
    const catBtns = document.querySelectorAll('.store-cat-btn');
    const cards = document.querySelectorAll('.store-product-item-card');

    let activeCat = 'all';

    function filterMenu() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';

        cards.forEach(card => {
            const cCat = card.getAttribute('data-category') || '';
            const cName = card.getAttribute('data-name') || '';

            const matchCat = (activeCat === 'all' || cCat === activeCat);
            const matchSearch = (!query || cName.includes(query));

            if (matchCat && matchSearch) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterMenu);
    }

    catBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            catBtns.forEach(b => {
                b.classList.remove('btn-dark', 'active');
                b.classList.add('btn-light', 'border');
            });
            btn.classList.remove('btn-light', 'border');
            btn.classList.add('btn-dark', 'active');

            activeCat = btn.getAttribute('data-cat');
            filterMenu();
        });
    });
});
</script>
