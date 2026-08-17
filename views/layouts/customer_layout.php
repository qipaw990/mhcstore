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
    <a href="<?= $baseUrl ?>/cart" id="floating-cart-pill" class="floating-cart-pill <?= empty($cartSummary['items']) ? 'd-none' : '' ?>">
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<!-- Midtrans Snap JS -->
<?php
$midtransServiceInst = new \App\Services\MidtransService();
$resolvedSnapKey = $client_key ?? $midtransServiceInst->getClientKey() ?? 'Mid-client-fa_UX3R3BzD4wXXl';
$resolvedSnapUrl = $snap_url ?? $midtransServiceInst->getSnapUrl() ?? 'https://app.sandbox.midtrans.com/snap/snap.js';
?>
<script type="text/javascript" src="<?= $resolvedSnapUrl ?>" data-client-key="<?= htmlspecialchars($resolvedSnapKey) ?>"></script>
<script src="<?= $baseUrl ?>/assets/js/pwa-install.js"></script>
<script src="<?= $baseUrl ?>/assets/js/customer-pwa.js?v=<?= time() ?>"></script>


<!-- Native Mobile App Bottom Sheet Alert & Toast Components -->
<div id="mobile-app-sheet-backdrop" class="mobile-app-sheet-backdrop" onclick="AppAlert.closeSheet()"></div>
<div id="mobile-app-sheet" class="mobile-app-sheet">
    <div class="mobile-sheet-drag-handle"></div>
    <div class="mobile-sheet-icon-box info" id="mobile-sheet-icon-box">
        <i class="bi bi-info-circle-fill" id="mobile-sheet-icon"></i>
    </div>
    <h5 class="mobile-sheet-title" id="mobile-sheet-title">Judul Informasi</h5>
    <p class="mobile-sheet-message" id="mobile-sheet-message">Pesan penjelasan aplikasi tampil di sini.</p>
    <div class="mobile-sheet-actions" id="mobile-sheet-actions">
        <button type="button" class="btn-mobile-sheet-primary" id="mobile-sheet-btn-confirm" onclick="AppAlert.closeSheet()">Mengerti</button>
        <button type="button" class="btn-mobile-sheet-secondary" id="mobile-sheet-btn-cancel" onclick="AppAlert.cancelSheet()" style="display: none;">Batal</button>
    </div>
</div>

<div id="mobile-app-toast" class="mobile-app-toast">
    <i class="bi bi-check-circle-fill me-1 text-success" id="mobile-toast-icon"></i>
    <span id="mobile-toast-message">Pesan tersimpan</span>
</div>

<script>
const AppAlert = {
    confirmCb: null,
    cancelCb: null,

    show(options) {
        const type = options.type || options.icon || 'info';
        const title = options.title || 'Informasi';
        const message = options.text || options.message || '';
        const confirmText = options.confirmButtonText || 'Mengerti';
        const cancelText = options.cancelButtonText || 'Batal';
        const showCancel = options.showCancelButton || false;

        const iconBox = document.getElementById('mobile-sheet-icon-box');
        const icon = document.getElementById('mobile-sheet-icon');
        const titleEl = document.getElementById('mobile-sheet-title');
        const msgEl = document.getElementById('mobile-sheet-message');
        const btnConfirm = document.getElementById('mobile-sheet-btn-confirm');
        const btnCancel = document.getElementById('mobile-sheet-btn-cancel');

        if (!iconBox || !icon || !titleEl || !msgEl) return;

        const safeType = type === 'error' ? 'danger' : type;
        iconBox.className = 'mobile-sheet-icon-box ' + safeType;
        
        let iconClass = 'bi-info-circle-fill';
        if (type === 'success') iconClass = 'bi-check-circle-fill';
        if (type === 'warning') iconClass = 'bi-exclamation-triangle-fill';
        if (type === 'error' || type === 'danger') iconClass = 'bi-x-circle-fill';
        icon.className = 'bi ' + iconClass;

        titleEl.textContent = title;
        msgEl.textContent = message;
        btnConfirm.textContent = confirmText;
        btnCancel.textContent = cancelText;
        btnCancel.style.display = showCancel ? 'block' : 'none';

        this.confirmCb = options.onConfirm || options.confirmCallback || null;
        this.cancelCb = options.onCancel || options.cancelCallback || null;

        document.getElementById('mobile-app-sheet-backdrop').classList.add('active');
        document.getElementById('mobile-app-sheet').classList.add('active');
    },

    confirm(title, message, onConfirm, confirmText = 'Ya, Lanjutkan') {
        this.show({
            type: 'warning',
            title: title,
            message: message,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: 'Batal',
            onConfirm: onConfirm
        });
    },

    toast(message, type = 'success') {
        const toastEl = document.getElementById('mobile-app-toast');
        const iconEl = document.getElementById('mobile-toast-icon');
        const msgEl = document.getElementById('mobile-toast-message');

        if (!toastEl || !iconEl || !msgEl) return;

        let iconClass = 'bi-check-circle-fill text-success';
        if (type === 'danger' || type === 'error') iconClass = 'bi-x-circle-fill text-danger';
        if (type === 'warning') iconClass = 'bi-exclamation-triangle-fill text-warning';
        if (type === 'info') iconClass = 'bi-info-circle-fill text-info';

        iconEl.className = 'bi me-1 ' + iconClass;
        msgEl.textContent = message;

        toastEl.classList.add('active');
        setTimeout(() => {
            toastEl.classList.remove('active');
        }, 2600);
    },

    closeSheet() {
        const backdrop = document.getElementById('mobile-app-sheet-backdrop');
        const sheet = document.getElementById('mobile-app-sheet');
        if (backdrop) backdrop.classList.remove('active');
        if (sheet) sheet.classList.remove('active');
        if (this.confirmCb) {
            const cb = this.confirmCb;
            this.confirmCb = null;
            cb();
        }
    },

    cancelSheet() {
        const backdrop = document.getElementById('mobile-app-sheet-backdrop');
        const sheet = document.getElementById('mobile-app-sheet');
        if (backdrop) backdrop.classList.remove('active');
        if (sheet) sheet.classList.remove('active');
        if (this.cancelCb) {
            const cb = this.cancelCb;
            this.cancelCb = null;
            cb();
        }
    }
};

// Global Swal override to route all SweetAlert calls to AppAlert Mobile Bottom Sheet
window.Swal = {
    fire: function(arg1, arg2, arg3) {
        return new Promise((resolve) => {
            let opts = {};
            if (typeof arg1 === 'object') {
                opts = arg1;
            } else {
                opts = { title: arg1 || 'Informasi', text: arg2 || '', type: arg3 || 'info' };
            }
            
            opts.onConfirm = () => resolve({ isConfirmed: true, value: true });
            opts.onCancel = () => resolve({ isConfirmed: false, isDismissed: true });

            AppAlert.show(opts);
        });
    },
    toast: function(msg, type) {
        AppAlert.toast(msg, type);
    }
};
</script>

<?php if (!empty($_SESSION['success'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            AppAlert.toast('<?= addslashes($_SESSION['success']) ?>', 'success');
        });
    </script>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            AppAlert.show({
                type: 'error',
                title: 'Perhatian',
                text: '<?= addslashes($_SESSION['error']) ?>'
            });
        });
    </script>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

</body>
</html>

