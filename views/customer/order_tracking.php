<!-- Tracking Top Header -->
<div class="p-3 border-bottom bg-white d-flex align-items-center justify-content-between sticky-top shadow-xs">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= $baseUrl ?>/orders" class="btn btn-light btn-sm rounded-circle"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h6 class="fw-bold m-0" style="color: var(--gojek-charcoal);">Lacak Pengantaran CicalengkaGO</h6>
            <span class="text-muted" style="font-size: 11px;">#<?= htmlspecialchars($order['order_code']) ?></span>
        </div>
    </div>
    <span id="order-status-badge" class="badge px-3 py-1 text-uppercase text-white fw-bold" style="background: #EE2737; font-size: 11px;">
        <?= strtoupper($order['order_status']) ?>
    </span>
</div>

<div class="p-3">
    <!-- Live Map Container with Floating HUD -->
    <div class="position-relative mb-3 rounded-4 overflow-hidden shadow-sm border">
        <!-- Live Floating HUD (Top) -->
        <div class="position-absolute top-0 start-0 end-0 p-2 z-3 d-flex align-items-center justify-content-between pointer-events-none">
            <div class="badge bg-dark bg-opacity-75 backdrop-blur text-white px-3 py-1 small fw-semibold rounded-pill d-flex align-items-center gap-1 shadow-sm" id="live-radar-status">
                <span class="live-dot me-1"></span> Live GPS Radar
            </div>
            <button onclick="toggleDriverSimulation('<?= $order['order_code'] ?>')" class="btn btn-warning btn-sm fw-bold shadow-sm rounded-pill py-0 px-2 pointer-events-auto text-dark" style="font-size: 10px;">
                <i class="bi bi-play-circle-fill me-1"></i> Demo Gerak Kurir
            </button>
        </div>

        <div id="tracking-map" style="width: 100%; height: 280px;"></div>

        <!-- Floating Telemetry HUD (Bottom) -->
        <div class="position-absolute bottom-0 start-0 end-0 p-2 z-3 d-flex flex-column gap-1 pointer-events-none">
            <div class="d-flex align-items-center justify-content-between gap-1 pointer-events-auto">
                <div class="bg-white bg-opacity-90 backdrop-blur border rounded-pill px-3 py-1 shadow-sm small fw-bold text-dark d-flex align-items-center" id="live-distance-text" style="font-size: 11px;">
                    <i class="bi bi-pin-map-fill text-danger me-1"></i> Menghitung jarak...
                </div>
                <div class="text-white rounded-pill px-3 py-1 shadow-sm small fw-bold d-flex align-items-center" style="background:#EE2737; font-size: 11px;" id="live-eta-text">
                    <i class="bi bi-stopwatch-fill me-1"></i> Estimasi Tiba
                </div>
            </div>

            <!-- Quick Action Floating Buttons -->
            <div class="d-flex justify-content-end gap-2 mt-1 pointer-events-auto">
                <button onclick="centerOnDriver()" class="btn btn-white btn-sm bg-white shadow-sm rounded-pill px-2 py-1 small fw-semibold" style="font-size: 11px;">
                    <i class="bi bi-crosshair me-1" style="color: #EE2737;"></i> Fokus Kurir
                </button>
                <button onclick="openGoogleMapsNav()" class="btn btn-sm shadow-sm rounded-pill px-2 py-1 small fw-semibold text-white" style="background: #EE2737; font-size: 11px;">
                    <i class="bi bi-compass me-1"></i> Google Maps
                </button>
            </div>
        </div>
    </div>

    <!-- OTP Code Banner Card -->
    <?php if ($order['order_status'] !== 'delivered' && $order['order_status'] !== 'canceled'): ?>
    <div class="p-3 mb-3 text-white rounded-4 shadow-sm text-center" style="background: linear-gradient(135deg, #101820 0%, #1e293b 100%); border-left: 4px solid #EE2737;">
        <div class="small text-white-50 mb-1" style="font-size: 11px; font-weight: 600; letter-spacing: 0.5px;">KODE OTP KONFIRMASI PENERIMAAN</div>
        <div class="display-6 fw-bold" style="letter-spacing: 6px; color: #EE2737;"><?= htmlspecialchars($order['otp']) ?></div>
        <div class="small text-white-50 mt-1" style="font-size: 10px;">Berikan kode 4-digit ini kepada kurir saat pesanan tiba di lokasi Anda.</div>
    </div>
    <?php endif; ?>

    <!-- Driver Info Card (If Assigned) -->
    <?php if (!empty($order['delivery_man_id'])): ?>
    <div class="p-3 bg-white rounded-4 border shadow-sm mb-3">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($order['dm_avatar'] ?? 'assets/images/users/driver.png') ?>" alt="Driver" class="rounded-circle border border-2 border-danger" style="width: 48px; height: 48px; object-fit: cover;">
                <div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="fw-bold small"><?= htmlspecialchars($order['dm_name'] ?? 'Mitra Kurir Cicalengka') ?></span>
                        <span class="badge bg-danger-subtle text-danger" style="font-size: 9px;"><i class="bi bi-patch-check-fill me-1"></i>Mitra Driver CCG</span>
                    </div>
                    <div class="text-muted" style="font-size: 11px;">
                        <i class="bi bi-bicycle me-1 text-danger"></i><?= htmlspecialchars($order['vehicle_type'] ?? 'Motor') ?> • <b><?= htmlspecialchars($order['vehicle_number'] ?? 'D 1234 CCG') ?></b>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2">
                <?php if (!empty($order['dm_phone'])): ?>
                    <a href="https://wa.me/<?= preg_replace('/^0/', '62', $order['dm_phone']) ?>?text=Halo%20Kurir%20CicalengkaGO%20Pesanan%20<?= $order['order_code'] ?>" target="_blank" class="btn btn-success btn-sm rounded-circle d-flex align-items-center justify-content-center shadow-xs" style="background:#25D366; width: 38px; height: 38px; border:none;" title="Chat WhatsApp">
                        <i class="bi bi-whatsapp fs-6"></i>
                    </a>
                    <a href="tel:<?= htmlspecialchars($order['dm_phone']) ?>" class="btn btn-light btn-sm rounded-circle border d-flex align-items-center justify-content-center shadow-xs" style="width: 38px; height: 38px;" title="Telepon">
                        <i class="bi bi-telephone text-dark fs-6"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="p-3 bg-warning-subtle text-warning-emphasis rounded-4 border border-warning mb-3 d-flex align-items-center gap-3">
        <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
        <div class="small fw-semibold">Sistem sedang mencarikan mitra kurir terdekat di Cicalengka...</div>
    </div>
    <?php endif; ?>

    <!-- Delivery Stepper Progress -->
    <div class="p-3 bg-white rounded-4 border shadow-sm mb-3">
        <h6 class="fw-bold small mb-3" style="color: var(--gojek-charcoal);">Status Pengantaran</h6>
        <div class="stepper-container p-0">
            <div class="step-item completed">
                <div class="step-dot"><i class="bi bi-check-lg"></i></div>
                <div class="flex-grow-1">
                    <div class="fw-bold small text-dark">Pesanan Dikonfirmasi</div>
                    <div class="text-muted" style="font-size: 11px;">Resto/Mitra menerima pesanan Anda</div>
                </div>
            </div>
            <div class="step-item <?= in_array($order['order_status'], ['processing', 'handover', 'on_the_way', 'delivered']) ? 'completed' : '' ?>">
                <div class="step-dot"><i class="bi bi-egg-fried"></i></div>
                <div class="flex-grow-1">
                    <div class="fw-bold small text-dark">Diproses & Disiapkan</div>
                    <div class="text-muted" style="font-size: 11px;">Makanan sedang dimasak / barang dikemas</div>
                </div>
            </div>
            <div class="step-item <?= in_array($order['order_status'], ['on_the_way', 'delivered']) ? 'completed' : (in_array($order['order_status'], ['processing', 'handover']) ? 'active' : '') ?>">
                <div class="step-dot"><i class="bi bi-bicycle"></i></div>
                <div class="flex-grow-1">
                    <div class="fw-bold small text-dark">Kurir Menuju Lokasi Anda</div>
                    <div class="text-muted" style="font-size: 11px;">Kurir dalam perjalanan pengantaran</div>
                </div>
            </div>
            <div class="step-item <?= $order['order_status'] === 'delivered' ? 'completed' : '' ?>">
                <div class="step-dot"><i class="bi bi-geo-alt-fill"></i></div>
                <div class="flex-grow-1">
                    <div class="fw-bold small text-dark">Pesanan Selesai</div>
                    <div class="text-muted" style="font-size: 11px;">Barang sampai dengan aman</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Items & Address Summary -->
    <div class="p-3 bg-white rounded-4 border shadow-sm mb-3">
        <h6 class="fw-bold small mb-2"><i class="bi bi-shop text-danger me-1"></i> Titik Penjemputan</h6>
        <p class="small text-dark fw-semibold mb-1"><?= htmlspecialchars($order['store_name'] ?? 'Penjemputan Parcel') ?></p>
        <p class="small text-muted mb-3" style="font-size: 11px;"><?= htmlspecialchars($order['store_address'] ?? 'Cicalengka, Bandung') ?></p>

        <h6 class="fw-bold small mb-2"><i class="bi bi-geo-alt-fill text-success me-1"></i> Alamat Tujuan Pengantaran</h6>
        <p class="small text-muted mb-3"><?= htmlspecialchars($order['delivery_address']['address'] ?? 'Cicalengka') ?></p>

        <h6 class="fw-bold small mb-2"><i class="bi bi-bag-check-fill text-primary me-1"></i> Rincian Menu / Paket</h6>
        <div class="d-flex flex-column gap-1 small text-muted mb-3">
            <?php if (!empty($order['items'])): ?>
                <?php foreach ($order['items'] as $item): ?>
                    <div class="d-flex justify-content-between">
                        <span><?= $item['quantity'] ?>x <?= htmlspecialchars($item['product_name']) ?></span>
                        <span class="text-dark fw-semibold"><?= format_rupiah($item['total_price']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="d-flex justify-content-between">
                    <span>Pengiriman Paket Kilat GoSend</span>
                    <span class="text-dark fw-semibold"><?= format_rupiah($order['order_amount']) ?></span>
                </div>
            <?php endif; ?>
        </div>
        <hr class="my-2">
        <div class="d-flex justify-content-between fw-bold small">
            <span>Total Pembayaran (<?= strtoupper($order['payment_method']) ?>)</span>
            <span class="text-dark fs-6"><?= format_rupiah($order['total_amount']) ?></span>
        </div>
    </div>
</div>

<script src="<?= $baseUrl ?>/assets/js/tracking-map.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const initialData = {
        order_status: "<?= $order['order_status'] ?>",
        store: {
            name: "<?= htmlspecialchars($order['store_name'] ?? 'Penjemputan') ?>",
            lat: <?= (float)($order['store_lat'] ?? -6.9835) ?>,
            lng: <?= (float)($order['store_lng'] ?? 107.8335) ?>
        },
        destination: {
            address: "<?= htmlspecialchars($order['delivery_address']['address'] ?? '') ?>",
            lat: <?= (float)($order['delivery_address']['lat'] ?? -6.9855) ?>,
            lng: <?= (float)($order['delivery_address']['lng'] ?? 107.8350) ?>
        },
        driver: {
            name: "<?= htmlspecialchars($order['dm_name'] ?? '') ?>",
            lat: <?= (float)($order['dm_lat'] ?? -6.9840) ?>,
            lng: <?= (float)($order['dm_lng'] ?? 107.8340) ?>
        }
    };

    initOrderTrackingMap("<?= $order['order_code'] ?>", initialData);
});
</script>
