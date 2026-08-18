/**
 * CicalengkaGO Service Worker v2.4.1
 * Resilient Network-First Strategy
 * Third-party CDN: Stale-While-Revalidate
 * Dynamic Pages / Payments / API: Pure Network Bypass (Never Intercept)
 */

const SW_VERSION = '2.4.3';
const CACHE_LOCAL = 'cicago-local-v' + SW_VERSION;
const CACHE_CDN   = 'cicago-cdn-v2';

// CDN assets to pre-cache
const CDN_ASSETS = [
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
];

// ─── Install: Pre-cache CDN assets & take over immediately ───────────────────
self.addEventListener('install', (event) => {
    console.log('[SW] Installing v' + SW_VERSION);
    event.waitUntil(
        caches.open(CACHE_CDN).then((cache) => {
            return cache.addAll(CDN_ASSETS).catch((err) => {
                console.warn('[SW] CDN pre-cache partial warning:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// ─── Activate: Clean up ALL old local caches ─────────────────────────────────
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating v' + SW_VERSION + ' — flushing old caches');
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key.startsWith('cicago-local-') && key !== CACHE_LOCAL) {
                        console.log('[SW] Deleting obsolete cache:', key);
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// ─── Fetch Handler ────────────────────────────────────────────────────────────
self.addEventListener('fetch', (event) => {
    const req = event.request;
    
    // Only handle GET requests
    if (req.method !== 'GET') {
        return;
    }

    const url = new URL(req.url);

    // 1. Bypass Service Worker entirely for payment, wallet, dynamic states & auth
    const isPaymentOrDynamic = 
        url.pathname.startsWith('/wallet') ||
        url.pathname.startsWith('/payment') ||
        url.pathname.startsWith('/orders') ||
        url.pathname.startsWith('/admin') ||
        url.pathname.startsWith('/vendor') ||
        url.pathname.startsWith('/delivery') ||
        url.pathname.startsWith('/chats') ||
        url.pathname.startsWith('/cart') ||
        url.pathname.startsWith('/checkout') ||
        url.pathname.startsWith('/login') ||
        url.pathname.startsWith('/register') ||
        url.pathname.startsWith('/logout') ||
        url.search.includes('order_id') ||
        url.search.includes('transaction_status') ||
        url.search.includes('status_code') ||
        url.search.includes('token') ||
        url.pathname.includes('/live-') ||
        url.pathname.includes('/toggle-') ||
        url.pathname.includes('/accept-') ||
        url.pathname.includes('/update-');

    if (isPaymentOrDynamic) {
        return; // Standard browser network fetch
    }

    const isCDN   = url.hostname !== self.location.hostname;
    const isLocal = !isCDN;
    const isAsset = /\.(js|css|woff2?|ttf|svg|png|jpg|jpeg|gif|ico|webp)(\?.*)?$/i.test(url.pathname);
    const isNav   = req.mode === 'navigate';

    // 2. CDN Assets (Stale-While-Revalidate)
    if (isCDN) {
        event.respondWith(
            caches.open(CACHE_CDN).then(async (cache) => {
                const cached = await cache.match(req);
                const networkFetch = fetch(req).then((res) => {
                    if (res && res.status === 200) {
                        cache.put(req, res.clone());
                    }
                    return res;
                }).catch(() => cached);

                return cached || networkFetch;
            })
        );
        return;
    }

    // 3. Local Static Assets (Network-First with Local Cache Fallback)
    if (isAsset && isLocal) {
        event.respondWith(
            fetch(req).then((res) => {
                if (res && res.status === 200) {
                    const clone = res.clone();
                    caches.open(CACHE_LOCAL).then((cache) => cache.put(req, clone));
                }
                return res;
            }).catch(async () => {
                const cached = await caches.match(req);
                return cached || new Response('', { status: 503, statusText: 'Service Unavailable' });
            })
        );
        return;
    }

    // 4. Navigation (HTML Pages) — Network-First with Safe Fallback
    if (isNav) {
        event.respondWith(
            fetch(req).catch(async () => {
                const cached = await caches.match(req);
                if (cached) return cached;

                const offlinePage = await caches.match('offline.html');
                if (offlinePage) return offlinePage;

                return new Response(
                    '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Offline</title></head><body style="font-family:sans-serif;text-align:center;padding:40px;"><h3>Anda sedang offline</h3><p>Periksa koneksi internet Anda.</p><button onclick="location.reload()">Muat Ulang</button></body></html>',
                    { headers: { 'Content-Type': 'text/html; charset=utf-8' }, status: 503 }
                );
            })
        );
    }
});

// ─── Message Listener ────────────────────────────────────────────────────────
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
