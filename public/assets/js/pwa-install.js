/**
 * PWA Registration & Install Prompt Handler
 */

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    // Use absolute path so it works on any sub-path like /orders/CODE/tracking
    const swPath = (window.BASE_URL || '') + '/service-worker.js';
    navigator.serviceWorker.register(swPath, { scope: '/' })
      .then((reg) => {
        console.log('[PWA] Service Worker registered with scope:', reg.scope);
      })
      .catch((err) => {
        console.warn('[PWA] Service Worker registration failed:', err);
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
