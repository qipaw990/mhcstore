<div class="row g-4">
    <div class="col-md-5">
        <div class="p-4 rounded-4 text-white shadow" style="background: linear-gradient(135deg, #1e293b, #0f172a);">
            <span class="text-white-50 small" style="font-size: 11px;">SALDO PENGHASILAN MITRA</span>
            <div class="display-6 fw-bold my-2"><?= format_rupiah($wallet['balance'] ?? 0) ?></div>
            <div class="small text-white-50 mb-3">Total Pendapatan: <?= format_rupiah($wallet['total_earned'] ?? 0) ?></div>
            <button onclick="Swal.fire('Tarik Dana', 'Pengajuan penarikan dana merchant diproses otomatis ke rekening toko Anda setiap hari kerja.', 'info')" class="btn btn-primary btn-sm fw-bold px-3">
                <i class="bi bi-bank me-1"></i> Tarik Dana ke Rekening
            </button>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="fw-bold m-0"><i class="bi bi-clock-history me-1 text-primary"></i> Mutasi Transaksi Toko</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($transactions)): ?>
                    <div class="p-4 text-center text-muted small">Belum ada mutasi transaksi.</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($transactions as $tx): ?>
                            <div class="list-group-item d-flex align-items-center justify-content-between py-3">
                                <div>
                                    <div class="fw-bold small text-dark"><?= htmlspecialchars($tx['description']) ?></div>
                                    <div class="text-muted" style="font-size: 11px;"><?= date('d M Y, H:i', strtotime($tx['created_at'])) ?></div>
                                </div>
                                <div class="fw-bold <?= $tx['type'] === 'credit' ? 'text-success' : 'text-danger' ?>">
                                    <?= $tx['type'] === 'credit' ? '+' : '-' ?><?= format_rupiah($tx['amount']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
