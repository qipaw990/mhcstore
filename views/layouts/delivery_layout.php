<?php
$appConfig = require APP_PATH . '/config/app.php';
$baseUrl = $appConfig['public_url'];
$user = auth_user();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= $title ?? 'Mitra Driver - CicalengkaGO' ?></title>
    
    <link rel="manifest" href="<?= $baseUrl ?>/manifest.json">
    <meta name="theme-color" content="#EE2737">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="<?= $baseUrl ?>/assets/icons/icon-192.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/mobile.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/driver-style.css?v=<?= time() ?>">

    <script>
        window.BASE_URL = "<?= $baseUrl ?>";
    </script>
</head>
<body style="background-color: #f1f5f9;">
<?php require_once dirname(__DIR__) . '/partials/preloader.php'; ?>

<div class="mobile-app-wrapper" style="background-color: #f8fafc;">
    <!-- Top Driver Header (CicalengkaGO Partner Bar) -->
    <header class="mobile-header d-flex align-items-center justify-content-between px-3 py-2.5" style="background: linear-gradient(135deg, #101820 0%, #1e293b 100%); border-bottom: 2px solid #EE2737; box-shadow: 0 4px 20px rgba(0,0,0,0.25);">
        <div class="d-flex align-items-center gap-2.5">
            <div class="driver-avatar-box position-relative">
                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($user['avatar'] ?? 'assets/images/users/driver.png') ?>" alt="Driver" class="rounded-circle" style="width: 42px; height: 42px; object-fit: cover; border: 2px solid #EE2737;">
                <span class="driver-status-dot online"></span>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-white small"><?= htmlspecialchars($user['name'] ?? 'Mitra Driver') ?></span>
                    <span class="badge rounded-pill text-dark px-2" style="background: #F7A800; font-size: 10px; font-weight: 800;">
                        <i class="bi bi-star-fill me-0.5"></i> 4.9
                    </span>
                </div>
                <div class="d-flex align-items-center gap-1 mt-0.5" style="font-size: 11px; color: #94a3b8;">
                    <img src="<?= $baseUrl ?>/assets/images/logo-icon.svg" alt="CicalengkaGO" style="width: 13px; height: 13px; border-radius: 3px;">
                    <span class="fw-semibold text-white-50">Mitra Kurir Cicalengka<span style="color: #EE2737;">GO</span></span>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button onclick="if(typeof centerDriverMap==='function'){centerDriverMap();}" class="btn btn-dark btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15);" title="Pusatkan GPS">
                <i class="bi bi-crosshair text-danger" style="font-size: 16px;"></i>
            </button>
            <a href="<?= $baseUrl ?>/logout" class="btn btn-outline-danger btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; border-color: rgba(238, 39, 55, 0.5);" title="Keluar">
                <i class="bi bi-box-arrow-right" style="font-size: 15px;"></i>
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow-1">
        <?= $content ?>
    </main>

    <!-- Delivery Bottom Navigation -->
    <nav class="mobile-bottom-nav">
        <a href="<?= $baseUrl ?>/delivery" class="nav-tab-item <?= ($active_tab ?? '') === 'home' ? 'active' : '' ?>">
            <i class="bi bi-radar"></i>
            <span>Radar Order</span>
        </a>
        <a href="<?= $baseUrl ?>/delivery/earnings" class="nav-tab-item <?= ($active_tab ?? '') === 'earnings' ? 'active' : '' ?>">
            <i class="bi bi-wallet2"></i>
            <span>Pendapatan</span>
        </a>
        <a href="<?= $baseUrl ?>/delivery/profile" class="nav-tab-item <?= ($active_tab ?? '') === 'profile' ? 'active' : '' ?>">
            <i class="bi bi-person-circle"></i>
            <span>Profil Driver</span>
        </a>
        <a href="<?= $baseUrl ?>/logout" class="nav-tab-item text-danger">
            <i class="bi bi-box-arrow-right"></i>
            <span>Keluar</span>
        </a>
    </nav>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= $baseUrl ?>/assets/js/delivery-pwa.js?v=<?= time() ?>"></script>

<?php if (!empty($_SESSION['success'])): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: '<?= addslashes($_SESSION['success']) ?>',
            timer: 3000,
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
