/**
 * OJT Sync service worker (vite-plugin-pwa injectManifest entry).
 *
 * Navigation requests use NetworkFirst with an offline fallback page — never
 * the precached welcome page, which would otherwise mask logged-out and
 * session-expired states. Authenticated pages go to the network so Laravel
 * redirects to /login when the session expired.
 */
import { clientsClaim } from 'workbox-core';
import { registerRoute } from 'workbox-routing';
import { PrecacheController } from 'workbox-precaching';
import { NetworkFirst, StaleWhileRevalidate, CacheFirst } from 'workbox-strategies';
import { CacheableResponsePlugin } from 'workbox-cacheable-response';
import { ExpirationPlugin } from 'workbox-expiration';
import { replayQueue } from './offline-sync.js';

const PRECACHE_URL = '/build/offline.html';

const precacheController = new PrecacheController();
precacheController.addToCacheList(self.__WB_MANIFEST || []);

self.skipWaiting();
clientsClaim();

self.addEventListener('install', (event) => {
    event.waitUntil(precacheController.install());
});

self.addEventListener('activate', (event) => {
    event.waitUntil(precacheController.activate());
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // Never intercept the service worker script itself.
    if (url.origin === self.location.origin && url.pathname === '/build/sw.js') {
        return;
    }

    // SPA-ish navigation: network first so logged-in/logged-out states stay
    // truthful; fall back to the precached offline page when unreachable.
    if (request.mode === 'navigate') {
        const strategy = new NetworkFirst({
            cacheName: 'html-cache',
            networkTimeoutSeconds: 5,
            plugins: [
                new CacheableResponsePlugin({ statuses: [200] }),
                new ExpirationPlugin({ maxEntries: 50, maxAgeSeconds: 60 * 60 * 24 }),
                {
                    handlerDidError: () => precacheController.matchPrecache(PRECACHE_URL),
                },
            ],
        });

        event.respondWith(strategy.handle({ request, event }));

        return;
    }

    // Resumes, QR images, company branding (plan: stale-tolerant CacheFirst).
    if (url.origin === self.location.origin && url.pathname.startsWith('/storage/')) {
        const strategy = new CacheFirst({
            cacheName: 'storage',
            plugins: [
                new CacheableResponsePlugin({ statuses: [0, 200] }),
                new ExpirationPlugin({ maxEntries: 100, maxAgeSeconds: 60 * 60 * 24 * 7 }),
            ],
        });

        event.respondWith(strategy.handle({ request, event }));

        return;
    }

    if (request.destination === 'image') {
        const strategy = new CacheFirst({
            cacheName: 'images',
            plugins: [
                new CacheableResponsePlugin({ statuses: [0, 200] }),
                new ExpirationPlugin({ maxEntries: 1000, maxAgeSeconds: 60 * 60 * 24 * 30 }),
            ],
        });

        event.respondWith(strategy.handle({ request, event }));

        return;
    }

    if (request.destination === 'font' || url.hostname === 'fonts.bunny.net' || url.hostname === 'fonts.gstatic.com') {
        const strategy = new CacheFirst({
            cacheName: 'fonts',
            plugins: [
                new CacheableResponsePlugin({ statuses: [0, 200] }),
                new ExpirationPlugin({ maxEntries: 100, maxAgeSeconds: 60 * 60 * 24 * 365 }),
            ],
        });

        event.respondWith(strategy.handle({ request, event }));

        return;
    }

    // Hashed Vite assets are immutable; revalidate in the background.
    if (request.destination === 'script' || request.destination === 'style') {
        const strategy = new StaleWhileRevalidate({
            cacheName: 'static-assets',
            plugins: [
                new CacheableResponsePlugin({ statuses: [0, 200] }),
                new ExpirationPlugin({ maxEntries: 100, maxAgeSeconds: 60 * 60 * 24 * 14 }),
            ],
        });

        event.respondWith(strategy.handle({ request, event }));
    }
});

// Background Sync: Chrome fires this when connectivity returns, even if the
// app was closed after the write was queued.
self.addEventListener('sync', (event) => {
    if (event.tag === 'ojt-offline-sync') {
        event.waitUntil(replayQueue());
    }
});

self.addEventListener('message', (event) => {
    if (! event.data) {
        return;
    }

    if (event.data.type === 'REPLAY_QUEUE') {
        event.waitUntil(replayQueue());
    }

    if (event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
