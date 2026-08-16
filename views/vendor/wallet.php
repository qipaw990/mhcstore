<!-- Merchant Digital Wallet Card -->
<div class="p-3.5 rounded-4 text-white shadow-sm mb-3 position-relative overflow-hidden" style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); border: 1px solid rgba(255,255,255,0.1);">
    <div class="position-absolute end-0 top-0 opacity-10 p-3" style="font-size: 80px; line-height: 1;">
        <i class="bi bi-wallet2"></i>
    </div>

    <div class="position-relative z-1">
        <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="text-white-50 small fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">SALDO PENGHASILAN MITRA</span>
            <span class="badge bg-success-subtle text-success px-2 py-0.5 rounded-pill" style="font-size: 9.5px; font-weight: 700;">
                <i class="bi bi-shield-check me-0.5"></i> Terverifikasi
            </span>
        </div>

        <div class="display-6 fw-extrabold my-1.5" style="letter-spacing: -0.5px; font-size: 26px;">
            <?= format_rupiah($wallet['balance'] ?? 0) ?>
        </div>

        <div class="d-flex align-items-center gap-1 text-white-50 small mb-3" style="font-size: 11.5px;">
            <span>Total Penghasilan:</span>
            <strong class="text-white"><?= format_rupiah($wallet['total_earned'] ?? 0) ?></strong>
        </div>

        <div class="d-flex gap-2">
            <button onclick="handleTarikDana()" class="btn btn-danger btn-sm rounded-pill fw-bold px-3 py-2 flex-grow-1 d-flex align-items-center justify-content-center gap-1.5 shadow-sm" style="background:#EE2737; font-size: 12px; border:none;">
                <i class="bi bi-bank"></i> Tarik Saldo ke Rekening
            </button>
        </div>
    </div>
</div>

<!-- Transaction History Section -->
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-3">
    <div class="card-header bg-white border-0 py-3 px-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold m-0 text-dark" style="font-size: 13.5px;">
            <i class="bi bi-clock-history text-danger me-1"></i> Mutasi Transaksi Toko
        </h6>
        <span class="badge bg-light text-muted border" style="font-size: 10px;"><?= count($transactions) ?> Transaksi</span>
    </div>

    <div class="card-body p-0">
        <?php if (empty($transactions)): ?>
            <div class="p-4 text-center text-muted small">
                <i class="bi bi-receipt display-6 text-muted opacity-50 d-block mb-1"></i>
                Belum ada riwayat mutasi transaksi.
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($transactions as $tx): 
                    $isCredit = ($tx['type'] === 'credit');
                ?>
                    <div class="list-group-item d-flex align-items-center justify-content-between py-2.5 px-3 border-bottom">
                        <div class="d-flex align-items-center gap-2.5">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 <?= $isCredit ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>" style="width: 34px; height: 34px; font-size: 14px;">
                                <i class="bi <?= $isCredit ? 'bi-arrow-down-left' : 'bi-arrow-up-right' ?>"></i>
                            </div>
                            <div>
                                <div class="fw-bold small text-dark"><?= htmlspecialchars($tx['description']) ?></div>
                                <div class="text-muted" style="font-size: 10.5px;">
                                    <i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($tx['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                        <div class="fw-extrabold <?= $isCredit ? 'text-success' : 'text-danger' ?>" style="font-size: 13px;">
                            <?= $isCredit ? '+' : '-' ?><?= format_rupiah($tx['amount']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function handleTarikDana() {
    Swal.fire({
        title: 'Tarik Dana Penghasilan',
        text: 'Penarikan saldo otomatis ditransfer ke rekening bank / e-wallet toko Anda setiap hari kerja pukul 17:00 WIB.',
        icon: 'info',
        confirmButtonText: 'Saya Mengerti',
        confirmButtonColor: '#EE2737'
    });
}
</script>
