<?php if (!empty($snap_url)): ?>
<script src="<?= $snap_url ?>" data-client-key="<?= $client_key ?? '' ?>"></script>
<?php endif; ?>

<div class="p-3 border-bottom bg-white d-flex align-items-center gap-2">
    <a href="<?= $baseUrl ?>/profile" class="btn btn-light btn-sm rounded-circle"><i class="bi bi-arrow-left"></i></a>
    <h6 class="fw-bold m-0" style="color: var(--gojek-charcoal);">Dompet Digital CicalengkaPay</h6>
</div>

<div class="p-3">
    <!-- CicalengkaPay Card Banner -->
    <div class="p-4 rounded-4 text-white shadow mb-4" style="background: linear-gradient(135deg, #EE2737 0%, #B91C1C 100%);">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span style="font-weight: 900; font-size: 20px; letter-spacing: -0.5px;">Cicalengka<span style="color: #FFE4E6;">Pay</span></span>
                </div>
                <div class="text-white-50 small" style="font-size: 11px;">SALDO UTAMA</div>
                <div class="display-6 fw-bold"><?= format_rupiah($wallet['balance'] ?? 0) ?></div>
            </div>
            <div class="rounded-circle bg-white text-dark d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                <i class="bi bi-shield-lock-fill fs-4" style="color: #EE2737;"></i>
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <button onclick="customTopUpDialog()" class="btn btn-light btn-sm fw-bold px-3 rounded-pill shadow-xs" style="color: #EE2737;">
                <i class="bi bi-plus-circle-fill me-1"></i> Top Up Saldo
            </button>
            <?php if (!empty($is_sandbox)): ?>
            <button onclick="instantSandboxTopUp(100000)" class="btn btn-warning btn-sm fw-bold px-3 rounded-pill text-dark shadow-xs border-0">
                <i class="bi bi-lightning-charge-fill me-1 text-dark"></i> +100rb (Sandbox)
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Top Up Amount Selector -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold small m-0" style="color: var(--gojek-charcoal);"><i class="bi bi-lightning-charge-fill text-warning me-1"></i> Isi Saldo Cepat (Midtrans)</h6>
            <span class="badge text-white px-2 py-1" style="background:#002B49; font-size: 10px; font-weight: 700; border-radius: 6px;">MIDTRANS SNAP</span>
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
        <div class="mt-3 text-center">
            <button onclick="customTopUpDialog()" class="btn btn-outline-danger btn-sm rounded-pill fw-semibold px-4 w-100">
                <i class="bi bi-pencil-square me-1"></i> Masukkan Nominal Lainnya
            </button>
        </div>
    </div>

    <!-- Riwayat Transaksi -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold small m-0" style="color: var(--gojek-charcoal);">Riwayat Transaksi Dompet</h6>
        <span class="badge bg-light text-muted border px-2 py-1" style="font-size: 10px;"><?= count($transactions) ?> Transaksi</span>
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
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center <?= $tx['type'] === 'credit' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>" style="width: 38px; height: 38px;">
                            <i class="bi <?= $tx['type'] === 'credit' ? 'bi-arrow-down-left' : 'bi-arrow-up-right' ?> fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-semibold small text-dark"><?= htmlspecialchars($tx['description'] ?? 'Transaksi Dompet') ?></div>
                            <div class="text-muted" style="font-size: 11px;"><?= date('d M Y, H:i', strtotime($tx['created_at'])) ?></div>
                        </div>
                    </div>
                    <div class="fw-bold small <?= $tx['type'] === 'credit' ? 'text-success' : 'text-danger' ?>">
                        <?= $tx['type'] === 'credit' ? '+' : '-' ?><?= format_rupiah($tx['amount']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
const IS_SANDBOX = <?= !empty($is_sandbox) ? 'true' : 'false' ?>;

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

async function instantSandboxTopUp(nominal) {
    Swal.fire({
        title: 'Top Up Sandbox Instan ⚡',
        text: 'Menambahkan saldo simulasi Rp ' + nominal.toLocaleString('id-ID') + ' ke akun Anda...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const orderId = 'TOPUP-<?= auth_id() ?>-' + Date.now() + '-' + Math.floor(Math.random() * 900 + 100);
        const res = await fetch(window.BASE_URL + '/payment/simulate-sandbox-success', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_id: orderId,
                amount: nominal,
                payment_type: 'midtrans_sandbox_quick'
            })
        });

        const data = await res.json();
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Top Up Berhasil! 🎉',
                text: 'Saldo CicalengkaPay sebesar Rp ' + nominal.toLocaleString('id-ID') + ' berhasil ditambahkan.',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Gagal', data.message || 'Gagal menambahkan saldo.', 'error');
        }
    } catch(e) {
        Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
    }
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

        window.snap.pay(data.data.snap_token, {
            onSuccess: function(result) {
                // Auto verify payment on backend
                fetch(window.BASE_URL + '/payment/verify', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        order_id: data.data.order_id,
                        transaction_status: result.transaction_status || 'settlement',
                        payment_type: result.payment_type || 'midtrans',
                        gross_amount: nominal
                    })
                }).finally(() => {
                    Swal.fire({
                        title: 'Top Up Berhasil! 🎉',
                        text: 'Saldo CicalengkaPay sebesar Rp ' + nominal.toLocaleString('id-ID') + ' telah masuk ke dompet Anda.',
                        icon: 'success',
                        confirmButtonColor: '#EE2737',
                        confirmButtonText: 'Selesai'
                    }).then(() => {
                        location.reload();
                    });
                });
            },
            onPending: function(result) {
                if (IS_SANDBOX) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Pembayaran Menunggu (Sandbox)',
                        text: 'Kode virtual account/QRIS top up telah dibuat. Ingin langsung menyelesaikan top up (Berhasil)?',
                        showCancelButton: true,
                        confirmButtonText: '⚡ Jadikan Berhasil (Lunas)',
                        confirmButtonColor: '#10B981',
                        cancelButtonText: 'Tutup'
                    }).then(async (r) => {
                        if (r.isConfirmed) {
                            await fetch(window.BASE_URL + '/payment/simulate-sandbox-success', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    order_id: data.data.order_id,
                                    amount: nominal,
                                    payment_type: result.payment_type || 'midtrans_sandbox_va'
                                })
                            });
                            Swal.fire({
                                icon: 'success',
                                title: 'Top Up Berhasil! 🎉',
                                text: 'Saldo sebesar Rp ' + nominal.toLocaleString('id-ID') + ' telah berhasil ditambahkan.',
                                timer: 1800,
                                showConfirmButton: false
                            }).then(() => { location.reload(); });
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Menunggu Pembayaran ⏳',
                        text: 'Silakan selesaikan pembayaran sesuai instruksi Virtual Account / QRIS yang dipilih.',
                        icon: 'info',
                        confirmButtonColor: '#EE2737'
                    });
                }
            },
            onError: function(result) {
                Swal.fire('Pembayaran Gagal', 'Proses top up dibatalkan atau gagal diproses.', 'error');
            },
            onClose: function() {
                if (IS_SANDBOX) {
                    Swal.fire({
                        icon: 'question',
                        title: 'Jendela Ditutup',
                        text: 'Apakah Anda ingin menyelesaikan top up Rp ' + nominal.toLocaleString('id-ID') + ' sekarang (Mode Sandbox)?',
                        showCancelButton: true,
                        confirmButtonText: '⚡ Ya, Selesaikan Top Up',
                        confirmButtonColor: '#10B981',
                        cancelButtonText: 'Batal'
                    }).then(async (r) => {
                        if (r.isConfirmed) {
                            await fetch(window.BASE_URL + '/payment/simulate-sandbox-success', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    order_id: data.data.order_id,
                                    amount: nominal,
                                    payment_type: 'midtrans_sandbox_close'
                                })
                            });
                            location.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Batal Top Up',
                        text: 'Jendela pembayaran ditutup sebelum transaksi diselesaikan.',
                        icon: 'warning',
                        confirmButtonColor: '#EE2737'
                    });
                }
            }
        });
    } catch (err) {
        console.error(err);
        Swal.fire('Error', err.message || 'Terjadi kesalahan sistem saat menghubungi gateway pembayaran.', 'error');
    }
}
</script>
