<div class="p-3 driver-page-container">
    <!-- Status Driver Online / Offline Switch Card -->
    <div class="driver-status-card mb-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center <?= $driver['is_online'] ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>" style="width: 42px; height: 42px; font-size: 18px; flex-shrink: 0;">
                <i class="bi <?= $driver['is_online'] ? 'bi-broadcast text-success' : 'bi-pause-circle' ?>"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-dark" style="font-size: 12px;">Status Kurir:</span>
                    <span class="badge rounded-pill <?= $driver['is_online'] ? 'bg-success text-white' : 'bg-secondary' ?>" style="font-size: 9.5px; font-weight: 700; letter-spacing: 0.3px;">
                        <?= $driver['is_online'] ? '● ONLINE (SIAP ANTAR)' : '● OFFLINE (ISTIRAHAT)' ?>
                    </span>
                </div>
                <div class="text-muted" style="font-size: 10.5px; margin-top: 2px;">
                    <?= $driver['is_online'] ? 'GPS aktif: Memindai orderan Cicalengka' : 'Aktifkan status untuk menerima pesanan' ?>
                </div>
            </div>
        </div>
        <label class="driver-toggle-switch m-0 flex-shrink-0">
            <input type="checkbox" id="onlineSwitch" name="online_status" <?= $driver['is_online'] ? 'checked' : '' ?> onchange="toggleDriverStatus()">
            <span class="driver-toggle-slider"></span>
        </label>
    </div>

    <!-- Prominent Driver Rating & Feedback Card -->
    <div class="p-3 bg-white rounded-4 border shadow-2xs mb-3">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2.5">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(245, 158, 11, 0.15); color: #F59E0B; font-size: 20px; flex-shrink:0;">
                    <i class="bi bi-star-fill"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-1.5">
                        <span class="fw-bold text-dark" style="font-size: 13px;">Rating Driver Anda</span>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-0.5" style="font-size: 11px; font-weight: 800;">
                            ⭐ <span id="driverRatingValueHeader"><?= !empty($driver['reviews_count']) && (int)$driver['reviews_count'] > 0 ? number_format($driver['rating'], 1) : '5.0' ?></span> / 5.0
                        </span>
                    </div>
                    <div class="text-muted" style="font-size: 10.5px; margin-top: 2px;">
                        Total <b id="driverReviewsCountHeader" class="text-dark"><?= (int)($driver['reviews_count'] ?? count($reviews ?? [])) ?></b> ulasan kepuasan pengantaran
                    </div>
                </div>
            </div>
            <a href="<?= $baseUrl ?>/delivery/profile" class="btn btn-outline-warning btn-sm rounded-pill px-3 py-1 fw-bold text-dark shadow-2xs" style="font-size: 10.5px; border-color: #FBBF24; background: #FFFBEB;">
                Rincian <i class="bi bi-chevron-right ms-0.5"></i>
            </a>
        </div>
    </div>

    <!-- Quick Stats Metric Cards -->
    <div class="row g-2 mb-3">
        <div class="col-4">
            <div class="driver-metric-card p-2.5">
                <div class="d-flex align-items-center justify-content-between mb-1.5">
                    <span class="text-muted fw-semibold" style="font-size: 10px;">Dompet</span>
                    <div class="driver-metric-icon red" style="width: 24px; height: 24px; font-size: 11px;">
                        <i class="bi bi-wallet2"></i>
                    </div>
                </div>
                <div>
                    <div id="driverWalletBalanceText" class="fw-bold text-danger text-truncate" style="font-size: 14px; letter-spacing: -0.3px;"><?= format_rupiah($wallet['balance'] ?? 0) ?></div>
                    <a href="<?= $baseUrl ?>/delivery/earnings" class="text-decoration-none text-muted d-inline-flex align-items-center gap-0.5 mt-0.5" style="font-size: 9.5px; font-weight: 600;">
                        <span>Saldo</span> <i class="bi bi-chevron-right text-danger" style="font-size: 8px;"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="driver-metric-card p-2.5">
                <div class="d-flex align-items-center justify-content-between mb-1.5">
                    <span class="text-muted fw-semibold" style="font-size: 10px;">Selesai</span>
                    <div class="driver-metric-icon green" style="width: 24px; height: 24px; font-size: 11px;">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                </div>
                <div>
                    <div class="fw-bold text-dark" style="font-size: 14px;"><span id="driverTotalOrdersText"><?= $driver['total_orders'] ?? 0 ?></span> <span class="fw-normal text-muted" style="font-size: 9.5px;">Order</span></div>
                    <span class="badge bg-success-subtle text-success rounded-pill px-1.5 py-0.5 mt-0.5" style="font-size: 8.5px; font-weight: 700;">
                        100%
                    </span>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="driver-metric-card p-2.5">
                <div class="d-flex align-items-center justify-content-between mb-1.5">
                    <span class="text-muted fw-semibold" style="font-size: 10px;">Rating</span>
                    <div class="driver-metric-icon amber" style="width: 24px; height: 24px; font-size: 11px; background: rgba(245, 158, 11, 0.15); color: #F59E0B;">
                        <i class="bi bi-star-fill"></i>
                    </div>
                </div>
                <div>
                    <div class="fw-bold text-dark" style="font-size: 14px;">
                        <?= !empty($driver['reviews_count']) && (int)$driver['reviews_count'] > 0 ? number_format($driver['rating'], 1) : '5.0' ?> <span style="font-size: 11px; color: #F59E0B;">★</span>
                    </div>
                    <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-1.5 py-0.5 mt-0.5" style="font-size: 8.5px; font-weight: 700;">
                        <?= $driver['reviews_count'] ?? count($reviews ?? []) ?> Ulasan
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Interactive Driver Radar & Navigation Map -->
    <div class="driver-map-card mb-3">
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
        <div id="driver-radar-map"></div>
    </div>

    <!-- Active Delivery Task (Batch Trip View) -->
    <div id="driverActiveOrderSection">
    <?php if (!empty($active_batch['orders'])): ?>
        <?php
        $batchOrders     = $active_batch['orders'];
        $batchId         = $active_batch['batch_id'];
        $batchCount      = count($batchOrders);
        $deliveredCount  = count(array_filter($batchOrders, fn($o) => $o['order_status'] === 'delivered'));
        $totalKm         = $active_batch['total_km'] ?? 0;
        $estCommission   = $active_batch['est_commission'] ?? 0;
        $progressPct     = $batchCount > 0 ? round(($deliveredCount / $batchCount) * 100) : 0;
        ?>
        <div class="active-task-container mb-4">
            <!-- Batch Header -->
            <div class="active-task-header">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge rounded-pill px-2.5 py-1 text-white" style="background: rgba(255,255,255,0.15); font-size: 10px; font-weight: 700; letter-spacing: 0.5px;">
                        <i class="bi bi-bicycle me-1 text-warning"></i> TRIP BERLANGSUNG
                    </span>
                    <span class="badge bg-warning text-dark fw-bold px-2.5 py-1 rounded-pill" style="font-size: 10px;">
                        <?= $deliveredCount ?>/<?= $batchCount ?> Terselesaikan
                    </span>
                </div>

                <!-- Progress Bar -->
                <div class="mb-2" style="background: rgba(255,255,255,0.15); border-radius: 99px; height: 6px; overflow: hidden;">
                    <div style="width: <?= $progressPct ?>%; height: 100%; background: #F59E0B; border-radius: 99px; transition: width 0.4s ease;"></div>
                </div>

                <!-- KM + Commission Summary -->
                <div class="d-flex align-items-center justify-content-between mt-2 pt-1">
                    <div class="d-flex align-items-center gap-1 text-white" style="font-size: 11px; opacity: 0.85;">
                        <i class="bi bi-signpost-2"></i>
                        <span>Total Rute: <b><?= $totalKm ?> Km</b></span>
                    </div>
                    <div class="d-flex align-items-center gap-1 text-warning fw-bold" style="font-size: 11px;">
                        <i class="bi bi-cash-stack"></i>
                        <span>Est. Komisi: <?= format_rupiah($estCommission) ?></span>
                    </div>
                </div>
            </div>

            <!-- Per-Order Cards in Batch -->
            <div class="p-3 bg-white d-flex flex-column gap-3">
                <?php foreach ($batchOrders as $idx => $bOrd): ?>
                <?php
                    $bIsPickedUp  = in_array($bOrd['order_status'], ['on_the_way', 'delivered']);
                    $bIsDelivered = $bOrd['order_status'] === 'delivered';
                    $bStoreLat    = (float)($bOrd['store_lat'] ?? -6.9835);
                    $bStoreLng    = (float)($bOrd['store_lng'] ?? 107.8335);
                    $bCustLat     = (float)($bOrd['delivery_address']['lat'] ?? -6.9855);
                    $bCustLng     = (float)($bOrd['delivery_address']['lng'] ?? 107.8350);
                    $bStoreUrl    = "https://www.google.com/maps/dir/?api=1&destination={$bStoreLat},{$bStoreLng}&travelmode=two_wheeler";
                    $bCustUrl     = "https://www.google.com/maps/dir/?api=1&destination={$bCustLat},{$bCustLng}&travelmode=two_wheeler";
                    $seqLabel     = "Toko " . ($idx + 1);
                ?>
                <div class="rounded-3 border overflow-hidden shadow-2xs <?= $bIsDelivered ? 'opacity-50' : '' ?>"
                     style="border-color: <?= $bIsDelivered ? '#10B981' : ($bIsPickedUp ? '#F59E0B' : '#EE2737') ?> !important;">

                    <!-- Order Mini Header -->
                    <div class="px-3 py-2 d-flex align-items-center justify-content-between"
                         style="background: <?= $bIsDelivered ? '#D1FAE5' : ($bIsPickedUp ? '#FEF3C7' : '#FEE2E2') ?>;">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge rounded-pill fw-bold text-white px-2 py-0.5"
                                  style="font-size: 9px; background: <?= $bIsDelivered ? '#10B981' : ($bIsPickedUp ? '#F59E0B' : '#EE2737') ?>;">
                                <?= $seqLabel ?>
                            </span>
                            <span class="fw-bold text-dark" style="font-size: 11.5px;">#<?= htmlspecialchars($bOrd['order_code']) ?></span>
                        </div>
                        <span class="badge rounded-pill px-2 py-0.5 fw-bold"
                              style="font-size: 9px; background: <?= $bIsDelivered ? '#D1FAE5' : ($bIsPickedUp ? '#FEF3C7' : '#FEE2E2') ?>; color: <?= $bIsDelivered ? '#059669' : ($bIsPickedUp ? '#D97706' : '#DC2626') ?>;">
                            <?= $bIsDelivered ? '✓ Selesai' : ($bIsPickedUp ? '🏍 Mengantar' : '⏳ Belum Dijemput') ?>
                        </span>
                    </div>

                    <div class="px-3 py-2.5">
                        <!-- Store info row -->
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                            <div class="d-flex align-items-center gap-2 min-w-0">
                                <i class="bi bi-shop text-danger flex-shrink-0" style="font-size: 14px;"></i>
                                <div class="min-w-0">
                                    <div class="fw-bold text-dark text-truncate" style="font-size: 11.5px;"><?= htmlspecialchars($bOrd['store_name'] ?? 'Toko') ?></div>
                                    <div class="text-muted text-truncate" style="font-size: 10px;"><?= htmlspecialchars($bOrd['store_address'] ?? '') ?></div>
                                </div>
                            </div>
                            <?php if (!$bIsPickedUp && !$bIsDelivered): ?>
                            <a href="<?= $bStoreUrl ?>" target="_blank"
                               class="btn btn-sm fw-bold flex-shrink-0 text-white rounded-pill px-2.5 py-1 shadow-xs d-flex align-items-center gap-1"
                               style="background:#EE2737; font-size: 10px;">
                                <i class="bi bi-compass-fill"></i> Navigasi
                            </a>
                            <?php endif; ?>
                        </div>

                        <!-- Customer info row -->
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                            <div class="d-flex align-items-center gap-2 min-w-0">
                                <i class="bi bi-geo-alt-fill text-success flex-shrink-0" style="font-size: 14px;"></i>
                                <div class="min-w-0">
                                    <div class="fw-bold text-dark text-truncate" style="font-size: 11.5px;"><?= htmlspecialchars($bOrd['customer_name'] ?? 'Pelanggan') ?></div>
                                    <div class="text-muted text-truncate" style="font-size: 10px;"><?= htmlspecialchars($bOrd['delivery_address']['address'] ?? 'Cicalengka') ?></div>
                                </div>
                            </div>
                            <?php if ($bIsPickedUp && !$bIsDelivered): ?>
                            <a href="<?= $bCustUrl ?>" target="_blank"
                               class="btn btn-sm fw-bold flex-shrink-0 text-white rounded-pill px-2.5 py-1 shadow-xs d-flex align-items-center gap-1"
                               style="background:#10B981; font-size: 10px;">
                                <i class="bi bi-compass-fill"></i> Navigasi
                            </a>
                            <?php endif; ?>
                        </div>

                        <!-- Action Buttons -->
                        <?php if (!$bIsDelivered): ?>
                        <div class="d-flex gap-2 mt-2 pt-2" style="border-top: 1px dashed #E2E8F0;">
                            <?php if (!$bIsPickedUp): ?>
                                <button onclick="updateDeliveryStep(<?= $bOrd['id'] ?>, 'picked_up')"
                                        class="btn fw-bold flex-grow-1 text-dark rounded-pill py-2 d-flex align-items-center justify-content-center gap-1 shadow-xs"
                                        style="background:#F59E0B; border:none; font-size: 11px;">
                                    <i class="bi bi-box-seam-fill"></i> Sudah Dijemput dari <?= $seqLabel ?>
                                </button>
                            <?php else: ?>
                                <button onclick="updateDeliveryStep(<?= $bOrd['id'] ?>, 'delivered')"
                                        class="btn btn-success fw-bold flex-grow-1 rounded-pill py-2 d-flex align-items-center justify-content-center gap-1 shadow-xs"
                                        style="font-size: 11px;">
                                    <i class="bi bi-shield-check"></i> Pesanan Sampai + OTP
                                </button>
                                <button type="button"
                                        onclick="openDriverChatModal('<?= htmlspecialchars($bOrd['order_code']) ?>')"
                                        class="btn btn-outline-danger btn-sm rounded-pill px-3 d-flex align-items-center gap-1 shadow-xs position-relative"
                                        style="font-size: 11px;">
                                    <i class="bi bi-chat-dots-fill"></i>
                                    <span class="ccg-unread-dot d-none" id="driverChatUnreadDot_<?= $bOrd['id'] ?>"></span>
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                            <div class="d-flex align-items-center gap-1 text-success mt-2 pt-2" style="border-top: 1px dashed #E2E8F0; font-size: 11px; font-weight: 700;">
                                <i class="bi bi-check-circle-fill"></i> Berhasil Diantar
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    </div>

    <!-- Radar Incoming Orders in Cicalengka Zone -->
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h6 class="fw-bold small m-0 text-dark">
            <i class="bi bi-radar me-1" style="color: #EE2737;"></i> Radar Order Sekitar Cicalengka
        </h6>
        <span id="radarOrderCountBadge" class="badge rounded-pill px-2.5 py-1 text-white <?= ($driver['is_online'] && !empty($available_orders)) ? '' : 'd-none' ?>" style="font-size: 10px; font-weight: 700; background: #EE2737;">
            <?= count($available_orders ?? []) ?> Order Siap
        </span>
    </div>

    <div id="driverRadarOrderSection">
    <?php if (!empty($active_batch['orders']) && ($active_batch['slots_left'] ?? 0) <= 0): ?>
        <!-- Batch full — no more orders -->
        <div class="ccg-card mb-3">
            <div class="d-flex align-items-center gap-3">
                <div class="driver-metric-icon red flex-shrink-0" style="width: 42px; height: 42px; font-size: 18px;">
                    <i class="bi bi-lock-fill"></i>
                </div>
                <div>
                    <div class="fw-bold small text-dark">Trip Anda Penuh (<?= count($active_batch['orders']) ?>/<?= \App\Services\DeliveryService::MAX_BATCH_ORDERS ?>)</div>
                    <div class="text-muted" style="font-size: 11px; line-height: 1.4;">
                        Selesaikan pengantaran yang ada sebelum mengambil pesanan baru.
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
                Aktifkan saklar status online di atas untuk mulai melihat pesanan masuk dari toko &amp; restoran Cicalengka.
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
        <?php
        $hasBatch    = !empty($active_batch['orders']);
        $slotsLeft   = $active_batch['slots_left'] ?? \App\Services\DeliveryService::MAX_BATCH_ORDERS;
        ?>
        <?php if ($hasBatch): ?>
        <div class="p-2.5 rounded-3 border mb-3 d-flex align-items-center gap-2"
             style="background: #FEF3C7; border-color: #F59E0B !important; font-size: 11px;">
            <i class="bi bi-bicycle text-warning fw-bold" style="font-size: 16px;"></i>
            <div>
                <b class="text-dark">Trip aktif!</b>
                <span class="text-muted"> Anda bisa tambah <b><?= $slotsLeft ?></b> pesanan lagi ke trip ini.</span>
            </div>
        </div>
        <?php endif; ?>

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
                            <div class="text-muted" style="font-size: 10px; font-weight: 600;">Est. Komisi:</div>
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
                        <button onclick="acceptDriverOrder(<?= $ord['id'] ?>)"
                                class="btn text-white btn-sm fw-bold px-4 rounded-pill shadow-sm d-flex align-items-center gap-1"
                                style="background: #EE2737; font-size: 11px;">
                            <?php if ($hasBatch): ?>
                                <i class="bi bi-plus-circle-fill"></i> Tambah ke Tripku
                            <?php else: ?>
                                <i class="bi bi-check-lg"></i> Ambil Order
                            <?php endif; ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    </div>
</div>

<!-- Driver Rating & Customer Feedback Card -->
<div class="bg-white rounded-4 border p-3 mb-3 shadow-2xs">
    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 15px;">
                <i class="bi bi-star-fill"></i>
            </div>
            <div>
                <h6 class="fw-bold small m-0 text-dark">Rating Saya & Ulasan Pelanggan</h6>
                <span class="text-muted" style="font-size: 10px;">Penilaian pengantaran dari pelanggan</span>
            </div>
        </div>
        <div class="text-end">
            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1 fw-bold" style="font-size: 11px;">
                ⭐ <?= !empty($driver['reviews_count']) && (int)$driver['reviews_count'] > 0 ? number_format($driver['rating'], 1) : '5.0' ?> / 5.0
            </span>
        </div>
    </div>

    <?php if (empty($reviews)): ?>
        <div class="text-center py-3 text-muted">
            <i class="bi bi-chat-heart text-warning fs-3 mb-1 d-block opacity-75"></i>
            <div class="fw-semibold text-dark" style="font-size: 11.5px;">Belum Ada Ulasan Driver</div>
            <div style="font-size: 10px;">Penilaian dari pelanggan yang Anda antar orderannya akan muncul di sini.</div>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-2">
            <?php foreach ($reviews as $rev): ?>
                <div class="p-2.5 rounded-3 bg-light border">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="d-flex align-items-center gap-2">
                            <img src="<?= $baseUrl ?>/<?= htmlspecialchars($rev['customer_avatar'] ?? 'assets/images/users/customer.png') ?>" alt="User" class="rounded-circle border" style="width: 26px; height: 26px; object-fit: cover;">
                            <div>
                                <div class="fw-bold text-dark" style="font-size: 11px;"><?= htmlspecialchars($rev['customer_name'] ?? 'Pelanggan') ?></div>
                                <div class="text-muted" style="font-size: 9px;">#<?= htmlspecialchars($rev['order_code'] ?? '-') ?> • <?= date('d M Y', strtotime($rev['created_at'])) ?></div>
                            </div>
                        </div>
                        <div class="text-warning fw-bold" style="font-size: 10px;">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <i class="bi <?= $s <= (int)$rev['rating'] ? 'bi-star-fill' : 'bi-star text-muted opacity-50' ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <?php if (!empty($rev['comment'])): ?>
                        <div class="p-2 bg-white rounded-2 text-dark mt-1 border" style="font-size: 10.5px; line-height: 1.35;">
                            "<?= htmlspecialchars($rev['comment']) ?>"
                        </div>
                    <?php endif; ?>
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

    // Helper SVG Data URIs for Driver Dashboard
    const driverSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 46" width="32" height="46">
      <defs>
        <linearGradient id="dg" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#3b82f6"/>
          <stop offset="100%" stop-color="#1d4ed8"/>
        </linearGradient>
      </defs>
      <path d="M16 2C9 2 3 8 3 15c0 10 13 29 13 29S29 25 29 15C29 8 23 2 16 2z" fill="url(#dg)" stroke="white" stroke-width="2"/>
      <circle cx="11" cy="17" r="3.5" fill="none" stroke="white" stroke-width="1.8"/>
      <circle cx="21" cy="17" r="3.5" fill="none" stroke="white" stroke-width="1.8"/>
      <polyline points="11,17 15,12 21,17" fill="none" stroke="white" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"/>
      <line x1="15" y1="12" x2="15" y2="17" stroke="white" stroke-width="1.8" stroke-linecap="round"/>
      <circle cx="17" cy="10" r="2" fill="white"/>
    </svg>`;
    
    const storeSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 46" width="32" height="46">
      <defs>
        <linearGradient id="sg" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#f87171"/>
          <stop offset="100%" stop-color="#b91c1c"/>
        </linearGradient>
      </defs>
      <path d="M16 2C9 2 3 8 3 15c0 10 13 29 13 29S29 25 29 15C29 8 23 2 16 2z" fill="url(#sg)" stroke="white" stroke-width="2"/>
      <path d="M9 14 L9 12 Q9 10 16 10 Q23 10 23 12 L23 14 Q19.5 17 16 16 Q12.5 17 9 14z" fill="white"/>
      <rect x="11" y="14.5" width="10" height="6" rx="0.5" fill="white" opacity="0.25"/>
      <rect x="13" y="15" width="6" height="5.5" fill="white"/>
      <rect x="14.5" y="16" width="3" height="4.5" fill="#b91c1c"/>
    </svg>`;
    
    const custSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 46" width="32" height="46">
      <defs>
        <linearGradient id="cg" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#34d399"/>
          <stop offset="100%" stop-color="#047857"/>
        </linearGradient>
      </defs>
      <path d="M16 2C9 2 3 8 3 15c0 10 13 29 13 29S29 25 29 15C29 8 23 2 16 2z" fill="url(#cg)" stroke="white" stroke-width="2"/>
      <circle cx="16" cy="11" r="3.8" fill="white"/>
      <path d="M9 22 Q9 17 16 17 Q23 17 23 22" fill="white"/>
    </svg>`;

    // Driver Location Pin
    const myIcon = L.icon({
        iconUrl: 'data:image/svg+xml,' + encodeURIComponent(driverSvg),
        iconSize: [32, 46],
        iconAnchor: [16, 46],
        popupAnchor: [0, -46]
    });

    window.myDriverMarker = L.marker([window.driverLat, window.driverLng], { icon: myIcon, zIndexOffset: 1000 })
        .bindPopup('<div class="ccg-map-popup"><div class="popup-title">Lokasi Anda (Driver)</div><span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5 mt-1" style="font-size:10px; font-weight: 600;"><i class="bi bi-broadcast me-1"></i>GPS Aktif</span></div>')
        .addTo(window.dRadarMap);

    <?php if (!empty($active_order)): ?>
        // Plot Active Order Details (Step-by-step)
        const isPickedUp = window.isPickedUp;
        const actStoreLat = window.actStoreLat;
        const actStoreLng = window.actStoreLng;
        const actDestLat = window.actDestLat;
        const actDestLng = window.actDestLng;

        const storeIcon = L.icon({
            iconUrl: 'data:image/svg+xml,' + encodeURIComponent(storeSvg),
            iconSize: [32, 46],
            iconAnchor: [16, 46],
            popupAnchor: [0, -46]
        });

        const custIcon = L.icon({
            iconUrl: 'data:image/svg+xml,' + encodeURIComponent(custSvg),
            iconSize: [32, 46],
            iconAnchor: [16, 46],
            popupAnchor: [0, -46]
        });

        window.actStoreMarker = L.marker([actStoreLat, actStoreLng], { icon: storeIcon, zIndexOffset: 100 })
            .addTo(window.dRadarMap)
            .bindPopup("<b><?= htmlspecialchars($active_order['store_name'] ?? 'Penjemputan') ?></b><br><small class='text-muted'>Titik Ambil Barang</small><br><a href='<?= $storeGmapsUrl ?>' target='_blank' class='btn btn-danger btn-sm text-white w-100 mt-2 py-0.5 rounded-pill' style='font-size:10px;'>Google Maps Toko</a>");

        window.actCustMarker = L.marker([actDestLat, actDestLng], { icon: custIcon, zIndexOffset: 500 })
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
        // Plot Available Incoming Orders
        <?php foreach ($available_orders as $ord): ?>
            <?php 
                $sLat = (float)($ord['store_lat'] ?? -6.9835);
                $sLng = (float)($ord['store_lng'] ?? 107.8335);
                $oId = (int)$ord['id'];
                $sNameJs = json_encode($ord['store_name'] ?? 'Toko / Resto');
                $dAddrJs = json_encode($ord['delivery_address']['address'] ?? 'Cicalengka');
            ?>
            (function() {
                const oIcon = L.icon({
                    iconUrl: 'data:image/svg+xml,' + encodeURIComponent(storeSvg),
                    iconSize: [32, 46],
                    iconAnchor: [16, 46],
                    popupAnchor: [0, -46]
                });

                const sName = <?= $sNameJs ?>;
                const dAddr = <?= $dAddrJs ?>;

                L.marker([<?= $sLat ?>, <?= $sLng ?>], { icon: oIcon, zIndexOffset: 100 })
                    .addTo(window.dRadarMap)
                    .bindPopup(`<b>${escapeHtml(sName)}</b><br><small class="text-muted">Antar ke: ${escapeHtml(dAddr)}</small><br><button onclick="acceptDriverOrder(<?= $oId ?>)" class="btn btn-sm w-100 mt-2 py-1 fw-bold rounded-pill text-white" style="background:#EE2737;">Ambil Order Ini</button>`);
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

