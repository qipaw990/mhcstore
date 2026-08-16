<!-- CicalengkaGO Universal App Preloader -->
<div id="cicago-preloader" class="cicago-preloader-overlay">
    <div class="cicago-preloader-content text-center">
        <!-- Animated Glowing Ring & Logo -->
        <div class="preloader-spinner-wrapper mb-3">
            <div class="preloader-spin-ring"></div>
            <div class="preloader-inner-circle shadow-sm">
                <i class="bi bi-rocket-takeoff-fill text-danger fs-3 preloader-icon"></i>
            </div>
        </div>

        <!-- Brand Title & Tagline -->
        <h6 class="fw-extrabold m-0 text-dark preloader-brand-text" style="font-size: 17px; letter-spacing: -0.5px;">
            Cicalengka<span style="color: #EE2737;">GO</span>
        </h6>
        <div class="preloader-subtext text-muted" id="preloader-message" style="font-size: 11.5px; margin-top: 2px;">
            Memuat aplikasi...
        </div>

        <!-- Animated Progress Line -->
        <div class="preloader-progress-track mt-3 mx-auto">
            <div class="preloader-progress-bar"></div>
        </div>
    </div>
</div>

<style>
/* Preloader Fullscreen Styles */
.cicago-preloader-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(255, 255, 255, 0.96);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    z-index: 9999999;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 1;
    visibility: visible;
    transition: opacity 0.35s ease, visibility 0.35s ease;
    user-select: none;
    touch-action: none;
}

.cicago-preloader-overlay.fade-out {
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
}

.cicago-preloader-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    animation: preloaderContentPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes preloaderContentPop {
    0% { transform: scale(0.9); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

.preloader-spinner-wrapper {
    position: relative;
    width: 72px;
    height: 72px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.preloader-spin-ring {
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    border: 3px solid rgba(238, 39, 55, 0.12);
    border-top-color: #EE2737;
    border-right-color: #0F172A;
    animation: preloaderSpin 0.9s cubic-bezier(0.5, 0, 0.5, 1) infinite;
}

@keyframes preloaderSpin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.preloader-inner-circle {
    width: 52px;
    height: 52px;
    background: #ffffff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #f1f5f9;
}

.preloader-icon {
    animation: preloaderPulse 1.4s ease-in-out infinite alternate;
}

@keyframes preloaderPulse {
    0% { transform: scale(0.92); opacity: 0.85; }
    100% { transform: scale(1.08); opacity: 1; }
}

.preloader-progress-track {
    width: 120px;
    height: 3.5px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
    position: relative;
}

.preloader-progress-bar {
    width: 45%;
    height: 100%;
    background: linear-gradient(90deg, #0F172A, #EE2737);
    border-radius: 999px;
    position: absolute;
    animation: preloaderTrackSlide 1.2s infinite ease-in-out;
}

@keyframes preloaderTrackSlide {
    0% { left: -50%; width: 30%; }
    50% { left: 30%; width: 55%; }
    100% { left: 100%; width: 30%; }
}
</style>

<script>
(function() {
    const preloader = document.getElementById('cicago-preloader');
    const msgEl = document.getElementById('preloader-message');

    window.hidePreloader = function() {
        if (!preloader) return;
        preloader.classList.add('fade-out');
        setTimeout(() => {
            preloader.style.display = 'none';
        }, 400);
    };

    window.showPreloader = function(msg) {
        if (!preloader) return;
        if (msg && msgEl) msgEl.textContent = msg;
        preloader.style.display = 'flex';
        // Force reflow
        void preloader.offsetWidth;
        preloader.classList.remove('fade-out');
    };

    // Auto hide on window load
    if (document.readyState === 'complete') {
        setTimeout(window.hidePreloader, 150);
    } else {
        window.addEventListener('load', function() {
            setTimeout(window.hidePreloader, 200);
        });
    }

    // Safety fallback: auto-hide after 3.5 seconds if slow network assets
    setTimeout(window.hidePreloader, 3500);

    // Auto-show preloader on internal navigation clicks
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        if (!link) return;

        const href = link.getAttribute('href');
        const target = link.getAttribute('target');
        const isDownload = link.hasAttribute('download');

        if (!href || href.startsWith('#') || href.startsWith('javascript:') || 
            href.startsWith('tel:') || href.startsWith('mailto:') || 
            href.includes('wa.me') || href.includes('api.whatsapp.com') ||
            target === '_blank' || isDownload || e.ctrlKey || e.metaKey) {
            return;
        }

        // Show preloader for page transition
        window.showPreloader('Memuat halaman...');
    });

    // Auto-show preloader on form submissions (excluding AJAX, chat, or no-preloader forms)
    document.addEventListener('submit', function(e) {
        if (e.defaultPrevented) return;
        const form = e.target;
        if (!form || !form.tagName || form.tagName.toLowerCase() !== 'form') return;
        if (form.classList.contains('no-preloader') || 
            form.classList.contains('ccg-chat-input-bar') ||
            form.closest('.ccg-chat-modal') ||
            form.closest('.modal') ||
            form.getAttribute('data-ajax') === 'true') {
            return;
        }
        window.showPreloader('Memproses data...');
    });
})();
</script>
