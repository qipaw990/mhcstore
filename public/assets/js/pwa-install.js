/**
 * PWA Registration & Install Prompt Handler
 * Network-First SW: local assets always up-to-date without hard refresh
 */

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    // Use absolute path so it works on any sub-path like /orders/CODE/tracking
    const swPath = (window.BASE_URL || '') + '/service-worker.js';
    navigator.serviceWorker.register(swPath, { scope: '/' })
      .then((reg) => {
        console.log('[PWA] Service Worker registered:', reg.scope);

        // When a new SW is found, activate it immediately
        reg.addEventListener('updatefound', () => {
          const newWorker = reg.installing;
          if (!newWorker) return;

          newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
              console.log('[PWA] New version available — activating silently...');
              // Tell new SW to skip waiting and take over
              newWorker.postMessage({ type: 'SKIP_WAITING' });
            }
          });
        });
      })
      .catch((err) => {
        console.warn('[PWA] Service Worker registration failed:', err);
      });

    // When a new SW takes control, refresh page to load fresh assets
    let refreshing = false;
    navigator.serviceWorker.addEventListener('controllerchange', () => {
      if (refreshing) return;
      refreshing = true;
      console.log('[PWA] New SW active — reloading for fresh content...');
      window.location.reload();
    });
  });
}

let deferredPrompt;
window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredPrompt = e;
  
  const installBanner = document.getElementById('pwa-install-banner');
  if (installBanner && !localStorage.getItem('cicago_pwa_dismissed')) {
    installBanner.classList.remove('d-none');
  }
});

function triggerPwaInstall() {
  if (deferredPrompt) {
    deferredPrompt.prompt();
    deferredPrompt.userChoice.then((choiceResult) => {
      if (choiceResult.outcome === 'accepted') {
        console.log('User accepted the PWA install prompt');
      }
      deferredPrompt = null;
      const installBanner = document.getElementById('pwa-install-banner');
      if (installBanner) installBanner.classList.add('d-none');
    });
  }
}

function dismissPwaInstall() {
  localStorage.setItem('cicago_pwa_dismissed', 'true');
  const installBanner = document.getElementById('pwa-install-banner');
  if (installBanner) installBanner.classList.add('d-none');
}
