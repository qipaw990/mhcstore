<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-wallet2 text-primary me-2"></i>Manajemen Top-Up Saldo Midtrans</h4>
        <p class="text-muted small mb-0">Pantau hasil transaksi top-up CicalengkaPay, sinkronkan status real-time via API Midtrans, dan kelola saldo pengguna.</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary btn-sm rounded-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#manualTopupModal">
            <i class="bi bi-plus-circle me-1"></i> Top-Up Saldo Manual
        </button>
        <a href="<?= $baseUrl ?>/admin/topups" class="btn btn-outline-secondary btn-sm rounded-3">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </a>
    </div>
</div>

<!-- Financial KPI Summary Cards -->
<div class="row g-3 mb-4">
    <!-- Card 1: Total Settled / Success -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 position-relative overflow-hidden">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-4 p-3 bg-success-subtle text-success fs-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Total Saldo Masuk (Settled)</div>
                    <div class="fs-5 fw-bold text-success mt-1"><?= format_rupiah($total_success_amount) ?></div>
                    <div class="text-muted" style="font-size: 11px;"><span class="fw-bold text-dark"><?= $total_success_count ?></span> transaksi lunas</div>
                </div>
            </div>
            <div class="position-absolute end-0 bottom-0 opacity-10 p-2 text-success" style="font-size: 4rem; line-height: 1; pointer-events: none;">
                <i class="bi bi-cash-coin"></i>
            </div>
        </div>
    </div>

    <!-- Card 2: Total Pending -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 position-relative overflow-hidden">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-4 p-3 bg-warning-subtle text-warning fs-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Menunggu Pembayaran</div>
                    <div class="fs-5 fw-bold text-warning-emphasis mt-1"><?= format_rupiah($total_pending_amount) ?></div>
                    <div class="text-muted" style="font-size: 11px;"><span class="fw-bold text-dark"><?= $total_pending_count ?></span> transaksi pending</div>
                </div>
            </div>
            <div class="position-absolute end-0 bottom-0 opacity-10 p-2 text-warning" style="font-size: 4rem; line-height: 1; pointer-events: none;">
                <i class="bi bi-clock-history"></i>
            </div>
        </div>
    </div>

    <!-- Card 3: Today's Success -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 position-relative overflow-hidden">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-4 p-3 bg-primary-subtle text-primary fs-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                    <i class="bi bi-lightning-charge-fill"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Top-Up Masuk Hari Ini</div>
                    <div class="fs-5 fw-bold text-primary mt-1"><?= format_rupiah($today_success_amount) ?></div>
                    <div class="text-muted" style="font-size: 11px;"><span class="fw-bold text-dark"><?= $today_success_count ?></span> transaksi hari ini</div>
                </div>
            </div>
            <div class="position-absolute end-0 bottom-0 opacity-10 p-2 text-primary" style="font-size: 4rem; line-height: 1; pointer-events: none;">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
        </div>
    </div>

    <!-- Card 4: Failed / Canceled -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 position-relative overflow-hidden">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-4 p-3 bg-danger-subtle text-danger fs-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Gagal / Dibatalkan</div>
                    <div class="fs-5 fw-bold text-danger mt-1"><?= $total_failed_count ?> Transaksi</div>
                    <div class="text-muted" style="font-size: 11px;">Expired / dibatalkan</div>
                </div>
            </div>
            <div class="position-absolute end-0 bottom-0 opacity-10 p-2 text-danger" style="font-size: 4rem; line-height: 1; pointer-events: none;">
                <i class="bi bi-shield-x"></i>
            </div>
        </div>
    </div>
</div>

<!-- Filters and Search Card -->
<div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= $baseUrl ?>/admin/topups" class="row g-2 align-items-center">
            <!-- Filter Status Pills -->
            <div class="col-12 col-lg-6">
                <div class="d-flex align-items-center gap-1 flex-wrap">
                    <a href="<?= $baseUrl ?>/admin/topups?status=all&period=<?= urlencode($current_period) ?>&search=<?= urlencode($current_search) ?>" 
                       class="btn btn-sm rounded-pill px-3 <?= ($current_status === 'all') ? 'btn-dark' : 'btn-outline-secondary' ?>">
                        Semua (<?= $total_all_count ?>)
                    </a>
                    <a href="<?= $baseUrl ?>/admin/topups?status=success&period=<?= urlencode($current_period) ?>&search=<?= urlencode($current_search) ?>" 
                       class="btn btn-sm rounded-pill px-3 <?= ($current_status === 'success') ? 'btn-success text-white fw-semibold' : 'btn-outline-secondary' ?>">
                        <i class="bi bi-check-circle-fill me-1"></i> Berhasil (<?= $total_success_count ?>)
                    </a>
                    <a href="<?= $baseUrl ?>/admin/topups?status=pending&period=<?= urlencode($current_period) ?>&search=<?= urlencode($current_search) ?>" 
                       class="btn btn-sm rounded-pill px-3 <?= ($current_status === 'pending') ? 'btn-warning text-dark fw-semibold' : 'btn-outline-secondary' ?>">
                        <i class="bi bi-hourglass-split me-1"></i> Pending (<?= $total_pending_count ?>)
                    </a>
                    <a href="<?= $baseUrl ?>/admin/topups?status=failed&period=<?= urlencode($current_period) ?>&search=<?= urlencode($current_search) ?>" 
                       class="btn btn-sm rounded-pill px-3 <?= ($current_status === 'failed') ? 'btn-danger text-white fw-semibold' : 'btn-outline-secondary' ?>">
                        <i class="bi bi-x-circle me-1"></i> Gagal (<?= $total_failed_count ?>)
                    </a>
                </div>
            </div>

            <!-- Period & Search -->
            <div class="col-12 col-sm-6 col-lg-3">
                <select name="period" class="form-select form-select-sm rounded-3" onchange="this.form.submit()">
                    <option value="all" <?= ($current_period === 'all') ? 'selected' : '' ?>>Semua Waktu</option>
                    <option value="today" <?= ($current_period === 'today') ? 'selected' : '' ?>>Hari Ini</option>
                    <option value="week" <?= ($current_period === 'week') ? 'selected' : '' ?>>7 Hari Terakhir</option>
                    <option value="month" <?= ($current_period === 'month') ? 'selected' : '' ?>>30 Hari Terakhir</option>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="input-group input-group-sm">
                    <input type="hidden" name="status" value="<?= htmlspecialchars($current_status) ?>">
                    <input type="text" name="search" class="form-control rounded-start-3" placeholder="Cari Kode, Nama, HP..." value="<?= htmlspecialchars($current_search) ?>">
                    <button class="btn btn-primary rounded-end-3" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                    <?php if (!empty($current_search)): ?>
                        <a href="<?= $baseUrl ?>/admin/topups?status=<?= urlencode($current_status) ?>&period=<?= urlencode($current_period) ?>" class="btn btn-outline-secondary btn-sm ms-1 rounded-3" title="Reset Pencarian">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Transactions Data Table Card -->
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-muted text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                    <th class="ps-3 py-3">Kode Top-Up</th>
                    <th>Pengguna & Akun</th>
                    <th>Nominal Top-Up</th>
                    <th>Saldo Terkini</th>
                    <th>Saluran Pembayaran</th>
                    <th>Status</th>
                    <th>Waktu Dibuat</th>
                    <th class="text-end pe-3">Aksi Midtrans</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($topups)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <div class="py-4">
                                <i class="bi bi-wallet2 display-4 d-block mb-3 text-secondary opacity-50"></i>
                                <h6 class="fw-bold text-dark">Tidak Ada Data Top-Up Ditemukan</h6>
                                <p class="small text-muted mb-0">Belum ada transaksi top-up yang sesuai dengan filter atau kata kunci pencarian.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($topups as $t): 
                        $status = $t['status'];
                        $badgeClass = 'bg-secondary-subtle text-secondary';
                        $badgeIcon = 'bi-circle';
                        $badgeLabel = ucfirst($status);

                        if ($status === 'success') {
                            $badgeClass = 'bg-success-subtle text-success border border-success-subtle';
                            $badgeIcon = 'bi-check-circle-fill';
                            $badgeLabel = 'Berhasil (Settled)';
                        } elseif ($status === 'pending') {
                            $badgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                            $badgeIcon = 'bi-hourglass-split';
                            $badgeLabel = 'Menunggu Pembayaran';
                        } elseif ($status === 'failed') {
                            $badgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                            $badgeIcon = 'bi-x-circle-fill';
                            $badgeLabel = 'Gagal';
                        } elseif ($status === 'canceled') {
                            $badgeClass = 'bg-secondary-subtle text-dark border';
                            $badgeIcon = 'bi-slash-circle';
                            $badgeLabel = 'Dibatalkan';
                        }

                        // Parse payment channel presentation
                        $payType = strtolower($t['payment_type'] ?? 'midtrans_snap');
                        $channelName = 'Midtrans Snap';
                        $channelIcon = 'bi-credit-card-2-front';
                        $channelBadge = 'bg-primary-subtle text-primary';

                        if (str_contains($payType, 'qris')) {
                            $channelName = 'QRIS Instant';
                            $channelIcon = 'bi-qr-code';
                            $channelBadge = 'bg-danger-subtle text-danger';
                        } elseif (str_contains($payType, 'gopay')) {
                            $channelName = 'GoPay';
                            $channelIcon = 'bi-wallet-fill';
                            $channelBadge = 'bg-info-subtle text-info-emphasis';
                        } elseif (str_contains($payType, 'shopeepay')) {
                            $channelName = 'ShopeePay';
                            $channelIcon = 'bi-bag-fill';
                            $channelBadge = 'bg-warning-subtle text-warning-emphasis';
                        } elseif (str_contains($payType, 'bca')) {
                            $channelName = 'BCA Virtual Account';
                            $channelIcon = 'bi-bank';
                            $channelBadge = 'bg-primary-subtle text-primary';
                        } elseif (str_contains($payType, 'bni')) {
                            $channelName = 'BNI Virtual Account';
                            $channelIcon = 'bi-bank';
                            $channelBadge = 'bg-warning-subtle text-dark';
                        } elseif (str_contains($payType, 'bri')) {
                            $channelName = 'BRI Virtual Account';
                            $channelIcon = 'bi-bank';
                            $channelBadge = 'bg-primary-subtle text-primary';
                        } elseif (str_contains($payType, 'cimb')) {
                            $channelName = 'CIMB Niaga VA';
                            $channelIcon = 'bi-bank';
                            $channelBadge = 'bg-danger-subtle text-danger';
                        } elseif (str_contains($payType, 'echannel') || str_contains($payType, 'mandiri')) {
                            $channelName = 'Mandiri Bill Payment';
                            $channelIcon = 'bi-bank';
                            $channelBadge = 'bg-warning-subtle text-dark';
                        } elseif (str_contains($payType, 'bank_transfer') || str_contains($payType, 'va')) {
                            $channelName = 'Virtual Account';
                            $channelIcon = 'bi-bank';
                            $channelBadge = 'bg-secondary-subtle text-dark';
                        } elseif (str_contains($payType, 'credit_card') || str_contains($payType, 'card')) {
                            $channelName = 'Credit/Debit Card';
                            $channelIcon = 'bi-credit-card';
                            $channelBadge = 'bg-dark-subtle text-dark';
                        } elseif (str_contains($payType, 'cstore') || str_contains($payType, 'indomaret') || str_contains($payType, 'alfamart')) {
                            $channelName = 'Gerai Retail (Alfa/Indo)';
                            $channelIcon = 'bi-shop';
                            $channelBadge = 'bg-success-subtle text-success';
                        } elseif (str_contains($payType, 'manual_admin')) {
                            $channelName = 'Admin Manual Credit';
                            $channelIcon = 'bi-shield-check';
                            $channelBadge = 'bg-success-subtle text-success';
                        }

                        $role = $t['user_role'] ?? 'customer';
                        $roleBadge = match($role) {
                            'customer'     => 'bg-primary-subtle text-primary',
                            'delivery_man' => 'bg-warning-subtle text-dark',
                            'vendor'       => 'bg-success-subtle text-success',
                            'admin'        => 'bg-danger-subtle text-danger',
                            default        => 'bg-secondary-subtle text-secondary'
                        };
                        $roleLabel = match($role) {
                            'customer'     => 'Pelanggan',
                            'delivery_man' => 'Driver Kurir',
                            'vendor'       => 'Mitra Toko',
                            'admin'        => 'Admin',
                            default        => ucfirst($role)
                        };
                    ?>
                        <tr id="topup-row-<?= $t['id'] ?>">
                            <!-- Topup Code -->
                            <td class="ps-3 py-3">
                                <div class="d-flex align-items-center gap-1">
                                    <span class="font-monospace fw-bold small text-primary"><?= htmlspecialchars($t['topup_code']) ?></span>
                                    <button class="btn btn-link btn-sm p-0 text-muted" onclick="copyToClipboard('<?= htmlspecialchars($t['topup_code']) ?>')" title="Salin Kode Topup">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                                <?php if (!empty($t['notes'])): ?>
                                    <div class="text-muted text-truncate" style="font-size: 11px; max-width: 180px;" title="<?= htmlspecialchars($t['notes']) ?>">
                                        <?= htmlspecialchars($t['notes']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- User Info -->
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center flex-shrink-0" style="width: 34px; height: 34px; font-weight: bold; color: #2563eb;">
                                        <?= strtoupper(substr($t['user_name'] ?? 'U', 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark small d-flex align-items-center gap-1">
                                            <?= htmlspecialchars($t['user_name'] ?? '-') ?>
                                            <span class="badge <?= $roleBadge ?> py-0 px-1" style="font-size: 9.5px;"><?= $roleLabel ?></span>
                                        </div>
                                        <div class="text-muted" style="font-size: 11px;">
                                            <span><?= htmlspecialchars($t['user_phone'] ?? '-') ?></span>
                                            <?php if (!empty($t['user_email'])): ?>
                                                <span class="text-secondary opacity-75 ms-1">&bull; <?= htmlspecialchars($t['user_email']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Amount -->
                            <td>
                                <div class="fw-bold text-success fs-6"><?= format_rupiah($t['amount']) ?></div>
                            </td>

                            <!-- Live Current Balance -->
                            <td>
                                <div class="badge bg-light text-dark border px-2 py-1" style="font-size: 11.5px;">
                                    <i class="bi bi-wallet-fill text-primary me-1"></i><?= format_rupiah($t['current_wallet_balance']) ?>
                                </div>
                            </td>

                            <!-- Payment Channel -->
                            <td>
                                <span class="badge <?= $channelBadge ?> rounded-pill px-2.5 py-1" style="font-size: 11px;">
                                    <i class="bi <?= $channelIcon ?> me-1"></i><?= $channelName ?>
                                </span>
                            </td>

                            <!-- Status -->
                            <td>
                                <span class="badge <?= $badgeClass ?> rounded-pill px-2.5 py-1" style="font-size: 11px;">
                                    <i class="bi <?= $badgeIcon ?> me-1"></i><?= $badgeLabel ?>
                                </span>
                            </td>

                            <!-- Created & Updated Time -->
                            <td class="small text-muted">
                                <div><i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($t['created_at'])) ?></div>
                                <?php if ($t['updated_at'] !== $t['created_at']): ?>
                                    <div class="text-secondary" style="font-size: 10.5px;">Update: <?= date('d M Y, H:i', strtotime($t['updated_at'])) ?></div>
                                <?php endif; ?>
                            </td>

                            <!-- Actions -->
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm">
                                    <!-- Cek Status Real-Time ke Midtrans -->
                                    <button class="btn btn-outline-primary btn-sm rounded-start-3" 
                                            onclick="syncMidtransStatus('<?= htmlspecialchars($t['topup_code']) ?>', <?= $t['id'] ?>)" 
                                            title="Cek Status Real-Time Langsung ke API Midtrans">
                                        <i class="bi bi-arrow-repeat me-1"></i> Sync API
                                    </button>

                                    <!-- Detail Modal -->
                                    <button class="btn btn-outline-secondary btn-sm" 
                                            onclick="showTopupDetail(<?= $t['id'] ?>)" 
                                            title="Lihat Detail Transaksi & Log">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <?php if ($status === 'pending'): ?>
                                        <!-- Manual Approve / Force Settlement -->
                                        <button class="btn btn-outline-success btn-sm" 
                                                onclick="manualApproveTopup(<?= $t['id'] ?>, '<?= htmlspecialchars($t['topup_code']) ?>', '<?= format_rupiah($t['amount']) ?>', '<?= htmlspecialchars($t['user_name']) ?>')" 
                                                title="Setujui & Tambah Saldo Manual (Rekonsiliasi)">
                                            <i class="bi bi-check2"></i>
                                        </button>

                                        <!-- Cancel Top-Up -->
                                        <button class="btn btn-outline-danger btn-sm rounded-end-3" 
                                                onclick="manualCancelTopup(<?= $t['id'] ?>, '<?= htmlspecialchars($t['topup_code']) ?>')" 
                                                title="Batalkan Transaksi Top-Up">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="btn btn-light btn-sm text-muted border-start disabled rounded-end-3" style="font-size: 11px;">
                                            <i class="bi bi-check2-all"></i>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Topups Table Pagination Footer -->
    <?php if (($total_pages ?? 1) > 1): ?>
        <div class="card-footer bg-white border-top py-2.5 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <small class="text-muted fw-semibold">
                Menampilkan Halaman <span class="text-dark fw-bold"><?= $current_page ?></span> dari <span class="text-dark fw-bold"><?= $total_pages ?></span> (<span class="text-primary fw-bold"><?= number_format($total_topups ?? 0) ?></span> Total Transaksi Topup)
            </small>
            <nav aria-label="Topups pagination">
                <ul class="pagination pagination-sm m-0 gap-1">
                    <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link px-2.5 py-1 rounded-2 border text-decoration-none small fw-semibold <?= ($current_page <= 1) ? 'text-muted bg-light' : 'text-dark bg-white' ?>" href="?page=<?= max(1, $current_page - 1) ?>&status=<?= urlencode($current_status ?? 'all') ?>&period=<?= urlencode($current_period ?? 'all') ?>&search=<?= urlencode($current_search ?? '') ?>">
                            <i class="bi bi-chevron-left me-1"></i> Prev
                        </a>
                    </li>
                    <?php 
                        $startP = max(1, $current_page - 2);
                        $endP = min($total_pages, $current_page + 2);
                        for ($p = $startP; $p <= $endP; $p++): 
                    ?>
                        <li class="page-item">
                            <a class="page-link px-2.5 py-1 rounded-2 border text-decoration-none small fw-bold <?= ($p == $current_page) ? 'bg-primary text-white border-primary' : 'bg-white text-dark' ?>" href="?page=<?= $p ?>&status=<?= urlencode($current_status ?? 'all') ?>&period=<?= urlencode($current_period ?? 'all') ?>&search=<?= urlencode($current_search ?? '') ?>">
                                <?= $p ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link px-2.5 py-1 rounded-2 border text-decoration-none small fw-semibold <?= ($current_page >= $total_pages) ? 'text-muted bg-light' : 'text-dark bg-white' ?>" href="?page=<?= min($total_pages, $current_page + 1) ?>&status=<?= urlencode($current_status ?? 'all') ?>&period=<?= urlencode($current_period ?? 'all') ?>&search=<?= urlencode($current_search ?? '') ?>">
                            Next <i class="bi bi-chevron-right ms-1"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- Modal 1: Detail Transaksi Top-Up -->
<div class="modal fade" id="topupDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-light border-0 py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 p-2 bg-primary-subtle text-primary">
                        <i class="bi bi-wallet2 fs-5"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold mb-0">Rincian Transaksi Top-Up Midtrans</h6>
                        <span class="small text-muted" id="modal-topup-code">TOPUP-...</span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="modal-topup-content">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="small text-muted mt-2">Memuat rincian transaksi...</div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-2 px-4 d-flex justify-content-between">
                <div id="modal-action-buttons"></div>
                <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Top-Up Saldo Manual oleh Admin -->
<div class="modal fade" id="manualTopupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white border-0 py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-plus-circle-fill fs-5"></i>
                    <h6 class="modal-title fw-bold mb-0">Top-Up Saldo Pengguna Manual</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="manualTopupForm" onsubmit="handleManualTopupSubmit(event)">
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 px-3 small rounded-3 border-0 d-flex align-items-start gap-2 mb-3">
                        <i class="bi bi-info-circle-fill fs-6 mt-0.5"></i>
                        <div>Top-up manual akan langsung menambahkan saldo ke dompet akun yang dipilih dan dicatat resmi pada riwayat mutasi platform.</div>
                    </div>

                    <!-- Select User -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Pilih Pengguna / Pelanggan <span class="text-danger">*</span></label>
                        <select id="manual-user-id" class="form-select rounded-3" required>
                            <option value="">-- Pilih Akun Penerima Saldo --</option>
                            <?php foreach ($users_list as $u): ?>
                                <option value="<?= $u['id'] ?>">
                                    <?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['phone'] ?? '-') ?>) - [<?= strtoupper($u['role']) ?>]
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Nominal Presets -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Nominal Pengisian (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group mb-2">
                            <span class="input-group-text bg-light fw-bold">Rp</span>
                            <input type="number" id="manual-amount" class="form-control form-control-lg fw-bold text-success rounded-end-3" min="1000" step="1000" placeholder="100000" required>
                        </div>
                        <!-- Quick Preset Buttons -->
                        <div class="d-flex gap-1 flex-wrap">
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="setManualAmount(20000)">+20rb</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="setManualAmount(50000)">+50rb</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="setManualAmount(100000)">+100rb</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="setManualAmount(200000)">+200rb</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="setManualAmount(500000)">+500rb</button>
                        </div>
                    </div>

                    <!-- Admin Notes -->
                    <div class="mb-2">
                        <label class="form-label fw-bold small text-dark">Catatan Admin / Referensi (Opsional)</label>
                        <input type="text" id="manual-notes" class="form-control rounded-3" placeholder="Contoh: Top-up promosi / kompensasi admin / setor tunai">
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3 px-4">
                    <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-bold" id="btn-submit-manual-topup">
                        <i class="bi bi-check2-circle me-1"></i> Proses Top-Up
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Copy text helper
function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Kode disalin: ' + text,
                showConfirmButton: false,
                timer: 2000
            });
        });
    } else {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Kode disalin: ' + text,
            showConfirmButton: false,
            timer: 2000
        });
    }
}

// Quick set nominal in manual modal
function setManualAmount(val) {
    document.getElementById('manual-amount').value = val;
}

// Format IDR Helper
function formatRupiahJs(number) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(number);
}

// 1. Sinkronisasi Real-Time dengan Midtrans Status API
async function syncMidtransStatus(topupCode, rowId) {
    Swal.fire({
        title: 'Menghubungi Midtrans API...',
        html: `Memeriksa status transaksi <code>${topupCode}</code> ke server Midtrans...`,
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const fd = new FormData();
        fd.append('topup_code', topupCode);

        const res = await fetch((window.BASE_URL || '') + '/admin/topups/sync-status', {
            method: 'POST',
            body: fd
        });

        const data = await res.json();

        if (data.success) {
            const midData = data.data.midtrans_data || {};
            const processRes = data.data.process_result || {};
            const txStatus = midData.transaction_status || 'unknown';
            const payType = midData.payment_type || '-';
            const grossAmount = midData.gross_amount ? formatRupiahJs(midData.gross_amount) : '-';

            await Swal.fire({
                title: 'Sinkronisasi Berhasil!',
                html: `
                    <div class="text-start small p-3 bg-light rounded-3 border">
                        <div class="mb-1"><strong>Kode Transaksi:</strong> <code>${topupCode}</code></div>
                        <div class="mb-1"><strong>Status Midtrans:</strong> <span class="badge ${txStatus === 'settlement' || txStatus === 'capture' ? 'bg-success' : 'bg-warning text-dark'} text-uppercase">${txStatus}</span></div>
                        <div class="mb-1"><strong>Metode Pembayaran:</strong> ${payType}</div>
                        <div class="mb-1"><strong>Nominal:</strong> ${grossAmount}</div>
                        <div class="mt-2 text-muted" style="font-size: 11px;">
                            ${processRes.message || 'Status database berhasil diperbarui.'}
                        </div>
                    </div>
                `,
                icon: 'success',
                confirmButtonColor: '#2563eb'
            });

            location.reload();
        } else {
            Swal.fire({
                title: 'Respon Midtrans',
                text: data.message || 'Transaksi belum terdata atau belum dibayar di Midtrans.',
                icon: 'info',
                confirmButtonColor: '#2563eb'
            });
        }
    } catch (err) {
        console.error(err);
        Swal.fire('Kesalahan Sistem', 'Gagal menghubungkan ke endpoint sinkronisasi Midtrans.', 'error');
    }
}

// 2. Tampilkan Detail Transaksi Modal
async function showTopupDetail(id) {
    const modalEl = document.getElementById('topupDetailModal');
    const modal = new bootstrap.Modal(modalEl);
    const contentEl = document.getElementById('modal-topup-content');
    const codeEl = document.getElementById('modal-topup-code');
    const actionBtnsEl = document.getElementById('modal-action-buttons');

    contentEl.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="small text-muted mt-2">Mengambil data detail transaksi...</div>
        </div>
    `;
    actionBtnsEl.innerHTML = '';
    modal.show();

    try {
        const res = await fetch((window.BASE_URL || '') + `/admin/topups/${id}`);
        const result = await res.json();

        if (!result.success || !result.data) {
            contentEl.innerHTML = `<div class="alert alert-danger">${result.message || 'Data tidak ditemukan.'}</div>`;
            return;
        }

        const t = result.data;
        const wTx = result.wallet_tx;
        const midStatus = result.midtrans_status;

        codeEl.textContent = t.topup_code;

        let statusBadge = '<span class="badge bg-secondary">Pending</span>';
        if (t.status === 'success') {
            statusBadge = '<span class="badge bg-success">Berhasil (Settled)</span>';
        } else if (t.status === 'failed') {
            statusBadge = '<span class="badge bg-danger">Gagal</span>';
        } else if (t.status === 'canceled') {
            statusBadge = '<span class="badge bg-secondary">Dibatalkan</span>';
        }

        let midtransInfoHtml = '';
        if (midStatus && midStatus.success && midStatus.data) {
            const m = midStatus.data;
            midtransInfoHtml = `
                <div class="p-3 bg-light rounded-3 border mb-3">
                    <h6 class="fw-bold small mb-2 text-primary"><i class="bi bi-cloud-check-fill me-1"></i> Respon Live Midtrans Gateway:</h6>
                    <div class="row g-2 small">
                        <div class="col-sm-6"><strong>Transaction Status:</strong> <span class="badge bg-dark">${m.transaction_status || '-'}</span></div>
                        <div class="col-sm-6"><strong>Payment Type:</strong> ${m.payment_type || '-'}</div>
                        <div class="col-sm-6"><strong>Transaction ID:</strong> <code class="small">${m.transaction_id || '-'}</code></div>
                        <div class="col-sm-6"><strong>Transaction Time:</strong> ${m.transaction_time || '-'}</div>
                        <div class="col-sm-6"><strong>Settlement Time:</strong> ${m.settlement_time || '-'}</div>
                        <div class="col-sm-6"><strong>Issuer / Bank:</strong> ${m.issuer || m.bank || '-'}</div>
                    </div>
                </div>
            `;
        } else {
            midtransInfoHtml = `
                <div class="p-2.5 bg-light rounded-3 border mb-3 small text-muted">
                    <i class="bi bi-info-circle me-1"></i> <strong>Midtrans Status:</strong> ${midStatus && midStatus.message ? midStatus.message : 'Belum ada transaksi di gateway'}
                </div>
            `;
        }

        contentEl.innerHTML = `
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 border h-100">
                        <h6 class="fw-bold small text-muted text-uppercase mb-2">Informasi Akun Pengguna</h6>
                        <div class="fw-bold text-dark fs-6">${t.user_name || '-'}</div>
                        <div class="small text-muted mb-1"><i class="bi bi-telephone me-1"></i> ${t.user_phone || '-'}</div>
                        <div class="small text-muted mb-2"><i class="bi bi-envelope me-1"></i> ${t.user_email || '-'}</div>
                        <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                            <span class="small text-muted">Saldo Dompet Saat Ini:</span>
                            <span class="fw-bold text-success">${formatRupiahJs(t.current_wallet_balance)}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 border h-100">
                        <h6 class="fw-bold small text-muted text-uppercase mb-2">Rincian Top-Up</h6>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small text-muted">Nominal:</span>
                            <span class="fw-bold text-success fs-5">${formatRupiahJs(t.amount)}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small text-muted">Status:</span>
                            <div>${statusBadge}</div>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small text-muted">Metode:</span>
                            <span class="small fw-semibold text-dark">${t.payment_type || 'Midtrans Snap'}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="small text-muted">Waktu Dibuat:</span>
                            <span class="small text-dark">${t.created_at}</span>
                        </div>
                    </div>
                </div>
            </div>

            ${midtransInfoHtml}

            ${t.snap_token ? `
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">Snap Token:</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control font-monospace" value="${t.snap_token}" readonly>
                        <button class="btn btn-outline-secondary" onclick="copyToClipboard('${t.snap_token}')"><i class="bi bi-clipboard"></i></button>
                    </div>
                </div>
            ` : ''}

            ${t.notes ? `
                <div class="p-2.5 bg-light rounded-3 border text-muted small">
                    <strong>Catatan Transaksi:</strong> ${t.notes}
                </div>
            ` : ''}
        `;

        if (t.status === 'pending') {
            actionBtnsEl.innerHTML = `
                <button type="button" class="btn btn-outline-primary btn-sm rounded-3 me-1" onclick="syncMidtransStatus('${t.topup_code}', ${t.id})">
                    <i class="bi bi-arrow-repeat me-1"></i> Sync Status Sekarang
                </button>
                <button type="button" class="btn btn-success btn-sm rounded-3" onclick="manualApproveTopup(${t.id}, '${t.topup_code}', '${formatRupiahJs(t.amount)}', '${t.user_name}')">
                    <i class="bi bi-check2 me-1"></i> Setujui Manual
                </button>
            `;
        }
    } catch (err) {
        console.error(err);
        contentEl.innerHTML = '<div class="alert alert-danger">Gagal memuat detail transaksi.</div>';
    }
}

// 3. Setujui Top-Up Manual (Rekonsiliasi)
function manualApproveTopup(id, code, amount, userName) {
    Swal.fire({
        title: 'Konfirmasi Persetujuan Manual',
        html: `
            <div class="text-start small">
                <div class="p-3 bg-light rounded-3 border mb-3">
                    <div><strong>Kode Top-Up:</strong> ${code}</div>
                    <div><strong>Pengguna:</strong> ${userName}</div>
                    <div><strong>Nominal Saldo:</strong> <span class="text-success fw-bold fs-6">${amount}</span></div>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-bold small">Catatan Admin (Opsional):</label>
                    <input id="swal-approve-notes" type="text" class="form-control form-control-sm rounded-3" placeholder="Contoh: Dikonfirmasi manual via transfer bukti rekening">
                </div>
                <div class="text-muted" style="font-size: 11px;">
                    Saldo sebesar <strong>${amount}</strong> akan langsung ditambahkan ke dompet pengguna dan notifikasi otomatis dikirimkan.
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Setujui & Tambah Saldo',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#16A34A',
        preConfirm: () => {
            return { notes: document.getElementById('swal-approve-notes').value.trim() };
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Menyetujui...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            try {
                const fd = new FormData();
                fd.append('id', id);
                fd.append('admin_notes', result.value.notes);

                const res = await fetch((window.BASE_URL || '') + '/admin/topups/manual-approve', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();

                if (data.success) {
                    await Swal.fire('Berhasil!', data.message, 'success');
                    location.reload();
                } else {
                    Swal.fire('Gagal', data.message || 'Terjadi kesalahan.', 'error');
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Kesalahan Sistem', 'Gagal memproses persetujuan.', 'error');
            }
        }
    });
}

// 4. Batalkan Top-Up Manual
function manualCancelTopup(id, code) {
    Swal.fire({
        title: 'Batalkan Transaksi Top-Up?',
        html: `Apakah Anda yakin ingin membatalkan transaksi <code>${code}</code>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Batalkan',
        cancelButtonText: 'Kembali',
        confirmButtonColor: '#DC2626',
        input: 'text',
        inputPlaceholder: 'Alasan pembatalan (opsional)...'
    }).then(async (result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Membatalkan...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            try {
                const fd = new FormData();
                fd.append('id', id);
                fd.append('admin_notes', result.value || 'Dibatalkan oleh Administrator');

                const res = await fetch((window.BASE_URL || '') + '/admin/topups/manual-cancel', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();

                if (data.success) {
                    await Swal.fire('Dibatalkan!', data.message, 'success');
                    location.reload();
                } else {
                    Swal.fire('Gagal', data.message || 'Terjadi kesalahan.', 'error');
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Kesalahan Sistem', 'Gagal membatalkan transaksi.', 'error');
            }
        }
    });
}

// 5. Submit Top-Up Saldo Manual ke User
async function handleManualTopupSubmit(e) {
    e.preventDefault();

    const userId = document.getElementById('manual-user-id').value;
    const amount = document.getElementById('manual-amount').value;
    const notes = document.getElementById('manual-notes').value.trim();

    if (!userId || !amount || parseFloat(amount) <= 0) {
        Swal.fire('Peringatan', 'Silakan pilih pengguna dan masukkan nominal valid.', 'warning');
        return;
    }

    const btn = document.getElementById('btn-submit-manual-topup');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';

    try {
        const fd = new FormData();
        fd.append('user_id', userId);
        fd.append('amount', amount);
        fd.append('notes', notes);

        const res = await fetch((window.BASE_URL || '') + '/admin/customers/topup', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();

        if (data.success) {
            // Also record a log entry in topup_logs for consistency
            bootstrap.Modal.getInstance(document.getElementById('manualTopupModal')).hide();
            await Swal.fire({
                title: 'Top-Up Berhasil!',
                text: data.message || `Saldo sebesar ${formatRupiahJs(amount)} berhasil ditambahkan.`,
                icon: 'success',
                confirmButtonColor: '#2563eb'
            });
            location.reload();
        } else {
            Swal.fire('Gagal', data.message || 'Gagal menambahkan saldo.', 'error');
        }
    } catch (err) {
        console.error(err);
        Swal.fire('Kesalahan Sistem', 'Gagal menghubungi server.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Proses Top-Up';
    }
}
</script>
