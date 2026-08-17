<?php if (!empty($snap_url)): ?>
<script src="<?= $snap_url ?>" data-client-key="<?= $client_key ?? '' ?>"></script>
<?php endif; ?>

<div class="border-bottom bg-white d-flex align-items-center gap-2.5 sticky-top app-subpage-header px-3 py-3">
    <a href="<?= $baseUrl ?>" class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px; border: 1px solid #E2E8F0; background: #F8FAFC;"><i class="bi bi-arrow-left text-dark" style="font-size: 15px;"></i></a>
    <h6 class="fw-bold m-0 text-dark" style="font-size: 15px; letter-spacing: -0.3px;">Dompet Digital CicalengkaPay</h6>
</div>

<div class="px-3 py-3">
    <!-- CicalengkaPay Card Banner -->
    <div class="p-4 text-white shadow-2xs mb-3 position-relative overflow-hidden" style="background: linear-gradient(135deg, #EE2737 0%, #991B1B 100%); border-radius: 18px;">
        <!-- Watermark Icon -->
        <div class="position-absolute" style="right: -10px; bottom: -15px; font-size: 95px; opacity: 0.08; line-height: 1; pointer-events: none;">
            <i class="bi bi-wallet-fill"></i>
        </div>

        <div class="position-relative z-1">
            <div class="d-flex justify-content-between align-items-start mb-2.5">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span style="font-weight: 900; font-size: 17px; letter-spacing: -0.3px;">Cicalengka<span style="color: #FFE4E6;">Pay</span></span>
                        <span class="badge bg-white text-danger px-2.5 py-1 rounded-pill fw-bold" style="font-size: 9px;">DIGITAL WALLET</span>
                    </div>
                    <div class="text-white-50" style="font-size: 10px; letter-spacing: 0.4px;">SALDO UTAMA AKTIF</div>
                    <div class="fw-extrabold my-1" style="font-size: 26px; letter-spacing: -0.6px;"><?= format_rupiah($wallet['balance'] ?? 0) ?></div>
                </div>
                <div class="rounded-circle bg-white text-dark d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; box-shadow: 0 3px 10px rgba(0,0,0,0.15);">
                    <i class="bi bi-shield-lock-fill" style="color: #EE2737; font-size: 20px;"></i>
                </div>
            </div>

            <!-- Mini Quick Stats -->
            <div class="row g-2 mt-1 mb-3">
                <div class="col-4">
                    <div class="p-2 rounded-3 text-center" style="background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.18);">
                        <div class="text-white-50" style="font-size: 9.5px;">Topup Berhasil</div>
                        <div class="fw-bold text-white mt-0.5" style="font-size: 11.5px;"><?= $topup_stats['success_count'] ?? 0 ?>x</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 rounded-3 text-center" style="background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.18);">
                        <div class="text-white-50" style="font-size: 9.5px;">Menunggu</div>
                        <div class="fw-bold text-warning mt-0.5" style="font-size: 11.5px;"><?= $topup_stats['pending_count'] ?? 0 ?>x</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 rounded-3 text-center" style="background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.18);">
                        <div class="text-white-50" style="font-size: 9.5px;">Gagal/Batal</div>
                        <div class="fw-bold text-white-50 mt-0.5" style="font-size: 11.5px;"><?= $topup_stats['failed_count'] ?? 0 ?>x</div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <button onclick="customTopUpDialog()" class="btn btn-light btn-sm fw-bold px-3.5 py-2.5 rounded-pill shadow-2xs flex-grow-1" style="color: #EE2737; font-size: 11.5px;">
                    <i class="bi bi-plus-circle-fill me-1.5"></i> Top Up Saldo
                </button>
            </div>
        </div>
    </div>

    <!-- Quick Top Up Amount Selector -->
    <div class="border shadow-2xs p-3.5 mb-3 bg-white" style="border-radius: 16px; border-color: #E2E8F0 !important;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold m-0 text-dark" style="font-size: 12.5px;">
                <i class="bi bi-lightning-charge-fill text-warning me-1.5"></i> Isi Saldo Instan
            </h6>
            <span class="badge text-white px-2.5 py-1" style="background:#002B49; font-size: 9px; font-weight: 700; border-radius: 6px;">QRIS / VA / E-WALLET</span>
        </div>
        <div class="row g-2.5">
            <div class="col-6">
                <button type="button" onclick="quickTopUp(20000)" class="btn btn-light border w-100 py-2.5 px-3 rounded-3 text-start hover-red-card" style="border-radius: 12px !important; border-color: #E2E8F0 !important;">
                    <div class="text-muted" style="font-size: 9.5px;">Nominal</div>
                    <div class="fw-bold text-dark" style="font-size: 12.5px;">Rp 20.000</div>
                </button>
            </div>
            <div class="col-6">
                <button type="button" onclick="quickTopUp(50000)" class="btn btn-light border w-100 py-2.5 px-3 rounded-3 text-start hover-red-card" style="border-radius: 12px !important; border-color: #E2E8F0 !important;">
                    <div class="text-muted" style="font-size: 9.5px;">Nominal</div>
                    <div class="fw-bold text-dark" style="font-size: 12.5px;">Rp 50.000</div>
                </button>
            </div>
            <div class="col-6">
                <button type="button" onclick="quickTopUp(100000)" class="btn btn-light border w-100 py-2.5 px-3 rounded-3 text-start hover-red-card" style="border-radius: 12px !important; border-color: #E2E8F0 !important;">
                    <div class="text-muted" style="font-size: 9.5px;">Nominal</div>
                    <div class="fw-bold text-dark" style="font-size: 12.5px;">Rp 100.000</div>
                </button>
            </div>
            <div class="col-6">
                <button type="button" onclick="quickTopUp(200000)" class="btn btn-light border w-100 py-2.5 px-3 rounded-3 text-start hover-red-card" style="border-radius: 12px !important; border-color: #E2E8F0 !important;">
                    <div class="text-muted" style="font-size: 9.5px;">Nominal</div>
                    <div class="fw-bold text-dark" style="font-size: 12.5px;">Rp 200.000</div>
                </button>
            </div>
        </div>
        <div class="mt-2.5 text-center">
            <button onclick="customTopUpDialog()" class="btn btn-outline-danger btn-sm rounded-pill fw-semibold px-3.5 py-2 w-100" style="font-size: 11.5px;">
                <i class="bi bi-pencil-square me-1"></i> Masukkan Nominal Lainnya
            </button>
        </div>
    </div>

    <!-- Navigation Tabs (Default ke Mutasi Saldo & Refund) -->
    <ul class="nav nav-pills nav-fill bg-white p-1 rounded-3 shadow-2xs border mb-3" id="walletTabs" role="tablist" style="border-radius: 14px; border-color: #E2E8F0 !important;">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-3 fw-bold py-2.5 px-2 d-flex align-items-center justify-content-center gap-1.5" id="mutation-tab" data-bs-toggle="tab" data-bs-target="#mutation-pane" type="button" role="tab" style="font-size: 11.5px; border-radius: 10px !important;">
                <i class="bi bi-receipt-cutoff text-danger"></i>
                <span>Mutasi Saldo & Refund</span>
                <span class="badge bg-danger-subtle text-danger rounded-pill px-2" style="font-size: 9.5px;"><?= count($transactions ?? []) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-3 fw-bold py-2.5 px-2 d-flex align-items-center justify-content-center gap-1.5" id="topup-tab" data-bs-toggle="tab" data-bs-target="#topup-pane" type="button" role="tab" style="font-size: 11.5px; border-radius: 10px !important;">
                <i class="bi bi-journal-check text-secondary"></i>
                <span>Tiket Top Up Midtrans</span>
                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2" style="font-size: 9.5px;"><?= count($topup_logs ?? []) ?></span>
            </button>
        </li>
    </ul>

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
                <div class="d-flex flex-column gap-2.5" id="mutationContainer">
                    <?php foreach ($transactions as $tx):
                        $txCat = $tx['category'] ?? $tx['type'] ?? 'credit';
                        $isCredit = ($tx['type'] === 'credit');

                        // Clean mapping category to icon, title, color & background
                        $catMap = [
                            'order_refund'     => ['icon' => 'bi-arrow-counterclockwise',  'label' => 'Refund Pengembalian Dana', 'color' => '#2563EB', 'bg' => '#EFF6FF', 'badge' => 'Refund Saldo'],
                            'topup'            => ['icon' => 'bi-plus-circle-fill',       'label' => 'Top Up CicalengkaPay',     'color' => '#16A34A', 'bg' => '#F0FDF4', 'badge' => 'Isi Saldo'],
                            'order_payment'    => ['icon' => 'bi-bag-check-fill',          'label' => 'Pembayaran Pesanan',       'color' => '#DC2626', 'bg' => '#FEF2F2', 'badge' => 'Pembayaran'],
                            'delivery_earning' => ['icon' => 'bi-bicycle',                 'label' => 'Komisi Pengantaran',       'color' => '#7C3AED', 'bg' => '#F5F3FF', 'badge' => 'Komisi Driver'],
                            'withdraw'         => ['icon' => 'bi-bank',                    'label' => 'Penarikan Dana',           'color' => '#D97706', 'bg' => '#FFFBEB', 'badge' => 'Penarikan'],
                            'admin_credit'     => ['icon' => 'bi-shield-check',            'label' => 'Kredit Admin',             'color' => '#16A34A', 'bg' => '#F0FDF4', 'badge' => 'Kredit'],
                            'admin_debit'      => ['icon' => 'bi-shield-exclamation',      'label' => 'Debit Admin',              'color' => '#4B5563', 'bg' => '#F9FAFB', 'badge' => 'Debit'],
                        ];
                        $catInfo = $catMap[$txCat] ?? ($isCredit
                            ? ['icon' => 'bi-arrow-down-left-circle-fill', 'label' => 'Saldo Masuk',  'color' => '#16A34A', 'bg' => '#F0FDF4', 'badge' => 'Masuk']
                            : ['icon' => 'bi-arrow-up-right-circle-fill',  'label' => 'Saldo Keluar', 'color' => '#DC2626', 'bg' => '#FEF2F2', 'badge' => 'Keluar']
                        );
                    ?>
                        <div class="mutation-item-card p-3.5 bg-white border shadow-xs" style="border-radius: 16px; border-color: #E2E8F0 !important;" data-cat="<?= htmlspecialchars($txCat) ?>">
                            <div class="d-flex align-items-center gap-3">
                                <!-- Round Icon Wrapper -->
                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background: <?= $catInfo['bg'] ?>; border: 1px solid <?= $catInfo['color'] ?>20;">
                                    <i class="bi <?= $catInfo['icon'] ?>" style="font-size: 17px; color: <?= $catInfo['color'] ?>;"></i>
                                </div>

                                <!-- Transaction Title & Time -->
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex align-items-center gap-1.5 mb-0.5">
                                        <div class="fw-bold text-dark text-truncate" style="font-size: 12px; letter-spacing: -0.2px;">
                                            <?= $catInfo['label'] ?>
                                        </div>
                                    </div>
                                    <div class="text-muted text-truncate" style="font-size: 10px; line-height: 1.3;">
                                        <?= htmlspecialchars($tx['description'] ?? 'Transaksi Dompet') ?>
                                    </div>
                                    <div class="text-muted mt-1" style="font-size: 9px;">
                                        <i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($tx['created_at'])) ?>
                                    </div>
                                </div>

                                <!-- Amount & Category Badge -->
                                <div class="text-end flex-shrink-0">
                                    <div class="fw-extrabold" style="font-size: 13px; color: <?= $catInfo['color'] ?>; letter-spacing: -0.3px;">
                                        <?= $isCredit ? '+' : '-' ?><?= format_rupiah($tx['amount']) ?>
                                    </div>
                                    <span class="badge rounded-pill px-2.5 py-1 mt-1 d-inline-block" style="font-size: 8.5px; font-weight: 700; background: <?= $catInfo['bg'] ?>; color: <?= $catInfo['color'] ?>; border: 1px solid <?= $catInfo['color'] ?>30;">
                                        <?= $catInfo['badge'] ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Extra Order Refund Banner Box -->
                            <?php if ($txCat === 'order_refund'): ?>
                            <div class="mt-2.5 p-2 rounded-3 d-flex align-items-center justify-content-between" style="background: #F0F9FF; border: 1px dashed #BAE6FD; font-size: 9.5px;">
                                <div class="d-flex align-items-center gap-1.5 text-primary">
                                    <i class="bi bi-info-circle-fill"></i>
                                    <span>Pengembalian dana otomatis (Tidak ada driver)</span>
                                </div>
                                <?php if (!empty($tx['reference_id'])): ?>
                                <span class="fw-bold text-primary" style="font-family: monospace;">Order #<?= htmlspecialchars($tx['reference_id']) ?></span>
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

