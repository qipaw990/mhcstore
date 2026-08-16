<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-cash-stack text-primary me-2"></i>Pusat Pencairan Dana (Withdrawals)</h4>
        <p class="text-muted small mb-0">Kelola dan proses pengajuan penarikan dana saldo dari Mitra Toko (Vendor) dan Driver Kurir.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $baseUrl ?>/admin/withdrawals" class="btn btn-outline-secondary btn-sm rounded-3">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </a>
    </div>
</div>

<!-- Financial Overview Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-4 p-3 bg-warning-subtle text-warning fs-3">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Menunggu Persetujuan</div>
                    <div class="fs-4 fw-bold text-dark mt-1"><?= $pending_count ?> Pengajuan</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-4 p-3 bg-success-subtle text-success fs-3">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Total Dana Telah Dicairkan</div>
                    <div class="fs-4 fw-bold text-success mt-1"><?= format_rupiah($total_paid) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-4 p-3 bg-primary-subtle text-primary fs-3">
                    <i class="bi bi-receipt"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Total Semua Pengajuan</div>
                    <div class="fs-4 fw-bold text-dark mt-1"><?= count($withdrawals) ?> Data</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Table Card with Status Filters -->
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
    <div class="card-header bg-white border-0 py-3 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= $baseUrl ?>/admin/withdrawals" class="btn btn-sm rounded-pill <?= ($current_filter === 'all') ? 'btn-dark' : 'btn-outline-secondary' ?>">
                Semua (<?= count($withdrawals) ?>)
            </a>
            <a href="<?= $baseUrl ?>/admin/withdrawals?status=pending" class="btn btn-sm rounded-pill <?= ($current_filter === 'pending') ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary' ?>">
                Pending (<?= $pending_count ?>)
            </a>
            <a href="<?= $baseUrl ?>/admin/withdrawals?status=approved" class="btn btn-sm rounded-pill <?= ($current_filter === 'approved') ? 'btn-success text-white fw-bold' : 'btn-outline-secondary' ?>">
                Disetujui
            </a>
            <a href="<?= $baseUrl ?>/admin/withdrawals?status=rejected" class="btn btn-sm rounded-pill <?= ($current_filter === 'rejected') ? 'btn-danger text-white fw-bold' : 'btn-outline-secondary' ?>">
                Ditolak
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-muted text-uppercase">
                    <th class="ps-3 py-3">Kode WD</th>
                    <th>Mitra Pemohon</th>
                    <th>Tujuan Bank / E-Wallet</th>
                    <th>Nominal Penarikan</th>
                    <th>Status</th>
                    <th>Waktu Pengajuan</th>
                    <th class="text-end pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($withdrawals)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox display-6 d-block mb-2 text-secondary opacity-50"></i>
                            Tidak ada data pengajuan penarikan dana.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($withdrawals as $w): 
                        $status = $w['status'];
                        $badgeClass = 'bg-warning-subtle text-warning-emphasis';
                        $badgeLabel = 'Menunggu Transfer';
                        if ($status === 'approved') {
                            $badgeClass = 'bg-success-subtle text-success';
                            $badgeLabel = 'Berhasil Ditransfer';
                        } elseif ($status === 'rejected') {
                            $badgeClass = 'bg-danger-subtle text-danger';
                            $badgeLabel = 'Ditolak';
                        }
                    ?>
                        <tr>
                            <td class="ps-3 fw-bold small text-primary">
                                <?= htmlspecialchars($w['withdraw_code']) ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark small"><?= htmlspecialchars($w['user_name']) ?></div>
                                <div class="text-muted" style="font-size: 11px;">
                                    <span class="badge bg-secondary-subtle text-secondary py-0 px-1 me-1"><?= ($w['user_type'] === 'vendor') ? 'Toko / Vendor' : 'Driver Kurir' ?></span>
                                    <?= htmlspecialchars($w['user_phone'] ?? '-') ?>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark small"><?= htmlspecialchars($w['bank_name']) ?></div>
                                <div class="text-muted" style="font-size: 11px;">
                                    <span class="font-monospace fw-semibold text-dark"><?= htmlspecialchars($w['account_number']) ?></span> 
                                    (a.n. <?= htmlspecialchars($w['account_holder']) ?>)
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-danger fs-6"><?= format_rupiah($w['amount']) ?></div>
                            </td>
                            <td>
                                <span class="badge <?= $badgeClass ?> rounded-pill px-2.5 py-1" style="font-size: 11px;">
                                    <?= $badgeLabel ?>
                                </span>
                                <?php if (!empty($w['admin_notes'])): ?>
                                    <div class="text-muted mt-1" style="font-size: 10.5px;" title="<?= htmlspecialchars($w['admin_notes']) ?>">
                                        <i class="bi bi-info-circle me-1"></i><?= htmlspecialchars(substr($w['admin_notes'], 0, 30)) ?>...
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted">
                                <div><i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($w['requested_at'])) ?></div>
                                <?php if (!empty($w['processed_at'])): ?>
                                    <div class="text-success" style="font-size: 10.5px;">Selesai: <?= date('d M Y, H:i', strtotime($w['processed_at'])) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                                <?php if ($status === 'pending'): ?>
                                    <div class="btn-group btn-group-sm">
                                        <button onclick="processWithdraw(<?= $w['id'] ?>, 'approved', '<?= htmlspecialchars($w['withdraw_code']) ?>', '<?= format_rupiah($w['amount']) ?>', '<?= htmlspecialchars($w['bank_name']) ?>', '<?= htmlspecialchars($w['account_number']) ?>', '<?= htmlspecialchars($w['account_holder']) ?>')" class="btn btn-success btn-sm rounded-start-3" title="Setujui & Tandai Transfer Selesai">
                                            <i class="bi bi-check-lg me-1"></i> Transfer
                                        </button>
                                        <button onclick="processWithdraw(<?= $w['id'] ?>, 'rejected', '<?= htmlspecialchars($w['withdraw_code']) ?>', '<?= format_rupiah($w['amount']) ?>', '<?= htmlspecialchars($w['bank_name']) ?>', '<?= htmlspecialchars($w['account_number']) ?>', '<?= htmlspecialchars($w['account_holder']) ?>')" class="btn btn-outline-danger btn-sm rounded-end-3" title="Tolak Penarikan & Kembalikan Saldo">
                                            <i class="bi bi-x-lg"></i> Tolak
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border px-2 py-1" style="font-size: 11px;">
                                        <i class="bi bi-lock-fill me-1"></i> Selesai
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function processWithdraw(id, action, code, amount, bank, acc, holder) {
    const isApprove = (action === 'approved');
    const title = isApprove ? 'Konfirmasi Transfer Dana' : 'Konfirmasi Tolak Penarikan';
    const confirmBtnText = isApprove ? 'Tandai Berhasil Ditransfer' : 'Tolak & Kembalikan Saldo';
    const confirmBtnColor = isApprove ? '#16A34A' : '#DC2626';

    Swal.fire({
        title: title,
        html: `
            <div class="text-start small">
                <div class="p-3 bg-light rounded-3 border mb-3">
                    <div><strong>Kode Penarikan:</strong> ${code}</div>
                    <div><strong>Nominal:</strong> <span class="text-danger fw-bold fs-6">${amount}</span></div>
                    <div><strong>Tujuan:</strong> ${bank} - ${acc}</div>
                    <div><strong>Atas Nama:</strong> ${holder}</div>
                </div>
                ${!isApprove ? `
                    <div class="mb-2">
                        <label class="form-label fw-bold small">Alasan Penolakan:</label>
                        <input id="swal-admin-notes" type="text" class="form-control form-control-sm rounded-3" placeholder="Contoh: Nomor rekening tidak sesuai / tidak aktif">
                    </div>
                ` : `
                    <div class="mb-2">
                        <label class="form-label fw-bold small">Catatan Bukti Transfer (Opsional):</label>
                        <input id="swal-admin-notes" type="text" class="form-control form-control-sm rounded-3" placeholder="Contoh: Transfer via BCA Internet Banking No. Ref 987123">
                    </div>
                `}
                <div class="text-muted" style="font-size: 11px;">
                    ${isApprove ? 'Pastikan Anda telah melakukan transfer ke rekening di atas sebelum menekan tombol konfirmasi.' : 'Saldo akan secara otomatis dikembalikan ke dompet mitra yang bersangkutan.'}
                </div>
            </div>
        `,
        icon: isApprove ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonText: confirmBtnText,
        cancelButtonText: 'Batal',
        confirmButtonColor: confirmBtnColor,
        preConfirm: () => {
            const notes = document.getElementById('swal-admin-notes').value.trim();
            return { notes: notes };
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Memproses...',
                text: 'Mohon tunggu',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            try {
                const fd = new FormData();
                fd.append('id', id);
                fd.append('status', action);
                fd.append('admin_notes', result.value.notes);

                const res = await fetch((window.BASE_URL || '') + '/admin/withdrawals/update-status', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();

                if (data.success) {
                    await Swal.fire({
                        title: 'Berhasil!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#2563eb'
                    });
                    location.reload();
                } else {
                    Swal.fire({
                        title: 'Gagal',
                        text: data.message || 'Terjadi kesalahan',
                        icon: 'error'
                    });
                }
            } catch (err) {
                console.error(err);
                Swal.fire({
                    title: 'Kesalahan Sistem',
                    text: 'Gagal menghubungkan ke server.',
                    icon: 'error'
                });
            }
        }
    });
}
</script>
