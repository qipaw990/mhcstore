<div class="p-3">
    <!-- Driver Balance Card (CicalengkaGO Pay / Dompet Mitra) -->
    <div class="p-4 rounded-4 text-white shadow-sm mb-3 position-relative overflow-hidden" style="background: linear-gradient(135deg, #EE2737 0%, #B91C1C 100%); border: 1px solid rgba(255,255,255,0.2); box-shadow: 0 8px 24px rgba(238, 39, 55, 0.35) !important;">
        <!-- Background decorative ambient circle -->
        <div style="position: absolute; top: -30px; right: -30px; width: 130px; height: 130px; border-radius: 50%; background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 70%); pointer-events: none;"></div>

        <div class="d-flex justify-content-between align-items-start mb-2 position-relative" style="z-index: 1;">
            <div>
                <div class="d-flex align-items-center gap-1.5 mb-1">
                    <span class="badge rounded-pill bg-white text-danger px-2 py-0.5 fw-bold" style="font-size: 10px; letter-spacing: 0.5px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
                        <i class="bi bi-wallet-fill me-1"></i> DOMPET KURIR
                    </span>
                </div>
                <div class="fs-2 fw-extrabold text-white" style="letter-spacing: -0.5px;"><?= format_rupiah($wallet['balance'] ?? 0) ?></div>
            </div>
            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 46px; height: 46px; background: rgba(255, 255, 255, 0.22); border: 1.5px solid rgba(255, 255, 255, 0.35); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);">
                <i class="bi bi-wallet2 text-white" style="font-size: 22px; line-height: 1;"></i>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2 pt-2 border-top border-white border-opacity-25 mt-2 position-relative" style="z-index: 1;">
            <button onclick="requestWithdrawal()" class="btn btn-light btn-sm fw-bold px-3 py-1.5 rounded-pill shadow-sm d-flex align-items-center gap-1.5" style="font-size: 12px; color: #EE2737 !important; background: #ffffff; border: none;">
                <i class="bi bi-arrow-up-right-circle-fill" style="color: #EE2737; font-size: 13px;"></i> Tarik Dana Komisi
            </button>
            <span class="text-white-50 small" style="font-size: 11px;">
                <i class="bi bi-shield-lock-fill me-1"></i>Pencairan Otomatis
            </span>
        </div>
    </div>

    <!-- Quick Earning Stats -->
    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="p-3 bg-white rounded-4 border shadow-sm">
                <div class="text-muted fw-semibold" style="font-size: 11px;">Total Transaksi</div>
                <div class="fw-bold fs-6 text-dark mt-1"><?= count($transactions) ?> Riwayat</div>
            </div>
        </div>
        <div class="col-6">
            <div class="p-3 bg-white rounded-4 border shadow-sm">
                <div class="text-muted fw-semibold" style="font-size: 11px;">Bagi Hasil Komisi</div>
                <div class="fw-bold fs-6 text-success mt-1">85% Bersih</div>
            </div>
        </div>
    </div>

    <!-- Earnings History -->
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h6 class="fw-bold small m-0 text-dark">
            <i class="bi bi-clock-history me-1" style="color: #EE2737;"></i> Rincian Komisi Pengantaran
        </h6>
        <span class="badge bg-secondary-subtle text-dark rounded-pill px-2" style="font-size: 11px;"><?= count($transactions) ?> Entri</span>
    </div>

    <?php if (empty($transactions)): ?>
        <div class="p-4 bg-white rounded-4 border text-center text-muted small shadow-sm">
            <div class="rounded-circle bg-secondary-subtle text-secondary d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 44px; height: 44px; font-size: 20px;">
                <i class="bi bi-receipt"></i>
            </div>
            <div class="fw-bold text-dark mb-1">Belum Ada Komisi Tercatat</div>
            <div class="text-muted" style="font-size: 11px;">Selesaikan orderan pertama Anda untuk mulai mengumpulkan saldo dan komisi pengantaran.</div>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-2">
            <?php foreach ($transactions as $tx): ?>
                <div class="p-3 bg-white rounded-4 border shadow-sm d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; font-size: 16px;">
                            <i class="bi <?= ($tx['type'] ?? '') === 'debit' ? 'bi-arrow-up-right text-danger' : 'bi-bicycle text-success' ?>"></i>
                        </div>
                        <div>
                            <div class="fw-bold small text-dark"><?= htmlspecialchars($tx['description'] ?? 'Komisi Pengantaran') ?></div>
                            <div class="text-muted" style="font-size: 11px;">
                                <i class="bi bi-calendar3 me-1"></i><?= date('d M Y, H:i', strtotime($tx['created_at'])) ?> WIB
                            </div>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold small <?= ($tx['type'] ?? '') === 'debit' ? 'text-danger' : 'text-success' ?>">
                            <?= ($tx['type'] ?? '') === 'debit' ? '-' : '+' ?><?= format_rupiah($tx['amount']) ?>
                        </div>
                        <span class="badge bg-success-subtle text-success rounded-pill px-2" style="font-size: 9px; font-weight: 700;">Sukses</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function requestWithdrawal() {
    Swal.fire({
        title: 'Penarikan Dana Driver',
        html: `
            <div class="text-start small">
                <p class="text-muted mb-2">Penarikan saldo komisi dapat ditransfer ke rekening bank BCA, BRI, Mandiri atau e-wallet (GoPay, DANA, OVO).</p>
                <div class="mb-3">
                    <label class="form-label fw-bold">Pilih Tujuan Pencairan:</label>
                    <select id="swal-payout-bank" class="form-select form-select-sm rounded-3">
                        <option value="BCA">BCA - Bank Central Asia</option>
                        <option value="BRI">BRI - Bank Rakyat Indonesia</option>
                        <option value="Mandiri">Bank Mandiri</option>
                        <option value="GoPay">GoPay Driver</option>
                        <option value="DANA">DANA Dompet Digital</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-bold">Nomor Rekening / No. HP Akun:</label>
                    <input id="swal-payout-acc" type="text" class="form-control form-control-sm rounded-3" placeholder="Contoh: 081234567890">
                </div>
            </div>
        `,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Ajukan Penarikan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#EE2737',
        preConfirm: () => {
            const acc = document.getElementById('swal-payout-acc').value;
            if (!acc) {
                Swal.showValidationMessage('Harap masukkan nomor rekening / e-wallet!');
                return false;
            }
            return { acc: acc, bank: document.getElementById('swal-payout-bank').value };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Pengajuan Diterima!',
                text: 'Permintaan penarikan ke ' + result.value.bank + ' (' + result.value.acc + ') telah diproses tim CicalengkaGO.',
                icon: 'success',
                confirmButtonColor: '#EE2737'
            });
        }
    });
}
</script>
