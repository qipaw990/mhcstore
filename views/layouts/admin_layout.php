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
        <a href="<?= $baseUrl ?>/admin" class="sidebar-brand text-decoration-none">
            <img src="<?= $baseUrl ?>/assets/images/logo-icon.svg" alt="CicalengkaGO" style="width: 30px; height: 30px; border-radius: 8px;">
            <div class="d-flex flex-column min-w-0">
                <div class="d-flex align-items-center gap-1.5">
                    <span class="fw-extrabold" style="font-size: 15px; letter-spacing: -0.4px; color: #FFFFFF;">Cicalengka<span style="color:#EE2737;">GO</span></span>
                    <span class="sidebar-brand-badge">ADMIN</span>
                </div>
                <span style="font-size: 10px; color: #64748B; font-weight: 600;">Enterprise Operations</span>
            </div>
        </a>

        <ul class="sidebar-menu">
            <!-- Group 1: Dispatch & Live Operations -->
            <li class="sidebar-group-title">Utama & Dispatch</li>
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
                        <i class="bi bi-crosshair"></i>
                        <span>Dispatch Order Radar</span>
                    </div>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/zones" class="menu-link <?= ($active_tab ?? '') === 'zones' ? 'active' : '' ?>">
                    <div class="menu-link-inner">
                        <i class="bi bi-geo-alt-fill"></i>
                        <span>Zona & Coverage Map</span>
                    </div>
                </a>
            </li>

            <!-- Group 2: Business Ecosystem -->
            <li class="sidebar-group-title">Ekosistem Bisnis</li>
            <li>
                <a href="<?= $baseUrl ?>/admin/modules" class="menu-link <?= ($active_tab ?? '') === 'modules' ? 'active' : '' ?>">
                    <div class="menu-link-inner">
                        <i class="bi bi-grid-3x3-gap-fill"></i>
                        <span>Modul Bisnis</span>
                    </div>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/stores" class="menu-link <?= ($active_tab ?? '') === 'stores' ? 'active' : '' ?>">
                    <div class="menu-link-inner">
                        <i class="bi bi-shop-window"></i>
                        <span>Mitra & Toko</span>
                    </div>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/products" class="menu-link <?= ($active_tab ?? '') === 'products' ? 'active' : '' ?>">
                    <div class="menu-link-inner">
                        <i class="bi bi-box-seam"></i>
                        <span>Katalog Produk</span>
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
            <li class="sidebar-group-title">Keuangan & Saldo</li>
            <li>
                <a href="<?= $baseUrl ?>/admin/withdrawals" class="menu-link <?= ($active_tab ?? '') === 'withdrawals' ? 'active' : '' ?>">
                    <div class="menu-link-inner">
                        <i class="bi bi-cash-stack"></i>
                        <span>Pencairan Dana Mitra</span>
                    </div>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/topups" class="menu-link <?= ($active_tab ?? '') === 'topups' ? 'active' : '' ?>">
                    <div class="menu-link-inner">
                        <i class="bi bi-wallet2"></i>
                        <span>Top-Up Saldo Midtrans</span>
                    </div>
                </a>
            </li>

            <!-- Group 4: Users & Settings -->
            <li class="sidebar-group-title">Sistem & Pengaturan</li>
            <li>
                <a href="<?= $baseUrl ?>/admin/customers" class="menu-link <?= ($active_tab ?? '') === 'customers' ? 'active' : '' ?>">
                    <div class="menu-link-inner">
                        <i class="bi bi-people-fill"></i>
                        <span>Daftar Pelanggan</span>
                    </div>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/banners" class="menu-link <?= ($active_tab ?? '') === 'banners' ? 'active' : '' ?>">
                    <div class="menu-link-inner">
                        <i class="bi bi-images"></i>
                        <span>Banner & Promo</span>
                    </div>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/settings" class="menu-link <?= ($active_tab ?? '') === 'settings' ? 'active' : '' ?>">
                    <div class="menu-link-inner">
                        <i class="bi bi-gear-fill"></i>
                        <span>Pengaturan Sistem</span>
                    </div>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/profile" class="menu-link <?= ($active_tab ?? '') === 'profile' ? 'active' : '' ?>">
                    <div class="menu-link-inner">
                        <i class="bi bi-person-badge-fill"></i>
                        <span>Profil Admin</span>
                    </div>
                </a>
            </li>

            <li class="mt-3 pt-2 border-top border-secondary-subtle">
                <a href="<?= $baseUrl ?>/logout" class="menu-link text-danger">
                    <div class="menu-link-inner">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Keluar Admin</span>
                    </div>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Workspace Area -->
    <div class="dashboard-main">
        <header class="dashboard-header">
            <div class="header-title-wrapper">
                <button type="button" class="sidebar-toggle-btn d-lg-none" onclick="toggleAdminSidebar()">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div class="d-none d-sm-flex align-items-center gap-2">
                    <span class="status-pill-online">
                        <span class="status-dot-pulse"></span>
                        Cicalengka Zone 1 Active
                    </span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <a href="<?= $baseUrl ?>" target="_blank" class="btn btn-sm btn-light border fw-bold text-dark rounded-pill px-3 py-1.5 shadow-2xs d-flex align-items-center gap-1.5" style="font-size: 12px;">
                    <i class="bi bi-phone-vibrate text-danger fs-6"></i>
                    <span>Buka PWA Customer</span>
                    <i class="bi bi-box-arrow-up-right text-muted" style="font-size: 10px;"></i>
                </a>

                <div class="dropdown">
                    <button class="btn btn-light btn-sm border rounded-pill d-flex align-items-center gap-2 px-2.5 py-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="rounded-circle bg-dark text-white fw-bold d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 12px;">
                            <?= strtoupper(substr($user['name'] ?? 'A', 0, 1)) ?>
                        </div>
                        <div class="text-start d-none d-md-block">
                            <div class="fw-bold text-dark" style="font-size: 12px; line-height: 1.2;"><?= htmlspecialchars($user['name'] ?? 'Super Administrator') ?></div>
                            <div class="text-muted" style="font-size: 10px;">Super Admin</div>
                        </div>
                        <i class="bi bi-chevron-down text-muted" style="font-size: 10px;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="font-size: 13px;">
                        <li>
                            <div class="px-3 py-2 border-bottom">
                                <div class="fw-bold text-dark"><?= htmlspecialchars($user['name'] ?? 'Super Admin') ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($user['email'] ?? 'admin@cicalengkago.id') ?></div>
                            </div>
                        </li>
                        <li><a class="dropdown-item py-2" href="<?= $baseUrl ?>/admin/profile"><i class="bi bi-person-gear me-2 text-primary"></i>Profil Saya</a></li>
                        <li><a class="dropdown-item py-2" href="<?= $baseUrl ?>/admin/settings"><i class="bi bi-sliders me-2 text-warning"></i>Pengaturan Sistem</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger" href="<?= $baseUrl ?>/logout"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
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
