// Bump this on any change to the caching strategy below so old clients
// pick up the new worker instead of running stale logic forever.
const CACHE_VERSION = 'jupyterdocs-v2';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const OFFLINE_URL = '/offline.html';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => cache.addAll([
            OFFLINE_URL,
            '/favicon.svg',
            '/icons/icon-192.png',
            '/icons/icon-512.png',
        ])).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.filter((key) => key.startsWith('jupyterdocs-') && key !== STATIC_CACHE)
                .map((key) => caches.delete(key))
        )).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET' || new URL(request.url).origin !== self.location.origin) {
        return;
    }

    // Page loads always go to the network. HTML is never cached: pages are
    // per-user (a cached logged-in page could be shown to the next person on
    // a shared device) and Safari rejects served redirected responses. Only
    // when the network is unreachable do we show the static offline page.
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    // Hashed build assets (Vite fingerprints every filename) and icons are
    // safe to cache indefinitely — a changed file always gets a new URL.
    const url = new URL(request.url);
    const isCacheable = url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/') || url.pathname === '/favicon.svg';

    if (!isCacheable) {
        return;
    }

    event.respondWith(
        caches.match(request).then((cached) => {
            if (cached) {
                return cached;
            }

            return fetch(request).then((response) => {
                const clone = response.clone();
                caches.open(STATIC_CACHE).then((cache) => cache.put(request, clone));
                return response;
            });
        })
    );
});
