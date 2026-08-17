<div class="p-3 driver-page-container">
    <!-- Driver Balance Card (CicalengkaGO Pay / Dompet Mitra Kurir) -->
    <div class="p-4 text-white shadow-xs mb-3 position-relative overflow-hidden" style="background: linear-gradient(135deg, #EE2737 0%, #B91C1C 100%); border-radius: 20px; border: 1px solid rgba(255,255,255,0.2); box-shadow: 0 10px 25px rgba(238, 39, 55, 0.3) !important;">
        <!-- Background decorative ambient circle -->
        <div style="position: absolute; top: -30px; right: -30px; width: 130px; height: 130px; border-radius: 50%; background: radial-gradient(circle, rgba(255,255,255,0.18) 0%, rgba(255,255,255,0) 70%); pointer-events: none;"></div>

        <div class="d-flex justify-content-between align-items-start mb-2 position-relative" style="z-index: 1;">
            <div>
                <div class="d-flex align-items-center gap-1.5 mb-1">
                    <span class="badge rounded-pill bg-white text-danger px-2.5 py-0.5 fw-bold" style="font-size: 9.5px; letter-spacing: 0.5px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
                        <i class="bi bi-wallet-fill me-1"></i> DOMPET MITRA DRIVER
                    </span>
                </div>
                <div class="fw-extrabold text-white" style="letter-spacing: -0.5px; font-size: 24px; font-weight: 800;"><?= format_rupiah($wallet['balance'] ?? 0) ?></div>
            </div>
            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; background: rgba(255, 255, 255, 0.2); border: 1.5px solid rgba(255, 255, 255, 0.3); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);">
                <i class="bi bi-wallet2 text-white" style="font-size: 20px;"></i>
            </div>
        </div>

        <!-- Driver Mini Metrics -->
        <div class="row g-2 mt-2 mb-3 position-relative" style="z-index: 1;">
            <div class="col-6">
                <div class="p-2.5 rounded-3" style="background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.18);">
                    <div class="text-white-50" style="font-size: 10px; font-weight: 600;">Total Pendapatan</div>
                    <div class="fw-bold text-white small mt-0.5" style="font-size: 12.5px;"><?= format_rupiah($wallet['total_earned'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col-6">
                <div class="p-2.5 rounded-3" style="background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.18);">
                    <div class="text-white-50" style="font-size: 10px; font-weight: 600;">Total Ditarik</div>
                    <div class="fw-bold text-warning small mt-0.5" style="font-size: 12.5px;"><?= format_rupiah($total_withdrawn ?? 0) ?></div>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between pt-2.5 border-top border-white border-opacity-25 position-relative" style="z-index: 1;">
            <button onclick="openDriverWithdrawModal()" class="btn btn-light btn-sm fw-bold px-3 py-1.5 rounded-pill shadow-xs d-flex align-items-center gap-1.5" style="font-size: 11.5px; color: #EE2737 !important; background: #FFFFFF; border: none; height: 36px;">
                <i class="bi bi-arrow-up-right-circle-fill" style="color: #EE2737; font-size: 14px;"></i>
                <span>Tarik Dana Komisi</span>
            </button>
            <span class="badge rounded-pill bg-white bg-opacity-20 text-white px-2.5 py-1" style="font-size: 10px; font-weight: 600;">
                <i class="bi bi-shield-lock-fill me-1"></i> Pencairan Cepat
            </span>
        </div>
    </div>

    <!-- Quick Driver Performance Info -->
    <div class="row g-2.5 mb-3">
        <div class="col-6">
            <div class="driver-metric-card text-center py-2.5">
                <div class="text-muted fw-semibold" style="font-size: 10.5px;">Bagi Hasil Driver</div>
                <div class="fw-bold text-success mt-0.5" style="font-size: 14px;">85% Bersih</div>
            </div>
        </div>
        <div class="col-6">
            <div class="driver-metric-card text-center py-2.5">
                <div class="text-muted fw-semibold" style="font-size: 10.5px;">Biaya Penarikan</div>
                <div class="fw-bold text-primary mt-0.5" style="font-size: 14px;">Gratis (Rp 0)</div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs for Driver Earnings & Withdrawals -->
    <ul class="nav nav-pills nav-fill bg-white p-1 shadow-2xs border mb-3" id="driverWalletTab" role="tablist" style="border-radius: 14px; border-color: #E2E8F0 !important;">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold py-2 px-2 small d-flex align-items-center justify-content-center gap-1.5" id="driver-withdraw-tab" data-bs-toggle="tab" data-bs-target="#driver-withdraw-pane" type="button" role="tab" style="font-size: 11.5px; border-radius: 10px;">
                <i class="bi bi-cash-stack"></i>
                <span>Riwayat Penarikan</span>
                <span class="badge bg-white bg-opacity-25 rounded-pill px-1.5 ms-0.5" style="font-size: 9.5px;"><?= count($withdraw_requests ?? []) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold py-2 px-2 small d-flex align-items-center justify-content-center gap-1.5" id="driver-mutation-tab" data-bs-toggle="tab" data-bs-target="#driver-mutation-pane" type="button" role="tab" style="font-size: 11.5px; border-radius: 10px;">
                <i class="bi bi-bicycle"></i>
                <span>Komisi Order</span>
                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-1.5 ms-0.5" style="font-size: 9.5px;"><?= count($transactions ?? []) ?></span>
            </button>
        </li>
    </ul>

    <!-- Driver Tabs Content -->
    <div class="tab-content" id="driverWalletTabContent">
        <!-- PANE 1: Riwayat Penarikan Dana Driver -->
        <div class="tab-pane fade show active" id="driver-withdraw-pane" role="tabpanel" tabindex="0">
            <div class="ccg-card p-0 overflow-hidden mb-3">
                <div class="p-3 bg-white border-bottom d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold m-0 text-dark" style="font-size: 13px;">
                        <i class="bi bi-clock-history text-danger me-1.5"></i> Riwayat Pencairan Dana Kurir
                    </h6>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 rounded-pill" style="font-size: 10px; font-weight: 700;">
                        <?= count($withdraw_requests ?? []) ?> Entri
                    </span>
                </div>

                <div>
                    <?php if (empty($withdraw_requests)): ?>
                        <div class="p-4 text-center text-muted small">
                            <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 44px; height: 44px; font-size: 20px;">
                                <i class="bi bi-arrow-up-right-circle"></i>
                            </div>
                            <div class="fw-bold text-dark mb-1">Belum Ada Riwayat Penarikan</div>
                            <div class="text-muted" style="font-size: 11px;">
                                Anda belum mengajukan pencairan saldo komisi. Klik tombol <strong>"Tarik Dana Komisi"</strong> di atas untuk mentransfer saldo ke rekening/e-wallet Anda.
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($withdraw_requests as $wd): 
                                $status = $wd['status'] ?? 'pending';
                                $badgeClass = 'bg-warning-subtle text-warning-emphasis';
                                $badgeLabel = 'Menunggu Transfer';
                                $badgeIcon = 'bi-hourglass-split';

                                if ($status === 'approved') {
                                    $badgeClass = 'bg-success-subtle text-success';
                                    $badgeLabel = 'Berhasil Ditransfer';
                                    $badgeIcon = 'bi-check-circle-fill';
                                } elseif ($status === 'rejected') {
                                    $badgeClass = 'bg-danger-subtle text-danger';
                                    $badgeLabel = 'Ditolak';
                                    $badgeIcon = 'bi-x-circle-fill';
                                }
                            ?>
                                <div class="list-group-item py-3 px-3 border-bottom">
                                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                        <div class="d-flex align-items-center gap-2.5">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 bg-danger-subtle text-danger" style="width: 40px; height: 40px; font-size: 17px;">
                                                <i class="bi bi-bank"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark d-flex align-items-center gap-1.5" style="font-size: 13px;">
                                                    <span><?= htmlspecialchars($wd['bank_name']) ?></span>
                                                    <span class="badge bg-light text-muted border fw-semibold" style="font-size: 9px;"><?= htmlspecialchars($wd['withdraw_code']) ?></span>
                                                </div>
                                                <div class="text-dark small mt-0.5" style="font-size: 11px;">
                                                    <span class="fw-bold"><?= htmlspecialchars($wd['account_number']) ?></span> 
                                                    <span class="text-muted">(a.n. <?= htmlspecialchars($wd['account_holder']) ?>)</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-extrabold text-danger" style="font-size: 14px;">
                                                -<?= format_rupiah($wd['amount']) ?>
                                            </div>
                                            <span class="badge <?= $badgeClass ?> rounded-pill px-2 py-0.5 mt-1" style="font-size: 9.5px; font-weight: 700;">
                                                <i class="bi <?= $badgeIcon ?> me-1"></i><?= $badgeLabel ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex align-items-center justify-content-between pt-2 border-top border-light-subtle text-muted" style="font-size: 10.5px;">
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
                                        <div class="mt-2.5 p-2.5 rounded-3 bg-danger-subtle text-danger small" style="font-size: 11px; border-left: 3.5px solid #EE2737;">
                                            <strong>Catatan Admin:</strong> <?= htmlspecialchars($wd['admin_notes']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- PANE 2: Komisi Pengantaran -->
        <div class="tab-pane fade" id="driver-mutation-pane" role="tabpanel" tabindex="0">
            <div class="ccg-card p-0 overflow-hidden mb-3">
                <div class="p-3 bg-white border-bottom d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold m-0 text-dark" style="font-size: 13px;">
                        <i class="bi bi-receipt text-secondary me-1.5"></i> Rincian Komisi Pengantaran
                    </h6>
                    <span class="badge bg-light text-muted border px-2.5 py-1 rounded-pill" style="font-size: 10px; font-weight: 700;"><?= count($transactions ?? []) ?> Entri</span>
                </div>

                <div>
                    <?php if (empty($transactions)): ?>
                        <div class="p-4 text-center text-muted small">
                            <div class="rounded-circle bg-secondary-subtle text-secondary d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 44px; height: 44px; font-size: 20px;">
                                <i class="bi bi-receipt"></i>
                            </div>
                            <div class="fw-bold text-dark mb-1">Belum Ada Komisi Tercatat</div>
                            <div class="text-muted" style="font-size: 11px;">Selesaikan orderan pertama Anda untuk mulai mengumpulkan saldo dan komisi pengantaran.</div>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($transactions as $tx): 
                                $isCredit = (($tx['type'] ?? '') === 'credit');
                            ?>
                                <div class="list-group-item d-flex align-items-center justify-content-between py-2.5 px-3 border-bottom">
                                    <div class="d-flex align-items-center gap-2.5">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 <?= $isCredit ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>" style="width: 38px; height: 38px; font-size: 16px;">
                                            <i class="bi <?= $isCredit ? 'bi-bicycle text-success' : 'bi-arrow-up-right text-danger' ?>"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 12.5px;"><?= htmlspecialchars($tx['description'] ?? 'Komisi Pengantaran') ?></div>
                                            <div class="text-muted" style="font-size: 10.5px; margin-top: 1px;">
                                                <i class="bi bi-calendar3 me-1"></i><?= date('d M Y, H:i', strtotime($tx['created_at'])) ?> WIB
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold <?= $isCredit ? 'text-success' : 'text-danger' ?>" style="font-size: 13px;">
                                            <?= $isCredit ? '+' : '-' ?><?= format_rupiah($tx['amount']) ?>
                                        </div>
                                        <span class="badge bg-success-subtle text-success rounded-pill px-2 py-0.5 mt-0.5" style="font-size: 9px; font-weight: 700;">Sukses</span>
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
window.DRIVER_CURRENT_BALANCE = <?= (float)($wallet['balance'] ?? 0) ?>;

function openDriverWithdrawModal() {
    if (window.DRIVER_CURRENT_BALANCE < 10000) {
        Swal.fire({
            title: 'Saldo Belum Mencukupi',
            text: 'Minimal saldo untuk pengajuan penarikan dana adalah Rp 10.000. Saldo Anda saat ini: ' + 'Rp ' + Number(window.DRIVER_CURRENT_BALANCE).toLocaleString('id-ID'),
            icon: 'warning',
            confirmButtonColor: '#EE2737'
        });
        return;
    }

    Swal.fire({
        title: '<div class="d-flex align-items-center gap-2 justify-content-center text-dark"><i class="bi bi-wallet2 text-danger"></i> Tarik Dana Driver</div>',
        html: `
            <div class="text-start small mt-1">
                <div class="p-3 bg-light rounded-4 border mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted" style="font-size: 11.5px;">Saldo Dompet Kurir:</span>
                        <strong class="text-dark fs-6">Rp ${Number(window.DRIVER_CURRENT_BALANCE).toLocaleString('id-ID')}</strong>
                    </div>
                </div>

                <div class="mb-2.5">
                    <label class="form-label fw-bold small text-dark mb-1" for="swal-driver-bank">Tujuan Rekening / E-Wallet:</label>
                    <select id="swal-driver-bank" name="driver_bank" class="form-select form-select-sm rounded-3 py-2">
                        <option value="GoPay Driver">GoPay Mitra Driver</option>
                        <option value="DANA">DANA Dompet Digital</option>
                        <option value="OVO">OVO Payment</option>
                        <option value="ShopeePay">ShopeePay</option>
                        <option value="BCA">BCA - Bank Central Asia</option>
                        <option value="BRI">BRI - Bank Rakyat Indonesia</option>
                        <option value="Mandiri">Bank Mandiri</option>
                        <option value="BNI">BNI - Bank Negara Indonesia</option>
                        <option value="BSI">BSI - Bank Syariah Indonesia</option>
                    </select>
                </div>

                <div class="mb-2.5">
                    <label class="form-label fw-bold small text-dark mb-1" for="swal-driver-acc">Nomor Rekening / No. HP Akun:</label>
                    <input id="swal-driver-acc" name="driver_account_no" type="text" class="form-control form-control-sm rounded-3 py-2" placeholder="Contoh: 081234567890">
                </div>

                <div class="mb-2.5">
                    <label class="form-label fw-bold small text-dark mb-1" for="swal-driver-holder">Nama Pemilik Akun / Rekening:</label>
                    <input id="swal-driver-holder" name="driver_account_holder" type="text" class="form-control form-control-sm rounded-3 py-2" placeholder="Contoh: Asep Saepudin">
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-bold small text-dark mb-0" for="swal-driver-amount">Nominal Penarikan (Rp):</label>
                        <button type="button" onclick="setDriverWdAmount(${window.DRIVER_CURRENT_BALANCE})" class="btn btn-link text-danger p-0 text-decoration-none fw-bold" style="font-size: 11px;">Tarik Semua</button>
                    </div>
                    <input id="swal-driver-amount" name="driver_withdraw_amount" type="number" min="10000" max="${window.DRIVER_CURRENT_BALANCE}" class="form-control form-control-sm rounded-3 py-2 fw-bold" placeholder="Minimal Rp 10.000">
                    <div class="d-flex gap-1.5 mt-2 flex-wrap">
                        <button type="button" onclick="setDriverWdAmount(20000)" class="btn btn-outline-secondary btn-sm py-0.5 px-2 rounded-pill" style="font-size: 11px;">20 Rb</button>
                        <button type="button" onclick="setDriverWdAmount(50000)" class="btn btn-outline-secondary btn-sm py-0.5 px-2 rounded-pill" style="font-size: 11px;">50 Rb</button>
                        <button type="button" onclick="setDriverWdAmount(100000)" class="btn btn-outline-secondary btn-sm py-0.5 px-2 rounded-pill" style="font-size: 11px;">100 Rb</button>
                    </div>
                </div>

                <div class="p-2 rounded-3 bg-success-subtle text-success small" style="font-size: 11px;">
                    <i class="bi bi-shield-check me-1"></i> Biaya transfer <strong>Rp 0 (Gratis)</strong>. Langsung diproses ke rekening/e-wallet Anda.
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-send-fill me-1"></i> Ajukan Penarikan Sekarang',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#EE2737',
        preConfirm: () => {
            const bank = document.getElementById('swal-driver-bank').value;
            const acc = document.getElementById('swal-driver-acc').value.trim();
            const holder = document.getElementById('swal-driver-holder').value.trim();
            const amount = parseFloat(document.getElementById('swal-driver-amount').value);

            if (!acc) {
                Swal.showValidationMessage('Harap masukkan nomor rekening / e-wallet!');
                return false;
            }
            if (!holder) {
                Swal.showValidationMessage('Harap masukkan nama pemilik akun!');
                return false;
            }
            if (!amount || isNaN(amount) || amount < 10000) {
                Swal.showValidationMessage('Nominal penarikan minimal Rp 10.000!');
                return false;
            }
            if (amount > window.DRIVER_CURRENT_BALANCE) {
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

                const res = await fetch((window.BASE_URL || '') + '/delivery/withdraw', {
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
                    await Swal.fire({
                        title: 'Pengajuan Berhasil!',
                        text: data.message || 'Penarikan saldo driver berhasil diajukan dan sedang diproses.',
                        icon: 'success',
                        confirmButtonColor: '#EE2737'
                    });
                    location.reload();
                } else {
                    Swal.fire({
                        title: 'Gagal',
                        text: data.message || 'Terjadi kesalahan saat mengajukan penarikan.',
                        icon: 'error',
                        confirmButtonColor: '#EE2737'
                    });
                }
            } catch (err) {
                console.error(err);
                Swal.fire({
                    title: 'Kesalahan Sistem',
                    text: err.message || 'Gagal menghubungkan ke server.',
                    icon: 'error',
                    confirmButtonColor: '#EE2737'
                });
            }
        }
    });
}

function setDriverWdAmount(val) {
    const input = document.getElementById('swal-driver-amount');
    if (input) {
        input.value = Math.min(val, window.DRIVER_CURRENT_BALANCE);
    }
}
</script>
