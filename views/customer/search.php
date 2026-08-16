<!-- Gojek Search Header Bar -->
<div class="bg-white border-bottom sticky-top shadow-xs px-3 py-2.5" style="z-index: 1020;">
    <div class="d-flex align-items-center gap-2.5 w-100">
        <a href="<?= $baseUrl ?>" class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px; border: 1px solid #E2E8F0; background: #F8FAFC;">
            <i class="bi bi-arrow-left text-dark" style="font-size: 15px;"></i>
        </a>
        <form action="<?= $baseUrl ?>/search" method="GET" class="flex-grow-1 m-0 position-relative min-w-0">
            <div class="m-0 d-flex align-items-center gap-2 px-3 py-1.5 rounded-pill overflow-hidden" style="background: #F1F5F9; border: 1px solid #E2E8F0; height: 40px;">
                <i class="bi bi-search text-danger flex-shrink-0" style="font-size: 14px;"></i>
                <input type="text" name="q" id="search-input" value="<?= htmlspecialchars($query ?? '') ?>" placeholder="Cari sate, geprek, martabak, sembako..." autocomplete="off" style="border: none; background: transparent; outline: none; font-size: 12.5px; width: 100%; font-weight: 500; color: #1E293B;">
                <?php if (!empty($query)): ?>
                    <a href="<?= $baseUrl ?>/search<?= !empty($_GET['module_id']) ? '?module_id=' . (int)$_GET['module_id'] : '' ?>" class="text-muted text-decoration-none ms-1 flex-shrink-0" title="Hapus"><i class="bi bi-x-circle-fill text-secondary" style="font-size: 15px;"></i></a>
                <?php endif; ?>
            </div>
            <?php if (!empty($_GET['module_id'])): ?>
                <input type="hidden" name="module_id" value="<?= (int)$_GET['module_id'] ?>">
            <?php endif; ?>
        </form>
    </div>

    <!-- Category Chips Pill Scroll -->
    <div class="d-flex gap-2 overflow-x-auto mt-2.5 pb-1 w-100" style="scrollbar-width: none;">
        <a href="<?= $baseUrl ?>/search<?= !empty($query) ? '?q=' . urlencode($query) : '' ?>" class="badge rounded-pill px-3 py-2 text-decoration-none d-flex align-items-center gap-1.5 flex-shrink-0 <?= empty($_GET['module_id']) ? 'bg-danger text-white shadow-2xs' : 'bg-white text-secondary border' ?>" style="font-size: 11px; font-weight: 600;">
            <i class="bi bi-grid-fill"></i> Semua
        </a>
        <a href="<?= $baseUrl ?>/search?module_id=1<?= !empty($query) ? '&q=' . urlencode($query) : '' ?>" class="badge rounded-pill px-3 py-2 text-decoration-none d-flex align-items-center gap-1.5 flex-shrink-0 <?= (($_GET['module_id'] ?? '') == '1') ? 'bg-danger text-white shadow-2xs' : 'bg-white text-secondary border' ?>" style="font-size: 11px; font-weight: 600;">
            <i class="bi bi-egg-fried"></i> GoFood
        </a>
        <a href="<?= $baseUrl ?>/search?module_id=2<?= !empty($query) ? '&q=' . urlencode($query) : '' ?>" class="badge rounded-pill px-3 py-2 text-decoration-none d-flex align-items-center gap-1.5 flex-shrink-0 <?= (($_GET['module_id'] ?? '') == '2') ? 'text-white shadow-2xs' : 'bg-white text-secondary border' ?>" style="<?= (($_GET['module_id'] ?? '') == '2') ? 'background:#F06400;' : '' ?> font-size: 11px; font-weight: 600;">
            <i class="bi bi-cart3"></i> GoMart
        </a>
        <a href="<?= $baseUrl ?>/search?module_id=3<?= !empty($query) ? '&q=' . urlencode($query) : '' ?>" class="badge rounded-pill px-3 py-2 text-decoration-none d-flex align-items-center gap-1.5 flex-shrink-0 <?= (($_GET['module_id'] ?? '') == '3') ? 'text-white shadow-2xs' : 'bg-white text-secondary border' ?>" style="<?= (($_GET['module_id'] ?? '') == '3') ? 'background:#0081A0;' : '' ?> font-size: 11px; font-weight: 600;">
            <i class="bi bi-capsule"></i> GoMed
        </a>
        <a href="<?= $baseUrl ?>/search?module_id=4<?= !empty($query) ? '&q=' . urlencode($query) : '' ?>" class="badge rounded-pill px-3 py-2 text-decoration-none d-flex align-items-center gap-1.5 flex-shrink-0 <?= (($_GET['module_id'] ?? '') == '4') ? 'text-white shadow-2xs' : 'bg-white text-secondary border' ?>" style="<?= (($_GET['module_id'] ?? '') == '4') ? 'background:#8B5CF6;' : '' ?> font-size: 11px; font-weight: 600;">
            <i class="bi bi-bag-heart"></i> GoShop
        </a>
    </div>
</div>

<div class="px-3 py-2.5">
    <?php if (empty($query)): ?>
        <!-- Trending & Popular Searches -->
        <div class="mb-3">
            <h6 class="fw-bold mb-2 text-dark d-flex align-items-center gap-1" style="font-size: 12px;">
                <i class="bi bi-fire text-danger"></i> Pencarian Populer
            </h6>
            <div class="d-flex flex-wrap gap-1.5">
                <button type="button" onclick="quickSearch('Ayam Geprek')" class="btn btn-sm bg-white rounded-pill px-2.5 py-1 border text-dark fw-medium" style="font-size: 10.5px; border-color: #E2E8F0 !important;">🍗 Ayam Geprek</button>
                <button type="button" onclick="quickSearch('Sate Maranggi')" class="btn btn-sm bg-white rounded-pill px-2.5 py-1 border text-dark fw-medium" style="font-size: 10.5px; border-color: #E2E8F0 !important;">🍢 Sate Maranggi</button>
                <button type="button" onclick="quickSearch('Seblak Pedas')" class="btn btn-sm bg-white rounded-pill px-2.5 py-1 border text-dark fw-medium" style="font-size: 10.5px; border-color: #E2E8F0 !important;">🌶️ Seblak</button>
                <button type="button" onclick="quickSearch('Martabak')" class="btn btn-sm bg-white rounded-pill px-2.5 py-1 border text-dark fw-medium" style="font-size: 10.5px; border-color: #E2E8F0 !important;">🥞 Martabak</button>
                <button type="button" onclick="quickSearch('Beras')" class="btn btn-sm bg-white rounded-pill px-2.5 py-1 border text-dark fw-medium" style="font-size: 10.5px; border-color: #E2E8F0 !important;">🍚 Sembako</button>
                <button type="button" onclick="quickSearch('Kopi')" class="btn btn-sm bg-white rounded-pill px-2.5 py-1 border text-dark fw-medium" style="font-size: 10.5px; border-color: #E2E8F0 !important;">☕ Kopi Susu</button>
            </div>
        </div>

        <!-- Quick Promo Banner -->
        <div class="shadow-2xs text-white mb-3 p-3 position-relative overflow-hidden" style="background: linear-gradient(135deg, #EE2737 0%, #B91C1C 100%); border-radius: 16px;">
            <div class="d-flex align-items-center justify-content-between position-relative" style="z-index: 2;">
                <div>
                    <span class="badge bg-white text-danger fw-bold px-2 py-0.5 rounded-pill mb-1.5 d-inline-block" style="font-size: 8.5px; letter-spacing: 0.3px;">PROMO SPESIAL</span>
                    <div class="fw-bold text-white" style="font-size: 13px; line-height: 1.3;">Gratis Ongkir s.d 10rb</div>
                    <div class="text-white-50 mt-1" style="font-size: 10px;">Berlaku di seluruh merchant Cicalengka</div>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 shadow-2xs" style="width: 44px; height: 44px; background: rgba(255, 255, 255, 0.15);">
                    <i class="bi bi-ticket-perforated-fill text-white fs-4"></i>
                </div>
            </div>
        </div>

        <!-- Popular Stores Discovery Section -->
        <?php if (!empty($popular_stores)): ?>
            <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="fw-bold m-0 text-dark d-flex align-items-center gap-1" style="font-size: 12px;">
                        <i class="bi bi-shop text-danger"></i> Resto Populer di Cicalengka
                    </h6>
                    <a href="<?= $baseUrl ?>/search?module_id=1" class="text-danger text-decoration-none fw-bold" style="font-size: 10.5px;">Lihat Semua</a>
                </div>
                <div class="gofood-stores-scroll mb-0 p-0">
                    <?php foreach ($popular_stores as $s): ?>
                        <a href="<?= $baseUrl ?>/stores/<?= $s['id'] ?>" class="gofood-store-card shadow-2xs" style="border-radius: 16px; border-color: #E2E8F0 !important;">
                            <div class="gofood-store-img-box">
                                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($s['cover_photo'] ?? 'assets/images/stores/geprek_cover.jpg') ?>" class="gofood-store-img" alt="Store">
                                <span class="gofood-open-tag" style="border-radius: 6px;"><i class="bi bi-door-open-fill me-0.5"></i> Buka</span>
                            </div>
                            <div class="gofood-store-body p-2.5">
                                <div class="gofood-store-name" style="font-size: 12px; letter-spacing: -0.2px;"><?= htmlspecialchars($s['name']) ?></div>
                                <div class="gofood-store-meta" style="font-size: 10px;">
                                    <span class="gofood-rating"><i class="bi bi-star-fill"></i> <?= number_format($s['rating'] ?? 5.0, 1) ?></span>
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
            <div class="mb-2">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="fw-bold m-0 text-dark d-flex align-items-center gap-1" style="font-size: 12px;">
                        <i class="bi bi-stars text-warning"></i> Rekomendasi Menu Favorit
                    </h6>
                </div>
                <div class="gofood-products-grid p-0">
                    <?php foreach ($recommend_products as $prod): ?>
                        <div class="gofood-product-card">
                            <div class="position-relative">
                                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($prod['image'] ?? 'assets/images/products/default.jpg') ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="gofood-prod-img">
                                <?php if (!empty($prod['discount']) && (float)$prod['discount'] > 0): ?>
                                    <span class="gofood-discount-tag">Diskon <?= $prod['discount_type'] === 'percent' ? (int)$prod['discount'] . '%' : format_rupiah($prod['discount']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="gofood-prod-body">
                                <div class="gofood-prod-store"><?= htmlspecialchars($prod['store_name'] ?? 'Mitra GoFood') ?></div>
                                <div class="gofood-prod-name"><?= htmlspecialchars($prod['name']) ?></div>
                                <div class="gofood-price-row">
                                    <div>
                                        <div class="gofood-price"><?= format_rupiah($prod['final_price']) ?></div>
                                        <?php if (!empty($prod['discount']) && (float)$prod['discount'] > 0 && (float)$prod['price'] > (float)$prod['final_price']): ?>
                                            <span class="text-muted text-decoration-line-through" style="font-size: 9px;"><?= format_rupiah($prod['price']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="gofood-btn-add" onclick="addToCart(<?= $prod['id'] ?>, 1)" title="Tambah ke Keranjang">
                                        <i class="bi bi-plus-lg"></i>
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
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="fw-bold m-0 text-dark" style="font-size: 12px;"><i class="bi bi-shop text-danger me-1"></i> Resto & Toko (<?= count($stores) ?>)</h6>
            </div>
            <div class="gofood-stores-scroll mb-3 p-0">
                <?php foreach ($stores as $s): ?>
                    <a href="<?= $baseUrl ?>/stores/<?= $s['id'] ?>" class="gofood-store-card">
                        <div class="gofood-store-img-box">
                            <img src="<?= $baseUrl ?>/<?= htmlspecialchars($s['cover_photo'] ?? 'assets/images/stores/geprek_cover.jpg') ?>" class="gofood-store-img" alt="Store">
                            <span class="gofood-open-tag"><i class="bi bi-door-open-fill me-0.5"></i> Buka</span>
                        </div>
                        <div class="gofood-store-body">
                            <div class="gofood-store-name"><?= htmlspecialchars($s['name']) ?></div>
                            <div class="gofood-store-meta">
                                <span class="gofood-rating"><i class="bi bi-star-fill"></i> <?= number_format($s['rating'] ?? 5.0, 1) ?></span>
                                <span>•</span>
                                <span><?= htmlspecialchars($s['delivery_time'] ?? '20-30 mnt') ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Products Search Results -->
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-bold m-0 text-dark" style="font-size: 12px;"><i class="bi bi-egg-fried text-primary me-1"></i> Menu & Produk (<?= count($products) ?>)</h6>
        </div>

        <?php if (empty($products)): ?>
            <div class="text-center py-4 bg-light rounded-3 border p-3">
                <i class="bi bi-search text-muted display-6 mb-1 d-block"></i>
                <h6 class="fw-bold text-dark" style="font-size: 12px;">Menu Tidak Ditemukan</h6>
                <p class="text-muted mb-0" style="font-size: 10.5px;">Tidak ada produk untuk kata kunci "<strong><?= htmlspecialchars($query) ?></strong>".</p>
            </div>
        <?php else: ?>
            <div class="gofood-products-grid p-0">
                <?php foreach ($products as $prod): ?>
                    <div class="gofood-product-card">
                        <div class="position-relative">
                            <img src="<?= $baseUrl ?>/<?= htmlspecialchars($prod['image'] ?? 'assets/images/products/default.jpg') ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="gofood-prod-img">
                            <?php if (!empty($prod['discount']) && (float)$prod['discount'] > 0): ?>
                                <span class="gofood-discount-tag">Diskon <?= $prod['discount_type'] === 'percent' ? (int)$prod['discount'] . '%' : format_rupiah($prod['discount']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="gofood-prod-body">
                            <div class="gofood-prod-store"><?= htmlspecialchars($prod['store_name'] ?? 'Mitra GoFood') ?></div>
                            <div class="gofood-prod-name"><?= htmlspecialchars($prod['name']) ?></div>
                            <div class="gofood-price-row">
                                <div>
                                    <div class="gofood-price"><?= format_rupiah($prod['final_price']) ?></div>
                                    <?php if (!empty($prod['discount']) && (float)$prod['discount'] > 0 && (float)$prod['price'] > (float)$prod['final_price']): ?>
                                        <span class="text-muted text-decoration-line-through" style="font-size: 9px;"><?= format_rupiah($prod['price']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="gofood-btn-add" onclick="addToCart(<?= $prod['id'] ?>, 1)" title="Tambah ke Keranjang">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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
