import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { VitePWA } from 'vite-plugin-pwa';
import fs from 'node:fs';
import path from 'node:path';

/**
 * Copies the zxing-wasm binary into public/ after each build so the QR
 * scanner's fallback decoder is served from our own origin instead of a
 * public CDN at scan time (CROSS_PLATFORM_PLAN.md, Phase 3). Only fetched
 * by browsers without the native BarcodeDetector API. The wasm is committed
 * with public/ (like public/build) because Render has no Node build env.
 */
function copyZxingWasm() {
    return {
        name: 'copy-zxing-wasm',
        closeBundle() {
            const src = path.join('node_modules', 'zxing-wasm', 'dist', 'reader', 'zxing_reader.wasm');
            const destDir = path.join('public', 'vendor', 'zxing-wasm');
            const dest = path.join(destDir, 'zxing_reader.wasm');

            if (! fs.existsSync(src)) {
                this.warn('copy-zxing-wasm: node_modules/zxing-wasm not found — run npm install.');
                return;
            }

            fs.mkdirSync(destDir, { recursive: true });
            fs.copyFileSync(src, dest);
        },
    };
}

export default defineConfig({
    plugins: [
        copyZxingWasm(),
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
