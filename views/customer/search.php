<!-- Gojek Search Header Bar -->
<div class="bg-white border-bottom sticky-top shadow-xs" style="padding: 6px 10px !important;">
    <div class="d-flex align-items-center gap-1.5">
        <a href="<?= $baseUrl ?>" class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 28px; height: 28px; border: 1px solid var(--gojek-border);">
            <i class="bi bi-arrow-left" style="font-size: 12px;"></i>
        </a>
        <form action="<?= $baseUrl ?>/search" method="GET" class="flex-grow-1 m-0 position-relative">
            <div class="gojek-search-box m-0" style="padding: 4px 10px; background: #F3F4F6; border-radius: 20px; height: 32px;">
                <i class="bi bi-search" style="color: #68717A; font-size: 11px;"></i>
                <input type="text" name="q" id="search-input" value="<?= htmlspecialchars($query ?? '') ?>" placeholder="Cari sate, ayam, martabak..." autofocus autocomplete="off" style="font-size: 11px;">
                <?php if (!empty($query)): ?>
                    <a href="<?= $baseUrl ?>/search" class="text-muted text-decoration-none" title="Hapus"><i class="bi bi-x-circle-fill" style="color: #94A3B8; font-size: 11px;"></i></a>
                <?php endif; ?>
            </div>
            <?php if (!empty($_GET['module_id'])): ?>
                <input type="hidden" name="module_id" value="<?= (int)$_GET['module_id'] ?>">
            <?php endif; ?>
        </form>
    </div>

    <!-- Category Chips Pill Scroll -->
    <div class="d-flex gap-1.5 overflow-x-auto mt-1.5 pb-0.5" style="scrollbar-width: none;">
        <a href="<?= $baseUrl ?>/search<?= !empty($query) ? '?q=' . urlencode($query) : '' ?>" class="badge rounded-pill px-2.5 py-1 text-decoration-none <?= empty($_GET['module_id']) ? 'text-white' : 'bg-light text-dark border' ?>" style="<?= empty($_GET['module_id']) ? 'background:#EE2737;' : '' ?> font-size: 9.5px;">
            Semua
        </a>
        <a href="<?= $baseUrl ?>/search?module_id=1<?= !empty($query) ? '&q=' . urlencode($query) : '' ?>" class="badge rounded-pill px-2.5 py-1 text-decoration-none <?= (($_GET['module_id'] ?? '') == '1') ? 'text-white' : 'bg-light text-dark border' ?>" style="<?= (($_GET['module_id'] ?? '') == '1') ? 'background:#EE2737;' : '' ?> font-size: 9.5px;">
            <i class="bi bi-egg-fried me-0.5"></i> GoFood
        </a>
        <a href="<?= $baseUrl ?>/search?module_id=2<?= !empty($query) ? '&q=' . urlencode($query) : '' ?>" class="badge rounded-pill px-2.5 py-1 text-decoration-none <?= (($_GET['module_id'] ?? '') == '2') ? 'text-white' : 'bg-light text-dark border' ?>" style="<?= (($_GET['module_id'] ?? '') == '2') ? 'background:#FF6A00;' : '' ?> font-size: 9.5px;">
            <i class="bi bi-cart3 me-0.5"></i> GoMart
        </a>
        <a href="<?= $baseUrl ?>/search?module_id=3<?= !empty($query) ? '&q=' . urlencode($query) : '' ?>" class="badge rounded-pill px-2.5 py-1 text-decoration-none <?= (($_GET['module_id'] ?? '') == '3') ? 'text-white' : 'bg-light text-dark border' ?>" style="<?= (($_GET['module_id'] ?? '') == '3') ? 'background:#0081A0;' : '' ?> font-size: 9.5px;">
            <i class="bi bi-capsule me-0.5"></i> GoMed
        </a>
        <a href="<?= $baseUrl ?>/search?module_id=4<?= !empty($query) ? '&q=' . urlencode($query) : '' ?>" class="badge rounded-pill px-2.5 py-1 text-decoration-none <?= (($_GET['module_id'] ?? '') == '4') ? 'text-white' : 'bg-light text-dark border' ?>" style="<?= (($_GET['module_id'] ?? '') == '4') ? 'background:#9333EA;' : '' ?> font-size: 9.5px;">
            <i class="bi bi-bag-heart me-0.5"></i> GoShop
        </a>
    </div>
</div>

<div class="p-2" style="padding: 8px 10px !important;">
    <?php if (empty($query)): ?>
        <!-- Trending & Popular Searches -->
        <div class="mb-2.5">
            <h6 class="fw-bold mb-1.5" style="font-size: 11px; color: var(--gojek-charcoal);"><i class="bi bi-fire text-danger me-1"></i> Pencarian Populer</h6>
            <div class="d-flex flex-wrap gap-1.5">
                <button type="button" onclick="quickSearch('Ayam Geprek')" class="btn btn-sm btn-light rounded-pill px-2 py-0.5 border text-dark fw-medium" style="font-size: 9.5px;">🍗 Ayam Geprek</button>
                <button type="button" onclick="quickSearch('Sate Maranggi')" class="btn btn-sm btn-light rounded-pill px-2 py-0.5 border text-dark fw-medium" style="font-size: 9.5px;">🍢 Sate Maranggi</button>
                <button type="button" onclick="quickSearch('Seblak Pedas')" class="btn btn-sm btn-light rounded-pill px-2 py-0.5 border text-dark fw-medium" style="font-size: 9.5px;">🌶️ Seblak</button>
                <button type="button" onclick="quickSearch('Martabak')" class="btn btn-sm btn-light rounded-pill px-2 py-0.5 border text-dark fw-medium" style="font-size: 9.5px;">🥞 Martabak</button>
                <button type="button" onclick="quickSearch('Beras')" class="btn btn-sm btn-light rounded-pill px-2 py-0.5 border text-dark fw-medium" style="font-size: 9.5px;">🍚 Sembako</button>
                <button type="button" onclick="quickSearch('Kopi')" class="btn btn-sm btn-light rounded-pill px-2 py-0.5 border text-dark fw-medium" style="font-size: 9.5px;">☕ Kopi Susu</button>
            </div>
        </div>

        <!-- Quick Promo Banner or Recommendation -->
        <div class="p-2.5 rounded-3 text-white shadow-xs mb-2.5" style="background: linear-gradient(135deg, #EE2737 0%, #C61524 100%);">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="badge bg-white text-danger fw-bold px-1.5 py-0.5 mb-0.5" style="font-size: 8px;">PROMO SPESIAL</span>
                    <h6 class="fw-bold m-0" style="font-size: 11px;">Gratis Ongkir s.d 10rb</h6>
                    <div class="text-white-50" style="font-size: 9px;">Berlaku di seluruh merchant Cicalengka</div>
                </div>
                <i class="bi bi-ticket-perforated-fill display-6 text-white-50"></i>
            </div>
        </div>

        <div class="text-center py-3 text-muted">
            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-1.5" style="width: 44px; height: 44px;">
                <i class="bi bi-search text-muted fs-5"></i>
            </div>
            <h6 class="fw-bold mb-0.5" style="color: var(--gojek-charcoal); font-size: 11px;">Cari Makanan & Belanjaan</h6>
            <p class="text-muted mb-0" style="font-size: 9.5px;">Ketik kata kunci untuk menemukan kuliner favorit.</p>
        </div>

    <?php else: ?>
        <!-- Stores Search Results -->
        <?php if (!empty($stores)): ?>
            <div class="d-flex align-items-center justify-content-between mb-1.5">
                <h6 class="fw-bold m-0" style="font-size: 11px; color: var(--gojek-charcoal);"><i class="bi bi-shop text-danger me-1"></i> Resto & Toko (<?= count($stores) ?>)</h6>
            </div>
            <div class="gofood-stores-scroll mb-2.5 p-0">
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
        <div class="d-flex align-items-center justify-content-between mb-1.5">
            <h6 class="fw-bold m-0" style="font-size: 11px; color: var(--gojek-charcoal);"><i class="bi bi-egg-fried text-primary me-1"></i> Menu & Produk (<?= count($products) ?>)</h6>
        </div>

        <?php if (empty($products)): ?>
            <div class="text-center py-4 bg-light rounded-3 border p-2">
                <i class="bi bi-search text-muted display-6 mb-1 d-block"></i>
                <h6 class="fw-bold" style="color: var(--gojek-charcoal); font-size: 11px;">Menu Tidak Ditemukan</h6>
                <p class="text-muted mb-0" style="font-size: 9.5px;">Tidak ada produk untuk kata kunci "<strong><?= htmlspecialchars($query) ?></strong>".</p>
            </div>
        <?php else: ?>
            <div class="gofood-products-grid p-0">
                <?php foreach ($products as $prod): ?>
                    <div class="gofood-product-card">
                        <div class="position-relative">
                            <img src="<?= $baseUrl ?>/<?= htmlspecialchars($prod['image'] ?? 'assets/images/products/default.jpg') ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="gofood-prod-img">
                            <?php if ((float)$prod['discount'] > 0): ?>
                                <span class="gofood-discount-tag">Diskon <?= $prod['discount_type'] === 'percent' ? (int)$prod['discount'] . '%' : format_rupiah($prod['discount']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="gofood-prod-body">
                            <div class="gofood-prod-store"><?= htmlspecialchars($prod['store_name'] ?? 'Mitra GoFood') ?></div>
                            <div class="gofood-prod-name"><?= htmlspecialchars($prod['name']) ?></div>
                            <div class="gofood-price-row">
                                <div>
                                    <div class="gofood-price"><?= format_rupiah($prod['final_price']) ?></div>
                                    <?php if ((float)$prod['discount'] > 0): ?>
                                        <span class="text-muted text-decoration-line-through" style="font-size: 8.5px;"><?= format_rupiah($prod['price']) ?></span>
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
