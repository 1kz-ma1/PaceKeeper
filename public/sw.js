const CACHE_VERSION = 'pacekeeper-shell-v2';
const STATIC_ASSETS = [
    '/offline.html',
    '/manifest.webmanifest',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE_VERSION).then((cache) => cache.addAll(STATIC_ASSETS)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.filter((key) => key.startsWith('pacekeeper-shell-') && key !== CACHE_VERSION)
                .map((key) => caches.delete(key))
        ))
    );
    self.clients.claim();
});

function timeoutAfter(ms) {
    return new Promise((_, reject) => setTimeout(() => reject(new Error('network-timeout')), ms));
}

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) return;

    if (request.mode === 'navigate') {
        const networkRequest = fetch(request, { cache: 'no-store' });
        event.waitUntil(networkRequest.then(() => undefined).catch(() => undefined));
        event.respondWith(
            Promise.race([networkRequest, timeoutAfter(1200)])
                .catch(() => caches.match('/offline.html'))
        );
        return;
    }

    if (STATIC_ASSETS.includes(url.pathname)) {
        event.respondWith(caches.match(request).then((cached) => cached || fetch(request)));
    }
});
