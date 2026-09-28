/**
 * QR scanner component (CROSS_PLATFORM_PLAN.md, Phase 3).
 *
 * Prefers the BarcodeDetector API (Chrome on Android, zero extra bytes);
 * falls back to zxing-wasm (~1 MiB, dynamically imported only when needed —
 * browsers with BarcodeDetector never download it).
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
// (copied to public/vendor/zxing-wasm by scripts/copy-zxing-wasm.sh after
// each build) instead of pulled from a public CDN at scan time — school and
// office networks sometimes block CDNs.
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

export default (initialState) => ({
    scanning: false,
    cameraState: 'idle', // idle | starting | running | denied | unavailable
    processing: false,
    result: null, // { type: 'success' | 'notice' | 'error', message }
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

        this.$cleanup?.(() => this.stopCamera());
        window.addEventListener('pagehide', () => this.stopCamera());
    },

    async start() {
        if (this.cameraState === 'running' || this.cameraState === 'starting') {
            return;
        }

        this.cameraState = 'starting';
        this.result = null;

        if (! navigator.mediaDevices?.getUserMedia) {
            this.cameraState = 'unavailable';
            this.result = {
                type: 'error',
                message: 'Camera is not available on this device. Please use manual Time In instead.',
            };
            return;
        }

        try {
            this.stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } },
                audio: false,
            });
        } catch (error) {
            this.cameraState = error?.name === 'NotAllowedError' ? 'denied' : 'unavailable';
            this.result = {
                type: 'error',
                message: this.cameraState === 'denied'
                    ? 'Camera permission was denied. Enable it in your browser settings, or use manual Time In.'
                    : 'No usable camera found. Please use manual Time In instead.',
            };
            return;
        }

        const video = this.$refs.video;
        video.srcObject = this.stream;

        try {
            await video.play();
        } catch (error) {
            // Autoplay edge case; the stream is still attached.
        }

        this.cameraState = 'running';
        this.scanLoop();
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
        return {
            idle: 'Point your camera at the QR code shown at your company.',
            starting: 'Starting camera…',
            running: 'Looking for the check-in QR…',
            denied: 'Camera access denied.',
            unavailable: 'Camera unavailable.',
        }[this.cameraState] ?? '';
    },
});
