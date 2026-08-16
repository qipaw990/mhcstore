<div class="p-3">
    <!-- Status Driver Online / Offline Switch Card -->
    <div class="p-3 bg-white rounded-4 border shadow-sm mb-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center <?= $driver['is_online'] ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>" style="width: 44px; height: 44px; font-size: 20px;">
                <i class="bi <?= $driver['is_online'] ? 'bi-broadcast-pin text-success' : 'bi-pause-circle' ?>"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-dark small">Status Kurir:</span>
                    <span class="badge rounded-pill <?= $driver['is_online'] ? 'bg-success text-white' : 'bg-secondary' ?>" style="font-size: 10px; font-weight: 700; letter-spacing: 0.3px;">
                        <?= $driver['is_online'] ? '● ONLINE (SIAP ANTAR)' : '● OFFLINE (ISTIRAHAT)' ?>
                    </span>
                </div>
                <div class="text-muted" style="font-size: 11px; margin-top: 2px;">
                    <?= $driver['is_online'] ? 'GPS aktif: Radar memindai orderan sekitar Cicalengka' : 'Geser saklar untuk mulai menerima pesanan' ?>
                </div>
            </div>
        </div>
        <label class="driver-toggle-switch m-0">
            <input type="checkbox" id="onlineSwitch" <?= $driver['is_online'] ? 'checked' : '' ?> onchange="toggleDriverStatus()">
            <span class="driver-toggle-slider"></span>
        </label>
    </div>

    <!-- Quick Stats Metric -->
    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="p-3 bg-white rounded-4 border shadow-sm h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted fw-semibold" style="font-size: 11px;">Dompet Driver</span>
                    <div class="rounded-2 p-1 d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 13px; background: rgba(238, 39, 55, 0.12); color: #EE2737;">
                        <i class="bi bi-wallet2"></i>
                    </div>
                </div>
                <div>
                    <div class="fw-extrabold fs-6" style="color: #EE2737; font-weight: 800;"><?= format_rupiah($wallet['balance'] ?? 0) ?></div>
                    <a href="<?= $baseUrl ?>/delivery/earnings" class="text-decoration-none text-muted" style="font-size: 10px; font-weight: 600;">
                        Rincian Saldo <i class="bi bi-chevron-right" style="font-size: 9px; color: #EE2737;"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="p-3 bg-white rounded-4 border shadow-sm h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted fw-semibold" style="font-size: 11px;">Pesanan Selesai</span>
                    <div class="rounded-2 bg-success-subtle text-success p-1 d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 13px;">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                </div>
                <div>
                    <div class="fw-bold fs-6 text-dark"><?= $driver['total_orders'] ?? 0 ?> <span class="fs-6 fw-normal text-muted">Order</span></div>
                    <span class="badge bg-success-subtle text-success rounded-pill px-2" style="font-size: 10px; font-weight: 700;">
                        Performa 100%
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Interactive Driver Radar & Navigation Map -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
        <div class="p-3 bg-white d-flex align-items-center justify-content-between border-bottom">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle text-white d-flex align-items-center justify-content-center shadow-xs" style="width: 28px; height: 28px; font-size: 14px; background: #EE2737;">
                    <i class="bi bi-radar"></i>
                </div>
                <div>
                    <h6 class="fw-bold small m-0 text-dark">Radar Peta GPS Cicalengka</h6>
                    <span class="text-muted" style="font-size: 10px;">Zona Pelayanan Cicalengka & Sekitarnya</span>
                </div>
            </div>
            <button onclick="centerDriverMap()" class="btn btn-outline-danger btn-sm rounded-pill py-1 px-3 fw-bold d-flex align-items-center gap-1" style="font-size: 11px; border-color: #EE2737; color: #EE2737;">
                <i class="bi bi-crosshair"></i> Posisi Saya
            </button>
        </div>
        <div id="driver-radar-map" style="width: 100%; height: 250px; z-index: 1;"></div>
    </div>

    <!-- Active Delivery Task (Step-by-Step Navigation: Store First, Then Customer) -->
    <?php if (!empty($active_order)): ?>
        <?php 
            $isPickedUp = in_array($active_order['order_status'], ['picked_up', 'on_the_way', 'delivered']);
            $storeLat = (float)($active_order['store_lat'] ?? -6.9835);
            $storeLng = (float)($active_order['store_lng'] ?? 107.8335);
            $custLat = (float)($active_order['delivery_address']['lat'] ?? -6.9855);
            $custLng = (float)($active_order['delivery_address']['lng'] ?? 107.8350);
            $storeGmapsUrl = "https://www.google.com/maps/dir/?api=1&destination={$storeLat},{$storeLng}&travelmode=two_wheeler";
            $custGmapsUrl = "https://www.google.com/maps/dir/?api=1&destination={$custLat},{$custLng}&travelmode=two_wheeler";
        ?>
        <div class="p-3 mb-4 rounded-4 shadow text-white position-relative overflow-hidden" style="background: linear-gradient(135deg, #EE2737 0%, #B91C1C 100%); border: 1px solid rgba(255,255,255,0.2); box-shadow: 0 8px 24px rgba(238,39,55,0.35) !important;">
            <!-- Header with Order Code & Stepper Badge -->
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div>
                    <?php if (!$isPickedUp): ?>
                        <span class="badge bg-warning text-dark fw-bold px-2.5 py-1 rounded-pill shadow-xs" style="font-size: 11px;">
                            <i class="bi bi-shop me-1 text-danger"></i> TAHAP 1: JEMPUT KE TOKO
                        </span>
                    <?php else: ?>
                        <span class="badge bg-success text-white fw-bold px-2.5 py-1 rounded-pill shadow-xs" style="font-size: 11px;">
                            <i class="bi bi-bicycle me-1"></i> TAHAP 2: ANTAR KE CUSTOMER
                        </span>
                    <?php endif; ?>
                </div>
                <span class="fw-bold" style="letter-spacing: 0.5px; font-size: 13px;">#<?= htmlspecialchars($active_order['order_code']) ?></span>
            </div>

            <!-- Two-Stage Delivery Stepper Progress Bar -->
            <div class="d-flex align-items-center gap-2 mb-3 bg-black bg-opacity-20 p-2 rounded-3" style="font-size: 11px;">
                <div class="d-flex align-items-center gap-1.5 flex-grow-1 <?= !$isPickedUp ? 'fw-bold text-white' : 'text-white-50' ?>">
                    <span class="rounded-circle d-flex align-items-center justify-content-center <?= !$isPickedUp ? 'bg-warning text-dark' : 'bg-success text-white' ?>" style="width: 20px; height: 20px; font-size: 10px; font-weight: 800;">
                        <?= !$isPickedUp ? '1' : '<i class="bi bi-check-lg"></i>' ?>
                    </span>
                    <span>1. Ke Toko/Resto</span>
                </div>
                <i class="bi bi-arrow-right text-white-50" style="font-size: 11px;"></i>
                <div class="d-flex align-items-center gap-1.5 flex-grow-1 <?= $isPickedUp ? 'fw-bold text-white' : 'text-white-50' ?>">
                    <span class="rounded-circle d-flex align-items-center justify-content-center <?= $isPickedUp ? 'bg-warning text-dark' : 'bg-white bg-opacity-25 text-white' ?>" style="width: 20px; height: 20px; font-size: 10px; font-weight: 800;">
                        2
                    </span>
                    <span>2. Ke Pelanggan</span>
                </div>
            </div>

            <!-- Dynamic Stepper Card Content -->
            <div class="bg-white text-dark p-3 rounded-4 shadow-sm mb-3">
                <?php if (!$isPickedUp): ?>
                    <!-- TAHAP 1: AMBIL PESANAN DI RESTO / TOKO -->
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge rounded-pill bg-danger-subtle text-danger px-2.5 py-1 fw-bold" style="font-size: 10px;">
                                <i class="bi bi-geo-alt-fill me-1"></i> LOKASI TOKO YANG HARUS DITUJU
                            </span>
                            <?php if (!empty($active_order['store_phone'])): ?>
                                <a href="https://wa.me/<?= preg_replace('/^0/', '62', $active_order['store_phone']) ?>" target="_blank" class="btn btn-outline-success btn-sm rounded-pill px-2.5 py-0.5 fw-bold" style="font-size: 11px;">
                                    <i class="bi bi-whatsapp me-1"></i> WA Toko
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex align-items-start gap-2.5 mb-2">
                            <div class="rounded-circle text-white d-flex align-items-center justify-content-center flex-shrink-0 shadow-xs" style="width: 34px; height: 34px; font-size: 16px; background: #EE2737;">
                                <i class="bi bi-shop"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars($active_order['store_name'] ?? 'Toko / Restoran Cicalengka') ?></div>
                                <div class="text-muted small" style="font-size: 12px; line-height: 1.4;"><?= htmlspecialchars($active_order['store_address'] ?? 'Pusat Kuliner Cicalengka') ?></div>
                            </div>
                        </div>

                        <!-- Big Store Google Maps Navigation Link Button -->
                        <a href="<?= $storeGmapsUrl ?>" target="_blank" class="btn text-white btn-sm w-100 fw-bold rounded-pill py-2.5 shadow-sm d-flex align-items-center justify-content-center gap-2 mb-3" style="background: #EE2737;">
                            <i class="bi bi-compass-fill" style="font-size: 16px;"></i>
                            <span>Buka Navigasi Maps ke Resto / Toko</span>
                        </a>

                        <!-- Secondary Info: Tujuan Pengantaran Selanjutnya (Pelanggan) -->
                        <div class="p-2.5 rounded-3 bg-light border d-flex align-items-start gap-2" style="font-size: 11px;">
                            <i class="bi bi-arrow-right-circle text-primary" style="font-size: 15px; margin-top: 1px;"></i>
                            <div>
                                <span class="text-muted fw-semibold">Tujuan Antar Berikutnya (Setelah Ambil):</span>
                                <div class="fw-bold text-dark mt-0.5"><?= htmlspecialchars($active_order['customer_name'] ?? 'Pelanggan') ?></div>
                                <div class="text-muted"><?= htmlspecialchars($active_order['delivery_address']['address'] ?? 'Cicalengka') ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Button Konfirmasi Sudah Ambil Menu / Barang -->
                    <button onclick="updateDeliveryStep(<?= $active_order['id'] ?>, 'picked_up')" class="btn btn-warning w-100 fw-bold py-2.5 text-dark rounded-pill shadow">
                        <i class="bi bi-box-seam-fill me-1"></i> Konfirmasi Sudah Ambil Menu / Barang
                    </button>

                <?php else: ?>
                    <!-- TAHAP 2: ANTAR PESANAN KE PELANGGAN -->
                    <div class="mb-3">
                        <!-- Toko sudah diambil banner -->
                        <div class="p-2 rounded-3 bg-success-subtle border border-success-subtle d-flex align-items-center justify-content-between mb-3" style="font-size: 11px;">
                            <div class="d-flex align-items-center gap-1.5 text-success fw-semibold">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Menu dari <b><?= htmlspecialchars($active_order['store_name'] ?? 'Toko') ?></b> sudah diambil</span>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge rounded-pill bg-success-subtle text-success px-2.5 py-1 fw-bold" style="font-size: 10px;">
                                <i class="bi bi-geo-alt-fill me-1"></i> LOKASI ANTAR PELANGGAN
                            </span>
                            <?php if (!empty($active_order['customer_phone'])): ?>
                                <a href="https://wa.me/<?= preg_replace('/^0/', '62', $active_order['customer_phone']) ?>" target="_blank" class="btn btn-success btn-sm rounded-pill px-2.5 py-0.5 fw-bold" style="font-size: 11px;">
                                    <i class="bi bi-whatsapp me-1"></i> Chat WA Pelanggan
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex align-items-start gap-2.5 mb-2">
                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center flex-shrink-0 shadow-xs" style="width: 34px; height: 34px; font-size: 16px;">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars($active_order['customer_name'] ?? 'Pelanggan') ?></div>
                                <div class="text-muted small" style="font-size: 12px; line-height: 1.4;"><?= htmlspecialchars($active_order['delivery_address']['address'] ?? 'Cicalengka') ?></div>
                            </div>
                        </div>

                        <!-- Big Customer Google Maps Navigation Link Button -->
                        <a href="<?= $custGmapsUrl ?>" target="_blank" class="btn btn-success btn-sm w-100 fw-bold rounded-pill py-2.5 shadow-sm d-flex align-items-center justify-content-center gap-2 mb-3">
                            <i class="bi bi-compass-fill" style="font-size: 16px;"></i>
                            <span>Buka Navigasi Maps ke Alamat Pelanggan</span>
                        </a>
                    </div>

                    <!-- Button Pesanan Sampai & Masukkan OTP -->
                    <button onclick="updateDeliveryStep(<?= $active_order['id'] ?>, 'delivered')" class="btn btn-success w-100 fw-bold py-2.5 rounded-pill shadow">
                        <i class="bi bi-shield-check me-1"></i> Pesanan Sampai & Masukkan OTP
                    </button>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Radar Incoming Orders in Cicalengka Zone -->
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h6 class="fw-bold small m-0 text-dark">
            <i class="bi bi-radar me-1" style="color: #EE2737;"></i> Radar Order Sekitar Cicalengka
        </h6>
        <?php if (empty($active_order) && $driver['is_online'] && !empty($available_orders)): ?>
            <span class="badge rounded-pill px-2.5 py-1 text-white" style="font-size: 10px; font-weight: 700; background: #EE2737;">
                <?= count($available_orders) ?> Order Siap
            </span>
        <?php endif; ?>
    </div>

    <?php if (!empty($active_order)): ?>
        <div class="p-3 bg-white rounded-4 border shadow-sm mb-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; background: rgba(238,39,55,0.12); color: #EE2737; font-size: 20px;">
                    <i class="bi bi-lock-fill"></i>
                </div>
                <div>
                    <div class="fw-bold small text-dark">Radar Order Baru Terkunci</div>
                    <div class="text-muted" style="font-size: 11px; line-height: 1.4;">
                        Anda sedang menjalankan pesanan <b>#<?= htmlspecialchars($active_order['order_code']) ?></b>. Selesaikan pengantaran pesanan saat ini terlebih dahulu sebelum mengambil pesanan lainnya.
                    </div>
                </div>
            </div>
        </div>
    <?php elseif (!$driver['is_online']): ?>
        <div class="p-4 bg-white rounded-4 border text-center shadow-sm">
            <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 48px; height: 48px; font-size: 22px;">
                <i class="bi bi-moon-stars"></i>
            </div>
            <div class="fw-bold small text-dark mb-1">Status Anda Saat Ini OFFLINE</div>
            <div class="text-muted small mb-3" style="font-size: 11px;">
                Aktifkan saklar status online di atas untuk mulai melihat pesanan masuk dari toko & restoran Cicalengka.
            </div>
            <button onclick="toggleDriverStatus()" class="btn text-white btn-sm fw-bold px-4 rounded-pill shadow-sm" style="background: #EE2737;">
                <i class="bi bi-power me-1"></i> Aktifkan Status Online
            </button>
        </div>
    <?php elseif (empty($available_orders)): ?>
        <div class="p-4 bg-white rounded-4 border text-center text-muted small shadow-sm">
            <div class="radar-scan-box">
                <i class="bi bi-broadcast fs-3" style="color: #EE2737;"></i>
            </div>
            <div class="fw-bold text-dark mb-1">Memindai orderan baru...</div>
            <div class="text-muted" style="font-size: 11px;">Radar aktif di area Cicalengka. Pesanan terdekat akan otomatis muncul di sini.</div>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($available_orders as $ord): ?>
                <div class="p-3 bg-white rounded-4 border shadow-sm">
                    <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                        <div class="d-flex align-items-center gap-1.5">
                            <span class="badge rounded-pill px-2.5 py-1 text-white" style="font-size: 10px; font-weight: 700; background: #EE2737;">
                                <i class="bi bi-box-seam me-1"></i> Order Masuk
                            </span>
                            <span class="text-muted" style="font-size: 11px;">#<?= htmlspecialchars($ord['order_code'] ?? $ord['id']) ?></span>
                        </div>
                        <div class="text-end">
                            <div class="text-muted" style="font-size: 10px; font-weight: 600;">Komisi Kurir:</div>
                            <span class="fw-bold text-success" style="font-size: 14px;">+ <?= format_rupiah((float)$ord['delivery_charge'] * 0.85) ?></span>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-2 mb-2">
                        <i class="bi bi-shop" style="font-size: 15px; margin-top: 1px; color: #EE2737;"></i>
                        <div>
                            <div class="fw-bold small text-dark"><?= htmlspecialchars($ord['store_name'] ?? 'Cicalengka Resto / Toko') ?></div>
                            <div class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($ord['store_address'] ?? 'Pusat Cicalengka') ?></div>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-2 mb-3">
                        <i class="bi bi-geo-alt-fill" style="font-size: 15px; margin-top: 1px; color: #EE2737;"></i>
                        <div>
                            <div class="fw-bold small text-dark">Antar ke:</div>
                            <div class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($ord['delivery_address']['address'] ?? 'Cicalengka') ?></div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <span class="small text-muted fw-semibold" style="font-size: 11px;">
                            <i class="bi bi-signpost-2 me-1" style="color: #EE2737;"></i> Est. Jarak: <?= $ord['distance_km'] ?? '1.5' ?> Km
                        </span>
                        <button onclick="acceptDriverOrder(<?= $ord['id'] ?>)" class="btn text-white btn-sm fw-bold px-4 rounded-pill shadow-sm" style="background: #EE2737;">
                            <i class="bi bi-check-lg me-1"></i> Ambil Order
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
window.HAS_ACTIVE_ORDER = <?= !empty($active_order) ? 'true' : 'false' ?>;
let dRadarMap, myDriverMarker;
let driverLat = <?= (float)($driver['latitude'] ?? -6.9840) ?>;
let driverLng = <?= (float)($driver['longitude'] ?? 107.8340) ?>;

function initDriverRadarMap() {
    if (!document.getElementById('driver-radar-map')) return;

    dRadarMap = L.map('driver-radar-map', { 
        zoomControl: false,
        attributionControl: false
    }).setView([driverLat, driverLng], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19
    }).addTo(dRadarMap);

    // Modern Driver Animated Marker Icon (CicalengkaGO Red)
    const myIcon = L.divIcon({
        className: 'driver-radar-marker',
        html: `<div class="driver-marker-pulse-wrapper">
                 <div class="pulse-ring"></div>
                 <div class="pulse-ring-delayed"></div>
                 <div class="marker-core">
                   <i class="bi bi-bicycle"></i>
                 </div>
               </div>`,
        iconSize: [44, 44],
        iconAnchor: [22, 22],
        popupAnchor: [0, -20]
    });

    myDriverMarker = L.marker([driverLat, driverLng], { icon: myIcon })
        .bindPopup('<div class="text-center"><b>Lokasi Anda (Driver CicalengkaGO)</b><br><span class="badge bg-success-subtle text-success rounded-pill px-2 py-0.5 mt-1" style="font-size:10px;"><i class="bi bi-broadcast me-1"></i>GPS Aktif</span></div>')
        .addTo(dRadarMap);

    <?php if (!empty($active_order)): ?>
        // Plot Active Order Details (Step-by-step)
        const isPickedUp = <?= $isPickedUp ? 'true' : 'false' ?>;
        const actStoreLat = <?= (float)($active_order['store_lat'] ?? -6.9835) ?>;
        const actStoreLng = <?= (float)($active_order['store_lng'] ?? 107.8335) ?>;
        const actDestLat = <?= (float)($active_order['delivery_address']['lat'] ?? -6.9855) ?>;
        const actDestLng = <?= (float)($active_order['delivery_address']['lng'] ?? 107.8350) ?>;

        const storeIcon = L.divIcon({
            className: 'custom-pin',
            html: '<div style="background:#EE2737;color:white;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2.5px solid white;box-shadow:0 3px 8px rgba(0,0,0,0.3);"><i class="bi bi-shop"></i></div>',
            iconSize: [34, 34],
            iconAnchor: [17, 17],
            popupAnchor: [0, -17]
        });

        const custIcon = L.divIcon({
            className: 'custom-pin',
            html: '<div style="background:#10b981;color:white;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2.5px solid white;box-shadow:0 3px 8px rgba(0,0,0,0.3);"><i class="bi bi-geo-alt-fill"></i></div>',
            iconSize: [34, 34],
            iconAnchor: [17, 17],
            popupAnchor: [0, -17]
        });

        const sMarker = L.marker([actStoreLat, actStoreLng], { icon: storeIcon })
            .addTo(dRadarMap)
            .bindPopup("<b><?= htmlspecialchars($active_order['store_name'] ?? 'Penjemputan') ?></b><br><small class='text-muted'>Titik Ambil Barang</small><br><a href='<?= $storeGmapsUrl ?>' target='_blank' class='btn btn-danger btn-sm text-white w-100 mt-2 py-0.5 rounded-pill' style='font-size:10px;'>Google Maps Toko</a>");

        const cMarker = L.marker([actDestLat, actDestLng], { icon: custIcon })
            .addTo(dRadarMap)
            .bindPopup("<b><?= htmlspecialchars($active_order['customer_name'] ?? 'Tujuan') ?></b><br><small class='text-muted'>Titik Antar Pelanggan</small><br><a href='<?= $custGmapsUrl ?>' target='_blank' class='btn btn-success btn-sm text-white w-100 mt-2 py-0.5 rounded-pill' style='font-size:10px;'>Google Maps Pelanggan</a>");

        if (!isPickedUp) {
            // Tahap 1: Driver -> Toko
            const routeToStore = L.polyline([[driverLat, driverLng], [actStoreLat, actStoreLng]], {
                color: '#EE2737',
                weight: 5,
                dashArray: '6, 8'
            }).addTo(dRadarMap);
            dRadarMap.fitBounds(routeToStore.getBounds(), { padding: [40, 40] });
            sMarker.openPopup();
        } else {
            // Tahap 2: Driver -> Customer
            const routeToCust = L.polyline([[driverLat, driverLng], [actDestLat, actDestLng]], {
                color: '#10b981',
                weight: 5,
                dashArray: '6, 8'
            }).addTo(dRadarMap);
            dRadarMap.fitBounds(routeToCust.getBounds(), { padding: [40, 40] });
            cMarker.openPopup();
        }
    <?php else: ?>
        // Plot Available Incoming Orders
        <?php foreach ($available_orders as $ord): ?>
            <?php 
                $sLat = (float)($ord['store_lat'] ?? -6.9835);
                $sLng = (float)($ord['store_lng'] ?? 107.8335);
                $oId = (int)$ord['id'];
                $sName = addslashes($ord['store_name'] ?? 'Toko / Resto');
                $dAddr = addslashes($ord['delivery_address']['address'] ?? 'Cicalengka');
            ?>
            (function() {
                const oIcon = L.divIcon({
                    className: 'custom-pin',
                    html: '<div style="background:#F7A800;color:white;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2.5px solid white;box-shadow:0 3px 8px rgba(0,0,0,0.3);"><i class="bi bi-box-seam"></i></div>',
                    iconSize: [32, 32],
                    iconAnchor: [16, 16],
                    popupAnchor: [0, -16]
                });

                L.marker([<?= $sLat ?>, <?= $sLng ?>], { icon: oIcon })
                    .addTo(dRadarMap)
                    .bindPopup(`<b><?= $sName ?></b><br><small class="text-muted">Antar ke: <?= $dAddr ?></small><br><button onclick="acceptDriverOrder(<?= $oId ?>)" class="btn btn-sm w-100 mt-2 py-1 fw-bold rounded-pill text-white" style="background:#EE2737;">Ambil Order Ini</button>`);
            })();
        <?php endforeach; ?>
    <?php endif; ?>

    // Send GPS update to server
    async function syncDriverLocation(lat, lng) {
        try {
            const fd = new FormData();
            fd.append('lat', lat);
            fd.append('lng', lng);
            await fetch(window.BASE_URL + '/delivery/update-location', {
                method: 'POST',
                body: fd
            });
        } catch (e) {
            console.warn('GPS sync error:', e);
        }
    }

    // Update GPS live via HTML5 Geolocation API
    if ('geolocation' in navigator) {
        navigator.geolocation.watchPosition((pos) => {
            driverLat = pos.coords.latitude;
            driverLng = pos.coords.longitude;
            if (myDriverMarker) {
                myDriverMarker.setLatLng([driverLat, driverLng]);
            }
            syncDriverLocation(driverLat, driverLng);
        }, null, { enableHighAccuracy: true, maximumAge: 3000 });
    }
}

function centerDriverMap() {
    if (dRadarMap) {
        dRadarMap.setView([driverLat, driverLng], 15);
        if (myDriverMarker) {
            myDriverMarker.openPopup();
        }
    }
}

document.addEventListener('DOMContentLoaded', initDriverRadarMap);
</script>
