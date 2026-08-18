<!-- CicalengkaPay Wallet Card -->
<div class="gopay-super-card shadow-2xs overflow-hidden mb-3">
    <div class="gopay-left min-w-0">
        <div class="gopay-logo-badge flex-shrink-0">
            <i class="bi bi-wallet2 text-white"></i>
            <span class="gopay-text">Cicalengka</span><span class="pay-text">Pay</span>
        </div>
        <div class="gopay-balance-row min-w-0">
            <span class="gopay-amount text-truncate"><?= format_rupiah($wallet_balance ?? 0) ?></span>
        </div>
        <div class="gopay-coins-row text-truncate">
            <i class="bi bi-award-fill text-warning flex-shrink-0"></i>
            <span class="text-truncate">0 Poin</span>
            <span class="ms-1 text-white-50 flex-shrink-0" style="font-size: 9px;">• Riwayat</span>
        </div>
    </div>

    <div class="gopay-actions-grid flex-shrink-0">
        <a href="<?= $baseUrl ?>/wallet" class="gopay-action-btn">
            <div class="gopay-action-icon">
                <i class="bi bi-arrow-up-right"></i>
            </div>
            <span class="gopay-action-label">Bayar</span>
        </a>
        <a href="<?= $baseUrl ?>/wallet" class="gopay-action-btn">
            <div class="gopay-action-icon">
                <i class="bi bi-plus-lg"></i>
            </div>
            <span class="gopay-action-label">Top Up</span>
        </a>
        <a href="<?= $baseUrl ?>/wallet" class="gopay-action-btn">
            <div class="gopay-action-icon">
                <i class="bi bi-compass"></i>
            </div>
            <span class="gopay-action-label">Eksplor</span>
        </a>
    </div>
</div>

<!-- Kategori Pilihan & Layanan CicalengkaGO -->
<div class="gojek-services-section px-3 pt-1 pb-2.5 mb-2">
    <div class="d-flex align-items-center justify-content-between px-0.5" style="margin-bottom: 14px !important;">
        <h6 class="fw-bold m-0" style="font-size: 12.5px; color: var(--gojek-charcoal); letter-spacing: -0.3px;">
            <i class="bi bi-grid-1x2-fill me-1" style="color: var(--gojek-green);"></i> Kategori Pilihan
        </h6>
        <a href="<?= $baseUrl ?>/search" class="text-decoration-none fw-bold" style="font-size: 10.5px; color: var(--gojek-green);">
            Lihat Semua <i class="bi bi-chevron-right" style="font-size: 9px;"></i>
        </a>
    </div>

    <div class="gojek-services-grid">
        <!-- 1. Ayam Geprek -->
        <a href="<?= $baseUrl ?>/search?q=Ayam" class="gojek-service-item">
            <div class="gojek-service-squircle ayam">
                <i class="bi bi-fire"></i>
            </div>
            <span class="gojek-service-label">Ayam & Geprek</span>
        </a>

        <!-- 2. Seblak Pedas -->
        <a href="<?= $baseUrl ?>/search?q=Seblak" class="gojek-service-item">
            <div class="gojek-service-squircle seblak">
                <i class="bi bi-emoji-heart-eyes-fill"></i>
            </div>
            <span class="gojek-service-label">Seblak Pedas</span>
        </a>

        <!-- 3. Bakso & Mie -->
        <a href="<?= $baseUrl ?>/search?q=Bakso" class="gojek-service-item">
            <div class="gojek-service-squircle bakso">
                <i class="bi bi-cup-hot-fill"></i>
            </div>
            <span class="gojek-service-label">Bakso & Mie</span>
        </a>

        <!-- 4. Nasi Liwet Sunda -->
        <a href="<?= $baseUrl ?>/search?q=Nasi" class="gojek-service-item">
            <div class="gojek-service-squircle sunda">
                <i class="bi bi-egg-fried"></i>
            </div>
            <span class="gojek-service-label">Nasi Liwet</span>
        </a>

        <!-- 5. Sate Maranggi -->
        <a href="<?= $baseUrl ?>/search?q=Sate" class="gojek-service-item">
            <div class="gojek-service-squircle sate">
                <i class="bi bi-distribute-vertical"></i>
            </div>
            <span class="gojek-service-label">Sate Maranggi</span>
        </a>

        <!-- 6. Martabak -->
        <a href="<?= $baseUrl ?>/search?q=Martabak" class="gojek-service-item">
            <div class="gojek-service-squircle martabak">
                <i class="bi bi-pie-chart-fill"></i>
            </div>
            <span class="gojek-service-label">Martabak</span>
        </a>

        <!-- 7. Kopi & Cafe -->
        <a href="<?= $baseUrl ?>/search?q=Kopi" class="gojek-service-item">
            <div class="gojek-service-squircle kopi">
                <i class="bi bi-cup-straw"></i>
            </div>
            <span class="gojek-service-label">Kopi & Cafe</span>
        </a>

        <!-- 8. Sembako & Mart -->
        <a href="<?= $baseUrl ?>/search?q=Sembako" class="gojek-service-item">
            <div class="gojek-service-squircle sembako">
                <i class="bi bi-basket3-fill"></i>
            </div>
            <span class="gojek-service-label">Sembako Mart</span>
        </a>
    </div>
</div>

<!-- Gojek Promo Banners Carousel -->
<?php if (!empty($banners)): ?>
<div class="gojek-promo-section px-3 py-1 mb-2">
    <div id="gojekBannerCarousel" class="carousel slide gojek-carousel-container shadow-2xs overflow-hidden" data-bs-ride="carousel" style="border-radius: 14px;">
        <div class="carousel-inner overflow-hidden">
            <?php foreach ($banners as $index => $banner): ?>
                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                    <img src="<?= $baseUrl ?>/<?= htmlspecialchars($banner['image']) ?>" alt="<?= htmlspecialchars($banner['title']) ?>" class="d-block w-100 gojek-banner-img">
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($banners) > 1): ?>
            <div class="carousel-indicators mb-1.5">
                <?php foreach ($banners as $index => $banner): ?>
                    <button type="button" data-bs-target="#gojekBannerCarousel" data-bs-slide-to="<?= $index ?>" class="<?= $index === 0 ? 'active' : '' ?>" aria-label="Slide <?= $index + 1 ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- GoFood Pilihan Terlaris Hari Ini -->
<div class="gojek-section-header px-3 pt-2.5 pb-1">
    <div>
        <h2 class="gojek-section-title" style="font-size: 12.5px; letter-spacing: -0.3px;">Paling Laris di Cicalengka</h2>
        <div class="gojek-section-subtitle" style="font-size: 9.5px;">Diskon & resto favorit pilihan warga Cicalengka</div>
    </div>
    <a href="<?= $baseUrl ?>/search?module_id=<?= $selected_module_id ?>" class="gojek-section-link" style="font-size: 10.5px;">Lihat Semua</a>
</div>

<div class="gofood-stores-scroll px-3 pb-2.5 mb-2">
    <?php if (empty($popular_stores)): ?>
        <div class="text-muted small p-3">Belum ada resto pada kategori ini.</div>
    <?php else: ?>
        <?php foreach ($popular_stores as $store): ?>
            <a href="<?= $baseUrl ?>/stores/<?= $store['id'] ?>" class="gofood-store-card shadow-2xs overflow-hidden">
                <div class="gofood-store-img-box position-relative overflow-hidden">
                    <img src="<?= $baseUrl ?>/<?= htmlspecialchars($store['cover_photo'] ?? 'assets/images/stores/geprek_cover.jpg') ?>" alt="<?= htmlspecialchars($store['name']) ?>" class="gofood-store-img">
                    <?php if ($store['is_open']): ?>
                        <span class="gofood-open-tag"><i class="bi bi-door-open-fill me-0.5"></i> Buka</span>
                    <?php else: ?>
                        <span class="gofood-closed-tag"><i class="bi bi-door-closed-fill me-0.5"></i> Tutup</span>
                    <?php endif; ?>
                </div>
                <div class="gofood-store-body">
                    <div class="gofood-store-name text-truncate"><?= htmlspecialchars($store['name']) ?></div>
                    <div class="gofood-store-meta">
                        <span class="gofood-rating flex-shrink-0"><i class="bi bi-star-fill"></i> <?= number_format((float)($store['rating'] ?? 5.0), 1) ?></span>
                        <span>•</span>
                        <span class="text-truncate"><?= htmlspecialchars($store['delivery_time'] ?? '20-30 mnt') ?></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- GoFood & GoMart Rekomendasi Menu & Produk -->
<div class="gojek-section-header px-3 pt-2.5 pb-1">
    <div>
        <h2 class="gojek-section-title" style="font-size: 12.5px; letter-spacing: -0.3px;">Rekomendasi Menu Favorit</h2>
        <div class="gojek-section-subtitle" style="font-size: 9.5px;">Paling sering dipesan dengan pengantaran cepat</div>
    </div>
</div>

<div class="gofood-products-grid px-3 pb-4">
    <?php foreach ($recommended_products as $prod): ?>
        <div class="gofood-product-card shadow-2xs overflow-hidden">
            <div class="position-relative overflow-hidden">
                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($prod['image'] ?? 'assets/images/products/default.jpg') ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="gofood-prod-img">
                <?php if ((float)$prod['discount'] > 0): ?>
                    <span class="gofood-discount-tag">Diskon <?= $prod['discount_type'] === 'percent' ? (int)$prod['discount'] . '%' : format_rupiah($prod['discount']) ?></span>
                <?php endif; ?>
            </div>
            <div class="gofood-prod-body">
                <div class="gofood-prod-store text-truncate"><?= htmlspecialchars($prod['store_name'] ?? 'Mitra GoFood') ?></div>
                <div class="gofood-prod-name text-truncate"><?= htmlspecialchars($prod['name']) ?></div>
                <div class="gofood-price-row mt-1 d-flex flex-column gap-0.5">
                    <div class="min-w-0">
                        <div class="gofood-price text-truncate"><?= format_rupiah($prod['final_price']) ?></div>
                        <?php if (!empty($prod['discount']) && (float)$prod['discount'] > 0 && (float)$prod['price'] > (float)$prod['final_price']): ?>
                            <span class="text-muted text-decoration-line-through text-truncate d-block" style="font-size: 9px;"><?= format_rupiah($prod['price']) ?></span>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="gofood-btn-add-block" onclick="addToCart(<?= $prod['id'] ?>, 1)" title="Tambah ke Keranjang">
                        <i class="bi bi-plus-lg" style="font-size: 10px;"></i> Tambah
                    </button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
