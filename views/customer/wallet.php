<?php if (!empty($snap_url)): ?>
<script src="<?= $snap_url ?>" data-client-key="<?= $client_key ?? '' ?>"></script>
<?php endif; ?>

<div class="border-bottom bg-white d-flex align-items-center gap-2.5 sticky-top app-subpage-header px-3 py-3">
    <a href="<?= $baseUrl ?>" class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px; border: 1px solid #E2E8F0; background: #F8FAFC;"><i class="bi bi-arrow-left text-dark" style="font-size: 15px;"></i></a>
    <h6 class="fw-bold m-0 text-dark" style="font-size: 15px; letter-spacing: -0.3px;">Dompet Digital CicalengkaPay</h6>
</div>

<div class="px-3 py-3">
    <!-- CicalengkaPay Premium Banner Card -->
    <div class="p-4 text-white mb-3.5 position-relative overflow-hidden" 
         style="background: linear-gradient(135deg, #EE2737 0%, #B91C1C 100%); border-radius: 20px; box-shadow: 0 8px 24px rgba(238, 39, 55, 0.22);">
        <!-- Watermark Pattern Background -->
        <div class="position-absolute" style="right: -15px; bottom: -20px; font-size: 110px; opacity: 0.09; line-height: 1; pointer-events: none;">
            <i class="bi bi-wallet2"></i>
        </div>

        <div class="position-relative z-1">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span style="font-weight: 900; font-size: 18px; letter-spacing: -0.3px;">Cicalengka<span style="color: #FFE4E6;">Pay</span></span>
                        <span class="badge bg-white text-danger px-2.5 py-1 rounded-pill fw-bold" style="font-size: 8.5px; letter-spacing: 0.5px;">E-WALLET</span>
                    </div>
                    <div class="text-white-50" style="font-size: 10px; font-weight: 500;">SALDO UTAMA AKTIF</div>
                    <div class="fw-extrabold my-1 text-white" style="font-size: 28px; letter-spacing: -0.8px; font-family: system-ui, -apple-system, sans-serif;">
                        <?= format_rupiah($wallet['balance'] ?? 0) ?>
                    </div>
                </div>
                <div class="rounded-circle bg-white text-danger d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; box-shadow: 0 4px 12px rgba(0,0,0,0.12);">
                    <i class="bi bi-shield-check" style="font-size: 22px; color: #EE2737;"></i>
                </div>
            </div>

            <!-- Ringkasan Aktivitas Saldo -->
            <div class="row g-2 mt-2 mb-3">
                <div class="col-4">
                    <div class="p-2 rounded-3 text-center" style="background: rgba(255,255,255,0.14); backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.2);">
                        <div class="text-white-50" style="font-size: 9px;">Topup Berhasil</div>
                        <div class="fw-bold text-white mt-0.5" style="font-size: 12px;"><?= $topup_stats['success_count'] ?? 0 ?>x</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 rounded-3 text-center" style="background: rgba(255,255,255,0.14); backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.2);">
                        <div class="text-white-50" style="font-size: 9px;">Menunggu</div>
                        <div class="fw-bold text-warning mt-0.5" style="font-size: 12px;"><?= $topup_stats['pending_count'] ?? 0 ?>x</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 rounded-3 text-center" style="background: rgba(255,255,255,0.14); backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.2);">
                        <div class="text-white-50" style="font-size: 9px;">Refund Masuk</div>
                        <div class="fw-bold text-info mt-0.5" style="font-size: 12px;"><?= count(array_filter($transactions ?? [], fn($t) => ($t['category'] ?? '') === 'order_refund')) ?>x</div>
                    </div>
                </div>
            </div>

            <button onclick="customTopUpDialog()" class="btn btn-white w-100 fw-extrabold py-2.5 rounded-pill shadow-xs d-flex align-items-center justify-content-center gap-2" style="color: #EE2737; font-size: 12px; background: #FFFFFF;">
                <i class="bi bi-plus-circle-fill fs-6"></i>
                <span>Isi Saldo CicalengkaPay Baru</span>
            </button>
        </div>
    </div>

    <!-- Quick Top Up Amount Grid -->
    <div class="p-3.5 mb-3.5 bg-white border" style="border-radius: 18px; border-color: #E2E8F0 !important; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-1.5">
                <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 13px;">
                    <i class="bi bi-lightning-charge-fill"></i>
                </div>
                <h6 class="fw-bold m-0 text-dark" style="font-size: 13px; letter-spacing: -0.2px;">Isi Saldo Instan</h6>
            </div>
            <span class="badge text-white px-2.5 py-1" style="background: #0F172A; font-size: 8.5px; font-weight: 700; border-radius: 8px;">
                QRIS / VA / E-WALLET
            </span>
        </div>

        <div class="row g-2.5">
            <?php
            $quickNominals = [
                ['amount' => 20000,  'label' => 'Rp 20.000',  'tag' => 'Hemat'],
                ['amount' => 50000,  'label' => 'Rp 50.000',  'tag' => 'Populer'],
                ['amount' => 100000, 'label' => 'Rp 100.000', 'tag' => 'Sering Dipakai'],
                ['amount' => 200000, 'label' => 'Rp 200.000', 'tag' => 'Bebas Reot']
            ];
            foreach ($quickNominals as $item):
            ?>
            <div class="col-6">
                <button type="button" onclick="quickTopUp(<?= $item['amount'] ?>)" 
                        class="btn w-100 py-2.5 px-3 border text-start d-flex flex-column justify-content-between transition-all" 
                        style="border-radius: 14px !important; background: #F8FAFC; border-color: #E2E8F0 !important; min-height: 58px;">
                    <div class="d-flex justify-content-between align-items-center w-100 mb-0.5">
                        <span class="text-muted" style="font-size: 9px; font-weight: 600;">PILIHAN</span>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 7.5px; padding: 2px 5px; font-weight: 700; border-radius: 4px;"><?= $item['tag'] ?></span>
                    </div>
                    <div class="fw-extrabold text-dark" style="font-size: 13.5px; letter-spacing: -0.3px; color: #0F172A !important;">
                        <?= $item['label'] ?>
                    </div>
                </button>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-3">
            <button onclick="customTopUpDialog()" class="btn btn-outline-danger w-100 py-2.5 rounded-pill fw-bold d-flex align-items-center justify-content-center gap-1.5" style="font-size: 11.5px; border-width: 1.5px;">
                <i class="bi bi-pencil-square"></i>
                <span>Masukkan Nominal Lainnya</span>
            </button>
        </div>
    </div>

    <!-- Navigation Tabs (Default ke Mutasi Saldo & Refund) -->
    <div class="bg-light p-1 rounded-4 border mb-3 d-flex" style="background: #F1F5F9 !important; border-color: #E2E8F0 !important;">
        <button class="btn w-50 active fw-bold py-2 rounded-3 text-truncate d-flex align-items-center justify-content-center gap-1.5 transition-all" 
                id="mutation-tab" data-bs-toggle="tab" data-bs-target="#mutation-pane" type="button" role="tab" style="font-size: 11.5px;">
            <i class="bi bi-receipt-cutoff text-danger fs-6"></i>
            <span>Mutasi Saldo & Refund</span>
            <span class="badge bg-danger text-white rounded-pill px-1.5" style="font-size: 9px;"><?= count($transactions ?? []) ?></span>
        </button>
        <button class="btn w-50 fw-bold py-2 rounded-3 text-truncate d-flex align-items-center justify-content-center gap-1.5 transition-all text-secondary" 
                id="topup-tab" data-bs-toggle="tab" data-bs-target="#topup-pane" type="button" role="tab" style="font-size: 11.5px;">
            <i class="bi bi-journal-check fs-6"></i>
            <span>Tiket Top Up Midtrans</span>
            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-1.5" style="font-size: 9px;"><?= count($topup_logs ?? []) ?></span>
        </button>
    </div>

    <!-- Tab Contents -->
    <div class="tab-content" id="walletTabContent">
        <!-- PANE 1 (DEFAULT): Riwayat Mutasi Saldo & Refund -->
        <div class="tab-pane fade show active" id="mutation-pane" role="tabpanel">
            <?php
                $refundCount = 0;
                $topupCount = 0;
                $orderCount = 0;
                foreach ($transactions as $t) {
                    $cat = $t['category'] ?? '';
                    if ($cat === 'order_refund') $refundCount++;
                    elseif ($cat === 'topup') $topupCount++;
                    elseif ($cat === 'order_payment') $orderCount++;
                }
            ?>

            <!-- Filter Pills -->
            <div class="d-flex gap-1.5 mb-3 overflow-auto pb-1" style="scrollbar-width: none;">
                <button type="button" onclick="filterMutationList('all')" class="btn btn-sm btn-dark rounded-pill px-3 py-1.5 fw-bold mutation-filter-btn active" data-mfilter="all" style="font-size: 10.5px;">
                    Semua (<?= count($transactions) ?>)
                </button>
                <button type="button" onclick="filterMutationList('order_refund')" class="btn btn-sm btn-light border rounded-pill px-3 py-1.5 fw-bold mutation-filter-btn text-primary" data-mfilter="order_refund" style="font-size: 10.5px;">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Refund (<?= $refundCount ?>)
                </button>
                <button type="button" onclick="filterMutationList('topup')" class="btn btn-sm btn-light border rounded-pill px-3 py-1.5 fw-bold mutation-filter-btn text-success" data-mfilter="topup" style="font-size: 10.5px;">
                    <i class="bi bi-plus-circle-fill me-1"></i> Top Up (<?= $topupCount ?>)
                </button>
                <button type="button" onclick="filterMutationList('order_payment')" class="btn btn-sm btn-light border rounded-pill px-3 py-1.5 fw-bold mutation-filter-btn text-danger" data-mfilter="order_payment" style="font-size: 10.5px;">
                    <i class="bi bi-bag-check-fill me-1"></i> Belanja (<?= $orderCount ?>)
                </button>
            </div>

            <?php if (empty($transactions)): ?>
                <div class="p-4 bg-white border text-center text-muted" style="font-size: 11px; border-radius: 16px;">
                    <div class="rounded-circle bg-light text-secondary d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 48px; height: 48px; font-size: 20px;">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                    <div class="fw-bold text-dark mb-1">Belum Ada Mutasi Transaksi</div>
                    <div class="text-muted" style="font-size: 10px;">
                        Semua transaksi saldo (Top Up, Refund, & Belanja) akan tercatat di sini.
                    </div>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-2" id="mutationContainer">
                    <?php foreach ($transactions as $tx):
                        $txCat = $tx['category'] ?? $tx['type'] ?? 'credit';
                        $isCredit = ($tx['type'] === 'credit');

                        // Clean mapping for mobile layout
                        if ($txCat === 'order_refund') {
                            $icon = 'bi-arrow-counterclockwise';
                            $title = 'Refund Pengembalian Dana';
                            $color = '#2563EB'; // Blue
                            $bgColor = '#EFF6FF';
                            $borderColor = '#2563EB';
                            $sign = '+';
                            $badgeText = 'Refund';
                        } elseif ($txCat === 'topup') {
                            $icon = 'bi-wallet2';
                            $title = 'Top Up CicalengkaPay';
                            $color = '#10B981'; // Green
                            $bgColor = '#ECFDF5';
                            $borderColor = '#10B981';
                            $sign = '+';
                            $badgeText = 'Top Up';
                        } elseif ($txCat === 'order_payment') {
                            $icon = 'bi-bag-dash-fill';
                            $title = 'Pembayaran Pesanan';
                            $color = '#E11D48'; // Red
                            $bgColor = '#FFF1F2';
                            $borderColor = '#E11D48';
                            $sign = '-';
                            $badgeText = 'Keluar';
                        } else {
                            $icon = $isCredit ? 'bi-arrow-down-left-circle' : 'bi-arrow-up-right-circle';
                            $title = $isCredit ? 'Saldo Masuk' : 'Saldo Keluar';
                            $color = $isCredit ? '#10B981' : '#E11D48';
                            $bgColor = $isCredit ? '#ECFDF5' : '#FFF1F2';
                            $borderColor = $color;
                            $sign = $isCredit ? '+' : '-';
                            $badgeText = $isCredit ? 'Masuk' : 'Keluar';
                        }
                    ?>
                        <div class="mutation-item-card bg-white border shadow-2xs px-3 py-2.5" 
                             style="border-radius: 14px; border-color: #E2E8F0 !important; <?= ($txCat === 'order_refund') ? 'border-left: 4px solid #2563EB !important;' : '' ?>" 
                             data-cat="<?= htmlspecialchars($txCat) ?>">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <!-- Left Icon + Title Info -->
                                <div class="d-flex align-items-center gap-2.5 min-w-0" style="flex: 1 1 auto;">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
                                         style="width: 36px; height: 36px; background: <?= $bgColor ?>;">
                                        <i class="bi <?= $icon ?>" style="font-size: 15px; color: <?= $color ?>;"></i>
                                    </div>
                                    <div class="min-w-0" style="line-height: 1.25;">
                                        <div class="fw-bold text-dark text-truncate" style="font-size: 11.5px; letter-spacing: -0.2px;">
                                            <?= $title ?>
                                        </div>
                                        <div class="text-muted text-truncate mt-0.5" style="font-size: 9.5px; max-width: 190px;">
                                            <?= htmlspecialchars($tx['description'] ?? 'Transaksi Dompet') ?>
                                        </div>
                                        <div class="text-muted mt-1" style="font-size: 8.5px;">
                                            <i class="bi bi-clock me-0.5"></i><?= date('d M Y, H:i', strtotime($tx['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Amount -->
                                <div class="text-end flex-shrink-0" style="min-width: 80px;">
                                    <div class="fw-extrabold" style="font-size: 12.5px; color: <?= $color ?>; letter-spacing: -0.3px;">
                                        <?= $sign ?><?= format_rupiah($tx['amount']) ?>
                                    </div>
                                    <span class="badge rounded-pill px-2 py-0.5 mt-0.5" style="font-size: 8px; font-weight: 700; background: <?= $bgColor ?>; color: <?= $color ?>; border: 1px solid <?= $color ?>20;">
                                        <?= $badgeText ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Refund Info Strip -->
                            <?php if ($txCat === 'order_refund'): ?>
                            <div class="mt-2 pt-1.5 border-top d-flex align-items-center justify-content-between" style="font-size: 9px; border-color: #F1F5F9 !important;">
                                <span class="text-primary fw-semibold"><i class="bi bi-shield-check me-1"></i>Dana dikembalikan otomatis (Tidak ada driver)</span>
                                <?php if (!empty($tx['reference_id'])): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-1.5 py-0.5 rounded" style="font-family: monospace; font-size: 8px;">
                                    #<?= htmlspecialchars($tx['reference_id']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- PANE 2: Tiket Top Up Midtrans -->
        <div class="tab-pane fade" id="topup-pane" role="tabpanel">
            <!-- Filter Pills -->
            <div class="d-flex gap-1.5 mb-2.5 overflow-auto pb-1" style="scrollbar-width: none;">
                <button type="button" onclick="filterTopupList('all')" class="btn btn-sm btn-dark rounded-pill px-3 py-1 fw-bold topup-filter-btn active" data-filter="all" style="font-size: 10px;">
                    Semua (<?= count($topup_logs ?? []) ?>)
                </button>
                <button type="button" onclick="filterTopupList('success')" class="btn btn-sm btn-light border rounded-pill px-3 py-1 fw-semibold topup-filter-btn text-success" data-filter="success" style="font-size: 10px;">
                    <i class="bi bi-check-circle-fill me-1 text-success"></i> Berhasil (<?= $topup_stats['success_count'] ?? 0 ?>)
                </button>
                <button type="button" onclick="filterTopupList('pending')" class="btn btn-sm btn-light border rounded-pill px-3 py-1 fw-semibold topup-filter-btn text-warning-emphasis" data-filter="pending" style="font-size: 10px;">
                    <i class="bi bi-hourglass-split me-1 text-warning"></i> Menunggu (<?= $topup_stats['pending_count'] ?? 0 ?>)
                </button>
                <button type="button" onclick="filterTopupList('failed')" class="btn btn-sm btn-light border rounded-pill px-3 py-1 fw-semibold topup-filter-btn text-danger" data-filter="failed" style="font-size: 10px;">
                    <i class="bi bi-x-circle-fill me-1 text-danger"></i> Batal (<?= $topup_stats['failed_count'] ?? 0 ?>)
                </button>
            </div>

            <?php if (empty($topup_logs)): ?>
                <div class="p-4 bg-white border text-center text-muted" style="font-size: 11px; border-radius: 14px;">
                    <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 44px; height: 44px; font-size: 18px;">
                        <i class="bi bi-journal-x"></i>
                    </div>
                    <div class="fw-bold text-dark mb-1">Belum Ada Tiket Top Up Midtrans</div>
                    <div class="text-muted" style="font-size: 10px;">
                        Pilih nominal di atas untuk mengisi saldo CicalengkaPay.
                    </div>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-2" id="topupLogsContainer">
                    <?php foreach ($topup_logs as $log): 
                        $status = $log['status'] ?? 'pending';
                        $statusClass = 'pending';
                        $badgeClass = 'bg-warning-subtle text-warning-emphasis border-warning-subtle';
                        $badgeText = 'Menunggu';
                        $badgeIcon = 'bi-hourglass-split';
                        $amountClass = 'text-warning-emphasis';
                        $iconClass = 'bg-warning-subtle text-warning';
                        $mainIcon = 'bi-clock-history';

                        if ($status === 'success') {
                            $statusClass = 'success';
                            $badgeClass = 'bg-success-subtle text-success border-success-subtle';
                            $badgeText = 'Berhasil';
                            $badgeIcon = 'bi-check-circle-fill';
                            $amountClass = 'text-success';
                            $iconClass = 'bg-success-subtle text-success';
                            $mainIcon = 'bi-arrow-down-left-circle-fill';
                        } elseif ($status === 'failed' || $status === 'canceled') {
                            $statusClass = 'failed';
                            $badgeClass = 'bg-danger-subtle text-danger border-danger-subtle';
                            $badgeText = ($status === 'canceled') ? 'Dibatalkan' : 'Gagal';
                            $badgeIcon = 'bi-x-circle-fill';
                            $amountClass = 'text-danger text-decoration-line-through';
                            $iconClass = 'bg-danger-subtle text-danger';
                            $mainIcon = 'bi-x-circle-fill';
                        }
                    ?>
                        <div class="topup-item-card p-3.5 bg-white border shadow-xs" style="border-radius: 14px;" data-status="<?= $statusClass ?>">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center gap-2.5">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center <?= $iconClass ?>" style="width: 34px; height: 34px; font-size: 15px; flex-shrink: 0;">
                                        <i class="bi <?= $mainIcon ?>"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 11.5px;">
                                            Top Up CicalengkaPay
                                        </div>
                                        <div class="text-muted" style="font-size: 9.5px; font-family: monospace;">
                                            <?= htmlspecialchars($log['topup_code']) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-extrabold <?= $amountClass ?>" style="font-size: 12.5px;">
                                        <?= $status === 'success' ? '+' : '' ?><?= format_rupiah($log['amount']) ?>
                                    </div>
                                    <span class="badge <?= $badgeClass ?> border px-2 py-0.5 rounded-pill mt-0.5" style="font-size: 8.5px; font-weight: 700;">
                                        <i class="bi <?= $badgeIcon ?> me-0.5"></i> <?= $badgeText ?>
                                    </span>
                                </div>
                            </div>

                            <div class="pt-2 border-top d-flex justify-content-between align-items-center text-muted" style="font-size: 10px;">
                                <div class="d-flex align-items-center gap-2">
                                    <span><i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($log['created_at'])) ?></span>
                                    <span class="text-secondary">• <?= htmlspecialchars($log['payment_type'] ?? 'Midtrans') ?></span>
                                </div>

                                <?php if ($status === 'pending'): ?>
                                    <div class="d-flex gap-1.5">
                                        <?php if (!empty($log['snap_token'])): ?>
                                        <button type="button" onclick="resumePendingSnap('<?= htmlspecialchars($log['snap_token']) ?>', '<?= htmlspecialchars($log['topup_code']) ?>', <?= (int)$log['amount'] ?>)" class="btn btn-danger btn-sm rounded-pill py-1 px-2.5 fw-bold" style="font-size: 9.5px;">
                                            <i class="bi bi-credit-card-fill me-1"></i> Bayar
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif ($status === 'failed' || $status === 'canceled'): ?>
                                    <button type="button" onclick="quickTopUp(<?= (int)$log['amount'] ?>)" class="btn btn-outline-danger btn-sm rounded-pill py-1 px-2 fw-semibold" style="font-size: 9.5px;">
                                        <i class="bi bi-arrow-repeat"></i> Ulang
                                    </button>
                                <?php else: ?>
                                    <span class="text-success fw-semibold" style="font-size: 9.5px;">
                                        <i class="bi bi-check2-all me-0.5"></i> Masuk
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($log['notes'])): ?>
                            <div class="mt-1.5 px-2 py-1 rounded-2 bg-light text-secondary" style="font-size: 9px;">
                                <i class="bi bi-info-circle me-1"></i><?= htmlspecialchars($log['notes']) ?>
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
const IS_SANDBOX = <?= !empty($is_sandbox) ? 'true' : 'false' ?>;

function filterMutationList(category) {
    document.querySelectorAll('.mutation-filter-btn').forEach(btn => {
        if (btn.dataset.mfilter === category) {
            btn.classList.remove('btn-light', 'border');
            btn.classList.add('btn-dark');
        } else {
            btn.classList.remove('btn-dark');
            btn.classList.add('btn-light', 'border');
        }
    });

    const items = document.querySelectorAll('.mutation-item-card');
    items.forEach(card => {
        if (category === 'all' || card.dataset.cat === category) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function filterTopupList(status) {
    // Update active filter button style
    document.querySelectorAll('.topup-filter-btn').forEach(btn => {
        if (btn.dataset.filter === status) {
            btn.classList.remove('btn-light', 'border');
            btn.classList.add('btn-dark');
        } else {
            btn.classList.remove('btn-dark');
            btn.classList.add('btn-light', 'border');
        }
    });

    // Filter items
    const items = document.querySelectorAll('.topup-item-card');
    items.forEach(card => {
        if (status === 'all' || card.dataset.status === status) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

async function quickTopUp(nominal) {
    Swal.fire({
        title: 'Top Up CicalengkaPay',
        text: 'Lanjutkan pengisian saldo Rp ' + nominal.toLocaleString('id-ID') + ' via Midtrans (QRIS / VA / E-Wallet)?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-credit-card-fill me-1"></i> Bayar Sekarang',
        confirmButtonColor: '#EE2737',
        cancelButtonText: 'Batal'
    }).then(async (res) => {
        if (res.isConfirmed) {
            executeMidtransTopUp(nominal);
        }
    });
}

function customTopUpDialog() {
    Swal.fire({
        title: 'Isi Saldo CicalengkaPay',
        input: 'number',
        inputLabel: 'Masukkan nominal saldo yang diinginkan (Min. Rp 10.000)',
        inputPlaceholder: 'Contoh: 50000',
        showCancelButton: true,
        confirmButtonText: 'Lanjut Bayar',
        confirmButtonColor: '#EE2737',
        cancelButtonText: 'Batal',
        inputValidator: (value) => {
            if (!value || parseInt(value) < 10000) {
                return 'Nominal minimal top up adalah Rp 10.000!';
            }
        }
    }).then((res) => {
        if (res.isConfirmed && res.value) {
            executeMidtransTopUp(parseInt(res.value));
        }
    });
}

function resumePendingSnap(snapToken, orderId, nominal) {
    if (typeof window.snap === 'undefined') {
        Swal.fire('Error', 'Script Midtrans Snap belum termuat. Silakan muat ulang halaman.', 'error');
        return;
    }
    openSnapPayment(snapToken, orderId, nominal);
}

async function executeMidtransTopUp(nominal) {
    Swal.fire({
        title: 'Menyiapkan Pembayaran...',
        text: 'Menghubungkan ke gateway Midtrans...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const formData = new FormData();
        formData.append('amount', nominal);

        const response = await fetch(window.BASE_URL + '/wallet/topup-midtrans', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (!data.success || !data.data.snap_token) {
            Swal.fire('Gagal', data.message || 'Gagal membuat tiket pembayaran Midtrans.', 'error');
            return;
        }

        Swal.close();

        if (typeof window.snap === 'undefined') {
            throw new Error('Script Midtrans Snap belum termuat. Silakan muat ulang halaman.');
        }

        openSnapPayment(data.data.snap_token, data.data.order_id, nominal);
    } catch (err) {
        console.error(err);
        Swal.fire('Error', err.message || 'Terjadi kesalahan sistem saat menghubungi gateway pembayaran.', 'error');
    }
}

function openSnapPayment(snapToken, orderId, nominal) {
    window.snap.pay(snapToken, {
        onSuccess: function(result) {
            // Auto verify payment on backend
            fetch(window.BASE_URL + '/payment/verify', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    order_id: orderId,
                    transaction_status: result.transaction_status || 'settlement',
                    payment_type: result.payment_type || 'midtrans',
                    gross_amount: nominal
                })
            }).finally(() => {
                Swal.fire({
                    title: 'Top Up Berhasil! 🎉',
                    text: 'Saldo CicalengkaPay sebesar Rp ' + Number(nominal).toLocaleString('id-ID') + ' telah masuk ke dompet Anda.',
                    icon: 'success',
                    confirmButtonColor: '#EE2737',
                    confirmButtonText: 'Selesai'
                }).then(() => {
                    location.reload();
                });
            });
        },
        onPending: function(result) {
            // Update log to pending
            fetch(window.BASE_URL + '/payment/topup-update-status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    order_id: orderId,
                    status: 'pending',
                    payment_type: result.payment_type || 'midtrans_va',
                    notes: 'Menunggu pembayaran di channel yang dipilih'
                })
            }).finally(() => {
                Swal.fire({
                    title: 'Menunggu Pembayaran ⏳',
                    text: 'Silakan selesaikan pembayaran sesuai instruksi Virtual Account / QRIS yang dipilih.',
                    icon: 'info',
                    confirmButtonColor: '#EE2737'
                }).then(() => {
                    location.reload();
                });
            });
        },
        onError: function(result) {
            // Update log to failed
            fetch(window.BASE_URL + '/payment/topup-update-status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    order_id: orderId,
                    status: 'failed',
                    notes: 'Pembayaran gagal diproses di gateway Midtrans'
                })
            }).finally(() => {
                Swal.fire('Pembayaran Gagal', 'Proses top up dibatalkan atau gagal diproses.', 'error').then(() => {
                    location.reload();
                });
            });
        },
        onClose: function() {
            Swal.fire({
                title: 'Jendela Pembayaran Ditutup',
                text: 'Transaksi belum diselesaikan. Anda dapat melanjutkan pembayaran kapan saja dari riwayat transaksi.',
                icon: 'info',
                confirmButtonColor: '#EE2737'
            }).then(() => {
                location.reload();
            });
        }
    });
}

// Check if returning from Midtrans redirect
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const retOrderId = urlParams.get('order_id');
    const retStatusCode = urlParams.get('status_code');
    const retTxnStatus = urlParams.get('transaction_status') || urlParams.get('status');

    if (retOrderId && retOrderId.startsWith('TOPUP-')) {
        // Clean URL query params cleanly without page reload
        window.history.replaceState({}, document.title, window.location.pathname);

        if (retStatusCode === '200' || retTxnStatus === 'settlement' || retTxnStatus === 'capture') {
            Swal.fire({
                icon: 'success',
                title: 'Top Up Berhasil! 🎉',
                text: 'Pembayaran telah dikonfirmasi dan saldo CicalengkaPay Anda telah bertambah.',
                confirmButtonColor: '#EE2737'
            });
        } else if (retStatusCode === '201' || retTxnStatus === 'pending') {
            Swal.fire({
                icon: 'info',
                title: 'Menunggu Pembayaran ⏳',
                text: 'Instruksi pembayaran telah dibuat. Silakan selesaikan pembayaran di channel yang Anda pilih.',
                confirmButtonColor: '#EE2737'
            });
        }
    }
});
</script>

