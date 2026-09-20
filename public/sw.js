// Bump this on any change to the caching strategy below so old clients
// pick up the new worker instead of running stale logic forever.
const CACHE_VERSION = 'jupyterdocs-v1';
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

    // Page loads: always prefer the network (this is a marketplace with
    // live, per-user, per-document state — never serve a stale page when
    // we're online) and only fall back to a cached copy or the offline
    // page when the network is unreachable.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    const clone = response.clone();
                    caches.open(STATIC_CACHE).then((cache) => cache.put(request, clone));
                    return response;
                })
                .catch(() => caches.match(request).then((cached) => cached || caches.match(OFFLINE_URL)))
        );
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
