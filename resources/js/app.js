import Alpine from 'alpinejs';
import qrScanner from './qr-scanner.js';
import {
    clearDraft,
    getDraft,
    getDtrState,
    onSyncChange,
    queueCount,
    queueWrite,
    replayQueue,
    refreshQueueUi,
    saveDraft,
    saveDtrState,
    uuid,
} from './offline-sync.js';

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

/* ------------------------------------------------------------------ */
/* Sync status store (global queue chip in the navigation)             */
/* ------------------------------------------------------------------ */

Alpine.store('syncStatus', {
    pending: 0,
    syncing: false,
    justSynced: false,
    justSyncedTimer: null,

    init() {
        onSyncChange((count, syncing) => {
            this.pending = count;
            this.syncing = syncing;
        });

        window.addEventListener('online', () => {
            replayQueue().then((result) => this.announce(result));
        });

        window.addEventListener('offline', () => refreshQueueUi());

        // Replay anything still queued from a previous session (fallback for
        // browsers without Background Sync) and keep the chip honest.
        replayQueue().then((result) => this.announce(result));

        // Belt-and-braces fallback if no sync/online event ever fires.
        setInterval(async () => {
            if (navigator.onLine && (await queueCount()) > 0) {
                this.announce(await replayQueue());
            }
        }, 60_000);
    },

    retry() {
        if (this.syncing) {
            return;
        }

        replayQueue().then((result) => this.announce(result));
    },

    announce(result) {
        refreshQueueUi();

        if (result && (result.done > 0 || result.dropped > 0)) {
            this.justSynced = true;
            clearTimeout(this.justSyncedTimer);
            this.justSyncedTimer = setTimeout(() => {
                this.justSynced = false;
            }, 4000);

            // A page that queued an action and is waiting on server truth
            // (e.g. the DTR table after an offline check-in) reloads so the
            // UI reflects the synced data.
            if (result.done > 0 && document.body?.dataset.syncReload === 'true') {
                delete document.body.dataset.syncReload;
                window.location.reload();
            }
        }
    },
});

/* ------------------------------------------------------------------ */
/* Offline DTR (student dtr page)                                      */
/* ------------------------------------------------------------------ */

Alpine.data('offlineDtr', (initialState) => ({
    checkedIn: initialState.checkedIn,
    activeTimeIn: initialState.activeTimeIn,
    totalHours: initialState.totalHours,
    requiredHours: initialState.requiredHours,
    pendingCount: 0,
    busy: false,
    notice: null,
    noticeTimer: null,

    async init() {
        const cached = await getDtrState();

        // Hydrate from the last-known server snapshot first, then refresh
        // from the network (the blade-rendered values are the freshest on
        // a normal load; the cache covers the SW-served offline page).
        if (cached) {
            this.checkedIn = cached.checked_in;
            this.activeTimeIn = cached.active_entry?.time_in ?? null;
            this.totalHours = cached.total_hours;
        }

        if (navigator.onLine) {
            try {
                const response = await fetch('/api/student/dtr/state', {
                    headers: { Accept: 'application/json' },
                });

                if (response.ok) {
                    const state = await response.json();
                    this.applyState(state);
                    saveDtrState(state);
                }
            } catch (error) {
                // Offline: cached values already applied.
            }
        }

        this.pendingCount = await queueCount();
        onSyncChange((count) => {
            this.pendingCount = count;
        });
    },

    applyState(state) {
        this.checkedIn = state.checked_in;
        this.activeTimeIn = state.active_entry?.time_in ?? null;
        this.totalHours = state.total_hours;
        this.requiredHours = state.required_hours;
    },

    showNotice(message) {
        this.notice = message;
        clearTimeout(this.noticeTimer);
        this.noticeTimer = setTimeout(() => {
            this.notice = null;
        }, 5000);
    },

    localTime(iso) {
        if (! iso) {
            return '';
        }

        return new Date(iso).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    },

    async checkin() {
        if (this.busy || this.checkedIn) {
            return;
        }

        this.busy = true;

        const uuid = this.newUuid();
        const timeIn = new Date().toISOString();

        try {
            const response = await fetch('/api/student/dtr/checkin', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({ client_uuid: uuid, time_in: timeIn }),
            });

            if (response.ok) {
                const payload = await response.json();
                this.checkedIn = true;
                this.activeTimeIn = payload.entry.time_in;
                this.showNotice(payload.message ?? 'Timed in successfully.');
            } else if (response.status === 409) {
                const payload = await response.json();

                if (payload.idempotent) {
                    this.checkedIn = true;
                    this.showNotice(payload.message);
                } else {
                    this.showNotice(payload.message ?? 'You already have an active time-in.');
                    this.checkedIn = true;
                }
            } else if (response.status === 403 || response.status === 422) {
                const payload = await response.json().catch(() => null);
                this.showNotice(payload?.message ?? 'You cannot clock in right now.');
            } else {
                await this.queueOffline('checkin', uuid, { time_in: timeIn });
            }
        } catch (error) {
            await this.queueOffline('checkin', uuid, { time_in: timeIn });
        }

        this.busy = false;
    },

    async checkout(taskSummary) {
        if (this.busy || ! this.checkedIn) {
            return;
        }

        this.busy = true;

        const uuid = this.newUuid();
        const timeOut = new Date().toISOString();

        try {
            const response = await fetch('/api/student/dtr/checkout', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({ client_uuid: uuid, time_out: timeOut, task_summary: taskSummary }),
            });

            if (response.ok) {
                const payload = await response.json();
                this.checkedIn = false;
                this.activeTimeIn = null;
                this.totalHours = Math.round((this.totalHours + payload.entry.hours) * 100) / 100;
                this.showNotice(payload.message ?? 'Timed out. Great work today!');
            } else if (response.status === 409) {
                const payload = await response.json();
                this.checkedIn = false;
                this.activeTimeIn = null;
                this.showNotice(payload.idempotent ? payload.message : (payload.message ?? 'Timed out.'));
            } else if (response.status === 403 || response.status === 422) {
                const payload = await response.json().catch(() => null);
                this.showNotice(payload?.message ?? 'You cannot clock out right now.');
            } else {
                await this.queueOffline('checkout', uuid, { time_out: timeOut, task_summary: taskSummary });
            }
        } catch (error) {
            await this.queueOffline('checkout', uuid, { time_out: timeOut, task_summary: taskSummary });
        }

        this.busy = false;
    },

    async queueOffline(action, uuid, body) {
        await queueWrite({
            uuid,
            url: action === 'checkin' ? '/api/student/dtr/checkin' : '/api/student/dtr/checkout',
            body: { client_uuid: uuid, ...body },
            queued_at: Date.now(),
            description: action === 'checkin' ? 'Time in' : 'Time out',
        });

        if (action === 'checkin') {
            this.checkedIn = true;
            this.activeTimeIn = body.time_in;
        } else {
            this.checkedIn = false;
            this.activeTimeIn = null;
        }

        this.pendingCount += 1;
        document.body.dataset.syncReload = 'true';

        try {
            // getRegistration (not .ready) — .ready never resolves when no SW
            // is registered (PWA_ENABLED off, non-Chrome browsers).
            const registration = await navigator.serviceWorker?.getRegistration?.();
            await registration?.sync?.register('ojt-offline-sync');
        } catch (error) {
            // Background Sync unavailable (not Chrome / private mode): the
            // online listener + interval in the syncStatus store will replay.
        }

        this.showNotice('Saved offline — it will sync automatically when you are back online.');
    },

    newUuid() {
        if (crypto.randomUUID) {
            return crypto.randomUUID();
        }

        return '10000000-1000-4000-8000-100000000000'.replace(/[018]/g, (c) => {
            const byte = (crypto.getRandomValues(new Uint8Array(1))[0] & (15 >> (c / 4))) | (Number(c) ^ 128);
            return byte.toString(16);
        });
    },
}));

Alpine.data('qrScanner', qrScanner);

/* ------------------------------------------------------------------ */
/* Live shift clock (student DTR page)                                 */
/*                                                                     */
/* Drives the analog clock's second/minute/hour hands and the digital  */
/* elapsed readout from the active entry's time_in, ticking every      */
/* second so the user can see the shift is actually being counted.     */
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

        if (! Number.isFinite(start)) {
            return;
        }

        this.seconds = Math.max(0, Math.floor((Date.now() - start) / 1000));
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
/* Journal draft autosave (student journal create page)                */
/* ------------------------------------------------------------------ */

Alpine.data('journalDraft', (initial = {}) => ({
    week: initial.week ?? '',
    title: initial.title ?? '',
    entry: initial.entry ?? '',
    hasDraft: false,
    submitting: false,

    async init() {
        // Values re-rendered by the server (validation errors) are fresher
        // than anything stored locally — only restore a draft otherwise.
        const fromServer = Boolean(initial.title || initial.entry);

        if (! fromServer) {
            const draft = await getDraft();

            if (draft && (draft.title || draft.entry)) {
                this.week = draft.week ?? this.week;
                this.title = draft.title;
                this.entry = draft.entry;
                this.hasDraft = true;
            }
        } else {
            this.hasDraft = true;
        }

        ['week', 'title', 'entry'].forEach((field) => {
            this.$watch(field, () => this.persist());
        });
    },

    persist() {
        this.hasDraft = true;
        saveDraft({ week: this.week, title: this.title, entry: this.entry, saved_at: Date.now() });
    },

    restoreDraft() {
        this.$refs.week?.focus();
    },

    discardDraft() {
        clearDraft();
        this.week = '';
        this.title = '';
        this.entry = '';
        this.hasDraft = false;
    },

    async submit() {
        if (this.submitting) {
            return;
        }

        this.submitting = true;

        const payload = {
            client_uuid: crypto.randomUUID?.() ?? uuid(),
            week: Number(this.week),
            title: (this.title ?? '').trim(),
            entry: (this.entry ?? '').trim(),
            submitted_at: new Date().toISOString(),
        };

        if (! Number.isFinite(payload.week) || ! payload.title || ! payload.entry) {
            alert('Please complete the week, title, and entry before submitting.');
            this.submitting = false;
            return;
        }

        try {
            const response = await fetch('/api/student/journals', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify(payload),
            });

            if (response.ok) {
                await clearDraft();
                window.location.href = '/student/journals';
                return;
            }

            if (response.status === 422) {
                const data = await response.json();
                const first = data.errors ? Object.values(data.errors)[0] : null;
                alert(first ? first[0] : 'Please check the form and try again.');
                this.submitting = false;
                return;
            }

            if (response.status === 401 || response.status === 419) {
                this.queueForLater(payload);
                return;
            }

            this.queueForLater(payload);
        } catch (error) {
            this.queueForLater(payload);
        }
    },

    async queueForLater(payload) {
        await queueWrite({
            uuid: payload.client_uuid,
            url: '/api/student/journals',
            body: payload,
            queued_at: Date.now(),
            description: `Journal: ${payload.title || 'untitled'}`,
        });

        await clearDraft();
        window.location.href = '/student/journals';
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

    navigator.serviceWorker.addEventListener('message', (event) => {
        if (event.data?.type === 'SYNC_FINISHED') {
            Alpine.store('syncStatus').announce(event.data.detail);
        }
    });
}

Alpine.start();
