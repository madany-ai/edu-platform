// Minimal PWA Service Worker - NO chunk caching
// Only provides: installability + offline fallback page

const CACHE_NAME = 'pwa-shell-v1';
const OFFLINE_URL = '/offline';

// On install: cache only the offline fallback page
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll([OFFLINE_URL]);
    })
  );
  self.skipWaiting();
});

// On activate: clean up old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys
          .filter((key) => key !== CACHE_NAME)
          .map((key) => caches.delete(key))
      );
    })
  );
  self.clients.claim();
});

// Fetch: ONLY intercept navigation requests for offline fallback
// ALL other requests (JS, CSS, images, API) go directly to network - NO caching
self.addEventListener('fetch', (event) => {
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request).catch(() => {
        return caches.match(OFFLINE_URL);
      })
    );
  }
  // For everything else: do nothing, let the browser handle it normally
});
