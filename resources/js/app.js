import Alpine from 'alpinejs';
import qrScanner from './qr-scanner.js';

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

Alpine.data('installPrompt', () => ({
    showInstallPrompt: false,
    deferredPrompt: null,
    SHOW_DELAY_MS: 1500,
    PROMPT_COOLDOWN_MS: 7 * 24 * 60 * 60 * 1000, // ask again after one week
    showTimer: null,

    init() {
        window.addEventListener('beforeinstallprompt', (e) => {
            // Prevent Chrome 67 and earlier from automatically showing the prompt
            e.preventDefault();

            // Respect the user's earlier decision: after a dismissal we stay
            // quiet for PROMPT_COOLDOWN_MS, and the OS/browser install
            // criteria themselves gate how often beforeinstallprompt fires.
            if (this.isDismissed()) {
                return;
            }

            // Stash the event so it can be triggered later
            this.deferredPrompt = e;

            // Give the page a moment to settle so the popup never fights the
            // loading screen for attention.
            clearTimeout(this.showTimer);
            this.showTimer = setTimeout(() => {
                this.showInstallPrompt = true;
            }, this.SHOW_DELAY_MS);
        });

        window.addEventListener('appinstalled', () => {
            // Hide the app-provided install prompt
            this.showInstallPrompt = false;
            clearTimeout(this.showTimer);
            // Clear the deferredPrompt so it can be garbage collected
            this.deferredPrompt = null;
        });

        // Defensive cleanup if the component dies before the timer fires
        this.$cleanup?.(() => clearTimeout(this.showTimer));
    },

    isDismissed() {
        try {
            const raw = localStorage.getItem('pwa-install-prompt');

            if (! raw) {
                return false;
            }

            const state = JSON.parse(raw);

            // A dismissal older than the cooldown expires and we may ask again.
            return Boolean(state.dismissedAt) &&
                (Date.now() - state.dismissedAt) < this.PROMPT_COOLDOWN_MS;
        } catch (error) {
            return false;
        }
    },

    dismissInstallPrompt() {
        this.showInstallPrompt = false;

        try {
            localStorage.setItem('pwa-install-prompt', JSON.stringify({
                dismissedAt: Date.now(),
            }));
        } catch (error) {
            // Private mode / storage full: fall back to current behavior
            // (prompt may reappear on later visits).
        }
    },

    installPWA() {
        // Show the prompt
        this.showInstallPrompt = false;
        clearTimeout(this.showTimer);
        // Show the install prompt
        if (this.deferredPrompt) {
            this.deferredPrompt.prompt();
            // Wait for the user to respond to the prompt
            this.deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('User accepted the install prompt');
                } else {
                    console.log('User dismissed the install prompt');
                }
                this.deferredPrompt = null;
            });
        }
    },
}));

Alpine.data('qrScanner', qrScanner);

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

/* ------------------------------------------------------------------ */
/* Service worker glue                                                 */
/* ------------------------------------------------------------------ */

if ('serviceWorker' in navigator) {
    // Periodically ask for SW updates so installs pick up new deploys.
    window.addEventListener('load', () => {
        navigator.serviceWorker.getRegistration().then((registration) => {
            if (registration) {
                setInterval(() => registration.update(), 60 * 60 * 1000);
            }
        });
    });
}

Alpine.start();
