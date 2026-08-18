<!-- Header & Navigation Bar -->
<div class="px-3 pt-3 pb-2 bg-white border-bottom shadow-2xs sticky-top" style="z-index: 15;">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div>
            <h5 class="fw-extrabold m-0 text-dark" style="font-size: 16px; letter-spacing: -0.3px;">
                <i class="bi bi-compass-fill me-1 text-danger"></i> Jelajah Resto & Toko
            </h5>
            <div class="text-muted" style="font-size: 10px;"><?= count($stores) ?> Mitra Terverifikasi di Cicalengka</div>
        </div>
        <div class="btn-group btn-group-sm rounded-pill border p-0.5 bg-light" role="group">
            <button type="button" class="btn btn-dark rounded-pill px-2.5 py-1 view-toggle-btn active" id="btnViewGrid" style="font-size: 10px; font-weight: 700;">
                <i class="bi bi-grid-fill me-1"></i> Kartu
            </button>
            <button type="button" class="btn btn-light rounded-pill px-2.5 py-1 view-toggle-btn text-dark" id="btnViewMap" style="font-size: 10px; font-weight: 700;">
                <i class="bi bi-map-fill me-1 text-danger"></i> Peta
            </button>
        </div>
    </div>

    <!-- Search Input Bar -->
    <form action="<?= $baseUrl ?>/explore-stores" method="GET" class="position-relative mb-2">
        <?php if ($selected_module): ?>
            <input type="hidden" name="module_id" value="<?= $selected_module ?>">
        <?php endif; ?>
        <?php if ($active_filter !== 'all'): ?>
            <input type="hidden" name="filter" value="<?= htmlspecialchars($active_filter) ?>">
        <?php endif; ?>
        <input type="text" name="q" value="<?= htmlspecialchars($search ?? '') ?>" class="form-control rounded-pill border-0 ps-4 pe-5 py-2 shadow-2xs" style="font-size: 11.5px; background: #F1F5F9;" placeholder="Cari nama resto, alamat, atau makanan di Cicalengka...">
        <button type="submit" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-danger pe-3 border-0 bg-transparent">
            <i class="bi bi-search" style="font-size: 13px;"></i>
        </button>
    </form>

    <!-- Filter Pills Navigation -->
    <div class="d-flex align-items-center gap-1.5 overflow-x-auto pb-1" style="scrollbar-width: none;">
        <a href="<?= $baseUrl ?>/explore-stores<?= $selected_module ? '?module_id='.$selected_module : '' ?>" class="btn btn-sm <?= $active_filter === 'all' ? 'btn-dark' : 'btn-light border text-dark' ?> rounded-pill px-3 py-1 flex-shrink-0" style="font-size: 10px; font-weight: 700;">
            Semua (<?= count($stores) ?>)
        </a>
        <a href="<?= $baseUrl ?>/explore-stores?filter=open<?= $selected_module ? '&module_id='.$selected_module : '' ?>" class="btn btn-sm <?= $active_filter === 'open' ? 'btn-dark' : 'btn-light border text-dark' ?> rounded-pill px-3 py-1 flex-shrink-0" style="font-size: 10px; font-weight: 700;">
            <i class="bi bi-door-open-fill text-success me-0.5"></i> Buka Sekarang
        </a>
        <a href="<?= $baseUrl ?>/explore-stores?filter=rating<?= $selected_module ? '&module_id='.$selected_module : '' ?>" class="btn btn-sm <?= $active_filter === 'rating' ? 'btn-dark' : 'btn-light border text-dark' ?> rounded-pill px-3 py-1 flex-shrink-0" style="font-size: 10px; font-weight: 700;">
            <i class="bi bi-star-fill text-warning me-0.5"></i> Rating 4.5+
        </a>
        <a href="<?= $baseUrl ?>/explore-stores?filter=popular<?= $selected_module ? '&module_id='.$selected_module : '' ?>" class="btn btn-sm <?= $active_filter === 'popular' ? 'btn-dark' : 'btn-light border text-dark' ?> rounded-pill px-3 py-1 flex-shrink-0" style="font-size: 10px; font-weight: 700;">
            <i class="bi bi-fire text-danger me-0.5"></i> Terpopuler
        </a>
    </div>
</div>

<!-- Interactive Map Container (Hidden by default until toggled) -->
<div id="exploreMapSection" class="px-3 pt-2 pb-3 d-none">
    <div class="card border shadow-2xs overflow-hidden rounded-4">
        <div class="card-header bg-dark text-white d-flex align-items-center justify-content-between py-2 px-3">
            <span style="font-size: 11px; font-weight: 700;"><i class="bi bi-pin-map-fill text-danger me-1"></i> Peta Persebaran Resto Cicalengka</span>
            <span class="badge bg-danger rounded-pill" style="font-size: 9px;"><?= count($stores) ?> Lokasi</span>
        </div>
        <div id="explore-full-map" style="width: 100%; height: 380px; background: #E2E8F0;"></div>
    </div>
</div>

<!-- Store Grid Container -->
<div id="exploreGridSection" class="px-3 pt-3 pb-5">
    <?php if (empty($stores)): ?>
        <div class="text-center py-5 bg-light rounded-4 border">
            <i class="bi bi-shop-window text-muted d-block mb-2" style="font-size: 32px;"></i>
            <h6 class="fw-bold text-dark mb-1" style="font-size: 13px;">Tidak ada resto yang ditemukan</h6>
            <p class="text-muted small mb-3" style="font-size: 11px;">Coba gunakan kata kunci pencarian lain atau ganti filter.</p>
            <a href="<?= $baseUrl ?>/explore-stores" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold" style="font-size: 11px;">Reset Filter</a>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($stores as $s): ?>
                <div class="col-12 col-md-6">
                    <a href="<?= $baseUrl ?>/stores/<?= $s['id'] ?>" class="text-decoration-none text-dark d-block">
                        <div class="card border-0 shadow-2xs overflow-hidden rounded-4 h-100 hover-shadow transition-all" style="border: 1px solid #E2E8F0 !important;">
                            <div class="position-relative" style="height: 120px;">
                                <img src="<?= asset_url($s['cover_photo'] ?? null, 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800&q=80') ?>" alt="<?= htmlspecialchars($s['name']) ?>" class="w-100 h-100" style="object-fit: cover;" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800&q=80';">
                                <div class="position-absolute top-0 end-0 p-2 d-flex flex-column align-items-end gap-1">
                                    <?php if (!empty($s['is_open'])): ?>
                                        <span class="badge bg-success shadow-2xs font-monospace fw-bold rounded-pill" style="font-size: 9px; padding: 4px 8px;"><i class="bi bi-door-open-fill me-0.5"></i> BUKA</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger shadow-2xs font-monospace fw-bold rounded-pill" style="font-size: 9px; padding: 4px 8px;"><i class="bi bi-door-closed-fill me-0.5"></i> TUTUP</span>
                                    <?php endif; ?>
                                    <span class="badge bg-dark text-white font-monospace rounded-pill" style="font-size: 8px; padding: 2.5px 6px; background: rgba(15,23,42,0.75) !important;">
                                        <i class="bi bi-clock me-0.5"></i> <?= htmlspecialchars($s['operating_hours'] ?? '08:00 - 22:00') ?>
                                    </span>
                                </div>
                                <div class="position-absolute bottom-0 start-0 p-2 w-100 d-flex align-items-end justify-content-between" style="background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);">
                                    <span class="badge bg-white text-dark font-monospace fw-bold rounded-pill border" style="font-size: 9px;">
                                        <i class="bi bi-grid-fill text-danger me-0.5"></i> <?= (int)($s['product_count'] ?? 0) ?> Menu
                                    </span>
                                    <span class="badge bg-warning text-dark font-monospace fw-bold rounded-pill" style="font-size: 9px;">
                                        <i class="bi bi-star-fill me-0.5"></i> <?= number_format((float)($s['rating'] ?? 5.0), 1) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="p-3">
                                <div class="d-flex align-items-start gap-2.5">
                                    <img src="<?= asset_url($s['logo'] ?? null, 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=300&q=80') ?>" alt="Logo" class="rounded-3 border shadow-2xs flex-shrink-0" style="width: 44px; height: 44px; object-fit: cover;" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=300&q=80';">
                                    <div class="min-w-0 flex-grow-1">
                                        <h6 class="fw-bold text-dark text-truncate mb-0.5" style="font-size: 13.5px; line-height: 1.3;"><?= htmlspecialchars($s['name']) ?></h6>
                                        <div class="text-muted text-truncate mb-1" style="font-size: 10px;">
                                            <i class="bi bi-geo-alt-fill text-danger me-0.5"></i> <?= htmlspecialchars($s['address']) ?>
                                        </div>
                                        <div class="d-flex align-items-center gap-2" style="font-size: 9.5px; color: #64748B;">
                                            <span><i class="bi bi-clock me-0.5 text-primary"></i> <?= htmlspecialchars($s['delivery_time'] ?? '15-25 mnt') ?></span>
                                            <span>•</span>
                                            <span><i class="bi bi-shield-check text-success me-0.5"></i> Terverifikasi</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Interactive JS Logic for View Switcher & Leaflet Map -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const btnGrid = document.getElementById('btnViewGrid');
    const btnMap  = document.getElementById('btnViewMap');
    const secGrid = document.getElementById('exploreGridSection');
    const secMap  = document.getElementById('exploreMapSection');

    let mapInitialized = false;
    let leafletMap = null;

    const storeData = <?= json_encode(array_map(function($st) use ($baseUrl) {
        return [
            'id'              => $st['id'],
            'name'            => $st['name'],
            'address'         => $st['address'],
            'lat'             => (float)($st['latitude'] ?? -6.9835),
            'lng'             => (float)($st['longitude'] ?? 107.8335),
            'rating'          => number_format((float)($st['rating'] ?? 5.0), 1),
            'is_open'         => (bool)$st['is_open'],
            'operating_hours' => $st['operating_hours'] ?? '08:00 - 22:00',
            'logo'            => asset_url($st['logo'] ?? null, 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=300&q=80'),
            'store_url'       => $baseUrl . '/stores/' . $st['id']
        ];
    }, $stores)) ?>;

    function initExploreMap() {
        if (mapInitialized) return;

        leafletMap = L.map('explore-full-map', { zoomControl: true }).setView([-6.9835, 107.8335], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(leafletMap);

        const foodSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 46" width="30" height="42">
          <defs>
            <linearGradient id="fsg" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#ef4444"/>
              <stop offset="100%" stop-color="#991b1b"/>
            </linearGradient>
          </defs>
          <path d="M16 2C9 2 3 8 3 15c0 10 13 29 13 29S29 25 29 15C29 8 23 2 16 2z" fill="url(#fsg)" stroke="white" stroke-width="2"/>
          <circle cx="16" cy="15" r="7" fill="white"/>
          <path d="M13 13 L13 18 M16 12 L16 18 M19 13 L19 18" stroke="#991b1b" stroke-width="1.5" stroke-linecap="round"/>
        </svg>`;

        const storeIcon = L.icon({
            iconUrl: 'data:image/svg+xml,' + encodeURIComponent(foodSvg),
            iconSize: [30, 42],
            iconAnchor: [15, 42],
            popupAnchor: [0, -42]
        });

        storeData.forEach(st => {
            if (st.lat && st.lng) {
                const statusBadge = st.is_open 
                    ? `<span style="color:#16a34a; font-weight:bold;">🟢 BUKA (${st.operating_hours})</span>` 
                    : `<span style="color:#dc2626; font-weight:bold;">🔴 TUTUP (${st.operating_hours})</span>`;

                const popupHtml = `
                    <div style="width: 180px; text-align: center; font-family: sans-serif;">
                        <img src="${st.logo}" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover; margin-bottom: 4px; border: 1px solid #cbd5e1;">
                        <h6 style="font-size: 11.5px; font-weight: 800; margin: 0 0 2px 0; color: #0f172a;">${st.name}</h6>
                        <div style="font-size: 9px; color: #64748b; margin-bottom: 4px;">${statusBadge} • ⭐ ${st.rating}</div>
                        <a href="${st.store_url}" class="btn btn-sm btn-danger rounded-pill w-100 text-white font-weight-bold" style="font-size: 9.5px; padding: 3px 0; text-decoration: none;">Lihat Menu Resto</a>
                    </div>
                `;

                L.marker([st.lat, st.lng], { icon: storeIcon })
                    .bindPopup(popupHtml)
                    .addTo(leafletMap);
            }
        });

        mapInitialized = true;
    }

    btnGrid.addEventListener('click', () => {
        btnGrid.classList.add('btn-dark', 'active');
        btnGrid.classList.remove('btn-light', 'text-dark');
        btnMap.classList.add('btn-light', 'text-dark');
        btnMap.classList.remove('btn-dark', 'active');

        secGrid.classList.remove('d-none');
        secMap.classList.add('d-none');
    });

    btnMap.addEventListener('click', () => {
        btnMap.classList.add('btn-dark', 'active');
        btnMap.classList.remove('btn-light', 'text-dark');
        btnGrid.classList.add('btn-light', 'text-dark');
        btnGrid.classList.remove('btn-dark', 'active');

        secMap.classList.remove('d-none');
        secGrid.classList.add('d-none');

        setTimeout(() => {
            initExploreMap();
            if (leafletMap) leafletMap.invalidateSize();
        }, 150);
    });
});
</script>
