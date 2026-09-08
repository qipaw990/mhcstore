<?php
$appConfig = require APP_PATH . '/config/app.php';
$baseUrl = $appConfig['public_url'];
$user = auth_user();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Super Admin - CicalengkaGO Enterprise' ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/admin.css">

    <script>
        window.BASE_URL = "<?= $baseUrl ?>";
    </script>
</head>
<body>
<?php require_once dirname(__DIR__) . '/partials/preloader.php'; ?>

<!-- Mobile Drawer Backdrop Overlay -->
<div class="sidebar-overlay" onclick="toggleAdminSidebar()"></div>

<div class="dashboard-wrapper">
    <!-- Enterprise Admin Sidebar -->
    <aside class="dashboard-sidebar" id="adminSidebar">
        <!-- Brand Header -->
        <a href="<?= $baseUrl ?>/admin" class="sidebar-brand text-decoration-none">
            <div class="brand-logo-container">
                <img src="<?= $baseUrl ?>/assets/images/logo-icon.svg" alt="CicalengkaGO" class="brand-icon">
            </div>
            <div class="d-flex flex-column min-w-0">
                <div class="d-flex align-items-center gap-1.5">
                    <span class="fw-extrabold brand-title">Cicalengka<span class="brand-accent">GO</span></span>
                    <span class="sidebar-brand-badge">ADMIN</span>
                </div>
                <span class="brand-subtitle">Enterprise Operations System</span>
            </div>
        </a>

        <!-- Navigation Menu -->
        <ul class="sidebar-menu">
            <!-- Group 1: Dispatch & Live Operations -->
            <li class="sidebar-group-title">
                <i class="bi bi-broadcast me-1 text-danger"></i> Dispatch & Operasional
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin" class="menu-link <?= ($active_tab ?? '') === 'dashboard' ? 'active' : '' ?>">
                    <div class="menu-link-inner">
                        <i class="bi bi-speedometer2"></i>
                        <span>Ringkasan Eksekutif</span>
                    </div>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/orders" class="menu-link <?= ($active_tab ?? '') === 'orders' ? 'active' : '' ?>">
                    <div class="menu-link-inner">
                        <i class="bi bi-crosshair2"></i>
                        <span>Dispatch Order Radar</span>
                    </div>
                    <span class="menu-badge badge-live">LIVE</span>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/zones" class="menu-link <?= ($active_tab ?? '') === 'zones' ? 'active' : '' ?>">
                    <div class="menu-link-inner">
                        <i class="bi bi-geo-alt-fill"></i>
                        <span>Zona & Tarif Wilayah</span>
                    </div>
                </a>
            </li>

            <!-- Group 2: Business Ecosystem -->
            <li class="sidebar-group-title">
                <i class="bi bi-shop me-1 text-primary"></i> Layanan & Mitra Bisnis
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/modules" class="menu-link <?= ($active_tab ?? '') === 'modules' ? 'active' : '' ?>">
                    <div class="menu-link-inner">
                        <i class="bi bi-grid-3x3-gap-fill"></i>
                        <span>Modul Multi-Vendor</span>
                    </div>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/stores" class="menu-link <?= ($active_tab ?? '') === 'stores' ? 'active' : '' ?>">
                    <div class="menu-link-inner">
                        <i class="bi bi-shop-window"></i>
                        <span>Mitra Toko & Resto</span>
                    </div>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/products" class="menu-link <?= ($active_tab ?? '') === 'products' ? 'active' : '' ?>">
                    <div class="menu-link-inner">
                        <i class="bi bi-box-seam-fill"></i>
                        <span>Katalog Menu & Produk</span>
                    </div>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/delivery-men" class="menu-link <?= ($active_tab ?? '') === 'drivers' ? 'active' : '' ?>">
                    <div class="menu-link-inner">
                        <i class="bi bi-bicycle"></i>
                        <span>Armada Driver Kurir</span>
                    </div>
                </a>
            </li>

            <!-- Group 3: Finance & Wallet -->
            <li class="sidebar-group-title">
                <i class="bi bi-wallet2 me-1 text-success"></i> Keuangan & Saldo
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/withdrawals" class="menu-link <?= ($active_tab ?? '') === 'withdrawals' ? 'active' : '' ?>">
                    <div class="menu-link-inner">
                        <i class="bi bi-cash-stack"></i>
                        <span>Pencairan Dana (WD)</span>
                    </div>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/topups" class="menu-link <?= ($active_tab ?? '') === 'topups' ? 'active' : '' ?>">
                    <div class="menu-link-inner">
                        <i class="bi bi-credit-card-2-front-fill"></i>
                        <span>Top-Up CicalengkaPay</span>
                    </div>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/payment-methods" class="menu-link <?= ($active_tab ?? '') === 'payment_methods' ? 'active' : '' ?>">
                    <div class="menu-link-inner">
                        <i class="bi bi-qr-code-scan"></i>
                        <span>Bank & QRIS Otomatis</span>
                    </div>
                </a>
            </li>

            <!-- Group 4: Users & Marketing -->
            <li class="sidebar-group-title">
                <i class="bi bi-people-fill me-1 text-warning"></i> Pengguna & Pemasaran
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/customers" class="menu-link <?= ($active_tab ?? '') === 'customers' ? 'active' : '' ?>">
                    <div class="menu-link-inner">
                        <i class="bi bi-people-fill"></i>
                        <span>Basis Data Pelanggan</span>
                    </div>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/banners" class="menu-link <?= ($active_tab ?? '') === 'banners' ? 'active' : '' ?>">
                    <div class="menu-link-inner">
                        <i class="bi bi-images"></i>
                        <span>Banner Promo & Iklan</span>
                    </div>
                </a>
            </li>

            <!-- Group 5: System & Gateway -->
            <li class="sidebar-group-title">
                <i class="bi bi-sliders me-1 text-info"></i> Gateway & Konfigurasi
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/whatsapp" class="menu-link <?= ($active_tab ?? '') === 'whatsapp' ? 'active' : '' ?>">
                    <div class="menu-link-inner">
                        <i class="bi bi-whatsapp"></i>
                        <span>WhatsApp OTP Gateway</span>
                    </div>
                    <span id="wa-status-dot" class="sidebar-status-dot" title="Memeriksa status..."></span>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/settings" class="menu-link <?= ($active_tab ?? '') === 'settings' ? 'active' : '' ?>">
                    <div class="menu-link-inner">
                        <i class="bi bi-gear-wide-connected"></i>
                        <span>Pengaturan & Tarif</span>
                    </div>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/profile" class="menu-link <?= ($active_tab ?? '') === 'profile' ? 'active' : '' ?>">
                    <div class="menu-link-inner">
                        <i class="bi bi-person-badge-fill"></i>
                        <span>Profil Super Admin</span>
                    </div>
                </a>
            </li>

            <li class="sidebar-logout-item">
                <a href="<?= $baseUrl ?>/logout" class="menu-link text-danger">
                    <div class="menu-link-inner">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Keluar Sistem</span>
                    </div>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Workspace Area -->
    <div class="dashboard-main">
        <header class="dashboard-header">
            <div class="header-title-wrapper">
                <button type="button" class="sidebar-toggle-btn d-lg-none" onclick="toggleAdminSidebar()" aria-label="Toggle Navigation">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div class="d-none d-sm-flex align-items-center gap-2">
                    <span class="status-pill-online">
                        <span class="status-dot-pulse"></span>
                        <span class="fw-semibold">Cicalengka Coverage:</span> Live Operational
                    </span>
                </div>
            </div>

            <!-- Header Actions -->
            <div class="d-flex align-items-center gap-2.5">
                <!-- PWA Customer Shortcut -->
                <a href="<?= $baseUrl ?>" target="_blank" class="btn btn-sm btn-light border fw-semibold text-dark rounded-pill px-3 py-1.5 shadow-2xs d-flex align-items-center gap-1.5 hover-lift" style="font-size: 11.5px;">
                    <i class="bi bi-phone text-danger fs-6"></i>
                    <span class="d-none d-md-inline">Aplikasi Pelanggan</span>
                    <i class="bi bi-box-arrow-up-right text-muted" style="font-size: 10px;"></i>
                </a>

                <!-- User Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-light btn-sm border rounded-pill d-flex align-items-center gap-2 px-2.5 py-1 shadow-2xs hover-lift" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="rounded-circle bg-danger text-white fw-bold d-flex align-items-center justify-content-center shadow-xs" style="width: 28px; height: 28px; font-size: 12px; background: linear-gradient(135deg, #EE2737, #B71C1C);">
                            <?= strtoupper(substr($user['name'] ?? 'A', 0, 1)) ?>
                        </div>
                        <div class="text-start d-none d-md-block">
                            <div class="fw-bold text-dark" style="font-size: 11.5px; line-height: 1.2;"><?= htmlspecialchars($user['name'] ?? 'Super Administrator') ?></div>
                            <div class="text-muted" style="font-size: 9.5px; font-weight: 600;">Super Admin</div>
                        </div>
                        <i class="bi bi-chevron-down text-muted ms-0.5" style="font-size: 9px;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-2 py-2" style="font-size: 12.5px; min-width: 210px;">
                        <li class="px-3 py-2 border-bottom mb-1">
                            <div class="fw-bold text-dark"><?= htmlspecialchars($user['name'] ?? 'Super Admin') ?></div>
                            <div class="text-muted small" style="font-size: 11px;"><?= htmlspecialchars($user['email'] ?? 'admin@cicalengkago.id') ?></div>
                        </li>
                        <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= $baseUrl ?>/admin/profile"><i class="bi bi-person-gear text-primary"></i>Profil Saya</a></li>
                        <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= $baseUrl ?>/admin/settings"><i class="bi bi-sliders text-warning"></i>Pengaturan Sistem</a></li>
                        <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= $baseUrl ?>/admin/whatsapp"><i class="bi bi-whatsapp text-success"></i>WhatsApp Gateway</a></li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item py-2 text-danger d-flex align-items-center gap-2" href="<?= $baseUrl ?>/logout"><i class="bi bi-box-arrow-right"></i>Keluar Sistem</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <?= $content ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
function toggleAdminSidebar() {
    document.body.classList.toggle('sidebar-open');
}

// Live WhatsApp Gateway Status Dot (sidebar indicator)
(function checkWaStatus() {
    const dot = document.getElementById('wa-status-dot');
    if (!dot) return;
    fetch((window.BASE_URL || '') + '/admin/whatsapp/status')
        .then(r => r.json())
        .then(data => {
            if (data.ready) {
                dot.style.background = '#16a34a';
                dot.title = 'WhatsApp: Terhubung ✅';
            } else {
                dot.style.background = data.status === 'QR_READY' ? '#f59e0b' : '#ef4444';
                dot.title = 'WhatsApp: ' + (data.status || 'Offline');
            }
        })
        .catch(() => {
            dot.style.background = '#ef4444';
            dot.title = 'WhatsApp: Gateway Offline';
        })
        .finally(() => setTimeout(checkWaStatus, 30000));
})();
</script>

<?php if (!empty($_SESSION['success'])): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: '<?= addslashes($_SESSION['success']) ?>',
        timer: 2500,
        showConfirmButton: false
    });
</script>
<?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Perhatian',
        text: '<?= addslashes($_SESSION['error']) ?>',
        confirmButtonColor: '#EE2737'
    });
</script>
<?php unset($_SESSION['error']); ?>
<?php endif; ?>

</body>
</html>
