// Minimal online-only pass-through service worker for PWA installation criteria.
// No offline caching or intercepting is performed.
self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});
