<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="page-title"><i class="bi bi-speedometer2 text-danger me-2"></i>Ringkasan Eksekutif</h4>
        <p class="page-subtitle">Performa platform delivery, statistik omset, dan aktivitas multi-vendor CicalengkaGO.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= $baseUrl ?>/admin/orders" class="btn btn-danger btn-sm rounded-pill px-3 fw-bold shadow-2xs d-flex align-items-center gap-1.5" style="background: linear-gradient(135deg, #EE2737, #C61524);">
            <i class="bi bi-crosshair"></i> Pusat Dispatch Radar
        </a>
    </div>
</div>

<!-- Executive Summary Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="min-w-0">
                <div class="stat-label">Total Omset Platform</div>
                <h5 class="stat-value text-danger"><?= format_rupiah($total_revenue ?? 0) ?></h5>
                <div class="stat-trend text-emerald-600">
                    <i class="bi bi-arrow-up-right me-0.5"></i> Transaksi Terverifikasi
                </div>
            </div>
            <div class="stat-icon bg-danger-subtle text-danger">
                <i class="bi bi-wallet2"></i>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="min-w-0">
                <div class="stat-label">Total Transaksi</div>
                <h5 class="stat-value text-dark"><?= number_format($total_orders ?? 0) ?></h5>
                <div class="stat-trend text-muted">
                    <i class="bi bi-bag-check me-1"></i> Pesanan Terproses
                </div>
            </div>
            <div class="stat-icon bg-success-subtle text-success">
                <i class="bi bi-bag-check-fill"></i>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="min-w-0">
                <div class="stat-label">Mitra Toko Terdaftar</div>
                <h5 class="stat-value text-dark"><?= number_format($total_stores ?? 0) ?></h5>
                <div class="stat-trend text-primary">
                    <i class="bi bi-shop me-1"></i> Merchant Aktif
                </div>
            </div>
            <div class="stat-icon bg-primary-subtle text-primary">
                <i class="bi bi-shop-window"></i>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="min-w-0">
                <div class="stat-label">Armada Driver Kurir</div>
                <h5 class="stat-value text-dark"><?= number_format($total_drivers ?? 0) ?></h5>
                <div class="stat-trend text-purple">
                    <i class="bi bi-bicycle me-1"></i> Kurir Siap Dispatch
                </div>
            </div>
            <div class="stat-icon bg-purple-subtle text-purple">
                <i class="bi bi-bicycle"></i>
            </div>
        </div>
    </div>
</div>

<!-- Business Modules Overview & Live Orders Table -->
<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card h-100 shadow-2xs">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold m-0 text-dark" style="font-size: 13.5px;"><i class="bi bi-grid-3x3-gap-fill me-2 text-danger"></i>Modul Multi-Vendor</h6>
                <a href="<?= $baseUrl ?>/admin/modules" class="badge bg-light text-dark border fw-bold text-decoration-none px-2.5 py-1.5 rounded-pill">Kelola</a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush border-0">
                    <?php foreach ($modules as $m): ?>
                        <?php
                            $mIcon = $m['icon'] ?? 'box';
                            $mIconClass = (str_starts_with($mIcon, 'bi-') || str_starts_with($mIcon, 'bi ')) ? $mIcon : 'bi-' . $mIcon;
                        ?>
                        <div class="list-group-item d-flex align-items-center justify-content-between py-3 px-3.5 border-bottom">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-3 bg-light border p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                    <i class="bi <?= htmlspecialchars($mIconClass) ?> text-danger fs-5"></i>
                                </div>
                                <div>
                                    <span class="fw-bold small text-dark d-block"><?= htmlspecialchars($m['name']) ?></span>
                                    <span class="text-muted" style="font-size: 11px;"><i class="bi bi-check-circle-fill text-success me-1"></i>Modul Aktif</span>
                                </div>
                            </div>
                            <span class="badge bg-light text-dark border px-2.5 py-1.5 rounded-pill fw-bold" style="font-size: 11px;"><?= number_format($m['store_count'] ?? 0) ?> Toko</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card h-100 shadow-2xs">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold m-0 text-dark" style="font-size: 13.5px;"><i class="bi bi-receipt-cutoff me-2 text-danger"></i>10 Transaksi Pesanan Terkini</h6>
                <a href="<?= $baseUrl ?>/admin/orders" class="btn btn-sm btn-outline-dark rounded-pill px-3 fw-bold" style="font-size: 11.5px;">Buka Dispatch Radar</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Order Code</th>
                            <th>Pelanggan</th>
                            <th>Toko / Vendor</th>
                            <th>Total Tagihan</th>
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
                                    $stClass = 'badge-soft-primary';
                                    if ($st === 'pending') $stClass = 'badge-soft-warning';
                                    if ($st === 'confirmed') $stClass = 'badge-soft-info';
                                    if ($st === 'processing') $stClass = 'badge-soft-purple';
                                    if ($st === 'on_the_way') $stClass = 'badge-soft-primary';
                                    if ($st === 'delivered') $stClass = 'badge-soft-success';
                                    if ($st === 'canceled') $stClass = 'badge-soft-danger';
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?= $baseUrl ?>/admin/orders?search=<?= urlencode($o['order_code']) ?>" class="fw-bold text-danger text-decoration-none">#<?= htmlspecialchars($o['order_code']) ?></a>
                                    </td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($o['customer_name']) ?></td>
                                    <td class="text-muted"><?= htmlspecialchars($o['store_name'] ?? 'Cicalengka Parcel Hub') ?></td>
                                    <td class="fw-bold text-dark"><?= format_rupiah($o['total_amount']) ?></td>
                                    <td>
                                        <span class="badge <?= $stClass ?> px-2.5 py-1 text-uppercase" style="font-size: 10px; font-weight: 700;"><?= str_replace('_', ' ', $o['order_status']) ?></span>
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
