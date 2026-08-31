<?php
$appConfig = require APP_PATH . '/config/app.php';
$baseUrl = $appConfig['public_url'];
$user = auth_user();

// Dynamic Driver Rating
if (!isset($driverRating)) {
    if (isset($driver['rating'])) {
        $driverRating = (float)$driver['rating'];
    } elseif ($user) {
        $dmRecord = (new \App\Models\DeliveryMan())->findByUserId((int)$user['id']);
        if ($dmRecord) {
            (new \App\Models\Review())->recalculateDmRating((int)$dmRecord['id']);
            $dmRecord = (new \App\Models\DeliveryMan())->find($dmRecord['id']);
        }
        $driverRating = (float)($dmRecord['rating'] ?? 5.0);
    } else {
        $driverRating = 5.0;
    }
}
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
        window.CURRENT_USER_ID = <?= (int)(auth_id() ?? ($_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? 0))) ?>;
    </script>
</head>
<body style="background-color: #f1f5f9;">
<?php require_once dirname(__DIR__) . '/partials/preloader.php'; ?>

<div class="mobile-app-wrapper" style="background-color: #f8fafc;">
    <!-- Top Driver Header (CicalengkaGO Partner Bar) -->
    <header class="driver-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <div class="driver-avatar-box position-relative flex-shrink-0" style="width: 42px; height: 42px;">
                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($user['avatar'] ?? 'assets/images/users/driver.png') ?>" alt="Driver" class="driver-avatar-ring" style="width: 42px; height: 42px; max-width: 42px; max-height: 42px; min-width: 42px; min-height: 42px; object-fit: cover; border-radius: 50%;">
                <span class="position-absolute bottom-0 end-0 bg-success border border-2 border-white rounded-circle" style="width: 12px; height: 12px;"></span>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-white small"><?= htmlspecialchars($user['name'] ?? 'Mitra Driver') ?></span>
                    <span class="badge rounded-pill text-dark px-2" style="background: #F7A800; font-size: 10px; font-weight: 800;">
                        <i class="bi bi-star-fill me-0.5"></i> <span id="headerDriverRatingText"><?= number_format($driverRating, 1) ?></span>
                    </span>
                </div>
                <div class="d-flex align-items-center gap-1 mt-0.5" style="font-size: 11px; color: #94a3b8;">
                    <i class="bi bi-shield-check text-danger" style="font-size: 12px;"></i>
                    <span class="fw-medium text-slate-300">Mitra Kurir Cicalengka<span style="color: #EE2737;">GO</span></span>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button onclick="if(typeof centerDriverMap==='function'){centerDriverMap();}" class="btn btn-dark btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15);" title="Pusatkan GPS">
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

    <!-- ===== CCG Driver Toast Notification Stack ===== -->
    <div id="ccgDriverToastStack" style="
        position: fixed;
        top: 60px;
        left: 50%;
        transform: translateX(-50%);
        width: 280px;
        max-width: calc(100vw - 24px);
        z-index: 9999;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        pointer-events: none;
    "></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= $baseUrl ?>/assets/js/delivery-pwa.js?v=<?= time() ?>"></script>
<script src="<?= $baseUrl ?>/assets/js/mobile-call.js?v=<?= time() ?>"></script>

<script>
/* =====================================================
   CCG Driver Toast Notification System
   Usage: showDriverToast('Pesan sukses!', 'success')
          showDriverToast('Terjadi kesalahan', 'error')
          showDriverToast('Info penting', 'info')
          showDriverToast('Peringatan', 'warning')
   ===================================================== */
function showDriverToast(message, type = 'info', duration = 4000) {
    const stack = document.getElementById('ccgDriverToastStack');
    if (!stack) return;

    const configs = {
        success: {
            icon: 'bi-check-circle-fill',
            bg: 'linear-gradient(135deg, #10B981 0%, #059669 100%)',
            border: '#059669',
            shadow: 'rgba(16, 185, 129, 0.35)',
            label: 'Berhasil'
        },
        error: {
            icon: 'bi-x-circle-fill',
            bg: 'linear-gradient(135deg, #EE2737 0%, #DC2626 100%)',
            border: '#DC2626',
            shadow: 'rgba(238, 39, 55, 0.35)',
            label: 'Perhatian'
        },
        info: {
            icon: 'bi-info-circle-fill',
            bg: 'linear-gradient(135deg, #3B82F6 0%, #2563EB 100%)',
            border: '#2563EB',
            shadow: 'rgba(59, 130, 246, 0.35)',
            label: 'Info'
        },
        warning: {
            icon: 'bi-exclamation-triangle-fill',
            bg: 'linear-gradient(135deg, #F59E0B 0%, #D97706 100%)',
            border: '#D97706',
            shadow: 'rgba(245, 158, 11, 0.35)',
            label: 'Peringatan'
        }
    };

    const cfg = configs[type] || configs.info;
    const id = 'toast_' + Date.now();

    const toast = document.createElement('div');
    toast.id = id;
    toast.style.cssText = `
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 9px 10px;
        border-radius: 12px;
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.13);
        pointer-events: all;
        transform: translateY(-20px);
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.2s ease;
        opacity: 0;
        overflow: hidden;
        position: relative;
        width: 100%;
    `;

    toast.innerHTML = `
        <div style="
            width: 28px; height: 28px;
            border-radius: 8px;
            background: ${cfg.bg};
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 3px 8px ${cfg.shadow};
        ">
            <i class="bi ${cfg.icon}" style="color: #FFFFFF; font-size: 13px;"></i>
        </div>
        <div style="flex: 1; min-width: 0;">
            <div style="font-size: 10.5px; font-weight: 700; color: #0F172A; line-height: 1.2;">${cfg.label}</div>
            <div style="font-size: 10px; color: #64748B; line-height: 1.35; margin-top: 1px;">${message}</div>
        </div>
        <button onclick="dismissDriverToast('${id}')" style="
            background: none; border: none; padding: 0;
            width: 18px; height: 18px;
            display: flex; align-items: center; justify-content: center;
            color: #CBD5E1; cursor: pointer; flex-shrink: 0;
        ">
            <i class="bi bi-x" style="font-size: 14px;"></i>
        </button>
        <div style="
            position: absolute; bottom: 0; left: 0; height: 2px;
            border-radius: 0 0 12px 12px;
            background: ${cfg.bg};
            width: 100%;
            animation: ccgToastProgress ${duration}ms linear forwards;
        "></div>
    `;

    stack.appendChild(toast);

    // Trigger drop-down animation
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            toast.style.transform = 'translateY(0)';
            toast.style.opacity = '1';
        });
    });

    // Auto dismiss
    setTimeout(() => dismissDriverToast(id), duration);
}

function dismissDriverToast(id) {
    const toast = document.getElementById(id);
    if (!toast) return;
    toast.style.transform = 'translateY(-16px)';
    toast.style.opacity = '0';
    setTimeout(() => { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 250);
}
</script>

<style>
@keyframes ccgToastProgress {
    from { width: 100%; }
    to { width: 0%; }
}
</style>

<?php if (!empty($_SESSION['success'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showDriverToast('<?= addslashes($_SESSION['success']) ?>', 'success');
        });
    </script>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showDriverToast('<?= addslashes($_SESSION['error']) ?>', 'error', 6000);
        });
    </script>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['info'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showDriverToast('<?= addslashes($_SESSION['info']) ?>', 'info');
        });
    </script>
    <?php unset($_SESSION['info']); ?>
<?php endif; ?>

</body>
</html>
