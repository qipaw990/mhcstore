/**
 * CicalengkaGO Service Worker
 * Strategy: Network-First for local files → no hard reload needed after deploy
 * CDN: Stale-While-Revalidate → fast + auto-refresh in background
 */

// Version auto-bumps on each server deploy via git commit hash or timestamp
const SW_VERSION = '2.1.0';
const CACHE_LOCAL = 'cicago-local-v' + SW_VERSION;
const CACHE_CDN   = 'cicago-cdn-v1'; // CDN cache is shared across versions

// Only these CDN assets are cached (third-party, rarely change)
const CDN_ASSETS = [
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
];

// ─── Install: pre-cache CDN only, skip waiting immediately ───────────────────
self.addEventListener('install', (event) => {
    console.log('[SW] Installing v' + SW_VERSION);
    event.waitUntil(
        caches.open(CACHE_CDN).then((cache) => {
            return cache.addAll(CDN_ASSETS).catch((err) => {
                console.warn('[SW] CDN pre-cache partial error:', err);
            });
        })
    );
    // Activate immediately — no need to wait for old SW to die
    self.skipWaiting();
});

// ─── Activate: delete ALL old local caches, take control of all tabs ─────────
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating v' + SW_VERSION + ' — clearing old caches');
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    // Delete old local caches but keep CDN cache
                    if (key.startsWith('cicago-local-') && key !== CACHE_LOCAL) {
                        console.log('[SW] Deleting old cache:', key);
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim()) // Take control of all open tabs NOW
    );
});

// ─── Fetch: smart caching by resource type ────────────────────────────────────
self.addEventListener('fetch', (event) => {
    const req = event.request;
    const url = new URL(req.url);

    // Skip non-GET and sensitive paths entirely (no caching)
    if (
        req.method !== 'GET' ||
        url.pathname.startsWith('/admin') ||
        url.pathname.startsWith('/vendor') ||
        url.pathname.startsWith('/delivery') ||
        url.pathname.startsWith('/chats') ||
        url.pathname.startsWith('/orders/') && url.pathname.includes('live') ||
        url.pathname.startsWith('/cart') ||
        url.pathname.startsWith('/checkout') ||
        url.pathname.includes('/live-') ||
        url.pathname.includes('/live-dashboard') ||
        url.pathname.includes('/live-tracking') ||
        url.pathname.includes('/toggle-') ||
        url.pathname.includes('/accept-') ||
        url.pathname.includes('/update-')
    ) {
        return; // Let browser handle normally
    }

    const isCDN    = url.hostname !== self.location.hostname;
    const isLocal  = !isCDN;
    const isNav    = req.mode === 'navigate';
    const isAsset  = /\.(js|css|woff2?|ttf|svg|png|jpg|jpeg|gif|ico|webp)(\?.*)?$/.test(url.pathname);

    // ── CDN assets: Stale-While-Revalidate (serve cache, update in background)
    if (isCDN) {
        event.respondWith(
            caches.open(CACHE_CDN).then(async (cache) => {
                const cached = await cache.match(req);
                const fetchPromise = fetch(req).then((res) => {
                    if (res && res.status === 200) cache.put(req, res.clone());
                    return res;
                }).catch(() => cached);
                return cached || fetchPromise;
            })
        );
        return;
    }

    // ── Local JS/CSS assets: Network-First with cache fallback
    //    This means changes are ALWAYS visible immediately without hard reload
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
                return cached || new Response('', { status: 503 });
            })
        );
        return;
    }

    // ── HTML pages: Network-First with offline fallback
    if (isNav || !isAsset) {
        event.respondWith(
            fetch(req).catch(async () => {
                const cached = await caches.match(req);
                return cached || caches.match('offline.html');
            })
        );
        return;
    }
});

// ─── Listen for manual update trigger from app ───────────────────────────────
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
