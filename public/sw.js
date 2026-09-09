const CACHE_VERSION = 'pacekeeper-shell-v1';
const STATIC_ASSETS = ['/manifest.webmanifest', '/icons/icon-192.png', '/icons/icon-512.png'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE_VERSION).then((cache) => cache.addAll(STATIC_ASSETS)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== CACHE_VERSION).map((key) => caches.delete(key))))
    );
    self.clients.claim();
});

// PaceKeeper pages contain personal, cookie-scoped plan data. Do not cache HTML/API
// responses here. The service worker only supplies installability and static app icons.
