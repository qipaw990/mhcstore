<!-- Executive Summary Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon bg-primary-subtle text-primary">
                <i class="bi bi-wallet2"></i>
            </div>
            <div>
                <div class="text-muted small fw-semibold">Total Omset Platform</div>
                <h5 class="fw-bold text-primary m-0"><?= format_rupiah($total_revenue ?? 0) ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon bg-success-subtle text-success">
                <i class="bi bi-bag-check-fill"></i>
            </div>
            <div>
                <div class="text-muted small fw-semibold">Total Transaksi</div>
                <h5 class="fw-bold text-success m-0"><?= $total_orders ?? 0 ?> Pesanan</h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon bg-warning-subtle text-warning">
                <i class="bi bi-shop"></i>
            </div>
            <div>
                <div class="text-muted small fw-semibold">Mitra Toko Terdaftar</div>
                <h5 class="fw-bold text-dark m-0"><?= $total_stores ?? 0 ?> Mitra</h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon bg-info-subtle text-info">
                <i class="bi bi-bicycle"></i>
            </div>
            <div>
                <div class="text-muted small fw-semibold">Armada Driver Kurir</div>
                <h5 class="fw-bold text-dark m-0"><?= $total_drivers ?? 0 ?> Driver Aktif</h5>
            </div>
        </div>
    </div>
</div>

<!-- Business Modules Overview & Live Orders Table -->
<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold m-0"><i class="bi bi-grid-3x3-gap-fill me-2 text-primary"></i>Modul Multi-Vendor</h6>
                <a href="<?= $baseUrl ?>/admin/modules" class="small fw-semibold text-primary text-decoration-none">Kelola</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($modules as $m): ?>
                        <?php
                            $mIcon = $m['icon'] ?? 'box';
                            $mIconClass = (str_starts_with($mIcon, 'bi-') || str_starts_with($mIcon, 'bi ')) ? $mIcon : 'bi-' . $mIcon;
                        ?>
                        <li class="list-group-item d-flex align-items-center justify-content-between py-3 px-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="badge bg-primary-subtle text-primary border-0 p-2 fs-6 rounded-3"><i class="bi <?= htmlspecialchars($mIconClass) ?>"></i></span>
                                <div>
                                    <span class="fw-bold small text-dark d-block"><?= htmlspecialchars($m['name']) ?></span>
                                    <span class="text-muted" style="font-size: 11px;">Status: Aktif</span>
                                </div>
                            </div>
                            <span class="badge bg-light text-dark border px-3 py-1"><?= $m['store_count'] ?? 0 ?> Toko</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold m-0"><i class="bi bi-receipt-cutoff me-2 text-primary"></i>10 Transaksi Pesanan Terkini</h6>
                <a href="<?= $baseUrl ?>/admin/orders" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold">Buka Dispatch</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Order Code</th>
                            <th>Pelanggan</th>
                            <th>Toko / Parcel</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_orders)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Belum ada pesanan dibuat.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_orders as $o): ?>
                                <?php
                                    $st = $o['order_status'];
                                    $stBadge = 'bg-primary';
                                    if ($st === 'pending') $stBadge = 'bg-secondary';
                                    if ($st === 'confirmed') $stBadge = 'bg-info text-dark';
                                    if ($st === 'processing') $stBadge = 'bg-warning text-dark';
                                    if ($st === 'on_the_way') $stBadge = 'bg-primary';
                                    if ($st === 'delivered') $stBadge = 'bg-success';
                                    if ($st === 'canceled') $stBadge = 'bg-danger';
                                ?>
                                <tr>
                                    <td class="fw-bold text-primary small">#<?= htmlspecialchars($o['order_code']) ?></td>
                                    <td class="small fw-semibold"><?= htmlspecialchars($o['customer_name']) ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars($o['store_name'] ?? 'Cicalengka Parcel') ?></td>
                                    <td class="fw-bold small"><?= format_rupiah($o['total_amount']) ?></td>
                                    <td>
                                        <span class="badge <?= $stBadge ?> text-uppercase px-2 py-1" style="font-size: 10px;"><?= $o['order_status'] ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
