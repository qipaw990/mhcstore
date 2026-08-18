<!-- Gojek Search Header Bar -->
<div class="bg-white border-bottom sticky-top shadow-2xs" style="z-index: 1020; border-bottom-color: #E2E8F0 !important; padding: 8px 12px !important;">
    <div class="d-flex align-items-center gap-2 w-100">
        <a href="<?= $baseUrl ?>" class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 shadow-2xs" style="width: 34px; height: 34px; border: 1px solid #E2E8F0; background: #F8FAFC; padding: 0;">
            <i class="bi bi-arrow-left text-dark" style="font-size: 14px;"></i>
        </a>
        <form action="<?= $baseUrl ?>/search" method="GET" class="flex-grow-1 m-0 position-relative min-w-0">
            <div class="m-0 d-flex align-items-center gap-2 px-3 rounded-pill" style="background: #F1F5F9; border: 1px solid #CBD5E1; height: 36px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);">
                <i class="bi bi-search flex-shrink-0" style="font-size: 13px; color: #64748B;"></i>
                <input type="text" name="q" id="search-input" value="<?= htmlspecialchars($query ?? '') ?>" placeholder="Cari sate, geprek, martabak, sembako..." autocomplete="off" style="border: none; background: transparent; outline: none; font-size: 12px; font-weight: 500; color: #0F172A; width: 100%; height: 100%; padding: 0; line-height: normal;">
                <?php if (!empty($query)): ?>
                    <a href="<?= $baseUrl ?>/search<?= !empty($_GET['module_id']) ? '?module_id=' . (int)$_GET['module_id'] : '' ?>" class="text-muted text-decoration-none ms-1 flex-shrink-0 d-flex align-items-center" title="Hapus"><i class="bi bi-x-circle-fill text-secondary" style="font-size: 14px;"></i></a>
                <?php endif; ?>
            </div>
            <?php if (!empty($_GET['module_id'])): ?>
                <input type="hidden" name="module_id" value="<?= (int)$_GET['module_id'] ?>">
            <?php endif; ?>
        </form>
    </div>

    <!-- Category Chips Pill Scroll -->
    <div class="d-flex gap-1 overflow-x-auto w-100" style="scrollbar-width: none; margin-top: 6px !important; padding-bottom: 2px !important;">
        <a href="<?= $baseUrl ?>/search<?= !empty($query) ? '?q=' . urlencode($query) : '' ?>" class="badge rounded-pill text-decoration-none d-flex align-items-center gap-1 flex-shrink-0 <?= empty($_GET['module_id']) ? 'bg-danger text-white shadow-2xs' : 'bg-white text-secondary border' ?>" style="font-size: 9.5px !important; font-weight: 600; padding: 3px 9px !important; border-radius: 20px !important;">
            <i class="bi bi-grid-fill" style="font-size: 9px;"></i> Semua
        </a>
        <a href="<?= $baseUrl ?>/search?module_id=1<?= !empty($query) ? '&q=' . urlencode($query) : '' ?>" class="badge rounded-pill text-decoration-none d-flex align-items-center gap-1 flex-shrink-0 <?= (($_GET['module_id'] ?? '') == '1') ? 'bg-danger text-white shadow-2xs' : 'bg-white text-secondary border' ?>" style="font-size: 9.5px !important; font-weight: 600; padding: 3px 9px !important; border-radius: 20px !important;">
            <i class="bi bi-egg-fried" style="font-size: 9px;"></i> GoFood
        </a>
        <a href="<?= $baseUrl ?>/search?module_id=2<?= !empty($query) ? '&q=' . urlencode($query) : '' ?>" class="badge rounded-pill text-decoration-none d-flex align-items-center gap-1 flex-shrink-0 <?= (($_GET['module_id'] ?? '') == '2') ? 'text-white shadow-2xs' : 'bg-white text-secondary border' ?>" style="<?= (($_GET['module_id'] ?? '') == '2') ? 'background:#F06400;' : '' ?> font-size: 9.5px !important; font-weight: 600; padding: 3px 9px !important; border-radius: 20px !important;">
            <i class="bi bi-cart3" style="font-size: 9px;"></i> GoMart
        </a>
        <a href="<?= $baseUrl ?>/search?module_id=3<?= !empty($query) ? '&q=' . urlencode($query) : '' ?>" class="badge rounded-pill text-decoration-none d-flex align-items-center gap-1 flex-shrink-0 <?= (($_GET['module_id'] ?? '') == '3') ? 'text-white shadow-2xs' : 'bg-white text-secondary border' ?>" style="<?= (($_GET['module_id'] ?? '') == '3') ? 'background:#0081A0;' : '' ?> font-size: 9.5px !important; font-weight: 600; padding: 3px 9px !important; border-radius: 20px !important;">
            <i class="bi bi-capsule" style="font-size: 9px;"></i> GoMed
        </a>
        <a href="<?= $baseUrl ?>/search?module_id=4<?= !empty($query) ? '&q=' . urlencode($query) : '' ?>" class="badge rounded-pill text-decoration-none d-flex align-items-center gap-1 flex-shrink-0 <?= (($_GET['module_id'] ?? '') == '4') ? 'text-white shadow-2xs' : 'bg-white text-secondary border' ?>" style="<?= (($_GET['module_id'] ?? '') == '4') ? 'background:#8B5CF6;' : '' ?> font-size: 9.5px !important; font-weight: 600; padding: 3px 9px !important; border-radius: 20px !important;">
            <i class="bi bi-bag-heart" style="font-size: 9px;"></i> GoShop
        </a>
    </div>
</div>

<div style="padding: 10px !important;">
    <?php if (empty($query)): ?>
        <!-- Trending & Popular Searches Card -->
        <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 14px !important; padding: 12px !important; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03); margin-bottom: 12px !important;">
            <h6 style="font-size: 11.5px; font-weight: 700; color: #1E293B; margin-top: 0 !important; margin-bottom: 10px !important; display: flex; align-items: center; gap: 5px;">
                <i class="bi bi-fire text-danger" style="font-size: 13px;"></i> Pencarian Populer
            </h6>
            <div style="display: flex; flex-wrap: wrap; gap: 6px 6px !important;">
                <button type="button" onclick="quickSearch('Ayam Geprek')" class="btn btn-sm p-0" style="font-size: 10.5px !important; padding: 4.5px 10px !important; border: 1px solid #E2E8F0 !important; border-radius: 20px !important; background: #F8FAFC !important; color: #334155 !important; font-weight: 600 !important; cursor: pointer;">🍗 Ayam Geprek</button>
                <button type="button" onclick="quickSearch('Sate Maranggi')" class="btn btn-sm p-0" style="font-size: 10.5px !important; padding: 4.5px 10px !important; border: 1px solid #E2E8F0 !important; border-radius: 20px !important; background: #F8FAFC !important; color: #334155 !important; font-weight: 600 !important; cursor: pointer;">🍢 Sate Maranggi</button>
                <button type="button" onclick="quickSearch('Seblak Pedas')" class="btn btn-sm p-0" style="font-size: 10.5px !important; padding: 4.5px 10px !important; border: 1px solid #E2E8F0 !important; border-radius: 20px !important; background: #F8FAFC !important; color: #334155 !important; font-weight: 600 !important; cursor: pointer;">🌶️ Seblak</button>
                <button type="button" onclick="quickSearch('Martabak')" class="btn btn-sm p-0" style="font-size: 10.5px !important; padding: 4.5px 10px !important; border: 1px solid #E2E8F0 !important; border-radius: 20px !important; background: #F8FAFC !important; color: #334155 !important; font-weight: 600 !important; cursor: pointer;">🥞 Martabak</button>
                <button type="button" onclick="quickSearch('Beras')" class="btn btn-sm p-0" style="font-size: 10.5px !important; padding: 4.5px 10px !important; border: 1px solid #E2E8F0 !important; border-radius: 20px !important; background: #F8FAFC !important; color: #334155 !important; font-weight: 600 !important; cursor: pointer;">🍚 Sembako</button>
                <button type="button" onclick="quickSearch('Kopi')" class="btn btn-sm p-0" style="font-size: 10.5px !important; padding: 4.5px 10px !important; border: 1px solid #E2E8F0 !important; border-radius: 20px !important; background: #F8FAFC !important; color: #334155 !important; font-weight: 600 !important; cursor: pointer;">☕ Kopi Susu</button>
            </div>
        </div>

        <!-- Quick Promo Banner -->
        <div style="background: linear-gradient(135deg, #EE2737 0%, #B91C1C 100%); border-radius: 14px !important; padding: 12px 14px !important; color: #FFFFFF; position: relative; overflow: hidden; margin-bottom: 12px !important; box-shadow: 0 3px 10px rgba(238, 39, 55, 0.18);">
            <div class="d-flex align-items-center justify-content-between position-relative" style="z-index: 2;">
                <div>
                    <span style="font-size: 8px; letter-spacing: 0.3px; padding: 2px 8px !important; margin-bottom: 6px !important; display: inline-block; background: #FFFFFF; color: #EE2737; font-weight: 800; border-radius: 20px;">PROMO SPESIAL</span>
                    <div style="font-size: 12.5px; font-weight: 700; line-height: 1.25; color: #FFFFFF;">Gratis Ongkir s.d 10rb</div>
                    <div style="font-size: 9.5px; color: rgba(255, 255, 255, 0.85); margin-top: 3px;">Berlaku di seluruh merchant Cicalengka</div>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px; background: rgba(255, 255, 255, 0.18);">
                    <i class="bi bi-ticket-perforated-fill text-white" style="font-size: 16px;"></i>
                </div>
            </div>
        </div>

        <!-- Popular Stores Discovery Section -->
        <?php if (!empty($popular_stores)): ?>
            <div style="margin-bottom: 12px !important;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px !important;">
                    <h6 style="font-size: 11.5px; font-weight: 700; color: #1E293B; margin: 0; display: flex; align-items: center; gap: 5px;">
                        <i class="bi bi-shop text-danger" style="font-size: 13px;"></i> Resto Populer di Cicalengka
                    </h6>
                    <a href="<?= $baseUrl ?>/search?module_id=1" class="text-danger text-decoration-none fw-bold" style="font-size: 10px;">Lihat Semua</a>
                </div>
                <div class="gofood-stores-scroll mb-0 p-0">
                    <?php foreach ($popular_stores as $s): ?>
                        <a href="<?= $baseUrl ?>/stores/<?= $s['id'] ?>" class="gofood-store-card shadow-2xs" style="border-radius: 14px; border-color: #E2E8F0 !important;">
                            <div class="gofood-store-img-box" style="height: 76px;">
                                <img src="<?= asset_url($s['cover_photo'] ?? null, 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800&q=80') ?>" class="gofood-store-img" alt="Store" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800&q=80';">
                                <span class="gofood-open-tag" style="border-radius: 5px; font-size: 8px; padding: 1.5px 5px;"><i class="bi bi-door-open-fill me-0.5"></i> Buka</span>
                            </div>
                            <div class="gofood-store-body p-2">
                                <div class="gofood-store-name" style="font-size: 11px; letter-spacing: -0.2px;"><?= htmlspecialchars($s['name']) ?></div>
                                <div class="gofood-store-meta" style="font-size: 9px;">
                                    <span class="gofood-rating"><i class="bi bi-star-fill"></i> <?= number_format((float)($s['rating'] ?? 5.0), 1) ?></span>
                                    <span>•</span>
                                    <span><?= htmlspecialchars($s['delivery_time'] ?? '20-30 mnt') ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recommended Products Grid -->
        <?php if (!empty($recommend_products)): ?>
            <div style="margin-bottom: 16px !important;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px !important;">
                    <h6 style="font-size: 11.5px; font-weight: 700; color: #1E293B; margin: 0; display: flex; align-items: center; gap: 5px;">
                        <i class="bi bi-stars text-warning" style="font-size: 13px;"></i> Rekomendasi Menu Favorit
                    </h6>
                </div>
                <div class="gofood-products-grid p-0">
                    <?php foreach ($recommend_products as $prod): ?>
                        <div class="gofood-product-card" style="border-radius: 12px; cursor: pointer;" onclick="window.location.href='<?= $baseUrl ?>/stores/<?= $prod['store_id'] ?>'">
                            <div class="position-relative">
                                <img src="<?= asset_url($prod['image'] ?? null, 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&q=80') ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="gofood-prod-img" style="height: 84px;" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&q=80';">
                                <?php if (!empty($prod['discount']) && (float)$prod['discount'] > 0): ?>
                                    <span class="gofood-discount-tag" style="font-size: 8px; padding: 2px 5px;">Diskon <?= $prod['discount_type'] === 'percent' ? (int)$prod['discount'] . '%' : format_rupiah($prod['discount']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="gofood-prod-body p-2">
                                <div class="gofood-prod-store" style="font-size: 8.5px;"><?= htmlspecialchars($prod['store_name'] ?? 'Mitra GoFood') ?></div>
                                <div class="gofood-prod-name" style="font-size: 10.5px; line-height: 1.2;"><?= htmlspecialchars($prod['name']) ?></div>
                                <div class="gofood-price-row mt-1 d-flex flex-column gap-0.5">
                                    <div>
                                        <div class="gofood-price" style="font-size: 11px;"><?= format_rupiah($prod['final_price']) ?></div>
                                        <?php if (!empty($prod['discount']) && (float)$prod['discount'] > 0 && (float)$prod['price'] > (float)$prod['final_price']): ?>
                                            <span class="text-muted text-decoration-line-through" style="font-size: 8.5px;"><?= format_rupiah($prod['price']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="gofood-btn-add-block" onclick="event.stopPropagation(); addToCart(<?= $prod['id'] ?>, 1)" title="Tambah ke Keranjang">
                                        <i class="bi bi-plus-lg" style="font-size: 10px;"></i> Tambah
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Stores Search Results -->
        <?php if (!empty($stores)): ?>
            <div style="margin-bottom: 12px !important;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px !important;">
                    <h6 style="font-size: 11.5px; font-weight: 700; color: #1E293B; margin: 0;"><i class="bi bi-shop text-danger me-1"></i> Resto & Toko (<?= count($stores) ?>)</h6>
                </div>
                <div class="gofood-stores-scroll mb-0 p-0">
                    <?php foreach ($stores as $s): ?>
                        <a href="<?= $baseUrl ?>/stores/<?= $s['id'] ?>" class="gofood-store-card">
                            <div class="gofood-store-img-box" style="height: 76px;">
                                <img src="<?= asset_url($s['cover_photo'] ?? null, 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800&q=80') ?>" class="gofood-store-img" alt="Store" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800&q=80';">
                                <?php if (!empty($s['is_open'])): ?>
                                    <span class="gofood-open-tag" style="border-radius: 5px; font-size: 8px; padding: 1.5px 5px;"><i class="bi bi-door-open-fill me-0.5"></i> Buka</span>
                                <?php else: ?>
                                    <span class="gofood-closed-tag" style="border-radius: 5px; font-size: 8px; padding: 1.5px 5px;"><i class="bi bi-door-closed-fill me-0.5"></i> Tutup</span>
                                <?php endif; ?>
                            </div>
                            <div class="gofood-store-body p-2">
                                <div class="gofood-store-name" style="font-size: 11px;"><?= htmlspecialchars($s['name']) ?></div>
                                <div class="gofood-store-meta" style="font-size: 9px;">
                                    <span class="gofood-rating"><i class="bi bi-star-fill"></i> <?= number_format(($s['rating'] > 0 ? $s['rating'] : 5.0), 1) ?></span>
                                    <span>•</span>
                                    <span><i class="bi bi-clock me-0.5"></i><?= htmlspecialchars($s['operating_hours'] ?? '08:00 - 22:00') ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Products Search Results -->
        <div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px !important;">
                <h6 style="font-size: 11.5px; font-weight: 700; color: #1E293B; margin: 0;"><i class="bi bi-egg-fried text-primary me-1"></i> Menu & Produk (<?= count($products) ?>)</h6>
            </div>

            <?php if (empty($products)): ?>
                <div class="text-center py-3 bg-light rounded-3 border p-2.5">
                    <i class="bi bi-search text-muted display-6 mb-1 d-block" style="font-size: 24px;"></i>
                    <h6 class="fw-bold text-dark" style="font-size: 11px;">Menu Tidak Ditemukan</h6>
                    <p class="text-muted mb-0" style="font-size: 10px;">Tidak ada produk untuk kata kunci "<strong><?= htmlspecialchars($query) ?></strong>".</p>
                </div>
            <?php else: ?>
                <div class="gofood-products-grid p-0">
                    <?php foreach ($products as $prod): ?>
                        <div class="gofood-product-card" style="border-radius: 12px; cursor: pointer;" onclick="window.location.href='<?= $baseUrl ?>/stores/<?= $prod['store_id'] ?>'">
                            <div class="position-relative">
                                <img src="<?= asset_url($prod['image'] ?? null, 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&q=80') ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="gofood-prod-img" style="height: 84px;" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&q=80';">
                                <?php if (!empty($prod['discount']) && (float)$prod['discount'] > 0): ?>
                                    <span class="gofood-discount-tag" style="font-size: 8px; padding: 2px 5px;">Diskon <?= $prod['discount_type'] === 'percent' ? (int)$prod['discount'] . '%' : format_rupiah($prod['discount']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="gofood-prod-body p-2">
                                <div class="gofood-prod-store" style="font-size: 8.5px;"><?= htmlspecialchars($prod['store_name'] ?? 'Mitra GoFood') ?></div>
                                <div class="gofood-prod-name" style="font-size: 10.5px; line-height: 1.2;"><?= htmlspecialchars($prod['name']) ?></div>
                                <div class="gofood-price-row mt-1 d-flex flex-column gap-0.5">
                                    <div>
                                        <div class="gofood-price" style="font-size: 11px;"><?= format_rupiah($prod['final_price']) ?></div>
                                        <?php if (!empty($prod['discount']) && (float)$prod['discount'] > 0 && (float)$prod['price'] > (float)$prod['final_price']): ?>
                                            <span class="text-muted text-decoration-line-through" style="font-size: 8.5px;"><?= format_rupiah($prod['price']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="gofood-btn-add-block" onclick="event.stopPropagation(); addToCart(<?= $prod['id'] ?>, 1)" title="Tambah ke Keranjang">
                                        <i class="bi bi-plus-lg" style="font-size: 10px;"></i> Tambah
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function quickSearch(keyword) {
    const input = document.getElementById('search-input');
    if (input) {
        input.value = keyword;
        input.form.submit();
    }
}
</script>
