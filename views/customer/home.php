<!-- CicalengkaPay Wallet Super Card -->
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
            <span class="text-truncate">Gratis Ongkir & Promo</span>
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

<!-- Quick Search & Trending Chips Bar -->
<div class="px-3 mb-3">
    <form action="<?= $baseUrl ?>/search" method="GET" class="position-relative mb-2">
        <input type="text" name="q" class="form-control rounded-pill border-0 shadow-2xs ps-4 pe-5 py-2" style="font-size: 12px; background: #F1F5F9; color: #0F172A;" placeholder="Cari seblak, bento cake, nasi goreng, martabak...">
        <button type="submit" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-success pe-3 border-0 bg-transparent">
            <i class="bi bi-search" style="font-size: 14px;"></i>
        </button>
    </form>
    
    <!-- Trending Chips -->
    <div class="d-flex align-items-center gap-1.5 overflow-x-auto pb-1" style="scrollbar-width: none;">
        <span class="text-muted fw-bold flex-shrink-0" style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px;"><i class="bi bi-fire text-danger"></i> Trending:</span>
        <a href="<?= $baseUrl ?>/search?q=Seblak" class="badge rounded-pill bg-light text-dark border px-2.5 py-1 text-decoration-none flex-shrink-0" style="font-size: 10px; font-weight: 600;">🌶️ Seblak</a>
        <a href="<?= $baseUrl ?>/search?q=Bento" class="badge rounded-pill bg-light text-dark border px-2.5 py-1 text-decoration-none flex-shrink-0" style="font-size: 10px; font-weight: 600;">🎂 Bento Cake</a>
        <a href="<?= $baseUrl ?>/search?q=Burger" class="badge rounded-pill bg-light text-dark border px-2.5 py-1 text-decoration-none flex-shrink-0" style="font-size: 10px; font-weight: 600;">🍔 Burger Bangor</a>
        <a href="<?= $baseUrl ?>/search?q=Nasi+Goreng" class="badge rounded-pill bg-light text-dark border px-2.5 py-1 text-decoration-none flex-shrink-0" style="font-size: 10px; font-weight: 600;">🍚 Nasi Goreng</a>
        <a href="<?= $baseUrl ?>/search?q=Cimol" class="badge rounded-pill bg-light text-dark border px-2.5 py-1 text-decoration-none flex-shrink-0" style="font-size: 10px; font-weight: 600;">🍡 Cimol Padang</a>
        <a href="<?= $baseUrl ?>/search?q=Sate" class="badge rounded-pill bg-light text-dark border px-2.5 py-1 text-decoration-none flex-shrink-0" style="font-size: 10px; font-weight: 600;">🍢 Sate Ade</a>
    </div>
</div>

<!-- Kategori Pilihan & Layanan CicalengkaGO -->
<div class="gojek-services-section px-3 pt-1 pb-2 mb-2">
    <div class="d-flex align-items-center justify-content-between px-0.5" style="margin-bottom: 12px !important;">
        <h6 class="fw-bold m-0" style="font-size: 12.5px; color: var(--gojek-charcoal); letter-spacing: -0.3px;">
            <i class="bi bi-grid-1x2-fill me-1" style="color: var(--gojek-green);"></i> Kategori Pilihan
        </h6>
        <a href="<?= $baseUrl ?>/search" class="text-decoration-none fw-bold" style="font-size: 10.5px; color: var(--gojek-green);">
            Lihat Semua <i class="bi bi-chevron-right" style="font-size: 9px;"></i>
        </a>
    </div>

    <div class="gojek-services-grid">
        <a href="<?= $baseUrl ?>/search?q=Ayam" class="gojek-service-item">
            <div class="gojek-service-squircle ayam"><i class="bi bi-fire"></i></div>
            <span class="gojek-service-label">Ayam & Geprek</span>
        </a>
        <a href="<?= $baseUrl ?>/search?q=Seblak" class="gojek-service-item">
            <div class="gojek-service-squircle seblak"><i class="bi bi-emoji-heart-eyes-fill"></i></div>
            <span class="gojek-service-label">Seblak Pedas</span>
        </a>
        <a href="<?= $baseUrl ?>/search?q=Bakso" class="gojek-service-item">
            <div class="gojek-service-squircle bakso"><i class="bi bi-cup-hot-fill"></i></div>
            <span class="gojek-service-label">Bakso & Mie</span>
        </a>
        <a href="<?= $baseUrl ?>/search?q=Nasi" class="gojek-service-item">
            <div class="gojek-service-squircle sunda"><i class="bi bi-egg-fried"></i></div>
            <span class="gojek-service-label">Nasi Liwet</span>
        </a>
        <a href="<?= $baseUrl ?>/search?q=Sate" class="gojek-service-item">
            <div class="gojek-service-squircle sate"><i class="bi bi-distribute-vertical"></i></div>
            <span class="gojek-service-label">Sate Maranggi</span>
        </a>
        <a href="<?= $baseUrl ?>/search?q=Martabak" class="gojek-service-item">
            <div class="gojek-service-squircle martabak"><i class="bi bi-pie-chart-fill"></i></div>
            <span class="gojek-service-label">Martabak</span>
        </a>
        <a href="<?= $baseUrl ?>/search?q=Kopi" class="gojek-service-item">
            <div class="gojek-service-squircle kopi"><i class="bi bi-cup-straw"></i></div>
            <span class="gojek-service-label">Kopi & Cafe</span>
        </a>
        <a href="<?= $baseUrl ?>/search?q=Sembako" class="gojek-service-item">
            <div class="gojek-service-squircle sembako"><i class="bi bi-basket3-fill"></i></div>
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

<!-- Flash Sale / Promo Diskon Hari Ini Section -->
<?php if (!empty($discounted_products)): ?>
<div class="px-3 pt-2 pb-1">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div>
            <h6 class="fw-bold m-0" style="font-size: 12.5px; color: #0F172A; letter-spacing: -0.3px;">
                <i class="bi bi-lightning-charge-fill me-1 text-warning"></i> Flash Sale & Promo Diskon
            </h6>
            <div style="font-size: 9.5px; color: #64748B;">Hemat sampai 50% untuk menu pilihan hari ini</div>
        </div>
        <span class="badge bg-danger-subtle text-danger font-monospace px-2 py-1" style="font-size: 9.5px; font-weight: 700;">PROMO SPESIAL</span>
    </div>

    <div class="d-flex gap-2.5 overflow-x-auto pb-2" style="scrollbar-width: none;">
        <?php foreach ($discounted_products as $discProd): ?>
            <div class="shadow-2xs overflow-hidden flex-shrink-0" style="width: 140px; background: #FFFFFF; border-radius: 12px; border: 1px solid #E2E8F0;">
                <div class="position-relative overflow-hidden" style="height: 100px;">
                    <img src="<?= asset_url($discProd['image'] ?? null, 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&q=80') ?>" alt="<?= htmlspecialchars($discProd['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&q=80';">
                    <span class="position-absolute top-0 start-0 bg-danger text-white px-1.5 py-0.5 font-monospace fw-bold" style="font-size: 8.5px; border-bottom-end-radius: 8px;">
                        -<?= $discProd['discount_type'] === 'percent' ? (int)$discProd['discount'] . '%' : format_rupiah($discProd['discount']) ?>
                    </span>
                </div>
                <div class="p-2">
                    <div class="text-muted text-truncate" style="font-size: 8.5px;"><?= htmlspecialchars($discProd['store_name'] ?? 'Resto Cicalengka') ?></div>
                    <div class="fw-bold text-dark text-truncate" style="font-size: 10.5px; line-height: 1.2;"><?= htmlspecialchars($discProd['name']) ?></div>
                    <div class="mt-1">
                        <div class="fw-extrabold text-danger" style="font-size: 11.5px;"><?= format_rupiah($discProd['final_price']) ?></div>
                        <div class="text-muted text-decoration-line-through text-truncate" style="font-size: 8.5px;"><?= format_rupiah($discProd['price']) ?></div>
                    </div>
                    <button type="button" class="gofood-btn-add-block mt-1 w-100" onclick="addToCart(<?= $discProd['id'] ?>, 1)">
                        <i class="bi bi-plus-lg" style="font-size: 9px;"></i> Tambah
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Resto Terpopuler & Pilihan Warga Cicalengka -->
<div class="gojek-section-header px-3 pt-2.5 pb-1">
    <div>
        <h2 class="gojek-section-title" style="font-size: 12.5px; letter-spacing: -0.3px;">
            <i class="bi bi-shop me-1 text-danger"></i> Resto & Toko Paling Hit di Cicalengka
        </h2>
        <div class="gojek-section-subtitle" style="font-size: 9.5px;">Buka sekarang dengan layanan antar cepat dan rating tinggi</div>
    </div>
    <a href="<?= $baseUrl ?>/search?module_id=<?= $selected_module_id ?>" class="gojek-section-link" style="font-size: 10.5px;">Lihat Semua</a>
</div>

<div class="gofood-stores-scroll px-3 pb-2.5 mb-2">
    <?php if (empty($top_rated_stores)): ?>
        <div class="text-muted small p-3">Belum ada resto pada kategori ini.</div>
    <?php else: ?>
        <?php foreach ($top_rated_stores as $store): ?>
            <a href="<?= $baseUrl ?>/stores/<?= $store['id'] ?>" class="gofood-store-card shadow-2xs overflow-hidden">
                <div class="gofood-store-img-box position-relative overflow-hidden">
                    <img src="<?= asset_url($store['cover_photo'] ?? null, 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800&q=80') ?>" alt="<?= htmlspecialchars($store['name']) ?>" class="gofood-store-img" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800&q=80';">
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
                        <span class="text-truncate"><?= htmlspecialchars($store['delivery_time'] ?? '15-25 mnt') ?></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Eksplor Menu Favorit & Variatif -->
<div class="gojek-section-header px-3 pt-2.5 pb-1">
    <div>
        <h2 class="gojek-section-title" style="font-size: 12.5px; letter-spacing: -0.3px;">
            <i class="bi bi-stars me-1 text-warning"></i> Eksplor Menu Kuliner Favorit
        </h2>
        <div class="gojek-section-subtitle" style="font-size: 9.5px;">Aneka pilihan makanan & cemilan segar siap diantar</div>
    </div>
</div>

<div class="gofood-products-grid px-3 pb-4">
    <?php foreach ($recommended_products as $prod): ?>
        <div class="gofood-product-card shadow-2xs overflow-hidden">
            <div class="position-relative overflow-hidden">
                <img src="<?= asset_url($prod['image'] ?? null, 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&q=80') ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="gofood-prod-img" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&q=80';">
                <?php if ((float)$prod['discount'] > 0): ?>
                    <span class="gofood-discount-tag">Diskon <?= $prod['discount_type'] === 'percent' ? (int)$prod['discount'] . '%' : format_rupiah($prod['discount']) ?></span>
                <?php endif; ?>
            </div>
            <div class="gofood-prod-body">
                <div class="gofood-prod-store text-truncate"><?= htmlspecialchars($prod['store_name'] ?? 'Mitra CicalengkaGO') ?></div>
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

<!-- Keunggulan CicalengkaGO Badges -->
<div class="px-3 pb-4">
    <div class="p-3 bg-light rounded-4 border d-flex align-items-center justify-content-between gap-2 shadow-2xs">
        <div class="text-center flex-1">
            <i class="bi bi-lightning-charge-fill text-warning fs-5 d-block mb-1"></i>
            <span class="fw-bold d-block" style="font-size: 10px; color: #0F172A;">Pengantaran Cepat</span>
            <span class="text-muted" style="font-size: 8.5px;">15-25 Menit</span>
        </div>
        <div class="vr opacity-25"></div>
        <div class="text-center flex-1">
            <i class="bi bi-patch-check-fill text-success fs-5 d-block mb-1"></i>
            <span class="fw-bold d-block" style="font-size: 10px; color: #0F172A;">Resto Terverifikasi</span>
            <span class="text-muted" style="font-size: 8.5px;">100% Higienis</span>
        </div>
        <div class="vr opacity-25"></div>
        <div class="text-center flex-1">
            <i class="bi bi-shield-lock-fill text-primary fs-5 d-block mb-1"></i>
            <span class="fw-bold d-block" style="font-size: 10px; color: #0F172A;">CicalengkaPay</span>
            <span class="text-muted" style="font-size: 8.5px;">Aman & Instan</span>
        </div>
    </div>
</div>
