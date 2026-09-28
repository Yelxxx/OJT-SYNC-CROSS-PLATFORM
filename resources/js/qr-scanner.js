/**
 * QR scanner component (CROSS_PLATFORM_PLAN.md, Phase 3).
 *
 * Prefers the BarcodeDetector API (Chrome on Android, zero extra bytes);
 * falls back to zxing-wasm (~1 MiB, dynamically imported only when needed —
 * browsers with BarcodeDetector never download it).
 *
 * Camera acquisition is defensive because real-world Android is messy:
 *  - constraint chain: ideal-facing rear camera first, then ANY camera;
 *  - NotReadableError (camera held by another app/tab) gets its own message;
 *  - in-app browsers (Messenger/Instagram/QR-scanner webviews) block camera
 *    with no prompt — we detect them and offer an escape hatch to Chrome;
 *  - the raw error name/message is always shown in small text so a failure
 *    on a user's phone is reportable (production has APP_DEBUG off).
 *
 * UX contract with the server (routes/api.php):
 *  - 201            → checked in, show success and redirect to the DTR page
 *  - 409 reused     → photographed QR; ask the student to rescan the live QR
 *  - 410 expired    → company's screen hasn't refreshed; rescan
 *  - 409 already_active (idempotent) → student is already timed in; treat as
 *    success and go to the DTR page
 *  - 403 wrong_company / no_company / inactive → banner, stay on scanner
 */

// The zxing fallback is loaded lazily (browsers with the native
// BarcodeDetector never download it). The wasm binary itself is self-hosted
// (copied to public/vendor/zxing-wasm by the vite build) instead of pulled
// from a public CDN at scan time — school and office networks sometimes
// block CDNs.
let wasmReaderPromise = null;

function loadWasmReader() {
    wasmReaderPromise ??= (async () => {
        const zxing = await import('zxing-wasm/reader');

        zxing.prepareZXingModule({
            overrides: {
                locateFile: (file) => `/vendor/zxing-wasm/${file}`,
            },
        });

        return zxing.readBarcodes;
    })();

    return wasmReaderPromise;
}

const READER_OPTIONS = { formats: ['QRCode'], tryHarder: true };

/**
 * Camera constraint attempts, most specific first. `ideal` values never
 * over-constrain; the plain `{video: true}` fallback accepts any camera
 * (front-facing included) for odd devices that reject facingMode entirely.
 */
const CAMERA_ATTEMPTS = [
    { video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false },
    { video: true, audio: false },
];

function isInAppBrowser() {
    const ua = navigator.userAgent ?? '';

    // Facebook/Messenger, Instagram, Line, MIUI browser-in-feed, etc.
    return /\b(FBAN|FBAV|FBSV|Instagram|Line\/|Snapchat|Musical_Ly| Musical|MicroMessenger|Twitter|Reddit)\b/i.test(ua)
        // Android WebViews that identify as generic but are embedded:
        || (/Android/.test(ua) && /;\s*wv\)/i.test(ua));
}

export default (initialState) => ({
    cameraState: 'idle', // idle | starting | running | denied | unavailable
    processing: false,
    result: null, // { type: 'success' | 'notice' | 'error', message }
    diag: null, // raw error text — always surfaced, makes phone failures reportable
    inAppBrowser: false,
    escapeUrl: null, // intent:// link that opens the same URL in Chrome
    stream: null,
    detector: null,
    useNativeDetector: false,
    rafId: null,
    tickTimer: null,
    submitTimer: null,
    lastPayload: null,
    lastPayloadAt: 0,
    redirectUrl: initialState?.redirectUrl ?? '/student/dtr',
    submitUrl: initialState?.submitUrl ?? '/api/dtr/scan-checkin',
    ...initialState,

    async init() {
        this.useNativeDetector = 'BarcodeDetector' in window;

        if (this.useNativeDetector) {
            try {
                this.detector = new BarcodeDetector({ formats: ['qr_code'] });
            } catch (error) {
                this.useNativeDetector = false;
            }
        }

        if (isInAppBrowser()) {
            this.inAppBrowser = true;

            // Android intent URL: asks the OS to open this exact page in
            // Chrome, escaping the webview that blocks the camera.
            if (/Android/.test(navigator.userAgent)) {
                const hostAndPath = location.host + location.pathname;

                this.escapeUrl = `intent://${hostAndPath}#Intent;scheme=https;package=com.android.chrome;end`;
            }
        }

        this.$cleanup?.(() => this.stopCamera());
        window.addEventListener('pagehide', () => this.stopCamera());
    },

    async start() {
        try {
            await this.startCamera();
        } catch (error) {
            // Never fail silently: any unexpected error becomes a visible
            // banner + the raw detail underneath.
            this.cameraState = 'unavailable';
            this.setDiag(error);
            this.result = {
                type: 'error',
                message: 'The camera could not be started on this device. Please use manual Time In instead.',
            };
        }
    },

    /**
     * Distinguish "this page was never granted" (browser will prompt) from
     * "camera is hard-blocked at OS/browser settings level" (prompt never
     * appears — the report from the field: Chrome AND Brave, no prompt, no
     * camera). Chrome denies without prompting when the permission is
     * already 'denied'; asking first lets us show the right recovery hint
     * instead of a generic failure.
     */
    async checkCameraPermission() {
        if (! navigator.permissions?.query) {
            return 'unknown';
        }

        try {
            const status = await navigator.permissions.query({ name: 'camera' });

            return status.state; // 'granted' | 'denied' | 'prompt'
        } catch (error) {
            return 'unknown'; // some browsers don't expose the camera permission name
        }
    },

    async startCamera() {
        if (this.cameraState === 'running' || this.cameraState === 'starting') {
            return;
        }

        this.cameraState = 'starting';
        this.result = null;
        this.diag = null;

        // getUserMedia only exists in secure contexts (https:// or localhost).
        // On http:// LAN testing the browser removes the API entirely — say
        // so instead of "camera not found".
        if (! window.isSecureContext || ! navigator.mediaDevices?.getUserMedia) {
            this.cameraState = 'unavailable';

            if (! window.isSecureContext) {
                this.result = {
                    type: 'error',
                    message: 'Camera needs a secure connection (https://). Open the app over https — or use manual Time In.',
                };
            } else {
                this.result = {
                    type: 'error',
                    message: 'This browser does not expose a camera API. Please use manual Time In instead.',
                };
            }

            return;
        }

        let lastError = null;

        const permission = await this.checkCameraPermission();

        if (permission === 'denied') {
            this.cameraState = 'denied';
            this.diag = 'permission query: denied (no prompt possible)';
            this.result = {
                type: 'error',
                message: 'Camera access is blocked in your browser settings. Chrome/Brave: tap the 🔒 (or ⋮) beside the address bar → Permissions → Camera → Allow, then reload this page.',
            };
            return;
        }

        for (const constraints of CAMERA_ATTEMPTS) {
            try {
                this.stream = await navigator.mediaDevices.getUserMedia(constraints);
                lastError = null;
                break;
            } catch (error) {
                lastError = error;

                // Permission/security problems never resolve by retrying
                // with looser constraints — bail immediately.
                if (error?.name === 'NotAllowedError' || error?.name === 'SecurityError') {
                    break;
                }
            }
        }

        if (! this.stream) {
            this.cameraState = lastError?.name === 'NotAllowedError' ? 'denied' : 'unavailable';
            this.setDiag(lastError);
            this.result = {
                type: 'error',
                message: this.cameraState === 'denied'
                    ? 'Camera permission was denied. Enable it in your browser settings, or use manual Time In.'
                    : this.cameraBusyMessage(lastError),
            };
            return;
        }

        const video = this.$refs.video;
        video.srcObject = this.stream;

        // Wait until frames actually flow before declaring success: on some
        // Android builds play() resolves while the preview is still black.
        // The timeout keeps "Starting…" from hanging forever on stragglers.
        await new Promise((resolve) => {
            if (video.readyState >= 2) {
                resolve();
                return;
            }

            video.addEventListener('loadeddata', () => resolve(), { once: true });
            setTimeout(resolve, 3000);
        });

        try {
            await video.play();
        } catch (error) {
            // The video tag carries autoplay+muted+playsinline, so most
            // builds start rendering regardless.
            this.diag = `play() blocked: ${error?.name ?? ''} ${error?.message ?? ''}`;
        }

        this.cameraState = 'running';
        this.scanLoop();
    },

    setDiag(error) {
        const name = error?.name ?? 'UnknownError';
        const message = error?.message ?? String(error ?? 'no details');

        this.diag = `${name}: ${message}`.slice(0, 200);
    },

    /**
     * Human message per failure class — the three real-world buckets.
     */
    cameraBusyMessage(error) {
        switch (error?.name) {
            case 'NotReadableError':
            case 'TrackStartError':
                return 'Your camera seems to be in use — close other camera tabs or apps, then try again.';
            case 'NotFoundError':
            case 'DevicesNotFoundError':
                return 'No camera was found on this device. Please use manual Time In instead.';
            case 'OverconstrainedError':
                return 'This device\u2019s camera does not support the requested mode. Please use manual Time In.';
            default:
                return 'No usable camera found. Please use manual Time In instead.';
        }
    },

    stopCamera() {
        cancelAnimationFrame(this.rafId);
        clearTimeout(this.tickTimer);
        this.stream?.getTracks().forEach((track) => track.stop());
        this.stream = null;

        if (this.$refs.video) {
            this.$refs.video.srcObject = null;
        }

        this.cameraState = this.cameraState === 'running' ? 'idle' : this.cameraState;
    },

    async scanLoop() {
        if (this.cameraState !== 'running' || this.processing || this.result) {
            return;
        }

        const video = this.$refs.video;

        if (! video || video.readyState < video.HAVE_ENOUGH_DATA) {
            this.tickTimer = setTimeout(() => this.scanLoop(), 200);
            return;
        }

        this.processing = true;

        try {
            const payload = this.useNativeDetector
                ? await this.detectNative(video)
                : await this.detectWasm(video);

            if (payload && this.isCheckinPayload(payload)) {
                // Debounce identical payloads: the loop runs several times a
                // second while the QR is in frame.
                if (payload !== this.lastPayload || Date.now() - this.lastPayloadAt > 3000) {
                    this.lastPayload = payload;
                    this.lastPayloadAt = Date.now();
                    await this.submit(payload);
                }
            }
        } catch (error) {
            // A single failed frame is normal (blur, distance); keep scanning.
        } finally {
            this.processing = false;
        }

        if (this.cameraState === 'running' && ! this.result) {
            this.rafId = requestAnimationFrame(() => this.scanLoop());
        }
    },

    async detectNative(video) {
        const codes = await this.detector.detect(video);

        return codes?.[0]?.rawValue ?? null;
    },

    async detectWasm(video) {
        const readBarcodes = await loadWasmReader();

        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        const context = canvas.getContext('2d', { willReadFrequently: true });
        context.drawImage(video, 0, 0);

        const results = await readBarcodes(
            context.getImageData(0, 0, canvas.width, canvas.height),
            READER_OPTIONS,
        );

        return results?.[0]?.text ?? null;
    },

    isCheckinPayload(payload) {
        return payload.startsWith('ojtsync://checkin?');
    },

    async submit(payload) {
        try {
            const response = await fetch(this.submitUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({ payload }),
            });

            if (response.ok) {
                const data = await response.json();
                this.stopCamera();
                this.result = { type: 'success', message: data.message ?? 'Timed in successfully.' };
                this.submitTimer = setTimeout(() => {
                    window.location.href = this.redirectUrl;
                }, 1200);
                return;
            }

            const data = await response.json().catch(() => null);
            const message = data?.message ?? 'Check-in failed. Please try again.';

            // Already timed in (e.g. scanned after a manual Time In): same
            // outcome as success — go to the DTR page.
            if (response.status === 409 && data?.idempotent) {
                this.stopCamera();
                this.result = { type: 'notice', message };
                this.submitTimer = setTimeout(() => {
                    window.location.href = this.redirectUrl;
                }, 1500);
                return;
            }

            this.stopCamera();

            // Expired / reused / wrong company / blocked: banner + manual
            // retry. Scanning stops so the same QR isn't re-submitted in a
            // tight loop while the student reads the message.
            this.result = { type: 'error', message };

            if (response.status === 410 || response.status === 409 || response.status === 403) {
                this.lastPayload = null;
            }
        } catch (error) {
            this.result = {
                type: 'error',
                message: 'Network problem while checking in. Check your connection and try again.',
            };
        }
    },

    get statusLabel() {
        const labels = {
            idle: 'Point your camera at the QR code shown at your company.',
            starting: 'Starting camera…',
            running: this.useNativeDetector
                ? 'Looking for the check-in QR…'
                : 'Looking for the check-in QR… (using the fallback scanner)',
            denied: 'Camera access denied.',
            unavailable: 'Camera unavailable.',
        };

        return labels[this.cameraState] ?? '';
    },
});
