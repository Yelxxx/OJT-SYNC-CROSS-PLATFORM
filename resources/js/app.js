import Alpine from 'alpinejs';

Alpine.data('loadingScreen', () => ({
    visible: true,
    startedAt: Date.now(),
    finishTimer: null,

    init() {
        const finish = () => {
            const elapsed = Date.now() - this.startedAt;
            clearTimeout(this.finishTimer);
            this.finishTimer = setTimeout(() => {
                this.visible = false;
            }, Math.max(0, 500 - elapsed));
        };

        if (document.readyState === 'complete') {
            finish();
        } else {
            window.addEventListener('load', finish, { once: true });
            this.finishTimer = setTimeout(finish, 3000);
        }

        window.addEventListener('pageshow', (e) => {
            if (e.persisted) {
                this.visible = false;
            }
        });

        document.addEventListener('click', (e) => {
            if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
                return;
            }

            const link = e.target.closest('a[href]');

            if (! link || link.target === '_blank' || link.hasAttribute('download')) {
                return;
            }

            const href = link.getAttribute('href') || '';

            if (href.startsWith('/') && ! href.startsWith('//')) {
                this.visible = true;
            }
        });

        document.addEventListener('submit', (e) => {
            if (e.defaultPrevented) {
                return;
            }

            this.visible = true;
        });
    },
}));

window.Alpine = Alpine;

/* ------------------------------------------------------------------ */
/* Live shift clock (student DTR page)                                 */
/*                                                                     */
/* Drives the analog clock's second/minute/hour hands and the digital  */
/* elapsed readout from the active entry's time_in, ticking every      */
/* second so the user can see the shift is actually being counted.     */
/* The page reloads on time in / out (plain forms), so the server-     */
/* rendered startedAt is always current.                               */
/* ------------------------------------------------------------------ */

Alpine.data('liveClock', ({ startedAt }) => ({
    seconds: 0,
    timer: null,

    init() {
        this.tick();
        this.timer = setInterval(() => this.tick(), 1000);
        this.$cleanup?.(() => clearInterval(this.timer));
    },

    tick() {
        const start = new Date(startedAt ?? '').getTime();

        this.seconds = Number.isFinite(start)
            ? Math.max(0, Math.floor((Date.now() - start) / 1000))
            : 0;
    },

    get secondDeg() {
        return (this.seconds % 60) * 6;
    },

    get minuteDeg() {
        return ((this.seconds / 60) % 60) * 6;
    },

    get hourDeg() {
        return ((this.seconds / 3600) % 12) * 30;
    },

    get elapsedLabel() {
        const h = Math.floor(this.seconds / 3600);
        const m = Math.floor((this.seconds % 3600) / 60);
        const s = this.seconds % 60;

        return `${h}h ${String(m).padStart(2, '0')}m ${String(s).padStart(2, '0')}s`;
    },
}));

Alpine.data('pwaInstallPrompt', () => ({
    deferredPrompt: null,
    canInstall: false,

    init() {
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
            this.canInstall = true;
        });

        window.addEventListener('appinstalled', () => {
            this.canInstall = false;
            this.deferredPrompt = null;
        });
    },

    async installApp() {
        if (! this.deferredPrompt) {
            return;
        }
        this.deferredPrompt.prompt();
        const { outcome } = await this.deferredPrompt.userChoice;
        if (outcome === 'accepted') {
            this.canInstall = false;
        }
        this.deferredPrompt = null;
    },
}));

// Register online-only pass-through Service Worker for PWA installation eligibility
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Service worker registration error ignored in environments without HTTPS/SW support
        });
    });
}

Alpine.start();
