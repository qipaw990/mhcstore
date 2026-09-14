<?php
// ============================================================
// Helper: format rupiah (fallback jika belum tersedia global)
// ============================================================
if (!function_exists('format_rupiah')) {
    function format_rupiah($n) {
        return 'Rp ' . number_format((float)$n, 0, ',', '.');
    }
}

// Pre-compute max revenue for bar chart scaling
$maxRev = 1;
foreach ($revenue_trend ?? [] as $t) {
    if ((float)$t['revenue'] > $maxRev) $maxRev = (float)$t['revenue'];
}

// Pipeline data
$pip = $pipeline ?? ['pending'=>0,'confirmed'=>0,'processing'=>0,'on_the_way'=>0,'delivered'=>0,'canceled'=>0];
?>

<!-- ======================= PAGE HEADER ======================= -->
<div class="page-header">
    <div class="page-header-left">
        <h4 class="page-title">
            <i class="bi bi-speedometer2 text-dark"></i> Pusat Kendali Eksekutif
        </h4>
        <p class="page-subtitle">
            Monitoring omset, armada kurir, pesanan real-time & ekosistem multi-vendor CicalengkaGO.
        </p>
    </div>
    <div class="page-header-right">
        <a href="<?= $baseUrl ?>/admin/orders"
           class="btn btn-sm btn-admin-primary rounded-pill px-3 shadow-sm d-flex align-items-center gap-1.5">
            <i class="bi bi-crosshair me-0.5"></i> Dispatch Radar
        </a>
        <button onclick="location.reload()" class="btn btn-sm btn-outline-theme rounded-pill px-3 d-flex align-items-center gap-1.5">
            <i class="bi bi-arrow-clockwise me-0.5"></i> Refresh
        </button>
    </div>
</div>

<!-- ======================= 6 KPI METRIC CARDS ======================= -->
<div class="row g-3 mb-4">

    <!-- GMV -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="stat-card stat-primary h-100">
            <div class="flex-grow-1 min-w-0">
                <div class="stat-label">Total GMV Transaksi</div>
                <h5 class="stat-value text-dark mb-1"><?= format_rupiah($total_revenue ?? 0) ?></h5>
                <div class="stat-trend text-emerald" style="color:#10b981;">
                    <i class="bi bi-arrow-up-right"></i> Gross Volume
                </div>
            </div>
            <div class="stat-icon">
                <i class="bi bi-wallet2"></i>
            </div>
        </div>
    </div>

    <!-- Platform Profit -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="stat-card stat-blue h-100">
            <div class="flex-grow-1 min-w-0">
                <div class="stat-label">Profit Platform</div>
                <h5 class="stat-value mb-1" style="color:#2563eb;"><?= format_rupiah($platform_profit ?? 0) ?></h5>
                <div class="stat-trend text-muted">
                    <i class="bi bi-percent"></i> Komisi <?= $commission_rate ?? 10 ?>% Net
                </div>
            </div>
            <div class="stat-icon" style="background:#eff6ff;color:#2563eb;border-color:#bfdbfe;">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
        </div>
    </div>

    <!-- Total Pesanan -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="stat-card stat-purple h-100">
            <div class="flex-grow-1 min-w-0">
                <div class="stat-label">Pesanan Total</div>
                <h5 class="stat-value mb-1" style="color:#7c3aed;"><?= number_format($total_orders ?? 0) ?> Order</h5>
                <div class="stat-trend text-muted">
                    <i class="bi bi-check2-circle"></i> Sukses <?= $success_rate ?? 100 ?>%
                </div>
            </div>
            <div class="stat-icon" style="background:#f5f3ff;color:#7c3aed;border-color:#ddd6fe;">
                <i class="bi bi-bag-check-fill"></i>
            </div>
        </div>
    </div>

    <!-- Mitra Toko -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="stat-card stat-amber h-100">
            <div class="flex-grow-1 min-w-0">
                <div class="stat-label">Mitra Toko/Resto</div>
                <h5 class="stat-value mb-1" style="color:#ea580c;"><?= number_format($total_stores ?? 0) ?></h5>
                <div class="stat-trend text-muted">
                    <i class="bi bi-shop"></i> GoFood &amp; Mart
                </div>
            </div>
            <div class="stat-icon" style="background:#fff7ed;color:#ea580c;border-color:#fed7aa;">
                <i class="bi bi-shop-window"></i>
            </div>
        </div>
    </div>

    <!-- Armada Driver -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="stat-card stat-emerald h-100">
            <div class="flex-grow-1 min-w-0">
                <div class="stat-label">Armada Driver</div>
                <h5 class="stat-value mb-1" style="color:#059669;"><?= ($active_drivers ?? 0) ?> / <?= ($total_drivers ?? 0) ?></h5>
                <div class="stat-trend text-muted">
                    <i class="bi bi-bicycle"></i> Online Siaga
                </div>
            </div>
            <div class="stat-icon" style="background:#ecfdf5;color:#059669;border-color:#a7f3d0;">
                <i class="bi bi-bicycle"></i>
            </div>
        </div>
    </div>

    <!-- Pelanggan -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="stat-card h-100">
            <div class="flex-grow-1 min-w-0">
                <div class="stat-label">Pelanggan Terdaftar</div>
                <h5 class="stat-value mb-1"><?= number_format($total_customers ?? 0) ?></h5>
                <div class="stat-trend text-muted">
                    <i class="bi bi-people-fill"></i> Pengguna Aktif
                </div>
            </div>
            <div class="stat-icon">
                <i class="bi bi-people-fill"></i>
            </div>
        </div>
    </div>

</div>

<!-- ======================= PIPELINE COUNTER ======================= -->
<div class="card mb-4">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <span class="d-inline-block rounded-circle bg-success" style="width:9px;height:9px;box-shadow:0 0 0 3px rgba(16,185,129,0.25);animation:pulse 1.8s infinite;"></span>
            <h6 class="fw-bold m-0 text-dark" style="font-size:13.5px;">Pipeline Pesanan Berjalan (Real-time Live)</h6>
        </div>
        <a href="<?= $baseUrl ?>/admin/orders" class="btn btn-sm btn-outline-theme rounded-pill px-3">
            <i class="bi bi-crosshair me-1"></i> Buka Dispatch Radar
        </a>
    </div>
    <div class="card-body px-3 py-3">
        <div class="row g-2">
            <?php
            $pipelineItems = [
                ['key'=>'pending',    'label'=>'Menunggu',    'color'=>'#f59e0b', 'bg'=>'#fffbeb', 'icon'=>'bi-clock-fill'],
                ['key'=>'confirmed',  'label'=>'Dikonfirmasi','color'=>'#0284c7', 'bg'=>'#f0f9ff', 'icon'=>'bi-check-circle-fill'],
                ['key'=>'processing', 'label'=>'Disiapkan',  'color'=>'#7c3aed', 'bg'=>'#f5f3ff', 'icon'=>'bi-fire'],
                ['key'=>'on_the_way', 'label'=>'Diantar',    'color'=>'#2563eb', 'bg'=>'#eff6ff', 'icon'=>'bi-bicycle'],
                ['key'=>'delivered',  'label'=>'Terkirim',   'color'=>'#10b981', 'bg'=>'#ecfdf5', 'icon'=>'bi-patch-check-fill'],
                ['key'=>'canceled',   'label'=>'Dibatalkan', 'color'=>'#f43f5e', 'bg'=>'#fff1f2', 'icon'=>'bi-x-circle-fill'],
            ];
            foreach ($pipelineItems as $pi):
            ?>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="rounded-3 text-center py-3 px-2 border"
                     style="background:<?= $pi['bg'] ?>;border-color:<?= $pi['color'] ?>25!important;">
                    <div class="fw-black" style="font-size:1.85rem;line-height:1;color:<?= $pi['color'] ?>;">
                        <?= $pip[$pi['key']] ?>
                    </div>
                    <div class="mt-1.5 fw-bold text-uppercase" style="font-size:10px;letter-spacing:.05em;color:<?= $pi['color'] ?>;">
                        <i class="bi <?= $pi['icon'] ?> me-1"></i><?= $pi['label'] ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ======================= CHART + TOP STORES ======================= -->
<div class="row g-4 mb-4">

    <!-- Revenue Trend Bar Chart -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold m-0 text-dark" style="font-size:13.5px;">
                            <i class="bi bi-bar-chart-fill text-dark me-2"></i>Tren Pendapatan 7 Hari Terakhir
                        </h6>
                        <p class="text-muted mb-0 mt-0.5" style="font-size:11px;">Volume transaksi harian platform (paid lunas)</p>
                    </div>
                    <span class="badge badge-soft-dark font-monospace">7-Day Analytics</span>
                </div>
            </div>
            <div class="card-body d-flex align-items-end gap-2 px-4 pb-3 pt-4" style="min-height:210px;">
                <?php foreach ($revenue_trend ?? [] as $t):
                    $pct = $maxRev > 0 ? max(8, round(($t['revenue'] / $maxRev) * 100)) : 8;
                    $isToday = ($t['date_val'] === date('Y-m-d'));
                ?>
                <div class="flex-fill d-flex flex-column align-items-center gap-1 position-relative" style="cursor:default;"
                     title="<?= $t['day'] ?> — <?= format_rupiah($t['revenue']) ?>">
                    <span class="fw-bold position-absolute top-0" style="font-size:9px;color:#64748b;white-space:nowrap;transform:translateY(-18px);">
                        <?= $t['revenue'] > 0 ? format_rupiah($t['revenue']) : '-' ?>
                    </span>
                    <div class="w-100 rounded-3 overflow-hidden" style="height:140px;background:#f1f5f9;display:flex;align-items:flex-end;">
                        <div class="w-100 rounded-3 dashboard-bar"
                             style="height:<?= $pct ?>%;
                                    background:<?= $isToday ? 'linear-gradient(to top, #090A0F, #27272A)' : 'linear-gradient(to top, #94a3b8, #cbd5e1)' ?>;
                                    transition:height .5s ease;">
                        </div>
                    </div>
                    <span class="fw-bold text-center" style="font-size:11px;color:<?= $isToday ? '#090A0F' : '#64748b' ?>;">
                        <?= htmlspecialchars($t['day']) ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <?php if (empty($revenue_trend)): ?>
                <div class="w-100 text-center py-5 text-muted" style="font-size:12px;">
                    <i class="bi bi-bar-chart fs-1 d-block mb-2 opacity-25"></i>Belum ada data pendapatan.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Top 5 Mitra Terlaris -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="fw-bold m-0 text-dark" style="font-size:13.5px;">
                    <i class="bi bi-trophy-fill text-amber-500 me-2" style="color:#f59e0b;"></i>Top Mitra Terlaris
                </h6>
                <a href="<?= $baseUrl ?>/admin/stores" class="text-muted small text-decoration-none">Kelola Toko</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($top_stores)): ?>
                <div class="text-center text-muted py-5" style="font-size:12px;">
                    <i class="bi bi-shop fs-2 d-block mb-2 opacity-25"></i>Belum ada transaksi mitra.
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($top_stores as $idx => $ts): ?>
                    <div class="list-group-item border-0 d-flex align-items-center justify-content-between py-3 px-4">
                        <div class="d-flex align-items-center gap-3 min-w-0">
                            <div class="rounded-3 d-flex align-items-center justify-content-center fw-black"
                                 style="width:34px;height:34px;font-size:12px;background:#f4f4f5;color:#090A0F;flex-shrink:0;border:1px solid #e4e4e7;">
                                #<?= $idx + 1 ?>
                            </div>
                            <div class="min-w-0">
                                <p class="fw-bold mb-0 text-truncate text-dark" style="font-size:12.5px;max-width:140px;"><?= htmlspecialchars($ts['name']) ?></p>
                                <span class="text-muted" style="font-size:10.5px;"><?= number_format($ts['total_orders']) ?> Pesanan Selesai</span>
                            </div>
                        </div>
                        <span class="fw-bold text-success text-nowrap ms-2" style="font-size:11.5px;"><?= format_rupiah($ts['total_omset'] ?? 0) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- ======================= RECENT ORDERS TABLE ======================= -->
<div class="card mb-4">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <div>
            <h6 class="fw-bold m-0 text-dark" style="font-size:13.5px;">
                <i class="bi bi-receipt-cutoff text-dark me-2"></i>10 Pesanan Masuk Terbaru
            </h6>
            <p class="text-muted mb-0 mt-0.5" style="font-size:11px;">Transaksi live paling mutakhir di sistem</p>
        </div>
        <a href="<?= $baseUrl ?>/admin/orders" class="btn btn-sm btn-outline-theme rounded-pill px-3">
            Semua Pesanan <i class="bi bi-arrow-up-right ms-1"></i>
        </a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th class="py-3 px-4">Kode Order</th>
                    <th class="py-3">Pelanggan</th>
                    <th class="py-3">Mitra Toko</th>
                    <th class="py-3">Total Tagihan</th>
                    <th class="py-3">Metode</th>
                    <th class="py-3">Status</th>
                    <th class="py-3 text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_orders)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>Belum ada pesanan aktif.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($recent_orders as $o):
                    $st = $o['order_status'] ?? 'pending';
                    $stMap = [
                        'pending'    => ['label'=>'MENUNGGU',     'class'=>'badge-soft-warning'],
                        'confirmed'  => ['label'=>'DIKONFIRMASI', 'class'=>'badge-soft-info'],
                        'processing' => ['label'=>'DISIAPKAN',    'class'=>'badge-soft-purple'],
                        'on_the_way' => ['label'=>'DIANTAR',      'class'=>'badge-soft-primary'],
                        'delivered'  => ['label'=>'TERKIRIM',     'class'=>'badge-soft-success'],
                        'canceled'   => ['label'=>'BATAL',        'class'=>'badge-soft-danger'],
                    ];
                    $badge = $stMap[$st] ?? ['label'=>strtoupper($st),'class'=>'badge-soft-secondary'];
                ?>
                <tr class="hover-row">
                    <td class="py-3 px-4">
                        <a href="<?= $baseUrl ?>/admin/orders?search=<?= urlencode($o['order_code']) ?>"
                           class="fw-bold text-dark text-decoration-none font-monospace">
                            #<?= htmlspecialchars($o['order_code']) ?>
                        </a>
                    </td>
                    <td class="py-3">
                        <p class="fw-bold mb-0 text-dark" style="font-size:12.5px;"><?= htmlspecialchars($o['customer_name'] ?? 'Pelanggan') ?></p>
                        <span class="text-muted" style="font-size:10.5px;"><?= htmlspecialchars($o['customer_phone'] ?? '-') ?></span>
                    </td>
                    <td class="py-3 text-muted fw-semibold"><?= htmlspecialchars($o['store_name'] ?? 'Layanan Reguler') ?></td>
                    <td class="py-3 fw-bold text-dark"><?= format_rupiah($o['total_amount'] ?? 0) ?></td>
                    <td class="py-3">
                        <span class="badge badge-soft-dark text-uppercase">
                            <?= htmlspecialchars($o['payment_method'] ?? 'COD') ?>
                        </span>
                    </td>
                    <td class="py-3">
                        <span class="badge <?= $badge['class'] ?> text-uppercase">
                            <?= $badge['label'] ?>
                        </span>
                    </td>
                    <td class="py-3 text-end pe-4">
                        <a href="<?= $baseUrl ?>/admin/orders/invoice/<?= (int)$o['id'] ?>" target="_blank"
                           class="btn btn-sm btn-outline-theme rounded-2 me-1" title="Cetak Invoice"
                           style="width:30px;height:30px;padding:0;display:inline-flex;align-items:center;justify-content:center;">
                            <i class="bi bi-printer fs-6"></i>
                        </a>
                        <a href="<?= $baseUrl ?>/admin/orders?search=<?= urlencode($o['order_code']) ?>"
                           class="btn btn-sm btn-outline-theme rounded-2" title="Lihat di Dispatch"
                           style="width:30px;height:30px;padding:0;display:inline-flex;align-items:center;justify-content:center;">
                            <i class="bi bi-eye fs-6"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ======================= MODULES GRID ======================= -->
<div class="card">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold m-0 text-dark" style="font-size:13.5px;">
            <i class="bi bi-grid-3x3-gap-fill text-dark me-2"></i>Modul Layanan Multi-Vendor Aktif
        </h6>
        <a href="<?= $baseUrl ?>/admin/modules" class="btn btn-sm btn-outline-theme rounded-pill px-3">Kelola Modul</a>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($modules ?? [] as $m):
                $mIcon = $m['icon'] ?? 'box';
                $mIconClass = (str_starts_with($mIcon, 'bi-') || str_starts_with($mIcon, 'bi ')) ? $mIcon : 'bi-' . $mIcon;
                $isActive = ($m['is_active'] ?? 1) == 1;
            ?>
            <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                <div class="border rounded-3 p-3 text-center h-100 d-flex flex-column align-items-center justify-content-center gap-2"
                     style="background:<?= $isActive ? '#ffffff' : '#f8f9fa' ?>;border-color:<?= $isActive ? '#e2e8f0' : '#e5e7eb' ?>!important;transition:.2s;">
                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                         style="width:44px;height:44px;background:#f4f4f5;color:#090A0F;border:1px solid #e4e4e7;">
                        <i class="bi <?= htmlspecialchars($mIconClass) ?> fs-5"></i>
                    </div>
                    <p class="fw-bold mb-0 text-dark text-center" style="font-size:12px;"><?= htmlspecialchars($m['name']) ?></p>
                    <div>
                        <span class="badge <?= $isActive ? 'badge-soft-success' : 'badge-soft-dark' ?>">
                            <?= $isActive ? 'Aktif' : 'Nonaktif' ?>
                        </span>
                        <span class="badge badge-soft-dark ms-1"><?= number_format($m['store_count'] ?? 0) ?> Toko</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
/* Pulse animation for live indicator */
@keyframes pulse {
  0%,100% { opacity:1; transform:scale(1); }
  50%      { opacity:.5; transform:scale(1.25); }
}
/* Hover row highlight */
.hover-row { transition: background .15s; }
.hover-row:hover { background: #f8fafc; }
/* Dashboard bar hover */
.dashboard-bar:hover { filter: brightness(1.15); }
</style>
