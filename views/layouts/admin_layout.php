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
    <title><?= $title ?? 'Super Admin - CicalengkaGO' ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/admin.css">

    <script>
        window.BASE_URL = "<?= $baseUrl ?>";
    </script>
</head>
<body>

<div class="dashboard-wrapper">
    <!-- Admin Sidebar -->
    <aside class="dashboard-sidebar">
        <a href="<?= $baseUrl ?>/admin" class="sidebar-brand d-flex align-items-center gap-2 text-decoration-none">
            <img src="<?= $baseUrl ?>/assets/images/logo-icon.svg" alt="CicalengkaGO" style="width: 28px; height: 28px; border-radius: 7px;">
            <span class="fw-bold" style="font-size: 15px; letter-spacing: -0.3px; color: #FFFFFF;">Cicalengka<span style="color:#00C817;">GO</span></span>
        </a>
        <ul class="sidebar-menu">
            <li>
                <a href="<?= $baseUrl ?>/admin" class="menu-link <?= ($active_tab ?? '') === 'dashboard' ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Ringkasan Eksekutif</span>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/orders" class="menu-link <?= ($active_tab ?? '') === 'orders' ? 'active' : '' ?>">
                    <i class="bi bi-receipt-cutoff"></i>
                    <span>Pusat Dispatch Order</span>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/zones" class="menu-link <?= ($active_tab ?? '') === 'zones' ? 'active' : '' ?>">
                    <i class="bi bi-geo-alt-fill"></i>
                    <span>Zona & Polygon Radius</span>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/modules" class="menu-link <?= ($active_tab ?? '') === 'modules' ? 'active' : '' ?>">
                    <i class="bi bi-grid-3x3-gap-fill"></i>
                    <span>Modul Bisnis</span>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/stores" class="menu-link <?= ($active_tab ?? '') === 'stores' ? 'active' : '' ?>">
                    <i class="bi bi-shop-window"></i>
                    <span>Mitra & Toko</span>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/products" class="menu-link <?= ($active_tab ?? '') === 'products' ? 'active' : '' ?>">
                    <i class="bi bi-box-seam"></i>
                    <span>Katalog Produk</span>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/delivery-men" class="menu-link <?= ($active_tab ?? '') === 'drivers' ? 'active' : '' ?>">
                    <i class="bi bi-bicycle"></i>
                    <span>Armada Driver Kurir</span>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/customers" class="menu-link <?= ($active_tab ?? '') === 'customers' ? 'active' : '' ?>">
                    <i class="bi bi-people-fill"></i>
                    <span>Daftar Pelanggan</span>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/banners" class="menu-link <?= ($active_tab ?? '') === 'banners' ? 'active' : '' ?>">
                    <i class="bi bi-images"></i>
                    <span>Banner & Promo</span>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/profile" class="menu-link <?= ($active_tab ?? '') === 'profile' ? 'active' : '' ?>">
                    <i class="bi bi-person-circle"></i>
                    <span>Profil Admin</span>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/admin/settings" class="menu-link <?= ($active_tab ?? '') === 'settings' ? 'active' : '' ?>">
                    <i class="bi bi-gear-fill"></i>
                    <span>Pengaturan Sistem</span>
                </a>
            </li>
            <li class="mt-3">
                <a href="<?= $baseUrl ?>/logout" class="menu-link text-danger">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Keluar Admin</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content Area -->
    <div class="dashboard-main">
        <header class="dashboard-header">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary px-3 py-2">Area Cicalengka, Kab. Bandung</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="small fw-semibold text-muted">Super Administrator</span>
                <a href="<?= $baseUrl ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-phone me-1"></i> Buka PWA Customer
                </a>
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
</body>
</html>
