/**
 * CicalengkaGO Service Worker
 * Version: 1.0.0
 */

const CACHE_NAME = 'cicago-v1.0.0';
const OFFLINE_URL = 'offline.html';

const STATIC_ASSETS = [
    './',
    'offline.html',
    'manifest.json',
    'assets/css/mobile.css',
    'assets/js/customer-pwa.js',
    'assets/js/pwa-install.js',
    'assets/icons/icon-192.png',
    'assets/icons/icon-512.png',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
    'https://cdn.jsdelivr.net/npm/sweetalert2@11'
];

// Install Event
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('[SW] Pre-caching static assets');
            return cache.addAll(STATIC_ASSETS).catch((err) => {
                console.warn('[SW] Pre-cache error:', err);
            });
        })
    );
    self.skipWaiting();
});

// Activate Event
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cache) => {
                    if (cache !== CACHE_NAME) {
                        console.log('[SW] Clearing old cache:', cache);
                        return caches.delete(cache);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Fetch Event
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Never cache API, auth, checkout, or sensitive POST requests
    if (
        request.method !== 'GET' ||
        url.pathname.includes('/api/') ||
        url.pathname.includes('/login') ||
        url.pathname.includes('/checkout') ||
        url.pathname.includes('/wallet') ||
        url.pathname.includes('/admin') ||
        url.pathname.includes('/vendor')
    ) {
        return;
    }

    // HTML Navigation requests: Network First -> Cache -> Offline Fallback
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((networkResponse) => {
                    return caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, networkResponse.clone());
                        return networkResponse;
                    });
                })
                .catch(() => {
                    return caches.match(request).then((cachedResponse) => {
                        return cachedResponse || caches.match(OFFLINE_URL);
                    });
                })
        );
        return;
    }

    // Static Assets (CSS, JS, Fonts, Images): Cache First -> Network fallback
    event.respondWith(
        caches.match(request).then((cachedResponse) => {
            if (cachedResponse) {
                return cachedResponse;
            }
            return fetch(request).then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200) {
                    const responseToCache = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, responseToCache);
                    });
                }
                return networkResponse;
            }).catch(() => {
                // Return nothing or empty response on static asset failure
            });
        })
    );
});
