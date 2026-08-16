<!-- Merchant Digital Wallet Card -->
<div class="rounded-4 text-white shadow-sm mb-3 position-relative overflow-hidden" style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); border: 1px solid rgba(255,255,255,0.12); padding: 22px 18px;">
    <!-- Background Decor Icon -->
    <div class="position-absolute" style="right: 12px; bottom: 8px; font-size: 78px; opacity: 0.08; line-height: 1; pointer-events: none;">
        <i class="bi bi-wallet2"></i>
    </div>

    <div class="position-relative z-1">
        <div class="d-flex align-items-center justify-content-between mb-1.5">
            <span class="text-white-50 small fw-bold" style="font-size: 11px; letter-spacing: 0.6px; text-transform: uppercase;">SALDO PENGHASILAN MITRA</span>
            <span class="badge bg-success-subtle text-success px-2.5 py-1 rounded-pill" style="font-size: 10px; font-weight: 700;">
                <i class="bi bi-shield-check me-0.5"></i> Terverifikasi
            </span>
        </div>

        <div class="fw-extrabold my-2" style="letter-spacing: -0.5px; font-size: 28px; line-height: 1.1;">
            <?= format_rupiah($wallet['balance'] ?? 0) ?>
        </div>

        <div class="d-flex align-items-center gap-1 text-white-50 small mb-3.5" style="font-size: 11.5px;">
            <span>Total Penghasilan:</span>
            <strong class="text-white"><?= format_rupiah($wallet['total_earned'] ?? 0) ?></strong>
        </div>

        <div>
            <button onclick="handleTarikDana()" class="btn btn-danger w-100 rounded-pill fw-bold py-2.5 d-flex align-items-center justify-content-center gap-2 shadow-sm" style="background: #EE2737; font-size: 13px; border: none;">
                <i class="bi bi-bank fs-6"></i>
                <span>Tarik Saldo ke Rekening</span>
            </button>
        </div>
    </div>
</div>

<!-- Transaction History Section -->
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-3">
    <div class="card-header bg-white border-0 py-3 px-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold m-0 text-dark" style="font-size: 13.5px;">
            <i class="bi bi-clock-history text-danger me-1.5"></i> Mutasi Transaksi Toko
        </h6>
        <span class="badge bg-light text-muted border px-2 py-1" style="font-size: 10.5px;"><?= count($transactions) ?> Transaksi</span>
    </div>

    <div class="card-body p-0">
        <?php if (empty($transactions)): ?>
            <div class="p-4 text-center text-muted small">
                <i class="bi bi-receipt display-6 text-muted opacity-50 d-block mb-1.5"></i>
                Belum ada riwayat mutasi transaksi toko.
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($transactions as $tx): 
                    $isCredit = ($tx['type'] === 'credit');
                ?>
                    <div class="list-group-item d-flex align-items-center justify-content-between py-2.5 px-3 border-bottom">
                        <div class="d-flex align-items-center gap-2.5">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 <?= $isCredit ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>" style="width: 36px; height: 36px; font-size: 15px;">
                                <i class="bi <?= $isCredit ? 'bi-arrow-down-left' : 'bi-arrow-up-right' ?>"></i>
                            </div>
                            <div>
                                <div class="fw-bold small text-dark" style="font-size: 12.5px;"><?= htmlspecialchars($tx['description']) ?></div>
                                <div class="text-muted" style="font-size: 11px;">
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
