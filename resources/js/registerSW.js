/**
 * PWA service-worker registration, gated on the PWA_ENABLED flag.
 *
 * The layout renders <meta name="pwa-enabled" content="true|false">; when the
 * flag is off nothing runs, so the service worker ships dark until QA passes
 * (CROSS_PLATFORM_PLAN.md, Phase 0). Only register on https or localhost —
 * service workers are insecure-context no-ops anyway, but this avoids noisy
 * console errors during plain-http local testing.
 */
const swSupported = 'serviceWorker' in navigator;
const pwaEnabled = document.querySelector('meta[name="pwa-enabled"]')?.content === 'true';
const secureContext = window.isSecureContext || location.hostname === 'localhost' || location.hostname === '127.0.0.1';

if (swSupported && pwaEnabled && secureContext) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/build/sw.js', { scope: '/' }).catch((error) => {
            console.warn('[PWA] service worker registration failed:', error);
        });
    });
}
