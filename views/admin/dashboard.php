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
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-3 border-bottom">
    <div>
        <h4 class="page-title mb-0">
            <i class="bi bi-speedometer2 text-danger me-2"></i>Pusat Kendali Eksekutif
        </h4>
        <p class="page-subtitle mb-0 mt-1">
            Monitoring omset, armada kurir, pesanan real-time & ekosistem multi-vendor CicalengkaGO.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= $baseUrl ?>/admin/orders"
           class="btn btn-sm fw-bold rounded-pill px-3 shadow-sm d-flex align-items-center gap-1"
           style="background:linear-gradient(135deg,#EE2737,#C61524);color:#fff;">
            <i class="bi bi-crosshair me-1"></i> Dispatch Radar
        </a>
        <button onclick="location.reload()" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold d-flex align-items-center gap-1">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
    </div>
</div>

<!-- ======================= 6 KPI CARDS ======================= -->
<div class="row g-3 mb-4">

    <!-- GMV -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="stat-card h-100" style="border-left:4px solid #EE2737;">
            <div class="flex-grow-1 min-w-0">
                <div class="stat-label">Total Transaksi (GMV)</div>
                <h5 class="stat-value text-danger mb-1" style="font-size:1.1rem;"><?= format_rupiah($total_revenue ?? 0) ?></h5>
                <div class="stat-trend text-success" style="font-size:11px;">
                    <i class="bi bi-arrow-up-right"></i> Gross Merchandise Value
                </div>
            </div>
            <div class="stat-icon bg-danger-subtle text-danger flex-shrink-0">
                <i class="bi bi-wallet2 fs-5"></i>
            </div>
        </div>
    </div>

    <!-- Platform Profit -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="stat-card h-100" style="border-left:4px solid #0d6efd;">
            <div class="flex-grow-1 min-w-0">
                <div class="stat-label">Profit Platform</div>
                <h5 class="stat-value mb-1" style="font-size:1.1rem;color:#0d6efd;"><?= format_rupiah($platform_profit ?? 0) ?></h5>
                <div class="stat-trend text-muted" style="font-size:11px;">
                    <i class="bi bi-percent"></i> Komisi <?= $commission_rate ?? 10 ?>% Net
                </div>
            </div>
            <div class="stat-icon bg-primary-subtle text-primary flex-shrink-0">
                <i class="bi bi-graph-up-arrow fs-5"></i>
            </div>
        </div>
    </div>

    <!-- Total Pesanan -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="stat-card h-100" style="border-left:4px solid #6f42c1;">
            <div class="flex-grow-1 min-w-0">
                <div class="stat-label">Pesanan Total</div>
                <h5 class="stat-value mb-1" style="font-size:1.1rem;color:#6f42c1;"><?= number_format($total_orders ?? 0) ?> Order</h5>
                <div class="stat-trend text-muted" style="font-size:11px;">
                    <i class="bi bi-check2-circle"></i> Sukses <?= $success_rate ?? 100 ?>%
                </div>
            </div>
            <div class="stat-icon flex-shrink-0" style="background:#f3eeff;color:#6f42c1;">
                <i class="bi bi-bag-check-fill fs-5"></i>
            </div>
        </div>
    </div>

    <!-- Mitra Toko -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="stat-card h-100" style="border-left:4px solid #fd7e14;">
            <div class="flex-grow-1 min-w-0">
                <div class="stat-label">Mitra Toko/Resto</div>
                <h5 class="stat-value mb-1" style="font-size:1.1rem;color:#fd7e14;"><?= number_format($total_stores ?? 0) ?></h5>
                <div class="stat-trend text-muted" style="font-size:11px;">
                    <i class="bi bi-shop"></i> GoFood &amp; GoMart
                </div>
            </div>
            <div class="stat-icon bg-warning-subtle text-warning flex-shrink-0">
                <i class="bi bi-shop-window fs-5"></i>
            </div>
        </div>
    </div>

    <!-- Armada Driver -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="stat-card h-100" style="border-left:4px solid #198754;">
            <div class="flex-grow-1 min-w-0">
                <div class="stat-label">Armada Driver</div>
                <h5 class="stat-value text-success mb-1" style="font-size:1.1rem;"><?= ($active_drivers ?? 0) ?> / <?= ($total_drivers ?? 0) ?></h5>
                <div class="stat-trend text-muted" style="font-size:11px;">
                    <i class="bi bi-bicycle"></i> Online Siap Antar
                </div>
            </div>
            <div class="stat-icon bg-success-subtle text-success flex-shrink-0">
                <i class="bi bi-bicycle fs-5"></i>
            </div>
        </div>
    </div>

    <!-- Pelanggan -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="stat-card h-100" style="border-left:4px solid #6c757d;">
            <div class="flex-grow-1 min-w-0">
                <div class="stat-label">Pelanggan Terdaftar</div>
                <h5 class="stat-value mb-1" style="font-size:1.1rem;"><?= number_format($total_customers ?? 0) ?></h5>
                <div class="stat-trend text-muted" style="font-size:11px;">
                    <i class="bi bi-people-fill"></i> Pengguna Aplikasi
                </div>
            </div>
            <div class="stat-icon bg-secondary-subtle text-secondary flex-shrink-0">
                <i class="bi bi-people-fill fs-5"></i>
            </div>
        </div>
    </div>

</div>

<!-- ======================= PIPELINE COUNTER ======================= -->
<div class="card shadow-2xs mb-4" style="border-radius:1rem;">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between" style="border-radius:1rem 1rem 0 0;">
        <div class="d-flex align-items-center gap-2">
            <span class="d-inline-block rounded-circle bg-success" style="width:10px;height:10px;animation:pulse 1.5s infinite;"></span>
            <h6 class="fw-black m-0" style="font-size:13.5px;">Pipeline Pesanan Berjalan (Real-time)</h6>
        </div>
        <a href="<?= $baseUrl ?>/admin/orders" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold" style="font-size:11px;">
            <i class="bi bi-crosshair me-1"></i>Buka Dispatch Console
        </a>
    </div>
    <div class="card-body px-3 py-3">
        <div class="row g-2">
            <?php
            $pipelineItems = [
                ['key'=>'pending',    'label'=>'Menunggu',    'color'=>'#ffc107', 'bg'=>'#fff8e1', 'icon'=>'bi-clock-fill'],
                ['key'=>'confirmed',  'label'=>'Dikonfirmasi','color'=>'#0dcaf0', 'bg'=>'#e0f8ff', 'icon'=>'bi-check-circle-fill'],
                ['key'=>'processing', 'label'=>'Disiapkan',  'color'=>'#6f42c1', 'bg'=>'#f3eeff', 'icon'=>'bi-fire'],
                ['key'=>'on_the_way', 'label'=>'Diantar',    'color'=>'#0d6efd', 'bg'=>'#e8f0ff', 'icon'=>'bi-bicycle'],
                ['key'=>'delivered',  'label'=>'Terkirim',   'color'=>'#198754', 'bg'=>'#e8f5ee', 'icon'=>'bi-patch-check-fill'],
                ['key'=>'canceled',   'label'=>'Dibatalkan', 'color'=>'#dc3545', 'bg'=>'#ffeef0', 'icon'=>'bi-x-circle-fill'],
            ];
            foreach ($pipelineItems as $pi):
            ?>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="rounded-3 text-center py-3 px-2 border"
                     style="background:<?= $pi['bg'] ?>;border-color:<?= $pi['color'] ?>30!important;">
                    <div class="fw-black" style="font-size:2rem;line-height:1;color:<?= $pi['color'] ?>;">
                        <?= $pip[$pi['key']] ?>
                    </div>
                    <div class="mt-1 fw-bold text-uppercase" style="font-size:10px;letter-spacing:.05em;color:<?= $pi['color'] ?>;">
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
        <div class="card shadow-2xs h-100" style="border-radius:1rem;">
            <div class="card-header bg-white py-3 border-bottom" style="border-radius:1rem 1rem 0 0;">
                <h6 class="fw-black m-0" style="font-size:13.5px;">
                    <i class="bi bi-bar-chart-fill text-danger me-2"></i>Tren Pendapatan 7 Hari Terakhir
                </h6>
                <p class="text-muted mb-0 mt-0.5" style="font-size:11px;">Volume transaksi harian platform (paid)</p>
            </div>
            <div class="card-body d-flex align-items-end gap-2 px-4 pb-3 pt-4" style="min-height:200px;">
                <?php foreach ($revenue_trend ?? [] as $t):
                    $pct = $maxRev > 0 ? max(8, round(($t['revenue'] / $maxRev) * 100)) : 8;
                    $isToday = ($t['date_val'] === date('Y-m-d'));
                ?>
                <div class="flex-fill d-flex flex-column align-items-center gap-1 position-relative" style="cursor:default;"
                     title="<?= $t['day'] ?> — <?= format_rupiah($t['revenue']) ?>">
                    <span class="fw-bold position-absolute top-0" style="font-size:9px;color:#94a3b8;white-space:nowrap;transform:translateY(-18px);">
                        <?= $t['revenue'] > 0 ? format_rupiah($t['revenue']) : '-' ?>
                    </span>
                    <div class="w-100 rounded-3 overflow-hidden" style="height:140px;background:#f1f5f9;display:flex;align-items:flex-end;">
                        <div class="w-100 rounded-3 dashboard-bar"
                             style="height:<?= $pct ?>%;
                                    background:<?= $isToday ? 'linear-gradient(to top,#C61524,#EE2737)' : 'linear-gradient(to top,#6c757d,#94a3b8)' ?>;
                                    transition:height .5s ease;">
                        </div>
                    </div>
                    <span class="fw-bold text-center" style="font-size:11px;color:<?= $isToday ? '#EE2737' : '#64748b' ?>;">
                        <?= htmlspecialchars($t['day']) ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <?php if (empty($revenue_trend)): ?>
                <div class="w-100 text-center py-5 text-muted" style="font-size:13px;">
                    <i class="bi bi-bar-chart fs-1 d-block mb-2 opacity-25"></i>Belum ada data pendapatan.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Top 5 Mitra Terlaris -->
    <div class="col-lg-4">
        <div class="card shadow-2xs h-100" style="border-radius:1rem;">
            <div class="card-header bg-white py-3 border-bottom" style="border-radius:1rem 1rem 0 0;">
                <h6 class="fw-black m-0" style="font-size:13.5px;">
                    <i class="bi bi-trophy-fill text-warning me-2"></i>Top Mitra Terlaris
                </h6>
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
                                 style="width:36px;height:36px;font-size:13px;background:#fff8e1;color:#fd7e14;flex-shrink:0;">
                                #<?= $idx + 1 ?>
                            </div>
                            <div class="min-w-0">
                                <p class="fw-black mb-0 text-truncate" style="font-size:13px;max-width:140px;"><?= htmlspecialchars($ts['name']) ?></p>
                                <span class="text-muted" style="font-size:11px;"><?= number_format($ts['total_orders']) ?> Pesanan Selesai</span>
                            </div>
                        </div>
                        <span class="fw-black text-success text-nowrap ms-2" style="font-size:12px;"><?= format_rupiah($ts['total_omset'] ?? 0) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- ======================= RECENT ORDERS TABLE ======================= -->
<div class="card shadow-2xs mb-4" style="border-radius:1rem;">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between" style="border-radius:1rem 1rem 0 0;">
        <div>
            <h6 class="fw-black m-0" style="font-size:13.5px;">
                <i class="bi bi-receipt-cutoff text-danger me-2"></i>10 Pesanan Masuk Terbaru
            </h6>
            <p class="text-muted mb-0 mt-0.5" style="font-size:11px;">Transaksi terakhir di sistem</p>
        </div>
        <a href="<?= $baseUrl ?>/admin/orders" class="btn btn-sm btn-outline-dark rounded-pill px-3 fw-bold" style="font-size:11px;">
            Semua Pesanan <i class="bi bi-arrow-up-right ms-1"></i>
        </a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size:13px;">
            <thead>
                <tr class="table-light text-uppercase" style="font-size:10px;letter-spacing:.06em;font-weight:700;">
                    <th class="py-3 px-4">Kode Order</th>
                    <th class="py-3">Pelanggan</th>
                    <th class="py-3">Mitra Toko</th>
                    <th class="py-3">Total Tagihan</th>
                    <th class="py-3">Metode</th>
                    <th class="py-3">Status</th>
                    <th class="py-3 text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody class="border-top">
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
                <tr class="border-bottom border-light hover-row">
                    <td class="py-3 px-4">
                        <a href="<?= $baseUrl ?>/admin/orders?search=<?= urlencode($o['order_code']) ?>"
                           class="fw-black text-danger text-decoration-none font-monospace">
                            #<?= htmlspecialchars($o['order_code']) ?>
                        </a>
                    </td>
                    <td class="py-3">
                        <p class="fw-bold mb-0" style="font-size:13px;"><?= htmlspecialchars($o['customer_name'] ?? 'Pelanggan') ?></p>
                        <span class="text-muted" style="font-size:11px;"><?= htmlspecialchars($o['customer_phone'] ?? '-') ?></span>
                    </td>
                    <td class="py-3 text-muted fw-semibold"><?= htmlspecialchars($o['store_name'] ?? 'Layanan Reguler') ?></td>
                    <td class="py-3 fw-black text-dark"><?= format_rupiah($o['total_amount'] ?? 0) ?></td>
                    <td class="py-3">
                        <span class="badge bg-light text-dark border fw-bold text-uppercase" style="font-size:10px;">
                            <?= htmlspecialchars($o['payment_method'] ?? 'COD') ?>
                        </span>
                    </td>
                    <td class="py-3">
                        <span class="badge <?= $badge['class'] ?> px-2.5 py-1 text-uppercase" style="font-size:10px;font-weight:700;">
                            <?= $badge['label'] ?>
                        </span>
                    </td>
                    <td class="py-3 text-end pe-4">
                        <a href="<?= $baseUrl ?>/admin/invoice?order_id=<?= (int)$o['id'] ?>"
                           class="btn btn-sm btn-outline-secondary rounded-2 me-1" title="Cetak Invoice"
                           style="width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;">
                            <i class="bi bi-receipt fs-6"></i>
                        </a>
                        <a href="<?= $baseUrl ?>/admin/orders?search=<?= urlencode($o['order_code']) ?>"
                           class="btn btn-sm btn-outline-danger rounded-2" title="Lihat di Dispatch"
                           style="width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;">
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
<div class="card shadow-2xs" style="border-radius:1rem;">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between" style="border-radius:1rem 1rem 0 0;">
        <h6 class="fw-black m-0" style="font-size:13.5px;">
            <i class="bi bi-grid-3x3-gap-fill text-danger me-2"></i>Modul Layanan Multi-Vendor Aktif
        </h6>
        <a href="<?= $baseUrl ?>/admin/modules" class="btn btn-sm btn-outline-dark rounded-pill px-3 fw-bold" style="font-size:11px;">Kelola Modul</a>
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
                     style="background:<?= $isActive ? '#fff8f8' : '#f8f9fa' ?>;border-color:<?= $isActive ? '#EE273730' : '#dee2e6' ?>!important;transition:.2s;">
                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                         style="width:46px;height:46px;background:<?= $isActive ? '#fee2e2' : '#e9ecef' ?>;">
                        <i class="bi <?= htmlspecialchars($mIconClass) ?> fs-4" style="color:<?= $isActive ? '#EE2737' : '#6c757d' ?>;"></i>
                    </div>
                    <p class="fw-black mb-0 text-dark text-center" style="font-size:12px;"><?= htmlspecialchars($m['name']) ?></p>
                    <div>
                        <span class="badge <?= $isActive ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?> fw-bold" style="font-size:10px;">
                            <?= $isActive ? 'Aktif' : 'Nonaktif' ?>
                        </span>
                        <span class="badge bg-light text-dark border fw-bold ms-1" style="font-size:10px;"><?= number_format($m['store_count'] ?? 0) ?> Toko</span>
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
  50%      { opacity:.5; transform:scale(1.3); }
}
/* Hover row highlight */
.hover-row { transition: background .15s; }
.hover-row:hover { background: #fafafa; }
/* Dashboard bar hover */
.dashboard-bar:hover { filter: brightness(1.15); }
</style>
