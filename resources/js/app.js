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

Alpine.data('installPrompt', () => ({
    showInstallPrompt: false,
    deferredPrompt: null,

    init() {
        window.addEventListener('beforeinstallprompt', (e) => {
            // Prevent Chrome 67 and earlier from automatically showing the prompt
            e.preventDefault();
            // Stash the event so it can be triggered later
            this.deferredPrompt = e;
            // Update UI to notify the user they can install the PWA
            this.showInstallPrompt = true;
        });

        window.addEventListener('appinstalled', () => {
            // Hide the app-provided install prompt
            this.showInstallPrompt = false;
            // Clear the deferredPrompt so it can be garbage collected
            this.deferredPrompt = null;
        });
    },

    installPWA() {
        // Show the prompt
        this.showInstallPrompt = false;
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

Alpine.start();
