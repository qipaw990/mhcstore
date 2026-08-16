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
    <title><?= $title ?? 'Merchant Portal - CicalengkaGO' ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/admin.css">

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        window.BASE_URL = "<?= $baseUrl ?>";
    </script>
</head>
<body>

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <aside class="dashboard-sidebar">
        <a href="<?= $baseUrl ?>/vendor" class="sidebar-brand d-flex align-items-center gap-2 text-decoration-none">
            <img src="<?= $baseUrl ?>/assets/images/logo-icon.svg" alt="CicalengkaGO" style="width: 28px; height: 28px; border-radius: 7px;">
            <span class="fw-bold" style="font-size: 15px; letter-spacing: -0.3px; color: #FFFFFF;">Cicalengka<span style="color:#00C817;">GO</span> <span class="badge bg-danger ms-1" style="font-size: 10px;">GoBiz</span></span>
        </a>
        <ul class="sidebar-menu">
            <li>
                <a href="<?= $baseUrl ?>/vendor" class="menu-link <?= ($active_tab ?? '') === 'dashboard' ? 'active' : '' ?>">
                    <i class="bi bi-grid-1x2"></i>
                    <span>Ringkasan Bisnis</span>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/vendor/orders" class="menu-link <?= ($active_tab ?? '') === 'orders' ? 'active' : '' ?>">
                    <i class="bi bi-receipt"></i>
                    <span>Pesanan Masuk</span>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/vendor/products" class="menu-link <?= ($active_tab ?? '') === 'products' ? 'active' : '' ?>">
                    <i class="bi bi-egg-fried"></i>
                    <span>Katalog Menu & Produk</span>
                </a>
            </li>
            <li>
                <a href="<?= $baseUrl ?>/vendor/wallet" class="menu-link <?= ($active_tab ?? '') === 'wallet' ? 'active' : '' ?>">
                    <i class="bi bi-wallet2"></i>
                    <span>Dompet & Penarikan</span>
                </a>
            </li>
            <li class="mt-4">
                <a href="<?= $baseUrl ?>/logout" class="menu-link text-danger">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Keluar Portal</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content Area -->
    <div class="dashboard-main">
        <header class="dashboard-header">
            <h5 class="fw-bold m-0"><?= $title ?? 'Merchant Panel' ?></h5>
            <div class="d-flex align-items-center gap-3">
                <span class="small fw-semibold text-muted">Halo, <?= htmlspecialchars($user['name'] ?? 'Mitra') ?></span>
                <a href="<?= $baseUrl ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-eye me-1"></i> Buka PWA
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
</body>
</html>
