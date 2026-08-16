<?php if (!empty($snap_url)): ?>
<script src="<?= $snap_url ?>" data-client-key="<?= $client_key ?? '' ?>"></script>
<?php endif; ?>

<div class="p-3 border-bottom bg-white d-flex align-items-center gap-2">
    <a href="<?= $baseUrl ?>/profile" class="btn btn-light btn-sm rounded-circle"><i class="bi bi-arrow-left"></i></a>
    <h6 class="fw-bold m-0" style="color: var(--gojek-charcoal);">Dompet Digital CicalengkaPay</h6>
</div>

<div class="p-3">
    <!-- CicalengkaPay Card Banner -->
    <div class="p-4 rounded-4 text-white shadow-sm mb-3 position-relative overflow-hidden" style="background: linear-gradient(135deg, #EE2737 0%, #991B1B 100%);">
        <!-- Watermark Icon -->
        <div class="position-absolute" style="right: -10px; bottom: -15px; font-size: 110px; opacity: 0.08; line-height: 1; pointer-events: none;">
            <i class="bi bi-wallet-fill"></i>
        </div>

        <div class="position-relative z-1">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <div class="d-flex align-items-center gap-1.5 mb-1">
                        <span style="font-weight: 900; font-size: 20px; letter-spacing: -0.5px;">Cicalengka<span style="color: #FFE4E6;">Pay</span></span>
                        <span class="badge bg-white text-danger px-2 py-0.5 rounded-pill fw-bold" style="font-size: 9.5px;">DIGITAL WALLET</span>
                    </div>
                    <div class="text-white-50 small" style="font-size: 11px; letter-spacing: 0.5px;">SALDO UTAMA AKTIF</div>
                    <div class="display-6 fw-extrabold my-1" style="font-size: 32px; letter-spacing: -0.5px;"><?= format_rupiah($wallet['balance'] ?? 0) ?></div>
                </div>
                <div class="rounded-circle bg-white text-dark d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                    <i class="bi bi-shield-lock-fill fs-4" style="color: #EE2737;"></i>
                </div>
            </div>

            <!-- Mini Quick Stats -->
            <div class="row g-2 mt-2 mb-3">
                <div class="col-4">
                    <div class="p-2 rounded-3 text-center" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.12);">
                        <div class="text-white-50" style="font-size: 9.5px;">Topup Berhasil</div>
                        <div class="fw-bold text-white small mt-0.5"><?= $topup_stats['success_count'] ?? 0 ?>x</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 rounded-3 text-center" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.12);">
                        <div class="text-white-50" style="font-size: 9.5px;">Menunggu</div>
                        <div class="fw-bold text-warning small mt-0.5"><?= $topup_stats['pending_count'] ?? 0 ?>x</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 rounded-3 text-center" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.12);">
                        <div class="text-white-50" style="font-size: 9.5px;">Gagal/Batal</div>
                        <div class="fw-bold text-white-50 small mt-0.5"><?= $topup_stats['failed_count'] ?? 0 ?>x</div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <button onclick="customTopUpDialog()" class="btn btn-light btn-sm fw-bold px-3 py-2 rounded-pill shadow-xs flex-grow-1" style="color: #EE2737; font-size: 12.5px;">
                    <i class="bi bi-plus-circle-fill me-1"></i> Top Up Saldo
                </button>
            </div>
        </div>
    </div>

    <!-- Quick Top Up Amount Selector -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-3 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-2.5">
            <h6 class="fw-bold small m-0" style="color: var(--gojek-charcoal); font-size: 12.5px;">
                <i class="bi bi-lightning-charge-fill text-warning me-1"></i> Isi Saldo Instan (Midtrans Snap)
            </h6>
            <span class="badge text-white px-2 py-0.5" style="background:#002B49; font-size: 9.5px; font-weight: 700; border-radius: 5px;">QRIS / VA / E-WALLET</span>
        </div>
        <div class="row g-2">
            <div class="col-6">
                <button type="button" onclick="quickTopUp(20000)" class="btn btn-light border w-100 py-2 rounded-3 text-start hover-red-card">
                    <div class="text-muted" style="font-size: 10px;">Nominal</div>
                    <div class="fw-bold text-dark small">Rp 20.000</div>
                </button>
            </div>
            <div class="col-6">
                <button type="button" onclick="quickTopUp(50000)" class="btn btn-light border w-100 py-2 rounded-3 text-start hover-red-card">
                    <div class="text-muted" style="font-size: 10px;">Nominal</div>
                    <div class="fw-bold text-dark small">Rp 50.000</div>
                </button>
            </div>
            <div class="col-6">
                <button type="button" onclick="quickTopUp(100000)" class="btn btn-light border w-100 py-2 rounded-3 text-start hover-red-card">
                    <div class="text-muted" style="font-size: 10px;">Nominal</div>
                    <div class="fw-bold text-dark small">Rp 100.000</div>
                </button>
            </div>
            <div class="col-6">
                <button type="button" onclick="quickTopUp(200000)" class="btn btn-light border w-100 py-2 rounded-3 text-start hover-red-card">
                    <div class="text-muted" style="font-size: 10px;">Nominal</div>
                    <div class="fw-bold text-dark small">Rp 200.000</div>
                </button>
            </div>
        </div>
        <div class="mt-2 text-center">
            <button onclick="customTopUpDialog()" class="btn btn-outline-danger btn-sm rounded-pill fw-semibold px-3 py-1.5 w-100" style="font-size: 12px;">
                <i class="bi bi-pencil-square me-1"></i> Masukkan Nominal Lainnya
            </button>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills nav-fill bg-white p-1 rounded-4 shadow-sm border mb-3" id="walletTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-4 fw-bold py-2 px-2 small d-flex align-items-center justify-content-center gap-1.5" id="topup-tab" data-bs-toggle="tab" data-bs-target="#topup-pane" type="button" role="tab" style="font-size: 12px;">
                <i class="bi bi-journal-check text-danger"></i>
                <span>Riwayat Log Top Up</span>
                <span class="badge bg-danger-subtle text-danger rounded-pill px-1.5 ms-0.5" style="font-size: 9.5px;"><?= count($topup_logs ?? []) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-4 fw-bold py-2 px-2 small d-flex align-items-center justify-content-center gap-1.5" id="mutation-tab" data-bs-toggle="tab" data-bs-target="#mutation-pane" type="button" role="tab" style="font-size: 12px;">
                <i class="bi bi-receipt text-secondary"></i>
                <span>Semua Mutasi</span>
                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-1.5 ms-0.5" style="font-size: 9.5px;"><?= count($transactions ?? []) ?></span>
            </button>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="walletTabContent">
        <!-- PANE 1: Riwayat Log Top Up (Berhasil, Gagal, Pending) -->
        <div class="tab-pane fade show active" id="topup-pane" role="tabpanel">
            <!-- Filter Pills -->
            <div class="d-flex gap-1.5 mb-2.5 overflow-auto pb-1" style="scrollbar-width: none;">
                <button type="button" onclick="filterTopupList('all')" class="btn btn-sm btn-dark rounded-pill px-3 py-1 fw-bold topup-filter-btn active" data-filter="all" style="font-size: 11px;">
                    Semua (<?= count($topup_logs ?? []) ?>)
                </button>
                <button type="button" onclick="filterTopupList('success')" class="btn btn-sm btn-light border rounded-pill px-3 py-1 fw-semibold topup-filter-btn text-success" data-filter="success" style="font-size: 11px;">
                    <i class="bi bi-check-circle-fill me-1 text-success"></i> Berhasil (<?= $topup_stats['success_count'] ?? 0 ?>)
                </button>
                <button type="button" onclick="filterTopupList('pending')" class="btn btn-sm btn-light border rounded-pill px-3 py-1 fw-semibold topup-filter-btn text-warning-emphasis" data-filter="pending" style="font-size: 11px;">
                    <i class="bi bi-hourglass-split me-1 text-warning"></i> Menunggu (<?= $topup_stats['pending_count'] ?? 0 ?>)
                </button>
                <button type="button" onclick="filterTopupList('failed')" class="btn btn-sm btn-light border rounded-pill px-3 py-1 fw-semibold topup-filter-btn text-danger" data-filter="failed" style="font-size: 11px;">
                    <i class="bi bi-x-circle-fill me-1 text-danger"></i> Gagal/Batal (<?= $topup_stats['failed_count'] ?? 0 ?>)
                </button>
            </div>

            <?php if (empty($topup_logs)): ?>
                <div class="p-4 bg-white rounded-4 border text-center text-muted small">
                    <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 44px; height: 44px; font-size: 20px;">
                        <i class="bi bi-journal-x"></i>
                    </div>
                    <div class="fw-bold text-dark mb-1">Belum Ada Riwayat Top Up</div>
                    <div class="text-muted" style="font-size: 11.5px;">
                        Anda belum pernah melakukan transaksi pengisian saldo. Pilih nominal di atas untuk mengisi saldo CicalengkaPay.
                    </div>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-2" id="topupLogsContainer">
                    <?php foreach ($topup_logs as $log): 
                        $status = $log['status'] ?? 'pending';
                        $statusClass = 'pending';
                        $badgeClass = 'bg-warning-subtle text-warning-emphasis border-warning-subtle';
                        $badgeText = 'Menunggu Pembayaran';
                        $badgeIcon = 'bi-hourglass-split';
                        $amountClass = 'text-warning-emphasis';
                        $iconClass = 'bg-warning-subtle text-warning';
                        $mainIcon = 'bi-clock-history';

                        if ($status === 'success') {
                            $statusClass = 'success';
                            $badgeClass = 'bg-success-subtle text-success border-success-subtle';
                            $badgeText = 'Top Up Berhasil';
                            $badgeIcon = 'bi-check-circle-fill';
                            $amountClass = 'text-success';
                            $iconClass = 'bg-success-subtle text-success';
                            $mainIcon = 'bi-arrow-down-left-circle-fill';
                        } elseif ($status === 'failed' || $status === 'canceled') {
                            $statusClass = 'failed';
                            $badgeClass = 'bg-danger-subtle text-danger border-danger-subtle';
                            $badgeText = ($status === 'canceled') ? 'Dibatalkan' : 'Top Up Gagal';
                            $badgeIcon = 'bi-x-circle-fill';
                            $amountClass = 'text-danger text-decoration-line-through';
                            $iconClass = 'bg-danger-subtle text-danger';
                            $mainIcon = 'bi-x-circle-fill';
                        }
                    ?>
                        <div class="topup-item-card p-3 bg-white rounded-4 border shadow-xs" data-status="<?= $statusClass ?>">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center gap-2.5">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center <?= $iconClass ?>" style="width: 36px; height: 36px; font-size: 18px; flex-shrink: 0;">
                                        <i class="bi <?= $mainIcon ?>"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold small text-dark" style="font-size: 13px;">
                                            Top Up CicalengkaPay
                                        </div>
                                        <div class="text-muted" style="font-size: 10.5px; font-family: monospace;">
                                            <?= htmlspecialchars($log['topup_code']) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-extrabold small <?= $amountClass ?>" style="font-size: 13.5px;">
                                        <?= $status === 'success' ? '+' : '' ?><?= format_rupiah($log['amount']) ?>
                                    </div>
                                    <span class="badge <?= $badgeClass ?> border px-2 py-0.5 rounded-pill" style="font-size: 9.5px; font-weight: 700;">
                                        <i class="bi <?= $badgeIcon ?> me-0.5"></i> <?= $badgeText ?>
                                    </span>
                                </div>
                            </div>

                            <div class="pt-2 border-top d-flex justify-content-between align-items-center text-muted" style="font-size: 11px;">
                                <div class="d-flex align-items-center gap-2">
                                    <span><i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($log['created_at'])) ?></span>
                                    <span class="text-secondary">• <?= htmlspecialchars($log['payment_type'] ?? 'Midtrans Snap') ?></span>
                                </div>

                                <?php if ($status === 'pending'): ?>
                                    <div class="d-flex gap-1">
                                        <?php if (!empty($log['snap_token'])): ?>
                                        <button type="button" onclick="resumePendingSnap('<?= htmlspecialchars($log['snap_token']) ?>', '<?= htmlspecialchars($log['topup_code']) ?>', <?= (int)$log['amount'] ?>)" class="btn btn-danger btn-sm rounded-pill py-0.5 px-2.5 fw-bold" style="font-size: 10.5px;">
                                            <i class="bi bi-credit-card-fill me-0.5"></i> Bayar
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif ($status === 'failed' || $status === 'canceled'): ?>
                                    <button type="button" onclick="quickTopUp(<?= (int)$log['amount'] ?>)" class="btn btn-outline-danger btn-sm rounded-pill py-0.5 px-2 fw-semibold" style="font-size: 10.5px;">
                                        <i class="bi bi-arrow-repeat"></i> Coba Lagi
                                    </button>
                                <?php else: ?>
                                    <span class="text-success small fw-semibold" style="font-size: 10.5px;">
                                        <i class="bi bi-check2-all me-0.5"></i> Saldo Masuk
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($log['notes'])): ?>
                            <div class="mt-1.5 px-2 py-1 rounded-2 bg-light text-secondary" style="font-size: 10px;">
                                <i class="bi bi-info-circle me-1"></i><?= htmlspecialchars($log['notes']) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- PANE 2: Semua Mutasi Dompet (Debit & Credit) -->
        <div class="tab-pane fade" id="mutation-pane" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-2.5">
                <h6 class="fw-bold small m-0" style="color: var(--gojek-charcoal); font-size: 12.5px;">Riwayat Mutasi Saldo</h6>
                <span class="badge bg-light text-muted border px-2 py-1" style="font-size: 10px;"><?= count($transactions) ?> Mutasi</span>
            </div>

            <?php if (empty($transactions)): ?>
                <div class="p-4 bg-white rounded-4 border text-center text-muted small">
                    <i class="bi bi-receipt-cutoff fs-2 text-muted mb-2 d-block"></i>
                    Belum ada mutasi transaksi dompet digital.
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($transactions as $tx): ?>
                        <div class="p-3 bg-white rounded-4 border shadow-xs d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2.5">
                                <div class="rounded-circle d-flex align-items-center justify-content-center <?= $tx['type'] === 'credit' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>" style="width: 36px; height: 36px; flex-shrink: 0;">
                                    <i class="bi <?= $tx['type'] === 'credit' ? 'bi-arrow-down-left' : 'bi-arrow-up-right' ?> fs-5"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold small text-dark" style="font-size: 12.5px;"><?= htmlspecialchars($tx['description'] ?? 'Transaksi Dompet') ?></div>
                                    <div class="text-muted" style="font-size: 10.5px;"><?= date('d M Y, H:i', strtotime($tx['created_at'])) ?></div>
                                </div>
                            </div>
                            <div class="fw-bold small text-end <?= $tx['type'] === 'credit' ? 'text-success' : 'text-danger' ?>" style="font-size: 13px;">
                                <?= $tx['type'] === 'credit' ? '+' : '-' ?><?= format_rupiah($tx['amount']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const IS_SANDBOX = <?= !empty($is_sandbox) ? 'true' : 'false' ?>;

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

