<!-- Merchant Digital Wallet & Withdrawal Hub -->
<div class="mb-4">
    <!-- Merchant Balance Card -->
    <div class="p-3.5 text-white shadow-2xs mb-3 position-relative overflow-hidden" style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); border-radius: 16px; border: 1px solid rgba(255,255,255,0.12);">
        <!-- Background Decor Icon -->
        <div class="position-absolute" style="right: 12px; bottom: 8px; font-size: 88px; opacity: 0.07; line-height: 1; pointer-events: none;">
            <i class="bi bi-wallet2"></i>
        </div>

        <div class="position-relative z-1">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="text-white-50 fw-bold" style="font-size: 10.5px; letter-spacing: 0.6px; text-transform: uppercase;">
                    <i class="bi bi-shop me-1 text-danger"></i> SALDO PENGHASILAN TOKO
                </span>
                <span class="badge bg-success-subtle text-success px-2.5 py-1 rounded-pill" style="font-size: 9.5px; font-weight: 700;">
                    <i class="bi bi-shield-check me-0.5"></i> Siap Ditarik
                </span>
            </div>

            <div class="fw-extrabold my-1" style="letter-spacing: -0.5px; font-size: 28px; font-weight: 800; line-height: 1.1;">
                <?= format_rupiah($wallet['balance'] ?? 0) ?>
            </div>

            <!-- Mini Summary Grid -->
            <div class="row g-2 mt-2 mb-3">
                <div class="col-6">
                    <div class="p-2 rounded-3" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.08);">
                        <div class="text-white-50" style="font-size: 10px;">Total Penghasilan</div>
                        <div class="fw-bold text-white small mt-0.5"><?= format_rupiah($wallet['total_earned'] ?? 0) ?></div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 rounded-3" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.08);">
                        <div class="text-white-50" style="font-size: 10px;">Total Telah Ditarik</div>
                        <div class="fw-bold text-warning small mt-0.5"><?= format_rupiah($total_withdrawn ?? 0) ?></div>
                    </div>
                </div>
            </div>

            <div>
                <button type="button" onclick="openVendorWithdrawModal()" class="vnd-action-btn red w-100 py-2.5" style="font-size: 12.5px; border-radius: 12px;">
                    <i class="bi bi-arrow-up-right-circle-fill" style="font-size: 16px;"></i>
                    <span>Ajukan Penarikan Dana (Payout)</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs for History -->
    <ul class="nav nav-pills nav-fill vnd-tab-nav mb-3" id="walletTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active d-flex align-items-center justify-content-center gap-1.5" id="withdraw-tab" data-bs-toggle="tab" data-bs-target="#withdraw-pane" type="button" role="tab">
                <i class="bi bi-cash-stack"></i>
                <span>Riwayat Penarikan</span>
                <span class="badge bg-danger-subtle text-danger rounded-pill px-1.5 ms-1" style="font-size: 9.5px;"><?= count($withdraw_requests ?? []) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center justify-content-center gap-1.5" id="mutation-tab" data-bs-toggle="tab" data-bs-target="#mutation-pane" type="button" role="tab">
                <i class="bi bi-receipt"></i>
                <span>Mutasi Transaksi</span>
                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-1.5 ms-1" style="font-size: 9.5px;"><?= count($transactions ?? []) ?></span>
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="walletTabContent">
        <!-- PANE 1: Riwayat Penarikan Dana -->
        <div class="tab-pane fade show active" id="withdraw-pane" role="tabpanel" tabindex="0">
            <div class="vnd-card p-0 overflow-hidden">
                <div class="p-3 bg-white d-flex align-items-center justify-content-between border-bottom">
                    <h6 class="fw-bold m-0 text-dark" style="font-size: 13.5px;">
                        <i class="bi bi-clock-history text-danger me-1.5"></i> Riwayat Penarikan Saldo Toko
                    </h6>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill" style="font-size: 10px;">
                        <?= count($withdraw_requests ?? []) ?> Pengajuan
                    </span>
                </div>

                <div>
                    <?php if (empty($withdraw_requests)): ?>
                        <div class="p-4 text-center text-muted small">
                            <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 48px; height: 48px; font-size: 22px;">
                                <i class="bi bi-arrow-up-right-circle"></i>
                            </div>
                            <div class="fw-bold text-dark mb-1">Belum Ada Riwayat Penarikan</div>
                            <div class="text-muted" style="font-size: 11.5px;">
                                Anda belum pernah mengajukan penarikan saldo toko. Klik tombol <strong>"Ajukan Penarikan Dana"</strong> di atas untuk mentransfer saldo ke rekening/e-wallet Anda.
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($withdraw_requests as $wd): 
                                $status = $wd['status'] ?? 'pending';
                                $badgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                                $badgeLabel = 'Menunggu Transfer';
                                $badgeIcon = 'bi-hourglass-split';

                                if ($status === 'approved') {
                                    $badgeClass = 'bg-success-subtle text-success border border-success-subtle';
                                    $badgeLabel = 'Berhasil Ditransfer';
                                    $badgeIcon = 'bi-check-circle-fill';
                                } elseif ($status === 'rejected') {
                                    $badgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                                    $badgeLabel = 'Ditolak';
                                    $badgeIcon = 'bi-x-circle-fill';
                                }
                            ?>
                                <div class="list-group-item py-3 px-3 border-bottom">
                                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                        <div class="d-flex align-items-center gap-2.5">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; font-size: 18px; background: #FEF2F2; color: #EE2737;">
                                                <i class="bi bi-bank"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold small text-dark d-flex align-items-center gap-1.5" style="font-size: 13px;">
                                                    <span><?= htmlspecialchars($wd['bank_name']) ?></span>
                                                    <span class="badge bg-light text-muted border" style="font-size: 9.5px;"><?= htmlspecialchars($wd['withdraw_code']) ?></span>
                                                </div>
                                                <div class="text-dark small" style="font-size: 11.5px;">
                                                    <span class="fw-semibold"><?= htmlspecialchars($wd['account_number']) ?></span> 
                                                    <span class="text-muted">(a.n. <?= htmlspecialchars($wd['account_holder']) ?>)</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-extrabold text-danger" style="font-size: 14.5px;">
                                                -<?= format_rupiah($wd['amount']) ?>
                                            </div>
                                            <span class="badge <?= $badgeClass ?> rounded-pill px-2.5 py-1 mt-1" style="font-size: 9.5px; font-weight: 700;">
                                                <i class="bi <?= $badgeIcon ?> me-1"></i><?= $badgeLabel ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex align-items-center justify-content-between pt-2 border-top text-muted" style="font-size: 11px; border-color: #F1F5F9 !important;">
                                        <div>
                                            <i class="bi bi-calendar3 me-1"></i> Diajukan: <?= date('d M Y, H:i', strtotime($wd['requested_at'])) ?> WIB
                                        </div>
                                        <?php if (!empty($wd['processed_at'])): ?>
                                            <div class="text-success fw-semibold">
                                                <i class="bi bi-check-all me-1"></i> Selesai: <?= date('d M Y, H:i', strtotime($wd['processed_at'])) ?> WIB
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($wd['admin_notes'])): ?>
                                        <div class="mt-2.5 p-2.5 rounded-3 d-flex align-items-start gap-2" style="background: #FEF2F2; border: 1px solid #FECDD3;">
                                            <i class="bi bi-exclamation-circle-fill text-danger flex-shrink-0 mt-0.5" style="font-size: 13px;"></i>
                                            <div style="font-size: 11px; color: #991B1B; line-height: 1.4;">
                                                <strong>Catatan Admin:</strong> <?= htmlspecialchars($wd['admin_notes']) ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- PANE 2: Mutasi Transaksi Toko -->
        <div class="tab-pane fade" id="mutation-pane" role="tabpanel" tabindex="0">
            <div class="vnd-card p-0 overflow-hidden">
                <div class="p-3 bg-white d-flex align-items-center justify-content-between border-bottom">
                    <h6 class="fw-bold m-0 text-dark" style="font-size: 13.5px;">
                        <i class="bi bi-list-ul text-secondary me-1.5"></i> Semua Mutasi Transaksi
                    </h6>
                    <span class="badge bg-light text-muted border px-2 py-1 rounded-pill" style="font-size: 10.5px;"><?= count($transactions ?? []) ?> Transaksi</span>
                </div>

                <div>
                    <?php if (empty($transactions)): ?>
                        <div class="p-4 text-center text-muted small">
                            <i class="bi bi-receipt display-6 text-muted opacity-50 d-block mb-1.5"></i>
                            Belum ada riwayat mutasi transaksi toko.
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($transactions as $tx): 
                                $isCredit = (($tx['type'] ?? '') === 'credit');
                            ?>
                                <div class="list-group-item d-flex align-items-center justify-content-between py-2.5 px-3 border-bottom">
                                    <div class="d-flex align-items-center gap-2.5">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 <?= $isCredit ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>" style="width: 38px; height: 38px; font-size: 15px;">
                                            <i class="bi <?= $isCredit ? 'bi-arrow-down-left' : 'bi-arrow-up-right' ?>"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold small text-dark" style="font-size: 12.5px;"><?= htmlspecialchars($tx['description'] ?? 'Transaksi Toko') ?></div>
                                            <div class="text-muted" style="font-size: 11px;">
                                                <i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($tx['created_at'])) ?> WIB
                                            </div>
                                        </div>
                                    </div>
                                    <div class="fw-extrabold <?= $isCredit ? 'text-success' : 'text-danger' ?>" style="font-size: 13.5px;">
                                        <?= $isCredit ? '+' : '-' ?><?= format_rupiah($tx['amount']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.VENDOR_CURRENT_BALANCE = <?= (float)($wallet['balance'] ?? 0) ?>;

function openVendorWithdrawModal() {
    if (window.VENDOR_CURRENT_BALANCE < 10000) {
        showVendorToast('Saldo belum mencukupi. Minimal penarikan Rp 10.000.', 'warning');
        return;
    }

    Swal.fire({
        title: '<div class="d-flex align-items-center gap-2 justify-content-center text-dark"><i class="bi bi-cash-coin text-danger"></i> Tarik Saldo Toko</div>',
        html: `
            <div class="text-start small mt-1">
                <div class="p-3 bg-light rounded-4 border mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted" style="font-size: 11.5px;">Saldo Tersedia:</span>
                        <strong class="text-dark fs-6">Rp ${Number(window.VENDOR_CURRENT_BALANCE).toLocaleString('id-ID')}</strong>
                    </div>
                </div>

                <div class="mb-2.5">
                    <label class="form-label fw-bold small text-dark mb-1" for="swal-vendor-bank">Tujuan Pencairan Dana:</label>
                    <select id="swal-vendor-bank" name="vendor_bank" class="form-select form-select-sm rounded-3 py-2">
                        <option value="BCA">BCA - Bank Central Asia</option>
                        <option value="BRI">BRI - Bank Rakyat Indonesia</option>
                        <option value="Mandiri">Bank Mandiri</option>
                        <option value="BNI">BNI - Bank Negara Indonesia</option>
                        <option value="BSI">BSI - Bank Syariah Indonesia</option>
                        <option value="Bank BJB">Bank BJB / Jawa Barat</option>
                        <option value="GoPay">GoPay (E-Wallet)</option>
                        <option value="DANA">DANA (E-Wallet)</option>
                        <option value="OVO">OVO (E-Wallet)</option>
                        <option value="ShopeePay">ShopeePay (E-Wallet)</option>
                    </select>
                </div>

                <div class="mb-2.5">
                    <label class="form-label fw-bold small text-dark mb-1" for="swal-vendor-acc">Nomor Rekening / No. HP E-Wallet:</label>
                    <input id="swal-vendor-acc" name="vendor_account_no" type="text" class="form-control form-control-sm rounded-3 py-2" placeholder="Contoh: 1234567890">
                </div>

                <div class="mb-2.5">
                    <label class="form-label fw-bold small text-dark mb-1" for="swal-vendor-holder">Nama Pemilik Rekening / Akun:</label>
                    <input id="swal-vendor-holder" name="vendor_account_holder" type="text" class="form-control form-control-sm rounded-3 py-2" placeholder="Contoh: Budi Santoso">
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-bold small text-dark mb-0" for="swal-vendor-amount">Nominal Penarikan (Rp):</label>
                        <button type="button" onclick="setVendorWdAmount(${window.VENDOR_CURRENT_BALANCE})" class="btn btn-link text-danger p-0 text-decoration-none fw-bold" style="font-size: 11px;">Tarik Semua</button>
                    </div>
                    <input id="swal-vendor-amount" name="vendor_withdraw_amount" type="number" min="10000" max="${window.VENDOR_CURRENT_BALANCE}" class="form-control form-control-sm rounded-3 py-2 fw-bold" placeholder="Minimal Rp 10.000">
                    <div class="d-flex gap-1.5 mt-2 flex-wrap">
                        <button type="button" onclick="setVendorWdAmount(50000)" class="btn btn-outline-secondary btn-sm py-0.5 px-2 rounded-pill" style="font-size: 11px;">50 Rb</button>
                        <button type="button" onclick="setVendorWdAmount(100000)" class="btn btn-outline-secondary btn-sm py-0.5 px-2 rounded-pill" style="font-size: 11px;">100 Rb</button>
                        <button type="button" onclick="setVendorWdAmount(250000)" class="btn btn-outline-secondary btn-sm py-0.5 px-2 rounded-pill" style="font-size: 11px;">250 Rb</button>
                        <button type="button" onclick="setVendorWdAmount(500000)" class="btn btn-outline-secondary btn-sm py-0.5 px-2 rounded-pill" style="font-size: 11px;">500 Rb</button>
                    </div>
                </div>

                <div class="p-2 rounded-3 bg-success-subtle text-success small" style="font-size: 11px;">
                    <i class="bi bi-shield-check me-1"></i> Biaya transfer <strong>Rp 0 (Gratis)</strong>. Pencairan diproses tim CicalengkaGO.
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-send-fill me-1"></i> Ajukan Penarikan Sekarang',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#EE2737',
        preConfirm: () => {
            const bank = document.getElementById('swal-vendor-bank').value;
            const acc = document.getElementById('swal-vendor-acc').value.trim();
            const holder = document.getElementById('swal-vendor-holder').value.trim();
            const amount = parseFloat(document.getElementById('swal-vendor-amount').value);

            if (!acc) {
                Swal.showValidationMessage('Harap masukkan nomor rekening / e-wallet!');
                return false;
            }
            if (!holder) {
                Swal.showValidationMessage('Harap masukkan nama pemilik rekening!');
                return false;
            }
            if (!amount || isNaN(amount) || amount < 10000) {
                Swal.showValidationMessage('Nominal penarikan minimal Rp 10.000!');
                return false;
            }
            if (amount > window.VENDOR_CURRENT_BALANCE) {
                Swal.showValidationMessage('Nominal melebihi saldo tersedia!');
                return false;
            }

            return { bank, acc, holder, amount };
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Memproses Penarikan...',
                text: 'Mohon tunggu sebentar',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            try {
                const fd = new FormData();
                fd.append('bank_name', result.value.bank);
                fd.append('account_number', result.value.acc);
                fd.append('account_holder', result.value.holder);
                fd.append('amount', result.value.amount);

                const res = await fetch((window.BASE_URL || '') + '/vendor/wallet/withdraw', {
                    method: 'POST',
                    body: fd
                });
                
                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch(e) {
                    console.error('Server error response:', text);
                    throw new Error('Respon server tidak valid.');
                }

                if (data.success) {
                    showVendorToast(data.message || 'Penarikan saldo berhasil diajukan.', 'success');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showVendorToast(data.message || 'Terjadi kesalahan saat mengajukan penarikan.', 'error');
                }
            } catch (err) {
                console.error(err);
                showVendorToast(err.message || 'Gagal menghubungkan ke server.', 'error');
            }
        }
    });
}

function setVendorWdAmount(val) {
    const input = document.getElementById('swal-vendor-amount');
    if (input) {
        input.value = Math.min(val, window.VENDOR_CURRENT_BALANCE);
    }
}
</script>
