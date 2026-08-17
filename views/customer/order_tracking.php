<?php
$isCanceled = ($order['order_status'] === 'canceled');
$isUnpaidOnline = ($order['payment_method'] === 'midtrans' && $order['payment_status'] !== 'paid' && !$isCanceled);

$statusLabels = [
    'pending'     => ['label' => 'Menunggu Pembayaran', 'class' => 'bg-warning text-dark'],
    'confirmed'   => ['label' => 'Pesanan Dikonfirmasi', 'class' => 'bg-info text-dark'],
    'processing'  => ['label' => 'Sedang Disiapkan Resto', 'class' => 'bg-warning text-dark'],
    'handover'    => ['label' => 'Diserahkan ke Kurir', 'class' => 'bg-primary text-white'],
    'picked_up'   => ['label' => 'Pesanan Diambil Kurir', 'class' => 'bg-primary text-white'],
    'on_the_way'  => ['label' => 'Kurir Menuju Lokasi Anda', 'class' => 'bg-primary text-white'],
    'delivered'   => ['label' => 'Pesanan Selesai', 'class' => 'bg-success text-white'],
    'canceled'    => ['label' => 'Pesanan Dibatalkan', 'class' => 'bg-danger text-white']
];
$currentBadge = $statusLabels[$order['order_status']] ?? ['label' => strtoupper($order['order_status']), 'class' => 'bg-secondary text-white'];
?>

<?php if ($isUnpaidOnline && !empty($snap_url)): ?>
<script src="<?= $snap_url ?>" data-client-key="<?= $client_key ?? '' ?>"></script>
<?php endif; ?>

<!-- Tracking Top Header -->
<div class="p-2 border-bottom bg-white d-flex align-items-center justify-content-between sticky-top shadow-xs" style="padding: 8px 12px !important;">
    <div class="d-flex align-items-center gap-1.5">
        <a href="<?= $baseUrl ?>/orders" class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 12px;"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h6 class="fw-bold m-0" style="color: var(--gojek-charcoal); font-size: 12px;">
                <?= $isUnpaidOnline ? 'Pembayaran Pesanan' : 'Lacak Pengantaran' ?>
            </h6>
            <span class="text-muted" style="font-size: 9.5px;">#<?= htmlspecialchars($order['order_code']) ?></span>
        </div>
    </div>
    
    <?php if ($isCanceled): ?>
        <span id="order-status-badge" class="badge px-2 py-0.5 text-uppercase text-white fw-bold bg-danger" style="font-size: 9px;">
            DIBATALKAN
        </span>
    <?php elseif ($isUnpaidOnline): ?>
        <span id="order-status-badge" class="badge px-2 py-0.5 text-uppercase bg-warning text-dark fw-bold border border-warning d-flex align-items-center gap-1" style="font-size: 8.5px;">
            <i class="bi bi-clock-history"></i> MENUNGGU BAYAR
        </span>
    <?php else: ?>
        <span id="order-status-badge" class="badge px-2 py-0.5 text-uppercase fw-bold <?= $currentBadge['class'] ?>" style="font-size: 9px;">
            <?= $currentBadge['label'] ?>
        </span>
    <?php endif; ?>
</div>

<div class="px-3 py-3 d-flex flex-column gap-3">
    <?php if ($isCanceled): ?>
        <!-- Canceled Card -->
        <div class="p-4 bg-white border shadow-xs text-center" style="border-radius: 16px; border-color: #E2E8F0 !important;">
            <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center mx-auto mb-2.5" style="width: 48px; height: 48px; font-size: 22px;">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <h6 class="fw-bold text-dark mb-1" style="font-size: 13px;">Pesanan Telah Dibatalkan</h6>
            <p class="text-muted mb-3" style="font-size: 10.5px;">Pesanan ini tidak lagi diproses.</p>
            <a href="<?= $baseUrl ?>" class="btn btn-sm rounded-pill px-4 py-1.5 fw-bold text-white shadow-xs" style="background:#EE2737; font-size: 11px;">Pesan Kembali</a>
        </div>

    <?php elseif ($isUnpaidOnline): ?>
        <!-- Unpaid Online Order Screen -->
        <div class="p-3.5 bg-white border shadow-xs text-center" style="border-radius: 16px; border-color: #E2E8F0 !important; padding: 16px !important;">
            <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center mx-auto mb-2.5" style="width: 52px; height: 52px; font-size: 24px;">
                <i class="bi bi-credit-card-2-front-fill text-danger"></i>
            </div>
            <div class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2.5 py-1 rounded-pill fw-bold mb-2" style="font-size: 9.5px;">
                <i class="bi bi-hourglass-split me-0.5"></i> Menunggu Pembayaran
            </div>
            <h6 class="fw-bold text-dark mb-0.5" style="font-size: 13px;">Selesaikan Pembayaran</h6>
            <div class="text-muted mb-2.5" style="font-size: 10.5px;">Total tagihan:</div>
            
            <div class="p-2.5 bg-light rounded-3 border mb-2.5">
                <div class="text-muted" style="font-size: 9.5px;">TOTAL NOMINAL</div>
                <div class="fw-bold text-danger my-1" style="font-size: 20px;"><?= format_rupiah($order['total_amount']) ?></div>
                <div class="badge bg-white text-dark border px-2 py-1" style="font-size: 9.5px;">
                    <i class="bi bi-shield-check text-success me-0.5"></i> Midtrans QRIS / VA / E-Wallet
                </div>
            </div>

            <div class="alert alert-warning border-0 rounded-3 text-start p-2.5 mb-2.5 d-flex align-items-start gap-2" style="font-size: 10px;">
                <i class="bi bi-exclamation-triangle-fill text-warning flex-shrink-0 mt-0.5" style="font-size: 12px;"></i>
                <div>
                    <b>Pesanan belum diproses.</b> Silakan klik tombol <b>Bayar Sekarang</b> di bawah.
                </div>
            </div>

            <!-- Action Buttons -->
            <button type="button" id="btnPayNow" onclick="payNow()" class="btn w-100 py-2.5 rounded-pill fw-bold text-white shadow-xs mb-2 d-flex align-items-center justify-content-center gap-2" style="background:#EE2737; font-size: 12.5px;">
                <i class="bi bi-wallet2"></i>
                <span>Bayar Sekarang (Midtrans)</span>
            </button>

            <div class="d-flex gap-2">
                <button type="button" id="btnCheckStatus" onclick="checkPaymentStatus()" class="btn btn-light border rounded-pill flex-grow-1 py-1.5 fw-semibold text-dark" style="font-size: 10.5px;">
                    <i class="bi bi-arrow-repeat me-0.5"></i> Cek Status Bayar
                </button>
                <button type="button" onclick="cancelUnpaidOrder()" class="btn btn-outline-danger rounded-pill px-3 py-1.5 fw-semibold" style="font-size: 10.5px;">
                    <i class="bi bi-x-circle me-0.5"></i> Batalkan
                </button>
            </div>
        </div>

    <?php else: ?>
        <!-- Active Tracking Screen (COD or Paid) -->
        <!-- Live Map Container with Floating HUD -->
        <div class="position-relative overflow-hidden shadow-xs border" style="border-radius: 16px; border-color: #E2E8F0 !important;">
            <!-- Live Floating HUD (Top) -->
            <div class="position-absolute top-0 start-0 end-0 p-2 z-3 d-flex align-items-center justify-content-between pointer-events-none">
                <div class="badge bg-dark bg-opacity-75 backdrop-blur text-white px-2.5 py-1 fw-semibold rounded-pill d-flex align-items-center gap-1 shadow-xs" id="live-radar-status" style="font-size: 9.5px;">
                    <span class="live-dot me-0.5"></span> Live GPS Radar
                </div>
                <button onclick="toggleDriverSimulation('<?= $order['order_code'] ?>')" class="btn btn-warning btn-sm fw-bold shadow-xs rounded-pill py-0.5 px-2.5 pointer-events-auto text-dark" style="font-size: 9.5px;">
                    <i class="bi bi-play-circle-fill me-0.5"></i> Demo Kurir
                </button>
            </div>

            <div id="tracking-map" style="width: 100%; height: 220px;"></div>

            <!-- Floating Telemetry HUD (Bottom) -->
            <div class="position-absolute bottom-0 start-0 end-0 p-2 z-3 d-flex flex-column gap-1.5 pointer-events-none">
                <div class="d-flex align-items-center justify-content-between gap-1.5 pointer-events-auto">
                    <div class="bg-white bg-opacity-90 backdrop-blur border rounded-pill px-2.5 py-1 shadow-xs fw-bold text-dark d-flex align-items-center" id="live-distance-text" style="font-size: 10px;">
                        <i class="bi bi-pin-map-fill text-danger me-1"></i> Menghitung jarak...
                    </div>
                    <div class="text-white rounded-pill px-2.5 py-1 shadow-xs fw-bold d-flex align-items-center" style="background:#EE2737; font-size: 10px;" id="live-eta-text">
                        <i class="bi bi-stopwatch-fill me-1"></i> Est. Tiba
                    </div>
                </div>

                <!-- Quick Action Floating Buttons -->
                <div class="d-flex justify-content-end gap-1.5 mt-0.5 pointer-events-auto">
                    <button onclick="centerOnDriver()" class="btn btn-white btn-sm bg-white shadow-xs rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 10px;">
                        <i class="bi bi-crosshair me-0.5" style="color: #EE2737;"></i> Fokus Kurir
                    </button>
                    <button onclick="openGoogleMapsNav()" class="btn btn-sm shadow-xs rounded-pill px-2.5 py-1 fw-semibold text-white" style="background: #EE2737; font-size: 10px;">
                        <i class="bi bi-compass me-0.5"></i> Maps
                    </button>
                </div>
            </div>
        </div>

        <!-- Order Delivered Success Celebration Card (Auto-Synced) -->
        <div id="order-completed-card" class="p-3 text-white shadow-xs <?= $order['order_status'] === 'delivered' ? '' : 'd-none' ?>" style="background: linear-gradient(135deg, #10B981 0%, #047857 100%); border-left: 4px solid #059669; border-radius: 16px;">
            <div class="d-flex align-items-center gap-2.5">
                <div class="rounded-circle bg-white text-success d-flex align-items-center justify-content-center flex-shrink-0 shadow-xs" style="width: 36px; height: 36px; font-size: 18px;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <h6 class="fw-bold m-0 text-white" style="font-size: 12px;">Pesanan Selesai Diantar! 🎉</h6>
                    <div class="text-white-50" style="font-size: 10px; line-height: 1.3;">Terima kasih telah memesan melalui CicalengkaGO.</div>
                </div>
            </div>
        </div>

        <!-- OTP Code Banner Card (Auto-Synced) -->
        <div id="otp-banner-card" class="p-3 text-white shadow-xs text-center <?= ($order['order_status'] === 'delivered' || $order['order_status'] === 'canceled') ? 'd-none' : '' ?>" style="background: linear-gradient(135deg, #101820 0%, #1e293b 100%); border-left: 4px solid #EE2737; border-radius: 16px;">
            <div class="text-white-50 mb-0.5" style="font-size: 9.5px; font-weight: 600; letter-spacing: 0.5px;">KODE OTP PENERIMAAN</div>
            <div class="fw-bold my-1" style="letter-spacing: 4px; color: #EE2737; font-size: 22px;"><?= htmlspecialchars($order['otp']) ?></div>
            <div class="text-white-50 mt-0.5" style="font-size: 9px;">Berikan kode 4-digit ini kepada kurir saat pesanan tiba.</div>
        </div>

        <!-- Driver Info Card (Dynamic Auto-Sync) -->
        <div id="driver-card-container">
            <!-- Assigned Driver Card -->
            <div id="driver-assigned-card" class="p-3.5 bg-white border shadow-xs <?= empty($order['delivery_man_id']) ? 'd-none' : '' ?>" style="border-radius: 16px; border-color: #E2E8F0 !important; padding: 14px 16px !important;">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2.5">
                        <img id="driver-avatar-img" src="<?= $baseUrl ?>/<?= htmlspecialchars($order['dm_avatar'] ?? 'assets/images/users/driver.png') ?>" alt="Driver" class="rounded-circle border border-2 border-danger" style="width: 40px; height: 40px; object-fit: cover;">
                        <div>
                            <div class="d-flex align-items-center gap-1.5">
                                <span id="driver-name-text" class="fw-bold text-dark" style="font-size: 11.5px;"><?= htmlspecialchars($order['dm_name'] ?? 'Mitra Kurir Cicalengka') ?></span>
                                <span class="badge bg-danger-subtle text-danger" style="font-size: 8.5px;"><i class="bi bi-patch-check-fill me-0.5"></i>Driver</span>
                            </div>
                            <div id="driver-vehicle-text" class="text-muted" style="font-size: 10px;">
                                <i class="bi bi-bicycle me-1 text-danger"></i><?= htmlspecialchars($order['vehicle_type'] ?? 'Motor') ?> • <b><?= htmlspecialchars($order['vehicle_number'] ?? 'D 1234 CCG') ?></b>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <button type="button" onclick="openChatModal()" class="btn btn-danger btn-sm rounded-pill px-3 py-1.5 d-flex align-items-center justify-content-center shadow-xs position-relative gap-1.5 fw-bold" style="background:#EE2737; border:none; font-size: 10.5px;" title="Chat Driver">
                            <i class="bi bi-chat-dots-fill text-white" style="font-size: 12px;"></i>
                            <span>Chat</span>
                            <span id="chatUnreadDot" class="ccg-unread-dot d-none"></span>
                        </button>
                        <a id="driver-call-btn" href="tel:<?= htmlspecialchars($order['dm_phone'] ?? '') ?>" class="btn btn-light btn-sm rounded-circle border d-flex align-items-center justify-content-center shadow-xs <?= empty($order['dm_phone']) ? 'd-none' : '' ?>" style="width: 32px; height: 32px;" title="Telepon Kurir">
                            <i class="bi bi-telephone-fill text-dark" style="font-size: 11px;"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Searching Driver Card -->
            <div id="driver-searching-card" class="p-3.5 bg-white border shadow-xs <?= !empty($order['delivery_man_id']) ? 'd-none' : '' ?>" style="border-radius: 16px; border-color: #E2E8F0 !important; padding: 14px 16px !important;">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2.5">
                        <div class="spinner-border spinner-border-sm text-danger" style="width: 16px; height: 16px;" role="status"></div>
                        <div>
                            <div class="fw-bold text-dark" style="font-size: 11.5px;">Mencari Kurir Terdekat...</div>
                            <div class="text-muted" style="font-size: 9.5px;">Sistem sedang menugaskan kurir untuk pesanan Anda</div>
                        </div>
                    </div>
                    <button type="button" onclick="openChatModal()" class="btn btn-outline-danger btn-sm rounded-pill px-2.5 py-1 d-flex align-items-center gap-1 fw-bold" style="font-size: 10.5px;">
                        <i class="bi bi-chat-dots"></i> Chat
                    </button>
                </div>
            </div>
        </div>

        <!-- Delivery Stepper Progress (Dynamic Auto-Sync) -->
        <div class="p-3.5 bg-white border shadow-xs" style="border-radius: 16px; border-color: #E2E8F0 !important; padding: 14px 16px !important;">
            <h6 class="fw-bold mb-2.5 text-dark" style="font-size: 12px;">Status Pengantaran</h6>
            <div class="stepper-container p-0" id="order-stepper">
                <?php
                $isStep1Done = true;
                $isStep2Done = in_array($order['order_status'], ['processing', 'handover', 'picked_up', 'on_the_way', 'delivered']);
                $isStep2Active = ($order['order_status'] === 'confirmed');
                $isStep3Done = in_array($order['order_status'], ['on_the_way', 'delivered']);
                $isStep3Active = in_array($order['order_status'], ['processing', 'handover', 'picked_up']);
                $isStep4Done = ($order['order_status'] === 'delivered');
                $isStep4Active = ($order['order_status'] === 'on_the_way');
                ?>
                <div class="step-item step-1 completed">
                    <div class="step-dot"><i class="bi bi-check-lg"></i></div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark" style="font-size: 11px;">Pesanan Dikonfirmasi</div>
                        <div class="text-muted" style="font-size: 9.5px;">Resto/Mitra menerima pesanan Anda</div>
                    </div>
                </div>
                <div class="step-item step-2 <?= $isStep2Done ? 'completed' : ($isStep2Active ? 'active' : '') ?>">
                    <div class="step-dot"><i class="bi <?= $isStep2Done ? 'bi-check-lg' : 'bi-egg-fried' ?>"></i></div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark" style="font-size: 11px;">Diproses & Disiapkan</div>
                        <div class="text-muted" style="font-size: 9.5px;">Makanan sedang dimasak / dikemas</div>
                    </div>
                </div>
                <div class="step-item step-3 <?= $isStep3Done ? 'completed' : ($isStep3Active ? 'active' : '') ?>">
                    <div class="step-dot"><i class="bi <?= $isStep3Done ? 'bi-check-lg' : 'bi-bicycle' ?>"></i></div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark" style="font-size: 11px;">Kurir Menuju Lokasi Anda</div>
                        <div class="text-muted" style="font-size: 9.5px;">Kurir dalam perjalanan pengantaran</div>
                    </div>
                </div>
                <div class="step-item step-4 <?= $isStep4Done ? 'completed' : ($isStep4Active ? 'active' : '') ?>">
                    <div class="step-dot"><i class="bi <?= $isStep4Done ? 'bi-check-lg' : 'bi-geo-alt-fill' ?>"></i></div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark" style="font-size: 11px;">Pesanan Selesai</div>
                        <div class="text-muted" style="font-size: 9.5px;">Barang sampai dengan aman</div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($order['order_status'] === 'delivered'): ?>
        <?php $hasReviewed = !empty($order['review_info']['has_reviewed']); ?>
        <div class="p-3.5 bg-white border shadow-xs" id="order-review-section" style="border-radius: 16px; border-color: #E2E8F0 !important; padding: 14px 16px !important;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; font-size: 14px;">
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark m-0" style="font-size: 12px;">Ulasan & Rating</h6>
                        <div class="text-muted" style="font-size: 9.5px;">Penilaian Anda sangat berharga bagi mitra kami</div>
                    </div>
                </div>
                <?php if ($hasReviewed): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 rounded-pill" style="font-size: 9px; font-weight: 700;">
                        <i class="bi bi-check-circle-fill me-0.5"></i> Sudah Diulas
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($hasReviewed): ?>
                <!-- Display Submitted Review -->
                <div class="p-2.5 bg-light rounded-3 border mt-2">
                    <?php if (!empty($order['review_info']['store_review'])): ?>
                        <?php $sr = $order['review_info']['store_review']; ?>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark" style="font-size: 11px;"><i class="bi bi-shop text-danger me-1"></i> <?= htmlspecialchars($order['store_name'] ?? 'Toko') ?></span>
                                <div class="text-warning fw-bold" style="font-size: 10px;">
                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                        <i class="bi <?= $s <= (int)$sr['rating'] ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                    <?php endfor; ?>
                                    <span class="ms-1 text-dark"><?= (int)$sr['rating'] ?>/5</span>
                                </div>
                            </div>
                            <?php if (!empty($sr['comment'])): ?>
                                <div class="text-muted mt-1 ps-1 fst-italic" style="font-size: 9.5px;">"<?= htmlspecialchars($sr['comment']) ?>"</div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($order['review_info']['dm_review'])): ?>
                        <?php $dr = $order['review_info']['dm_review']; ?>
                        <div class="pt-2 border-top">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark" style="font-size: 11px;"><i class="bi bi-bicycle text-primary me-1"></i> <?= htmlspecialchars($order['dm_name'] ?? 'Kurir Driver') ?></span>
                                <div class="text-warning fw-bold" style="font-size: 10px;">
                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                        <i class="bi <?= $s <= (int)$dr['rating'] ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                    <?php endfor; ?>
                                    <span class="ms-1 text-dark"><?= (int)$dr['rating'] ?>/5</span>
                                </div>
                            </div>
                            <?php if (!empty($dr['comment'])): ?>
                                <div class="text-muted mt-1 ps-1 fst-italic" style="font-size: 9.5px;">"<?= htmlspecialchars($dr['comment']) ?>"</div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Rating Input Widget Form -->
                <div class="p-2.5 bg-light rounded-3 border mt-2">
                    <div class="mb-2.5">
                        <label class="fw-bold text-dark d-block mb-1" style="font-size: 10.5px;"><i class="bi bi-shop text-danger me-1"></i> Rating Toko / Makanan</label>
                        <div class="d-flex justify-content-center gap-2.5 py-1" id="tracking-store-stars">
                            <i class="bi bi-star-fill text-warning fs-4 cursor-pointer" data-val="1" onclick="setTrackingStoreStar(1)"></i>
                            <i class="bi bi-star-fill text-warning fs-4 cursor-pointer" data-val="2" onclick="setTrackingStoreStar(2)"></i>
                            <i class="bi bi-star-fill text-warning fs-4 cursor-pointer" data-val="3" onclick="setTrackingStoreStar(3)"></i>
                            <i class="bi bi-star-fill text-warning fs-4 cursor-pointer" data-val="4" onclick="setTrackingStoreStar(4)"></i>
                            <i class="bi bi-star-fill text-warning fs-4 cursor-pointer" data-val="5" onclick="setTrackingStoreStar(5)"></i>
                        </div>
                        <div class="text-center fw-bold text-warning-emphasis mb-1.5" id="tracking-store-text" style="font-size: 10px;">Sangat Puas (5 Bintang)</div>
                        <textarea id="tracking-store-comment" name="store_comment" class="form-control form-control-sm rounded-2" rows="2" style="font-size: 10px;" placeholder="Bagaimana rasa makanan atau pelayanan toko?"></textarea>
                    </div>

                    <?php if (!empty($order['delivery_man_id'])): ?>
                        <div class="mb-2.5 pt-2 border-top">
                            <label class="fw-bold text-dark d-block mb-1" style="font-size: 10.5px;"><i class="bi bi-bicycle text-primary me-1"></i> Rating Pengantaran Kurir</label>
                            <div class="d-flex justify-content-center gap-2.5 py-1" id="tracking-dm-stars">
                                <i class="bi bi-star-fill text-warning fs-4 cursor-pointer" data-val="1" onclick="setTrackingDmStar(1)"></i>
                                <i class="bi bi-star-fill text-warning fs-4 cursor-pointer" data-val="2" onclick="setTrackingDmStar(2)"></i>
                                <i class="bi bi-star-fill text-warning fs-4 cursor-pointer" data-val="3" onclick="setTrackingDmStar(3)"></i>
                                <i class="bi bi-star-fill text-warning fs-4 cursor-pointer" data-val="4" onclick="setTrackingDmStar(4)"></i>
                                <i class="bi bi-star-fill text-warning fs-4 cursor-pointer" data-val="5" onclick="setTrackingDmStar(5)"></i>
                            </div>
                            <div class="text-center fw-bold text-warning-emphasis mb-1.5" id="tracking-dm-text" style="font-size: 10px;">Pengantaran Cepat & Ramah (5 Bintang)</div>
                            <textarea id="tracking-dm-comment" name="dm_comment" class="form-control form-control-sm rounded-2" rows="2" style="font-size: 10px;" placeholder="Tuliskan ulasan untuk kurir (opsional)..."></textarea>
                        </div>
                    <?php endif; ?>

                    <button type="button" id="btnSubmitTrackingReview" onclick="submitTrackingReview(<?= (int)$order['id'] ?>, <?= !empty($order['delivery_man_id']) ? 'true' : 'false' ?>)" class="btn btn-danger w-100 rounded-pill fw-bold py-2 shadow-xs" style="background:#EE2737; border:none; font-size: 11.5px;">
                        <i class="bi bi-send-fill me-1"></i> Kirim Ulasan & Rating
                    </button>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Order Items & Address Summary (Always Visible) -->
    <div class="p-3.5 bg-white border shadow-xs" style="border-radius: 16px; border-color: #E2E8F0 !important; padding: 14px 16px !important;">
        <h6 class="fw-bold mb-1.5 text-dark" style="font-size: 11.5px;"><i class="bi bi-shop text-danger me-1"></i> Titik Penjemputan</h6>
        <p class="text-dark fw-semibold mb-0.5" style="font-size: 10.5px;"><?= htmlspecialchars($order['store_name'] ?? 'Penjemputan Parcel') ?></p>
        <p class="text-muted mb-2.5" style="font-size: 9.5px;"><?= htmlspecialchars($order['store_address'] ?? 'Cicalengka, Bandung') ?></p>

        <h6 class="fw-bold mb-1.5 text-dark" style="font-size: 11.5px;"><i class="bi bi-geo-alt-fill text-success me-1"></i> Alamat Tujuan Pengantaran</h6>
        <p class="text-muted mb-2.5" style="font-size: 10px;"><?= htmlspecialchars($order['delivery_address']['address'] ?? 'Cicalengka') ?></p>

        <h6 class="fw-bold mb-1.5 text-dark" style="font-size: 11.5px;"><i class="bi bi-bag-check-fill text-primary me-1"></i> Rincian Menu / Paket</h6>
        <div class="d-flex flex-column gap-1.5 text-muted mb-2.5" style="font-size: 10px;">
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
        <div class="d-flex justify-content-between fw-bold" style="font-size: 11.5px;">
            <span>Total Tagihan (<?= strtoupper($order['payment_method']) ?>)</span>
            <span class="text-danger" style="font-size: 13px;"><?= format_rupiah($order['total_amount']) ?></span>
        </div>
        <div class="d-flex justify-content-between text-muted mt-1.5" style="font-size: 9.5px;">
            <span>Status Pembayaran</span>
            <span id="payment-status-text" class="fw-bold <?= ($order['payment_status'] === 'paid' || $order['order_status'] === 'delivered') ? 'text-success' : ($order['payment_method'] === 'cod' ? 'text-warning' : 'text-danger') ?>">
                <?= ($order['payment_status'] === 'paid' || $order['order_status'] === 'delivered') ? 'LUNAS' : ($order['payment_method'] === 'cod' ? 'BAYAR DI TEMPAT (COD)' : 'BELUM LUNAS') ?>
            </span>
        </div>
    </div>
</div>

<?php if (!$isUnpaidOnline && !$isCanceled): ?>
<script src="<?= $baseUrl ?>/assets/js/tracking-map.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const initialData = {
        order_code: "<?= $order['order_code'] ?>",
        order_status: "<?= $order['order_status'] ?>",
        payment_status: "<?= $order['payment_status'] ?>",
        payment_method: "<?= $order['payment_method'] ?>",
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
            assigned: <?= !empty($order['delivery_man_id']) ? 'true' : 'false' ?>,
            name: "<?= htmlspecialchars($order['dm_name'] ?? '') ?>",
            phone: "<?= htmlspecialchars($order['dm_phone'] ?? '') ?>",
            avatar: "<?= htmlspecialchars($order['dm_avatar'] ?? 'assets/images/users/driver.png') ?>",
            vehicle: "<?= htmlspecialchars($order['vehicle_type'] ?? 'Motor') ?>",
            plate: "<?= htmlspecialchars($order['vehicle_number'] ?? '') ?>",
            lat: <?= (float)($order['dm_lat'] ?? -6.9840) ?>,
            lng: <?= (float)($order['dm_lng'] ?? 107.8340) ?>
        }
    };

    initOrderTrackingMap("<?= $order['order_code'] ?>", initialData);
});
</script>
<?php endif; ?>

<?php if ($isUnpaidOnline): ?>
<script>
let currentSnapToken = "<?= $snap_token ?? '' ?>";
const orderCode = "<?= $order['order_code'] ?>";

async function payNow() {
    const btn = document.getElementById('btnPayNow');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Membuka Pembayaran...';

    try {
        const res = await fetch(window.BASE_URL + '/orders/get-snap-token', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ order_code: orderCode })
        });
        const data = await res.json();
        if (!data.success || !data.data.snap_token) {
            throw new Error(data.message || 'Gagal memuat token pembayaran');
        }

        const token = data.data.snap_token;

        if (typeof window.snap === 'undefined') {
            throw new Error('Midtrans Snap script belum termuat. Silakan refresh halaman.');
        }

        window.snap.pay(token, {
            onSuccess: function(result) {
                fetch(window.BASE_URL + '/payment/verify', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        order_id: result.order_id || orderCode,
                        transaction_status: result.transaction_status || 'settlement',
                        payment_type: result.payment_type || 'midtrans',
                        gross_amount: result.gross_amount
                    })
                }).finally(() => {
                    Swal.fire({
                        title: 'Pembayaran Berhasil! 🎉',
                        text: 'Pesanan Anda telah lunas dan siap diantar kurir CicalengkaGO.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                });
            },
            onPending: function(result) {
                Swal.fire({
                    title: 'Menunggu Pembayaran ⏳',
                    text: 'Silakan selesaikan pembayaran Anda via QRIS / Virtual Account.',
                    icon: 'info'
                });
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-wallet2 fs-5"></i> <span>Bayar Sekarang (Buka Midtrans)</span>';
            },
            onError: function(result) {
                Swal.fire('Pembayaran Gagal', 'Terjadi kendala pada transaksi online.', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-wallet2 fs-5"></i> <span>Bayar Sekarang (Buka Midtrans)</span>';
            },
            onClose: function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-wallet2 fs-5"></i> <span>Bayar Sekarang (Buka Midtrans)</span>';
            }
        });
    } catch(err) {
        console.error(err);
        Swal.fire('Error', err.message || 'Gagal memproses pembayaran.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-wallet2 fs-5"></i> <span>Bayar Sekarang (Buka Midtrans)</span>';
    }
}

async function checkPaymentStatus() {
    const btn = document.getElementById('btnCheckStatus');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengecek...';

    try {
        const res = await fetch(window.BASE_URL + '/payment/verify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: orderCode })
        });
        const data = await res.json();

        if (data.success && (data.data.status === 'settled' || data.data.payment_status === 'paid')) {
            Swal.fire({
                title: 'Pembayaran Dikonfirmasi! 🎉',
                text: 'Pesanan telah lunas!',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire({
                title: 'Belum Terdeteksi Lunas',
                text: 'Pembayaran Anda belum kami terima. Jika sudah transfer/scan QRIS, mohon tunggu 1-2 menit lalu klik cek kembali.',
                icon: 'info'
            });
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Cek Status Bayar';
        }
    } catch(e) {
        Swal.fire('Info', 'Belum dapat memverifikasi pembayaran.', 'info');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Cek Status Bayar';
    }
}

function cancelUnpaidOrder() {
    Swal.fire({
        title: 'Batalkan Pesanan?',
        text: 'Pesanan yang belum dibayar ini akan dibatalkan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EE2737',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Batalkan',
        cancelButtonText: 'Kembali'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const res = await fetch(window.BASE_URL + '/orders/cancel-unpaid', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ order_code: orderCode })
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire('Dibatalkan', 'Pesanan berhasil dibatalkan.', 'success').then(() => {
                        window.location.href = window.BASE_URL + '/orders';
                    });
                } else {
                    Swal.fire('Gagal', data.message || 'Gagal membatalkan pesanan', 'error');
                }
            } catch(e) {
                Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
            }
        }
    });
}
</script>
<?php endif; ?>

<!-- CicalengkaGO In-App Chat Modal (Customer) -->
<div id="chatModal" class="ccg-chat-modal" onclick="handleChatModalBackdrop(event)">
    <div class="ccg-chat-card" onclick="event.stopPropagation()">
        <!-- Chat Header -->
        <div class="ccg-chat-header">
            <div class="d-flex align-items-center gap-2.5">
                <img id="chatPartnerAvatar" src="<?= $baseUrl ?>/<?= htmlspecialchars($order['dm_avatar'] ?? 'assets/images/users/driver.png') ?>" alt="Driver" class="ccg-chat-avatar">
                <div>
                    <div class="d-flex align-items-center gap-1.5">
                        <h6 id="chatPartnerName" class="fw-bold m-0 text-dark small"><?= htmlspecialchars($order['dm_name'] ?? 'Mitra Kurir Cicalengka') ?></h6>
                        <span class="badge bg-danger-subtle text-danger" style="font-size: 9px;"><i class="bi bi-patch-check-fill me-1"></i>Kurir CCG</span>
                    </div>
                    <div id="chatPartnerSub" class="text-muted" style="font-size: 11px;">
                        <?= htmlspecialchars($order['vehicle_type'] ?? 'Motor') ?> • <?= htmlspecialchars($order['vehicle_number'] ?? 'D 1234 CCG') ?>
                    </div>
                </div>
            </div>
            <button type="button" class="btn-close" onclick="closeChatModal()" aria-label="Close"></button>
        </div>

        <!-- Chat Message Stream -->
        <div id="chatBody" class="ccg-chat-body">
            <div class="text-center py-4 text-muted small" id="chatLoading">
                <div class="spinner-border spinner-border-sm text-danger me-2" role="status"></div> Memuat percakapan...
            </div>
        </div>

        <!-- Quick Reply Chips for Customer -->
        <div class="ccg-chat-quick-chips">
            <button type="button" class="ccg-chip-btn" onclick="sendQuickReply('Saya sudah di titik lokasi ya pak 👍')">📍 Sudah di titik lokasi</button>
            <button type="button" class="ccg-chip-btn" onclick="sendQuickReply('Tolong titipkan di pagar / teras ya pak 🙏')">🏠 Titip di teras/satpam</button>
            <button type="button" class="ccg-chip-btn" onclick="sendQuickReply('Hati-hati di jalan ya pak!')">🛵 Hati-hati di jalan</button>
            <button type="button" class="ccg-chip-btn" onclick="sendQuickReply('Kabari kalau sudah sampai di depan ya')">🔔 Kabari jika sampai</button>
        </div>

        <!-- Chat Input Bar -->
        <form id="chatForm" class="ccg-chat-input-bar no-preloader" onsubmit="handleSendChat(event)">
            <input type="text" id="chatInput" name="message" class="ccg-chat-input" placeholder="Ketik pesan untuk kurir..." autocomplete="off" maxlength="500">
            <button type="submit" id="btnSendChat" class="ccg-chat-send-btn" title="Kirim">
                <i class="bi bi-send-fill"></i>
            </button>
        </form>
    </div>
</div>

<script>
const TRACKING_ORDER_CODE = "<?= $order['order_code'] ?>";
let chatPollingTimer = null;
window.isChatModalOpen = false;

function openChatModal() {
    const modal = document.getElementById('chatModal');
    if (!modal) return;
    modal.classList.add('show');
    window.isChatModalOpen = true;
    document.body.style.overflow = 'hidden';
    
    // Hide unread dot
    const unreadDot = document.getElementById('chatUnreadDot');
    if (unreadDot) unreadDot.classList.add('d-none');

    // Focus input
    setTimeout(() => {
        const inp = document.getElementById('chatInput');
        if (inp) inp.focus();
    }, 300);

    // Initial load
    fetchChatMessages(true);
    
    // Start fast polling
    if (chatPollingTimer) clearInterval(chatPollingTimer);
    chatPollingTimer = setInterval(() => fetchChatMessages(false), 2500);
}

function closeChatModal() {
    const modal = document.getElementById('chatModal');
    if (!modal) return;
    modal.classList.remove('show');
    window.isChatModalOpen = false;
    document.body.style.overflow = '';
    
    if (chatPollingTimer) {
        clearInterval(chatPollingTimer);
        chatPollingTimer = null;
    }
}

function handleChatModalBackdrop(e) {
    if (e.target.id === 'chatModal') {
        closeChatModal();
    }
}

async function fetchChatMessages(isFirstLoad = false) {
    try {
        const res = await fetch(window.BASE_URL + `/chats/messages?order_code=${TRACKING_ORDER_CODE}&since_id=0&mark_read=1`);
        const result = await res.json();
        
        if (!result.success) return;
        
        const data = result.data;
        const chatBody = document.getElementById('chatBody');
        if (!chatBody) return;

        // Update partner header if info available
        if (data.partner) {
            const pName = document.getElementById('chatPartnerName');
            const pAvatar = document.getElementById('chatPartnerAvatar');
            const pSub = document.getElementById('chatPartnerSub');
            if (pName && data.partner.name) pName.textContent = data.partner.name;
            if (pAvatar && data.partner.avatar) pAvatar.src = window.BASE_URL + '/' + data.partner.avatar;
            if (pSub && data.partner.vehicle_info) pSub.textContent = data.partner.vehicle_info;
        }

        const messages = data.messages || [];
        
        if (messages.length === 0) {
            chatBody.innerHTML = `
                <div class="text-center py-5 text-muted small">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 48px; height: 48px;">
                        <i class="bi bi-chat-heart text-danger fs-4"></i>
                    </div>
                    <div class="fw-bold text-dark">Mulai Obrolan</div>
                    <div>Kirim pesan atau gunakan pilihan pesan cepat di bawah untuk berkomunikasi dengan kurir.</div>
                </div>`;
            return;
        }

        // Render messages
        let html = '';
        const dmUserId = parseInt(data.dm_user_id) || 0;
        const currentUserId = parseInt(data.user_id) || 0;

        messages.forEach(msg => {
            const msgSenderId = parseInt(msg.sender_id) || 0;
            // On customer tracking page: message is INCOMING only if sent by the assigned driver
            // All other messages (sent by logged-in user or guest customer) are OUTGOING (red bubble on right)
            let isOutgoing = true;
            if (dmUserId > 0 && msgSenderId === dmUserId) {
                isOutgoing = false;
            }

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

        // Determine if scroll is needed
        const shouldScroll = isFirstLoad || (chatBody.scrollTop + chatBody.clientHeight >= chatBody.scrollHeight - 140);
        chatBody.innerHTML = html;

        if (shouldScroll) {
            chatBody.scrollTop = chatBody.scrollHeight;
        }
    } catch(err) {
        console.warn('Chat fetch error:', err);
    }
}

async function handleSendChat(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    if (window.hidePreloader) window.hidePreloader();

    const input = document.getElementById('chatInput');
    const btn = document.getElementById('btnSendChat');
    const message = input.value.trim();
    if (!message) return;

    // Optimistic UI preview
    const chatBody = document.getElementById('chatBody');
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
        fd.append('order_code', TRACKING_ORDER_CODE);
        fd.append('message', message);

        const res = await fetch(window.BASE_URL + '/chats/send', {
            method: 'POST',
            body: fd
        });

        const result = await res.json();
        if (result.success) {
            await fetchChatMessages(true);
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
        console.error('Send error:', err);
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

function sendQuickReply(text) {
    const input = document.getElementById('chatInput');
    if (input) {
        input.value = text;
        handleSendChat(null);
    }
}

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Background unread polling when modal is closed
setInterval(async () => {
    if (isChatModalOpen) return;
    try {
        const res = await fetch(window.BASE_URL + `/chats/unread-count?order_code=${TRACKING_ORDER_CODE}`);
        const result = await res.json();
        if (result.success && result.data && result.data.unread_count > 0) {
            const unreadDot = document.getElementById('chatUnreadDot');
            if (unreadDot) unreadDot.classList.remove('d-none');
        }
    } catch(e){}
}, 6000);

// Auto open chat if URL contains ?open_chat=1 or hash #chat
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('open_chat') === '1' || window.location.hash === '#chat') {
        setTimeout(openChatModal, 300);
    }
});

// ========================================================
// Midtrans Payment & Sandbox Simulation Handlers
// ========================================================
let snapToken = <?= json_encode($snap_token ?? null) ?>;
const IS_SANDBOX = <?= !empty($is_sandbox) ? 'true' : 'false' ?>;

async function payNow() {
    const btn = document.getElementById('btnPayNow');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Menghubungkan Midtrans...';
    }

    try {
        if (!snapToken) {
            const res = await fetch(window.BASE_URL + '/orders/get-snap-token', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'order_code=' + encodeURIComponent(TRACKING_ORDER_CODE)
            });
            const data = await res.json();
            if (data.success && data.data && data.data.snap_token) {
                snapToken = data.data.snap_token;
            } else {
                throw new Error(data.message || 'Gagal memuat tiket pembayaran.');
            }
        }

        if (typeof window.snap === 'undefined') {
            throw new Error('Script Midtrans Snap belum termuat. Silakan refresh halaman.');
        }

        window.snap.pay(snapToken, {
            onSuccess: function(result) {
                handlePaymentSuccessCallback(result);
            },
            onPending: function(result) {
                handlePaymentPendingCallback(result);
            },
            onError: function(result) {
                Swal.fire({
                    icon: 'error',
                    title: 'Pembayaran Gagal',
                    text: 'Terjadi kendala dalam proses pembayaran online.',
                    confirmButtonColor: '#EE2737'
                });
                resetPayButton();
            },
            onClose: function() {
                Swal.fire({
                    icon: 'info',
                    title: 'Pembayaran Belum Selesai',
                    text: 'Silakan klik Bayar Sekarang kapan saja untuk menyelesaikan pembayaran pesanan.',
                    confirmButtonColor: '#EE2737'
                });
                resetPayButton();
            }
        });
    } catch(err) {
        console.error(err);
        Swal.fire({
            icon: 'error',
            title: 'Gagal Membuka Pembayaran',
            text: err.message || 'Terjadi kesalahan sistem.',
            confirmButtonColor: '#EE2737'
        });
        resetPayButton();
    }
}

function resetPayButton() {
    const btn = document.getElementById('btnPayNow');
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-wallet2 fs-5"></i> <span>Bayar Sekarang (Buka Midtrans)</span>';
    }
}

async function handlePaymentSuccessCallback(result) {
    Swal.fire({
        title: 'Memverifikasi Pembayaran...',
        text: 'Mengonfirmasi transaksi ke server...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        await fetch(window.BASE_URL + '/payment/verify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_id: TRACKING_ORDER_CODE,
                transaction_status: result?.transaction_status || 'settlement',
                payment_type: result?.payment_type || 'midtrans',
                gross_amount: result?.gross_amount
            })
        });
    } catch(e) {
        console.warn('Verify callback err:', e);
    }

    Swal.fire({
        icon: 'success',
        title: 'Pembayaran Berhasil! 🎉',
        text: 'Pesanan Anda telah lunas dan siap diantar.',
        timer: 2000,
        showConfirmButton: false
    }).then(() => {
        location.reload();
    });
}

function handlePaymentPendingCallback(result) {
    Swal.fire({
        icon: 'info',
        title: 'Menunggu Pembayaran ⏳',
        text: 'Silakan selesaikan pembayaran sesuai instruksi Virtual Account / QRIS yang dipilih.',
        confirmButtonColor: '#EE2737'
    });
    resetPayButton();
}

async function checkPaymentStatus() {
    const btn = document.getElementById('btnCheckStatus');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengecek...';
    }

    try {
        const res = await fetch(window.BASE_URL + '/payment/verify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: TRACKING_ORDER_CODE })
        });
        const data = await res.json();

        if (data.success && (data.data?.status === 'settled' || data.data?.payment_status === 'paid')) {
            Swal.fire({
                icon: 'success',
                title: 'Pembayaran Terkonfirmasi! 🎉',
                text: 'Pesanan telah lunas. Memperbarui status pesanan...',
                timer: 1800,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Belum Terbayar',
                text: data.message || 'Pembayaran belum terdeteksi. Silakan selesaikan transaksi Anda.',
                confirmButtonColor: '#EE2737'
            });
        }
    } catch(err) {
        console.error(err);
        Swal.fire('Error', 'Gagal memeriksa status pembayaran.', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Cek Status Bayar';
        }
    }
}

async function cancelUnpaidOrder() {
    const confirm = await Swal.fire({
        title: 'Batalkan Pesanan?',
        text: 'Pesanan yang belum dibayar ini akan dibatalkan permanen.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Batalkan',
        confirmButtonColor: '#dc3545',
        cancelButtonText: 'Kembali'
    });

    if (!confirm.isConfirmed) return;

    try {
        const res = await fetch(window.BASE_URL + '/orders/cancel-unpaid', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'order_code=' + encodeURIComponent(TRACKING_ORDER_CODE)
        });
        const data = await res.json();
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Pesanan Dibatalkan',
                text: 'Pesanan berhasil dibatalkan.',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Gagal', data.message || 'Gagal membatalkan pesanan.', 'error');
        }
    } catch(err) {
        Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
    }
}

// Live Rating Functions for Tracking Page
let currentTrackingStoreRating = 5;
let currentTrackingDmRating = 5;

function setTrackingStoreStar(val) {
    currentTrackingStoreRating = val;
    const stars = document.querySelectorAll('#tracking-store-stars i');
    stars.forEach((s, idx) => {
        if (idx < val) {
            s.className = 'bi bi-star-fill text-warning fs-3 cursor-pointer';
        } else {
            s.className = 'bi bi-star text-muted fs-3 cursor-pointer';
        }
    });
    const texts = ['', 'Kecewa (1 Bintang)', 'Kurang Puas (2 Bintang)', 'Cukup Baik (3 Bintang)', 'Puas (4 Bintang)', 'Sangat Puas (5 Bintang)'];
    const el = document.getElementById('tracking-store-text');
    if (el) el.textContent = texts[val] || '';
}

function setTrackingDmStar(val) {
    currentTrackingDmRating = val;
    const stars = document.querySelectorAll('#tracking-dm-stars i');
    stars.forEach((s, idx) => {
        if (idx < val) {
            s.className = 'bi bi-star-fill text-warning fs-3 cursor-pointer';
        } else {
            s.className = 'bi bi-star text-muted fs-3 cursor-pointer';
        }
    });
    const texts = ['', 'Kurang Baik (1 Bintang)', 'Biasa Saja (2 Bintang)', 'Cukup Ramah (3 Bintang)', 'Pengantaran Baik (4 Bintang)', 'Pengantaran Cepat & Ramah (5 Bintang)'];
    const el = document.getElementById('tracking-dm-text');
    if (el) el.textContent = texts[val] || '';
}

async function submitTrackingReview(orderId, hasDriver) {
    const btn = document.getElementById('btnSubmitTrackingReview');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1.5"></span> Mengirim Ulasan...';
    }

    const storeComment = document.getElementById('tracking-store-comment')?.value || '';
    const dmComment = document.getElementById('tracking-dm-comment')?.value || '';

    const fd = new FormData();
    fd.append('order_id', orderId);
    fd.append('store_rating', currentTrackingStoreRating);
    fd.append('store_comment', storeComment);
    if (hasDriver) {
        fd.append('dm_rating', currentTrackingDmRating);
        fd.append('dm_comment', dmComment);
    }

    try {
        const res = await fetch(window.BASE_URL + '/orders/review', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Ulasan Terkirim! 🎉',
                text: 'Terima kasih atas penilaian dan ulasan Anda.',
                timer: 1800,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Gagal', data.message || 'Gagal mengirim ulasan.', 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send-fill me-1.5"></i> Kirim Ulasan & Rating';
            }
        }
    } catch (err) {
        Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill me-1.5"></i> Kirim Ulasan & Rating';
        }
    }
}
</script>


