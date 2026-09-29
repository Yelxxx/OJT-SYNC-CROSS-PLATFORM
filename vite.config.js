import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/registerSW.js',
            ],
            refresh: true,
        }),
        VitePWA({
            // injectManifest = bundle our custom service worker
            // (resources/js/sw.js) instead of generating one. srcDir/filename
            // tell the plugin where the SW source lives (v1.3.0 resolves it
            // from these, not from injectManifest.swSrc); the bundle lands in
            // public/build/sw.js because laravel-vite-plugin sets outDir.
            strategies: 'injectManifest',
            srcDir: 'resources/js',
            filename: 'sw.js',
            registerType: 'autoUpdate',
            manifest: false, // Custom Blade-served manifest via /manifest.webmanifest route
            injectManifest: {
                // Builds self.__WB_MANIFEST: the offline fallback page (static,
                // in public/) + hashed assets + the Vite manifest the Blade
                // layout needs offline.
                globDirectory: 'public',
                globPatterns: [
                    'offline.html',
                    'build/manifest.json',
                    'build/assets/*.js',
                    'build/assets/*.css',
                ],
                maximumFileSizeToCacheInBytes: 4 * 1024 * 1024,
            },
            devOptions: { enabled: false },
        }),
    ],
});
