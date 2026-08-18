<?php
$appConfig = require APP_PATH . '/config/app.php';
$baseUrl = $appConfig['public_url'];
$user = auth_user();
$cartSummary = (new \App\Models\Cart())->getUserCart(auth_id(), session_id());
$unreadNotifs = $user ? (new \App\Models\Notification())->getUnreadCount($user['id']) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= $title ?? 'CicalengkaGO - Delivery Platform' ?></title>
    
    <!-- PWA Manifest & Theme -->
    <link rel="manifest" href="<?= $baseUrl ?>/manifest.json">
    <meta name="theme-color" content="#EE2737">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="CicalengkaGO">
    <link rel="apple-touch-icon" href="<?= $baseUrl ?>/assets/icons/icon-192.png">

    <!-- CSS Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/mobile.css?v=<?= time() ?>">

    <script>
        window.BASE_URL = "<?= $baseUrl ?>";
    </script>
</head>
<body>
<?php require_once dirname(__DIR__) . '/partials/preloader.php'; ?>

<div class="mobile-app-wrapper">
    <!-- PWA Install Banner -->
    <div id="pwa-install-banner" class="d-none bg-dark text-white px-3.5 py-2.5 d-flex align-items-center justify-content-between shadow-sm" style="min-height: 48px; border-bottom: 1px solid rgba(255,255,255,0.1);">
        <div class="d-flex align-items-center gap-2.5">
            <img src="<?= $baseUrl ?>/assets/images/logo-icon.svg" alt="CicalengkaGO" style="width: 28px; height: 28px; border-radius: 7px;">
            <span class="fw-semibold" style="font-size: 11.5px;">Pasang Aplikasi CicalengkaGO di HP</span>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <button onclick="triggerPwaInstall()" class="btn btn-danger btn-sm rounded-pill fw-bold px-3.5 py-1.5" style="background:#EE2737; font-size: 11.5px; border:none;">Install</button>
            <button onclick="dismissPwaInstall()" class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center p-0" style="width: 26px; height: 26px; font-size: 14px;"><i class="bi bi-x"></i></button>
        </div>
    </div>

    <!-- Gojek Super App Top Header (Only on Home Page) -->
    <?php if (($active_tab ?? '') === 'home'): ?>
    <header class="gojek-header">
        <div class="gojek-top-row d-flex align-items-center justify-content-between gap-1">
            <div class="d-flex align-items-center gap-1.5 min-w-0 flex-shrink-1">
                <a href="<?= $baseUrl ?>/" class="d-flex align-items-center gap-1.5 text-decoration-none flex-shrink-0">
                    <img src="<?= $baseUrl ?>/assets/images/logo-icon.svg" alt="CicalengkaGO" style="width: 28px; height: 28px; border-radius: 8px; box-shadow: 0 2px 5px rgba(238,39,55,0.25);">
                    <span class="fw-bold" style="font-size: 13.5px; color: var(--gojek-charcoal); letter-spacing: -0.3px;">Cicalengka<span style="color: #EE2737;">GO</span></span>
                </a>

                <div class="gojek-location-btn min-w-0 flex-shrink-1" onclick="if (typeof locateCustomerHomeGps === 'function') { locateCustomerHomeGps(); } else { window.location.href='<?= $baseUrl ?>/profile'; }" title="Pilih Lokasi">
                    <i class="bi bi-geo-alt-fill gojek-location-icon"></i>
                    <div class="gojek-location-text">
                        <span>Cicalengka</span>
                        <i class="bi bi-chevron-down ms-0.5" style="font-size: 8px;"></i>
                    </div>
                </div>
            </div>

            <div class="gojek-header-actions d-flex align-items-center gap-1.5 flex-shrink-0">
                <a href="<?= $baseUrl ?>/cart" class="gojek-icon-btn position-relative" title="Keranjang Belanja">
                    <i class="bi bi-cart3"></i>
                    <?php if (!empty($cartSummary['count']) && $cartSummary['count'] > 0): ?>
                        <span id="header-cart-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 7.5px; padding: 2px 3.5px; border: 1.5px solid #FFFFFF;">
                            <?= $cartSummary['count'] ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="<?= $baseUrl ?>/notifications" class="gojek-icon-btn position-relative" title="Notifikasi">
                    <i class="bi bi-bell"></i>
                    <?php if ($unreadNotifs > 0): ?>
                        <span class="badge-dot" style="background:#EE2737;"></span>
                    <?php endif; ?>
                </a>
                <?php if ($user): ?>
                    <a href="<?= $baseUrl ?>/profile" class="gojek-icon-btn p-0 overflow-hidden" title="Profil">
                        <img src="<?= $baseUrl ?>/<?= htmlspecialchars($user['avatar'] ?? 'assets/images/users/default.png') ?>" alt="User" style="width: 100%; height: 100%; object-fit: cover;">
                    </a>
                <?php else: ?>
                    <a href="<?= $baseUrl ?>/login" class="gojek-icon-btn" title="Masuk">
                        <i class="bi bi-person"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Gojek Search Box Bar -->
        <div class="gojek-search-box" onclick="window.location.href='<?= $baseUrl ?>/search'">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Cari makanan, belanjaan, atau resto..." readonly>
        </div>
    </header>
    <?php endif; ?>

    <!-- Main Dynamic Content Slot -->
    <main class="flex-grow-1">
        <?= $content ?>
    </main>

    <!-- CicalengkaGO Floating Cart Sticky Pill (When Cart is Not Empty and not on cart/checkout page) -->
    <?php 
    $currentUri = $_SERVER['REQUEST_URI'] ?? '';
    $isCartOrCheckout = (strpos($currentUri, '/cart') !== false || strpos($currentUri, '/checkout') !== false || ($active_tab ?? '') === 'cart' || ($active_tab ?? '') === 'checkout');
    ?>
    <?php if (!$isCartOrCheckout): ?>
    <a href="<?= $baseUrl ?>/cart" id="floating-cart-pill" class="floating-cart-pill <?= (empty($cartSummary['items']) || ($cartSummary['count'] ?? 0) <= 0) ? 'd-none' : '' ?>">
        <div class="floating-cart-left">
            <span id="floating-cart-count" class="cart-qty-badge"><i class="bi bi-bag-fill" style="font-size:9.5px;"></i> <span id="floating-cart-count-num"><?= $cartSummary['count'] ?? 0 ?></span> Menu</span>
            <span id="floating-cart-price" class="floating-cart-price"><?= format_rupiah($cartSummary['subtotal'] ?? 0) ?></span>
        </div>
        <div class="floating-cart-right">
            <span>Lihat Keranjang</span>
            <i class="bi bi-arrow-right-short"></i>
        </div>
    </a>
    <?php endif; ?>

    <!-- Gojek 5-Tab Mobile Bottom Navigation Bar -->
    <nav class="gojek-bottom-nav">
        <a href="<?= $baseUrl ?>" class="gojek-nav-item <?= ($active_tab ?? '') === 'home' ? 'active' : '' ?>">
            <i class="bi bi-house-door<?= ($active_tab ?? '') === 'home' ? '-fill' : '' ?>"></i>
            <span>Beranda</span>
        </a>
        <a href="<?= $baseUrl ?>/search" class="gojek-nav-item <?= in_array(($active_tab ?? ''), ['promos', 'search']) ? 'active' : '' ?>">
            <i class="bi bi-percent"></i>
            <span>Promo</span>
        </a>
        <a href="<?= $baseUrl ?>/orders" class="gojek-nav-item <?= ($active_tab ?? '') === 'orders' ? 'active' : '' ?>">
            <i class="bi bi-receipt"></i>
            <span>Pesanan</span>
        </a>
        <a href="<?= $baseUrl ?>/notifications" class="gojek-nav-item <?= ($active_tab ?? '') === 'chat' ? 'active' : '' ?>">
            <i class="bi bi-chat-dots<?= ($active_tab ?? '') === 'chat' ? '-fill' : '' ?>"></i>
            <span>Chat</span>
        </a>
        <a href="<?= $baseUrl ?>/profile" class="gojek-nav-item <?= ($active_tab ?? '') === 'profile' ? 'active' : '' ?>">
            <i class="bi bi-person<?= ($active_tab ?? '') === 'profile' ? '-fill' : '' ?>"></i>
            <span>Akun</span>
        </a>
    </nav>
</div>

<!-- Location Permission Modal (BottomSheet / Center Card) -->
<div class="modal fade" id="locationPermissionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px; margin: 1.5rem auto;">
        <div class="modal-content border-0 shadow-lg rounded-5 overflow-hidden text-center p-4" id="location-modal-content">
            <!-- Modal Body injected via JS or default template -->
            <div id="location-modal-body">
                <div class="position-relative d-inline-block mx-auto mb-3 mt-2">
                    <div class="location-pulse-ring"></div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white shadow-lg" style="width: 72px; height: 72px; background: linear-gradient(135deg, #EE2737, #C61524); position: relative; z-index: 2;">
                        <i class="bi bi-geo-alt-fill" style="font-size: 34px;"></i>
                    </div>
                </div>

                <h5 class="fw-extrabold text-dark mb-2" style="letter-spacing: -0.4px;">Aktifkan Lokasi Anda 📍</h5>
                <p class="text-muted small px-2 mb-3" style="font-size: 13px; line-height: 1.55;">
                    Aktifkan GPS agar CicalengkaGO dapat menampilkan rekomendasi resto terdekat, estimasi pengantaran presisi, dan ongkir akurat!
                </p>

                <div class="p-2.5 bg-light rounded-3 text-start mb-4 border" style="font-size: 11.5px; color: var(--gojek-gray);">
                    <i class="bi bi-shield-lock-fill text-danger me-1 fs-6"></i>
                    <b>Privasi Terjamin:</b> Lokasi Anda hanya digunakan untuk menentukan posisi pengantaran dan toko terdekat di Cicalengka.
                </div>

                <div class="d-flex flex-column gap-2">
                    <button type="button" onclick="requestCustomerGpsLocation()" class="btn text-white rounded-pill py-3 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" style="background: #EE2737; font-size: 14px;" id="btn-request-location">
                        <i class="bi bi-crosshair fs-5"></i> Izinkan & Deteksi Lokasi (GPS)
                    </button>
                    <a href="<?= $baseUrl ?>/profile" class="btn btn-outline-secondary rounded-pill py-2.5 fw-semibold small">
                        <i class="bi bi-house-door me-1"></i> Pilih Alamat Pengantaran Manual
                    </a>
                    <button type="button" onclick="dismissLocationPrompt()" class="btn btn-link text-muted fw-semibold text-decoration-none small py-1" style="font-size: 12px;">
                        Nanti Saja
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes locationPulse {
    0% {
        transform: scale(0.95);
        box-shadow: 0 0 0 0 rgba(238, 39, 55, 0.7);
    }
    70% {
        transform: scale(1.3);
        box-shadow: 0 0 0 22px rgba(238, 39, 55, 0);
    }
    100% {
        transform: scale(0.95);
        box-shadow: 0 0 0 0 rgba(238, 39, 55, 0);
    }
}
.location-pulse-ring {
    position: absolute;
    top: 0;
    left: 0;
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: rgba(238, 39, 55, 0.25);
    animation: locationPulse 2s infinite;
}
</style>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Native Mobile App Bottom Sheet Alert & Toast -->
<div id="mobile-app-sheet-backdrop" class="mobile-app-sheet-backdrop" onclick="AppAlert.closeSheet()"></div>
<div id="mobile-app-sheet" class="mobile-app-sheet">
    <div class="mobile-sheet-drag-handle"></div>
    <div class="mobile-sheet-icon-box info" id="mobile-sheet-icon-box">
        <i class="bi bi-info-circle-fill" id="mobile-sheet-icon"></i>
    </div>
    <h5 class="mobile-sheet-title" id="mobile-sheet-title">Informasi</h5>
    <p class="mobile-sheet-message" id="mobile-sheet-message"></p>
    <div class="mobile-sheet-actions">
        <button type="button" class="btn-mobile-sheet-primary" id="mobile-sheet-btn-confirm" onclick="AppAlert.closeSheet()">Mengerti</button>
        <button type="button" class="btn-mobile-sheet-secondary" id="mobile-sheet-btn-cancel" onclick="AppAlert.cancelSheet()" style="display:none;">Batal</button>
    </div>
</div>
<div id="mobile-app-toast" class="mobile-app-toast">
    <i class="bi bi-check-circle-fill" id="mobile-toast-icon" style="font-size:13px;color:#22C55E;"></i>
    <span id="mobile-toast-message">Berhasil</span>
</div>

<script>
/* AppAlert - Native Mobile Bottom Sheet & Toast for CicalengkaGO */
const AppAlert = {
    confirmCb: null,
    cancelCb: null,
    show: function(options) {
        var type    = options.type || options.icon || 'info';
        var title   = options.title || 'Informasi';
        var message = options.text  || options.message || '';
        var confirmText = options.confirmButtonText || 'Mengerti';
        var cancelText  = options.cancelButtonText  || 'Batal';
        var showCancel  = options.showCancelButton  || false;
        var iconBox    = document.getElementById('mobile-sheet-icon-box');
        var iconEl     = document.getElementById('mobile-sheet-icon');
        var titleEl    = document.getElementById('mobile-sheet-title');
        var msgEl      = document.getElementById('mobile-sheet-message');
        var btnConfirm = document.getElementById('mobile-sheet-btn-confirm');
        var btnCancel  = document.getElementById('mobile-sheet-btn-cancel');
        var backdrop   = document.getElementById('mobile-app-sheet-backdrop');
        var sheet      = document.getElementById('mobile-app-sheet');
        if (!sheet || !backdrop) { return; }
        var safeType = type === 'error' ? 'danger' : type;
        iconBox.className = 'mobile-sheet-icon-box ' + safeType;
        var iconMap = { success:'bi-check-circle-fill', warning:'bi-exclamation-triangle-fill', danger:'bi-x-circle-fill', error:'bi-x-circle-fill', info:'bi-info-circle-fill' };
        iconEl.className = 'bi ' + (iconMap[safeType] || iconMap[type] || 'bi-info-circle-fill');
        titleEl.textContent   = title;
        msgEl.textContent     = message;
        btnConfirm.textContent = confirmText;
        btnCancel.textContent  = cancelText;
        btnCancel.style.display = showCancel ? 'block' : 'none';
        this.confirmCb = options.onConfirm || null;
        this.cancelCb  = options.onCancel  || null;
        backdrop.classList.add('active');
        sheet.classList.add('active');
    },
    confirm: function(title, message, onConfirm, confirmText) {
        this.show({ type:'warning', title:title, message:message, showCancelButton:true, confirmButtonText:confirmText||'Ya, Lanjutkan', cancelButtonText:'Batal', onConfirm:onConfirm });
    },
    toast: function(message, type) {
        var toastEl = document.getElementById('mobile-app-toast');
        var iconEl  = document.getElementById('mobile-toast-icon');
        var msgEl   = document.getElementById('mobile-toast-message');
        if (!toastEl) return;
        type = type || 'success';
        var colors  = { success:'#22C55E', error:'#EE2737', danger:'#EE2737', warning:'#F59E0B', info:'#3B82F6' };
        var iconMap = { success:'bi-check-circle-fill', error:'bi-x-circle-fill', danger:'bi-x-circle-fill', warning:'bi-exclamation-triangle-fill', info:'bi-info-circle-fill' };
        iconEl.className  = 'bi ' + (iconMap[type] || 'bi-check-circle-fill');
        iconEl.style.color = colors[type] || '#22C55E';
        msgEl.textContent  = message;
        toastEl.classList.add('active');
        setTimeout(function() { toastEl.classList.remove('active'); }, 2800);
    },
    closeSheet: function() {
        document.getElementById('mobile-app-sheet-backdrop').classList.remove('active');
        document.getElementById('mobile-app-sheet').classList.remove('active');
        if (this.confirmCb) { var cb = this.confirmCb; this.confirmCb = null; cb(); }
    },
    cancelSheet: function() {
        document.getElementById('mobile-app-sheet-backdrop').classList.remove('active');
        document.getElementById('mobile-app-sheet').classList.remove('active');
        if (this.cancelCb)  { var cb = this.cancelCb;  this.cancelCb  = null; cb(); }
    }
};

/* Override window.Swal -> AppAlert (backwards compat for all existing Swal.fire calls) */
window.Swal = {
    fire: function(a1, a2, a3) {
        return new Promise(function(resolve) {
            var opts = (typeof a1 === 'object') ? a1 : { title: a1||'Informasi', text: a2||'', type: a3||'info' };
            opts.onConfirm = function() { resolve({ isConfirmed:true, value:true }); };
            opts.onCancel  = function() { resolve({ isConfirmed:false, isDismissed:true }); };
            AppAlert.show(opts);
        });
    },
    showLoading:            function() {},
    hideLoading:            function() {},
    showValidationMessage:  function(msg) { console.warn(msg); },
    close:                  function() { AppAlert.closeSheet(); }
};
</script>

<!-- Midtrans Snap JS -->
<?php
$midtransServiceInst = new \App\Services\MidtransService();
$resolvedSnapKey = $client_key ?? $midtransServiceInst->getClientKey() ?? 'Mid-client-fa_UX3R3BzD4wXXl';
$resolvedSnapUrl = $snap_url ?? $midtransServiceInst->getSnapUrl() ?? 'https://app.sandbox.midtrans.com/snap/snap.js';
?>
<script type="text/javascript" src="<?= $resolvedSnapUrl ?>" data-client-key="<?= htmlspecialchars($resolvedSnapKey) ?>"></script>
<script src="<?= $baseUrl ?>/assets/js/pwa-install.js"></script>
<script src="<?= $baseUrl ?>/assets/js/customer-pwa.js?v=<?= time() ?>"></script>

<?php if (!empty($_SESSION['success'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AppAlert.toast('<?= addslashes($_SESSION['success']) ?>', 'success');
        });
    </script>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AppAlert.show({ type:'error', title:'Perhatian', text:'<?= addslashes($_SESSION['error']) ?>' });
        });
    </script>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

</body>
</html>
