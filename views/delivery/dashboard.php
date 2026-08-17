<div class="p-3 driver-page-container">
    <!-- Status Driver Online / Offline Switch Card -->
    <div class="p-3.5 bg-white border shadow-2xs mb-3 d-flex align-items-center justify-content-between" style="border-radius: 16px; border-color: #E2E8F0 !important;">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center <?= $driver['is_online'] ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>" style="width: 44px; height: 44px; font-size: 20px;">
                <i class="bi <?= $driver['is_online'] ? 'bi-broadcast-pin text-success' : 'bi-pause-circle' ?>"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-dark" style="font-size: 12px;">Status Kurir:</span>
                    <span class="badge rounded-pill <?= $driver['is_online'] ? 'bg-success text-white' : 'bg-secondary' ?>" style="font-size: 9.5px; font-weight: 700; letter-spacing: 0.3px;">
                        <?= $driver['is_online'] ? '● ONLINE (SIAP ANTAR)' : '● OFFLINE (ISTIRAHAT)' ?>
                    </span>
                </div>
                <div class="text-muted" style="font-size: 10.5px; margin-top: 2px;">
                    <?= $driver['is_online'] ? 'GPS aktif: Radar memindai orderan sekitar Cicalengka' : 'Geser saklar untuk mulai menerima pesanan' ?>
                </div>
            </div>
        </div>
        <label class="driver-toggle-switch m-0">
            <input type="checkbox" id="onlineSwitch" name="online_status" <?= $driver['is_online'] ? 'checked' : '' ?> onchange="toggleDriverStatus()">
            <span class="driver-toggle-slider"></span>
        </label>
    </div>

    <!-- Quick Stats Metric -->
    <div class="row g-2.5 mb-3">
        <div class="col-6">
            <div class="p-3.5 bg-white border shadow-2xs h-100 d-flex flex-column justify-content-between" style="border-radius: 16px; border-color: #E2E8F0 !important;">
                <div class="d-flex align-items-center justify-content-between mb-1.5">
                    <span class="text-muted fw-bold" style="font-size: 11px;">Dompet Driver</span>
                    <div class="rounded-circle p-1 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 14px; background: rgba(238, 39, 55, 0.12); color: #EE2737;">
                        <i class="bi bi-wallet2"></i>
                    </div>
                </div>
                <div>
                    <div id="driverWalletBalanceText" class="fw-bold" style="color: #EE2737; font-size: 16px; letter-spacing: -0.2px;"><?= format_rupiah($wallet['balance'] ?? 0) ?></div>
                    <a href="<?= $baseUrl ?>/delivery/earnings" class="text-decoration-none text-muted" style="font-size: 10px; font-weight: 600;">
                        Rincian Saldo <i class="bi bi-chevron-right" style="font-size: 9px; color: #EE2737;"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="p-3.5 bg-white border shadow-2xs h-100 d-flex flex-column justify-content-between" style="border-radius: 16px; border-color: #E2E8F0 !important;">
                <div class="d-flex align-items-center justify-content-between mb-1.5">
                    <span class="text-muted fw-bold" style="font-size: 11px;">Pesanan Selesai</span>
                    <div class="rounded-circle bg-success-subtle text-success p-1 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 14px;">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                </div>
                <div>
                    <div class="fw-bold text-dark" style="font-size: 16px;"><span id="driverTotalOrdersText"><?= $driver['total_orders'] ?? 0 ?></span> <span class="fw-normal text-muted" style="font-size: 11px;">Order</span></div>
                    <span class="badge bg-success-subtle text-success rounded-pill px-2 py-0.5" style="font-size: 9.5px; font-weight: 700;">
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
    <div id="driverActiveOrderSection">
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
                                <a href="tel:<?= htmlspecialchars($active_order['store_phone']) ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-2.5 py-0.5 fw-bold" style="font-size: 11px;">
                                    <i class="bi bi-telephone me-1"></i> Telp Toko
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
                        <div class="p-2.5 rounded-3 bg-light border d-flex flex-column gap-2 mb-3" style="font-size: 11px;">
                            <div class="d-flex align-items-start gap-2">
                                <i class="bi bi-arrow-right-circle text-primary" style="font-size: 15px; margin-top: 1px;"></i>
                                <div class="flex-grow-1">
                                    <span class="text-muted fw-semibold">Tujuan Antar Berikutnya (Setelah Ambil):</span>
                                    <div class="fw-bold text-dark mt-0.5"><?= htmlspecialchars($active_order['customer_name'] ?? 'Pelanggan') ?></div>
                                    <div class="text-muted"><?= htmlspecialchars($active_order['delivery_address']['address'] ?? 'Cicalengka') ?></div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end pt-1 border-top">
                                <button type="button" onclick="openDriverChatModal('<?= htmlspecialchars($active_order['order_code']) ?>')" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1 fw-bold position-relative d-flex align-items-center gap-1" style="font-size: 11px;">
                                    <i class="bi bi-chat-dots-fill"></i> Chat Pelanggan
                                    <span id="driverChatUnreadDot1" class="ccg-unread-dot d-none"></span>
                                </button>
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
                            <div class="d-flex gap-1.5 align-items-center">
                                <button type="button" onclick="openDriverChatModal('<?= htmlspecialchars($active_order['order_code']) ?>')" class="btn btn-danger btn-sm rounded-pill px-3 py-1 fw-bold text-white position-relative shadow-xs d-flex align-items-center gap-1" style="background:#EE2737; font-size: 11px;">
                                    <i class="bi bi-chat-dots-fill"></i> Chat App
                                    <span id="driverChatUnreadDot2" class="ccg-unread-dot d-none"></span>
                                </button>
                                <?php if (!empty($active_order['customer_phone'])): ?>
                                    <a href="tel:<?= htmlspecialchars($active_order['customer_phone']) ?>" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;" title="Telepon Pelanggan">
                                        <i class="bi bi-telephone-fill" style="font-size: 11px;"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
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
    </div>

    <!-- Radar Incoming Orders in Cicalengka Zone -->
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h6 class="fw-bold small m-0 text-dark">
            <i class="bi bi-radar me-1" style="color: #EE2737;"></i> Radar Order Sekitar Cicalengka
        </h6>
        <span id="radarOrderCountBadge" class="badge rounded-pill px-2.5 py-1 text-white <?= (empty($active_order) && $driver['is_online'] && !empty($available_orders)) ? '' : 'd-none' ?>" style="font-size: 10px; font-weight: 700; background: #EE2737;">
            <?= count($available_orders) ?> Order Siap
        </span>
    </div>

    <div id="driverRadarOrderSection">
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
        <div class="d-flex flex-column gap-3" id="availableOrdersList">
            <?php foreach ($available_orders as $ord): ?>
                <div class="p-3 bg-white rounded-4 border shadow-sm order-incoming-card" id="avail-order-<?= $ord['id'] ?>">
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
</div>
</div>

<script>
window.HAS_ACTIVE_ORDER = <?= !empty($active_order) ? 'true' : 'false' ?>;
window.dRadarMap = null;
window.myDriverMarker = null;
window.activeRouteLine = null;
window.actStoreMarker = null;
window.actCustMarker = null;

window.actStoreLat = <?= (float)($active_order['store_lat'] ?? -6.9835) ?>;
window.actStoreLng = <?= (float)($active_order['store_lng'] ?? 107.8335) ?>;
window.actDestLat = <?= (float)($active_order['delivery_address']['lat'] ?? -6.9855) ?>;
window.actDestLng = <?= (float)($active_order['delivery_address']['lng'] ?? 107.8350) ?>;
window.isPickedUp = <?= (!empty($active_order) && in_array($active_order['order_status'], ['picked_up', 'on_the_way', 'delivered'])) ? 'true' : 'false' ?>;

window.driverLat = <?= (float)($driver['current_latitude'] ?? $driver['latitude'] ?? -6.9840) ?>;
window.driverLng = <?= (float)($driver['current_longitude'] ?? $driver['longitude'] ?? 107.8340) ?>;

window.updateDriverLiveLocation = function(lat, lng, recenter = false) {
    window.driverLat = lat;
    window.driverLng = lng;

    if (window.myDriverMarker) {
        window.myDriverMarker.setLatLng([lat, lng]);
    }

    if (window.HAS_ACTIVE_ORDER && window.dRadarMap) {
        const targetLat = window.isPickedUp ? window.actDestLat : window.actStoreLat;
        const targetLng = window.isPickedUp ? window.actDestLng : window.actStoreLng;

        if (targetLat && targetLng && targetLat !== 0 && targetLng !== 0) {
            if (window.activeRouteLine) {
                window.activeRouteLine.setLatLngs([[lat, lng], [targetLat, targetLng]]);
            } else {
                window.activeRouteLine = L.polyline([[lat, lng], [targetLat, targetLng]], {
                    color: window.isPickedUp ? '#10b981' : '#EE2737',
                    weight: 5,
                    dashArray: '6, 8'
                }).addTo(window.dRadarMap);
            }
            if (recenter) {
                const bounds = L.latLngBounds([[lat, lng], [targetLat, targetLng]]);
                window.dRadarMap.fitBounds(bounds, { padding: [40, 40] });
            }
        }
    } else if (recenter && window.dRadarMap) {
        window.dRadarMap.setView([lat, lng], 15);
    }

    // Persist to server
    const fd = new FormData();
    fd.append('lat', lat);
    fd.append('lng', lng);
    fetch((window.BASE_URL || '') + '/delivery/update-location', {
        method: 'POST',
        body: fd
    }).catch(() => {});
};

function initDriverRadarMap() {
    if (!document.getElementById('driver-radar-map')) return;

    window.dRadarMap = L.map('driver-radar-map', { 
        zoomControl: false,
        attributionControl: false
    }).setView([window.driverLat, window.driverLng], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19
    }).addTo(window.dRadarMap);

    // Driver Location Pin (Bulat Merah CicalengkaGO + Motor)
    const myIcon = L.divIcon({
        className: 'custom-pin',
        html: '<div style="background:#101820;color:#EE2737;width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid #ffffff;box-shadow:0 4px 14px rgba(16,24,32,0.45);font-size:18px;"><i class="bi bi-bicycle"></i></div>',
        iconSize: [38, 38],
        iconAnchor: [19, 19],
        popupAnchor: [0, -19]
    });

    window.myDriverMarker = L.marker([window.driverLat, window.driverLng], { icon: myIcon })
        .bindPopup('<div style="min-width: 140px; text-align: center;"><b>Lokasi Anda (Driver)</b><br><span class="badge bg-success-subtle text-success rounded-pill px-2 py-0.5 mt-1" style="font-size:10px;"><i class="bi bi-broadcast me-1"></i>GPS Aktif</span></div>')
        .addTo(window.dRadarMap);

    <?php if (!empty($active_order)): ?>
        // Plot Active Order Details (Step-by-step)
        const isPickedUp = window.isPickedUp;
        const actStoreLat = window.actStoreLat;
        const actStoreLng = window.actStoreLng;
        const actDestLat = window.actDestLat;
        const actDestLng = window.actDestLng;

        const storeIcon = L.divIcon({
            className: 'custom-pin',
            html: '<div style="background:#D32F2F;color:white;width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid #ffffff;box-shadow:0 4px 14px rgba(211,47,47,0.45);font-size:18px;"><i class="bi bi-shop"></i></div>',
            iconSize: [38, 38],
            iconAnchor: [19, 19],
            popupAnchor: [0, -19]
        });

        const custIcon = L.divIcon({
            className: 'custom-pin',
            html: '<div style="background:#00A082;color:white;width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid #ffffff;box-shadow:0 4px 14px rgba(0,160,130,0.45);font-size:18px;"><i class="bi bi-geo-alt-fill"></i></div>',
            iconSize: [38, 38],
            iconAnchor: [19, 19],
            popupAnchor: [0, -19]
        });

        window.actStoreMarker = L.marker([actStoreLat, actStoreLng], { icon: storeIcon })
            .addTo(window.dRadarMap)
            .bindPopup("<b><?= htmlspecialchars($active_order['store_name'] ?? 'Penjemputan') ?></b><br><small class='text-muted'>Titik Ambil Barang</small><br><a href='<?= $storeGmapsUrl ?>' target='_blank' class='btn btn-danger btn-sm text-white w-100 mt-2 py-0.5 rounded-pill' style='font-size:10px;'>Google Maps Toko</a>");

        window.actCustMarker = L.marker([actDestLat, actDestLng], { icon: custIcon })
            .addTo(window.dRadarMap)
            .bindPopup("<b><?= htmlspecialchars($active_order['customer_name'] ?? 'Tujuan') ?></b><br><small class='text-muted'>Titik Antar Pelanggan</small><br><a href='<?= $custGmapsUrl ?>' target='_blank' class='btn btn-success btn-sm text-white w-100 mt-2 py-0.5 rounded-pill' style='font-size:10px;'>Google Maps Pelanggan</a>");

        if (!isPickedUp) {
            // Tahap 1: Driver -> Toko
            window.activeRouteLine = L.polyline([[window.driverLat, window.driverLng], [actStoreLat, actStoreLng]], {
                color: '#EE2737',
                weight: 5,
                dashArray: '6, 8'
            }).addTo(window.dRadarMap);
            window.dRadarMap.fitBounds(window.activeRouteLine.getBounds(), { padding: [40, 40] });
            window.actStoreMarker.openPopup();
        } else {
            // Tahap 2: Driver -> Customer
            window.activeRouteLine = L.polyline([[window.driverLat, window.driverLng], [actDestLat, actDestLng]], {
                color: '#10b981',
                weight: 5,
                dashArray: '6, 8'
            }).addTo(window.dRadarMap);
            window.dRadarMap.fitBounds(window.activeRouteLine.getBounds(), { padding: [40, 40] });
            window.actCustMarker.openPopup();
        }
    <?php else: ?>
        // Plot Available Incoming Orders (Red Store Badges like Customer View)
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
                    html: '<div style="background:#D32F2F;color:white;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid #ffffff;box-shadow:0 4px 14px rgba(211,47,47,0.45);font-size:16px;"><i class="bi bi-shop"></i></div>',
                    iconSize: [36, 36],
                    iconAnchor: [18, 18],
                    popupAnchor: [0, -18]
                });

                L.marker([<?= $sLat ?>, <?= $sLng ?>], { icon: oIcon })
                    .addTo(window.dRadarMap)
                    .bindPopup(`<b><?= $sName ?></b><br><small class="text-muted">Antar ke: <?= $dAddr ?></small><br><button onclick="acceptDriverOrder(<?= $oId ?>)" class="btn btn-sm w-100 mt-2 py-1 fw-bold rounded-pill text-white" style="background:#EE2737;">Ambil Order Ini</button>`);
            })();
        <?php endforeach; ?>
    <?php endif; ?>

    // Immediately fetch device physical GPS
    if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition((pos) => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            window.updateDriverLiveLocation(lat, lng, true);
        }, (err) => {
            console.warn('Initial driver GPS error:', err);
        }, {
            enableHighAccuracy: true,
            timeout: 6000
        });
    }
}

function centerDriverMap() {
    if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition((pos) => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            window.updateDriverLiveLocation(lat, lng, true);
            if (window.myDriverMarker) {
                window.myDriverMarker.openPopup();
            }
        }, () => {
            if (window.dRadarMap) {
                window.dRadarMap.setView([window.driverLat, window.driverLng], 15);
                if (window.myDriverMarker) window.myDriverMarker.openPopup();
            }
        }, { enableHighAccuracy: true });
    } else if (window.dRadarMap) {
        window.dRadarMap.setView([window.driverLat, window.driverLng], 15);
        if (window.myDriverMarker) {
            window.myDriverMarker.openPopup();
        }
    }
}

if (typeof window.acceptDriverOrder === 'undefined') {
    window.acceptDriverOrder = async function(orderId) {
        if (typeof Swal !== 'undefined') {
            const confirmRes = await Swal.fire({
                title: 'Ambil Pesanan Ini?',
                text: 'Anda akan ditugaskan mengantar pesanan ini ke pelanggan.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#EE2737',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Ambil Sekarang!',
                cancelButtonText: 'Batal'
            });
            if (!confirmRes.isConfirmed) return;
        } else {
            if (!confirm('Ambil pesanan ini untuk diantar sekarang?')) return;
        }

        try {
            const fd = new FormData();
            fd.append('order_id', orderId);
            const res = await fetch(window.BASE_URL + '/delivery/accept-order', {
                method: 'POST',
                body: fd
            });
            const json = await res.json();
            if (json.success) {
                if (typeof Swal !== 'undefined') {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Order Diambil!',
                        text: json.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
                window.location.reload();
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: json.message || 'Tidak dapat mengambil pesanan.',
                        confirmButtonColor: '#EE2737'
                    });
                } else {
                    alert(json.message || 'Gagal mengambil order');
                }
            }
        } catch (e) {
            console.error('Accept order error:', e);
        }
    };
}

document.addEventListener('DOMContentLoaded', initDriverRadarMap);
</script>

<!-- CicalengkaGO In-App Chat Modal (Driver) -->
<div id="driverChatModal" class="ccg-chat-modal" onclick="handleDriverChatBackdrop(event)">
    <div class="ccg-chat-card" onclick="event.stopPropagation()">
        <!-- Chat Header -->
        <div class="ccg-chat-header">
            <div class="d-flex align-items-center gap-2.5">
                <img id="dChatPartnerAvatar" src="<?= $baseUrl ?>/assets/images/users/customer.png" alt="Customer" class="ccg-chat-avatar" style="border-color: #10B981;">
                <div>
                    <div class="d-flex align-items-center gap-1.5">
                        <h6 id="dChatPartnerName" class="fw-bold m-0 text-dark small">Pelanggan</h6>
                        <span class="badge bg-success-subtle text-success" style="font-size: 9px;"><i class="bi bi-person-fill me-1"></i>Pelanggan</span>
                    </div>
                    <div id="dChatPartnerSub" class="text-muted" style="font-size: 11px;">
                        Tujuan Pengantaran
                    </div>
                </div>
            </div>
            <button type="button" class="btn-close" onclick="closeDriverChatModal()" aria-label="Close"></button>
        </div>

        <!-- Chat Message Stream -->
        <div id="dChatBody" class="ccg-chat-body">
            <div class="text-center py-4 text-muted small" id="dChatLoading">
                <div class="spinner-border spinner-border-sm text-danger me-2" role="status"></div> Memuat obrolan...
            </div>
        </div>

        <!-- Quick Reply Chips for Driver -->
        <div class="ccg-chat-quick-chips">
            <button type="button" class="ccg-chip-btn" onclick="sendDriverQuickReply('Halo kak, saya sedang menuju resto/toko ya 👍')">🛵 Menuju resto</button>
            <button type="button" class="ccg-chip-btn" onclick="sendDriverQuickReply('Pesanan sudah saya ambil, otw ke lokasi kakak 💨')">📦 Pesanan diambil, otw</button>
            <button type="button" class="ccg-chip-btn" onclick="sendDriverQuickReply('Saya sudah sampai di depan ya kak/bu/pak 📍')">🏠 Sudah sampai di depan</button>
            <button type="button" class="ccg-chip-btn" onclick="sendDriverQuickReply('Boleh minta patokan atau warna pagar rumahnya kak? 🙏')">🔍 Minta patokan rumah</button>
        </div>

        <!-- Chat Input Bar -->
        <form id="dChatForm" class="ccg-chat-input-bar no-preloader" onsubmit="handleSendDriverChat(event)">
            <input type="text" id="dChatInput" name="message" class="ccg-chat-input" placeholder="Ketik pesan untuk pelanggan..." autocomplete="off" maxlength="500">
            <button type="submit" id="btnSendDriverChat" class="ccg-chat-send-btn" title="Kirim">
                <i class="bi bi-send-fill"></i>
            </button>
        </form>
    </div>
</div>

<script>
let currentDriverChatOrderCode = '<?= !empty($active_order) ? htmlspecialchars($active_order['order_code']) : '' ?>';
let driverChatPollingTimer = null;
let isDriverChatOpen = false;

function openDriverChatModal(orderCode) {
    if (orderCode) currentDriverChatOrderCode = orderCode;
    if (!currentDriverChatOrderCode) {
        Swal.fire('Info', 'Tidak ada pesanan aktif saat ini.', 'info');
        return;
    }

    const modal = document.getElementById('driverChatModal');
    if (!modal) return;
    modal.classList.add('show');
    isDriverChatOpen = true;
    document.body.style.overflow = 'hidden';

    // Hide unread dots
    const d1 = document.getElementById('driverChatUnreadDot1');
    const d2 = document.getElementById('driverChatUnreadDot2');
    if (d1) d1.classList.add('d-none');
    if (d2) d2.classList.add('d-none');

    // Focus input
    setTimeout(() => {
        const inp = document.getElementById('dChatInput');
        if (inp) inp.focus();
    }, 300);

    fetchDriverChatMessages(true);

    if (driverChatPollingTimer) clearInterval(driverChatPollingTimer);
    driverChatPollingTimer = setInterval(() => fetchDriverChatMessages(false), 2500);
}

function closeDriverChatModal() {
    const modal = document.getElementById('driverChatModal');
    if (!modal) return;
    modal.classList.remove('show');
    isDriverChatOpen = false;
    document.body.style.overflow = '';

    if (driverChatPollingTimer) {
        clearInterval(driverChatPollingTimer);
        driverChatPollingTimer = null;
    }
}

function handleDriverChatBackdrop(e) {
    if (e.target.id === 'driverChatModal') {
        closeDriverChatModal();
    }
}

async function fetchDriverChatMessages(isFirstLoad = false) {
    if (!currentDriverChatOrderCode) return;
    try {
        const res = await fetch(window.BASE_URL + `/chats/messages?order_code=${currentDriverChatOrderCode}&since_id=0&mark_read=1`);
        const result = await res.json();
        if (!result.success) return;

        const data = result.data;
        const chatBody = document.getElementById('dChatBody');
        if (!chatBody) return;

        if (data.partner) {
            const pName = document.getElementById('dChatPartnerName');
            const pAvatar = document.getElementById('dChatPartnerAvatar');
            if (pName && data.partner.name) pName.textContent = data.partner.name;
            if (pAvatar && data.partner.avatar) pAvatar.src = window.BASE_URL + '/' + data.partner.avatar;
        }

        const messages = data.messages || [];
        if (messages.length === 0) {
            chatBody.innerHTML = `
                <div class="text-center py-5 text-muted small">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 48px; height: 48px;">
                        <i class="bi bi-chat-heart text-danger fs-4"></i>
                    </div>
                    <div class="fw-bold text-dark">Obrolan Pelanggan</div>
                    <div>Gunakan tombol pesan cepat di bawah atau ketik pesan untuk memberi kabar ke pelanggan.</div>
                </div>`;
            return;
        }

        let html = '';
        const currentUserId = parseInt(data.user_id) || 0;
        const custUserId = parseInt(data.cust_user_id) || 0;

        messages.forEach(msg => {
            const msgSenderId = parseInt(msg.sender_id) || 0;
            // Driver: message is outgoing if sent by driver (msgSenderId !== custUserId)
            const isOutgoing = (currentUserId > 0 && msgSenderId === currentUserId) ||
                               (custUserId > 0 && msgSenderId !== custUserId);

            const rowClass = isOutgoing ? 'outgoing' : 'incoming';
            const checkIcon = isOutgoing ? `<i class="bi bi-check2-all ${msg.is_read ? 'text-primary' : ''}"></i>` : '';

            html += `
                <div class="ccg-chat-row ${rowClass}">
                    <div class="ccg-chat-bubble">
                        <span>${escapeHtml(msg.message)}</span>
                        <span class="ccg-chat-time">
                            ${msg.time_formatted || ''} ${checkIcon}
                        </span>
                    </div>
                </div>
            `;
        });

        const shouldScroll = isFirstLoad || (chatBody.scrollTop + chatBody.clientHeight >= chatBody.scrollHeight - 120);
        chatBody.innerHTML = html;

        if (shouldScroll) {
            chatBody.scrollTop = chatBody.scrollHeight;
        }
    } catch(err) {
        console.warn('Driver chat fetch error:', err);
    }
}

async function handleSendDriverChat(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    if (window.hidePreloader) window.hidePreloader();

    const input = document.getElementById('dChatInput');
    const btn = document.getElementById('btnSendDriverChat');
    const message = input.value.trim();
    if (!message || !currentDriverChatOrderCode) return;

    // Optimistic UI preview
    const chatBody = document.getElementById('dChatBody');
    let tempBubbleEl = null;
    if (chatBody) {
        const now = new Date();
        const timeStr = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
        const emptyState = chatBody.querySelector('.text-center.py-5');
        if (emptyState) emptyState.remove();

        const tempBubbleHtml = `
            <div class="ccg-chat-row outgoing ccg-temp-bubble" style="opacity: 0.85;">
                <div class="ccg-chat-bubble">
                    <span>${escapeHtml(message)}</span>
                    <span class="ccg-chat-time">
                        ${timeStr} <i class="bi bi-clock"></i>
                    </span>
                </div>
            </div>
        `;
        chatBody.insertAdjacentHTML('beforeend', tempBubbleHtml);
        tempBubbleEl = chatBody.querySelector('.ccg-temp-bubble');
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    input.value = '';
    btn.disabled = true;

    try {
        const fd = new FormData();
        fd.append('order_code', currentDriverChatOrderCode);
        fd.append('message', message);

        const res = await fetch(window.BASE_URL + '/chats/send', {
            method: 'POST',
            body: fd
        });

        const result = await res.json();
        if (result.success) {
            await fetchDriverChatMessages(true);
        } else {
            if (tempBubbleEl) tempBubbleEl.remove();
            input.value = message;
            Swal.fire({
                icon: 'warning',
                title: 'Pesan Tidak Terkirim',
                text: result.message || 'Tidak dapat mengirim pesan',
                confirmButtonColor: '#EE2737'
            });
        }
    } catch(err) {
        console.error('Driver send error:', err);
        if (tempBubbleEl) tempBubbleEl.remove();
        input.value = message;
        Swal.fire({
            icon: 'error',
            title: 'Koneksi Terputus',
            text: 'Gagal terhubung ke server',
            confirmButtonColor: '#EE2737'
        });
    } finally {
        btn.disabled = false;
        input.focus();
    }
}

function sendDriverQuickReply(text) {
    const input = document.getElementById('dChatInput');
    if (input) {
        input.value = text;
        handleSendDriverChat(null);
    }
}

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Background driver unread polling
<?php if (!empty($active_order)): ?>
setInterval(async () => {
    if (isDriverChatOpen) return;
    try {
        const res = await fetch(window.BASE_URL + `/chats/unread-count?order_code=<?= htmlspecialchars($active_order['order_code']) ?>`);
        const result = await res.json();
        if (result.success && result.data && result.data.unread_count > 0) {
            const d1 = document.getElementById('driverChatUnreadDot1');
            const d2 = document.getElementById('driverChatUnreadDot2');
            if (d1) d1.classList.remove('d-none');
            if (d2) d2.classList.remove('d-none');
        }
    } catch(e){}
}, 8000);
<?php endif; ?>
</script>

