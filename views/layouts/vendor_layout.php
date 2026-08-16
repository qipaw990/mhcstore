<?php
$appConfig = require APP_PATH . '/config/app.php';
$baseUrl = $appConfig['public_url'];
$user = auth_user();
$store = null;
$pendingOrdersCount = 0;

if ($user) {
    $store = (new \App\Models\Store())->findByVendorId($user['id']);
    if ($store) {
        $pendingOrders = \App\Core\Database::fetchOne(
            "SELECT COUNT(*) as count FROM `orders` WHERE `store_id` = ? AND `order_status` IN ('pending', 'confirmed')",
            [$store['id']]
        );
        $pendingOrdersCount = (int)($pendingOrders['count'] ?? 0);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= $title ?? 'GoBiz Mitra Toko - CicalengkaGO' ?></title>
    
    <!-- PWA Manifest & Theme -->
    <link rel="manifest" href="<?= $baseUrl ?>/manifest.json">
    <meta name="theme-color" content="#0F172A">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Cicago Merchant">
    <link rel="apple-touch-icon" href="<?= $baseUrl ?>/assets/icons/icon-192.png">

    <!-- CSS Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/mobile.css?v=<?= time() ?>">

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        window.BASE_URL = "<?= $baseUrl ?>";
        window.VENDOR_STORE_ID = <?= $store ? (int)$store['id'] : 0 ?>;
    </script>
</head>
<body class="merchant-pwa-body">
<?php require_once dirname(__DIR__) . '/partials/preloader.php'; ?>

<div class="merchant-app-wrapper">
    <!-- Merchant Sticky Top Header -->
    <header class="merchant-top-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2.5">
            <a href="<?= $baseUrl ?>/vendor/profile" class="position-relative text-decoration-none">
                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($store['logo'] ?? 'assets/images/stores/default.jpg') ?>" alt="Store Logo" class="merchant-avatar-ring">
                <?php if ($store && $store['is_open']): ?>
                    <span class="position-absolute bottom-0 end-0 bg-success border border-2 border-white rounded-circle p-1" style="width: 12px; height: 12px;" title="Toko Buka"></span>
                <?php else: ?>
                    <span class="position-absolute bottom-0 end-0 bg-danger border border-2 border-white rounded-circle p-1" style="width: 12px; height: 12px;" title="Toko Tutup"></span>
                <?php endif; ?>
            </a>
            <div>
                <div class="d-flex align-items-center gap-1.5">
                    <h6 class="fw-bold text-white m-0 text-truncate" style="max-width: 160px; font-size: 14px;">
                        <?= htmlspecialchars($store['name'] ?? 'Mitra Toko') ?>
                    </h6>
                    <span class="badge bg-danger text-white px-1.5 py-0.5" style="font-size: 9px; font-weight: 800; letter-spacing: 0.5px;">GoBiz</span>
                </div>
                <div class="d-flex align-items-center gap-2 mt-0.5">
                    <?php if ($store && $store['is_open']): ?>
                        <span class="merchant-store-status-pill open"><i class="bi bi-circle-fill" style="font-size: 6px;"></i> Buka</span>
                    <?php else: ?>
                        <span class="merchant-store-status-pill closed"><i class="bi bi-circle-fill" style="font-size: 6px;"></i> Tutup</span>
                    <?php endif; ?>
                    <span class="text-white-50" style="font-size: 10.5px;"><i class="bi bi-star-fill text-warning me-0.5"></i> <?= number_format($store['rating'] ?? 5.0, 1) ?></span>
                </div>
            </div>
        </div>

        <!-- Header Actions -->
        <div class="d-flex align-items-center gap-2">
            <button onclick="toggleAudioChime()" id="btnSoundToggle" class="btn btn-dark btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15);" title="Suara Notifikasi Order">
                <i class="bi bi-volume-up-fill text-warning fs-6" id="iconSoundToggle"></i>
            </button>
            <a href="<?= $baseUrl ?>" target="_blank" class="btn btn-dark btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15);" title="Lihat Toko di Aplikasi Pelanggan">
                <i class="bi bi-eye text-white fs-6"></i>
            </a>
            <a href="<?= $baseUrl ?>/logout" class="btn btn-outline-danger btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; border-color: rgba(238, 39, 55, 0.5);" title="Keluar">
                <i class="bi bi-box-arrow-right fs-6"></i>
            </a>
        </div>
    </header>

    <!-- Main Content Dynamic Container -->
    <main class="flex-grow-1 p-3">
        <?= $content ?>
    </main>

    <!-- 5-Tab Merchant Bottom Navigation Bar -->
    <nav class="merchant-bottom-nav">
        <a href="<?= $baseUrl ?>/vendor" class="merchant-nav-item <?= ($active_tab ?? '') === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2<?= ($active_tab ?? '') === 'dashboard' ? '-fill' : '' ?>"></i>
            <span>Beranda</span>
        </a>
        <a href="<?= $baseUrl ?>/vendor/orders" class="merchant-nav-item <?= ($active_tab ?? '') === 'orders' ? 'active' : '' ?>">
            <i class="bi bi-receipt<?= ($active_tab ?? '') === 'orders' ? '-cutoff' : '' ?>"></i>
            <span>Pesanan</span>
            <?php if ($pendingOrdersCount > 0): ?>
                <span class="nav-badge" id="merchantNavBadge"><?= $pendingOrdersCount ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= $baseUrl ?>/vendor/products" class="merchant-nav-item <?= ($active_tab ?? '') === 'products' ? 'active' : '' ?>">
            <i class="bi bi-egg-fried"></i>
            <span>Menu</span>
        </a>
        <a href="<?= $baseUrl ?>/vendor/wallet" class="merchant-nav-item <?= ($active_tab ?? '') === 'wallet' ? 'active' : '' ?>">
            <i class="bi bi-wallet2"></i>
            <span>Dompet</span>
        </a>
        <a href="<?= $baseUrl ?>/vendor/profile" class="merchant-nav-item <?= ($active_tab ?? '') === 'profile' ? 'active' : '' ?>">
            <i class="bi bi-person-gear"></i>
            <span>Toko</span>
        </a>
    </nav>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $baseUrl ?>/assets/js/pwa-install.js"></script>

<script>
// Web Audio API Order Chime Generator
let soundEnabled = true;
let lastKnownOrderCount = <?= $pendingOrdersCount ?>;

function playOrderChime() {
    if (!soundEnabled) return;
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();

        const osc1 = ctx.createOscillator();
        const gain1 = ctx.createGain();
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
        osc1.frequency.setValueAtTime(880, ctx.currentTime + 0.15); // A5
        gain1.gain.setValueAtTime(0.3, ctx.currentTime);
        gain1.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.6);

        osc1.connect(gain1);
        gain1.connect(ctx.destination);
        osc1.start(ctx.currentTime);
        osc1.stop(ctx.currentTime + 0.6);
    } catch(e) {
        console.warn('Audio chime err:', e);
    }
}

function toggleAudioChime() {
    soundEnabled = !soundEnabled;
    const icon = document.getElementById('iconSoundToggle');
    if (icon) {
        if (soundEnabled) {
            icon.className = 'bi bi-volume-up-fill text-warning fs-6';
            playOrderChime();
            Swal.fire({
                title: 'Suara Notifikasi Aktif',
                text: 'Bunyi lonceng akan berdering saat ada pesanan baru masuk.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            });
        } else {
            icon.className = 'bi bi-volume-mute-fill text-muted fs-6';
            Swal.fire({
                title: 'Suara Dinonaktifkan',
                text: 'Notifikasi suara pesanan dimatikan.',
                icon: 'info',
                timer: 1500,
                showConfirmButton: false
            });
        }
    }
}

// Background Order Polling
<?php if ($store): ?>
setInterval(async () => {
    try {
        const res = await fetch(window.BASE_URL + '/vendor/orders/check-new');
        if (!res.ok) return;
        const result = await res.json();
        if (result.success && result.data) {
            const currentPending = parseInt(result.data.pending_count || 0);
            const navBadge = document.getElementById('merchantNavBadge');
            
            if (currentPending > 0) {
                if (navBadge) {
                    navBadge.textContent = currentPending;
                    navBadge.classList.remove('d-none');
                }
            } else if (navBadge) {
                navBadge.classList.add('d-none');
            }

            if (currentPending > lastKnownOrderCount) {
                playOrderChime();
                Swal.fire({
                    title: '🔔 Pesanan Baru Masuk!',
                    text: 'Ada ' + (currentPending - lastKnownOrderCount) + ' pesanan baru yang siap diproses.',
                    icon: 'info',
                    confirmButtonText: 'Buka Pesanan',
                    confirmButtonColor: '#EE2737'
                }).then((r) => {
                    if (r.isConfirmed) {
                        window.location.href = window.BASE_URL + '/vendor/orders';
                    }
                });
            }
            lastKnownOrderCount = currentPending;
        }
    } catch(e){}
}, 8000);
<?php endif; ?>
</script>

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
