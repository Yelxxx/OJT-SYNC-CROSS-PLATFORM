/**
 * Offline sync queue — shared between the page bundle (app.js) and the
 * service worker (sw.js).
 *
 * Writes captured while offline are stored in IndexedDB as serialized
 * requests, each carrying a client-generated UUID idempotency key the server
 * records (unique index) so a replay can never duplicate an entry.
 *
 * Replays happen in three ways:
 *  1. Background Sync (`sync` event, tag "ojt-offline-sync") — fired by
 *     Chrome when connectivity returns, even if the app is closed;
 *  2. replayQueue() from the page when it loads with queued items;
 *  3. online event / interval fallback in the page context.
 *
 * Server error contract (routes/api.php):
 *  - 201/200          → drop from queue
 *  - 4xx idempotent   → conflict already resolved server-side; drop quietly
 *  - 401/403/422      → permanent rejection; drop and flag
 *  - 429 / 5xx / offline → keep queued for the next replay
 */

const DB_NAME = 'ojt-sync';
const DB_VERSION = 1;
const QUEUE_STORE = 'queue';
const STATE_STORE = 'state';

/** @type {Array<(count: number, syncing: boolean) => void>} */
const listeners = [];

let syncing = false;

function supportsIdb() {
    return typeof indexedDB !== 'undefined';
}

function openDb() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = () => {
            const db = request.result;
            if (! db.objectStoreNames.contains(QUEUE_STORE)) {
                db.createObjectStore(QUEUE_STORE, { keyPath: 'uuid' });
            }
            if (! db.objectStoreNames.contains(STATE_STORE)) {
                db.createObjectStore(STATE_STORE);
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function withStore(name, mode, callback) {
    if (! supportsIdb()) return null;

    const db = await openDb();

    return new Promise((resolve, reject) => {
        const tx = db.transaction(name, mode);
        const store = tx.objectStore(name);
        const result = callback(store);

        tx.oncomplete = () => {
            db.close();
            resolve(result?.result !== undefined ? result.result : null);
        };
        tx.onerror = () => {
            db.close();
            reject(tx.error);
        };
        tx.onabort = () => {
            db.close();
            reject(tx.error || new Error('transaction aborted'));
        };
    });
}

/* ------------------------------------------------------------------ */
/* Queue                                                               */
/* ------------------------------------------------------------------ */

export function uuid() {
    if (typeof crypto !== 'undefined' && crypto.randomUUID) {
        return crypto.randomUUID();
    }

    // RFC4122-v4 fallback for non-secure contexts.
    return '10000000-1000-4000-8000-100000000000'.replace(/[018]/g, (c) => {
        const byte = (crypto.getRandomValues(new Uint8Array(1))[0] & (15 >> (c / 4))) | (Number(c) ^ 128);
        return byte.toString(16);
    });
}

export function queueWrite(entry) {
    return withStore(QUEUE_STORE, 'readwrite', (store) => store.put(entry));
}

export async function queueCount() {
    if (! supportsIdb()) return 0;

    return (await withStore(QUEUE_STORE, 'readonly', (store) => store.count())) ?? 0;
}

export async function getQueuedWrites() {
    if (! supportsIdb()) return [];

    return (await withStore(QUEUE_STORE, 'readonly', (store) => store.getAll())) ?? [];
}

export async function removeQueuedWrite(uuid) {
    return withStore(QUEUE_STORE, 'readwrite', (store) => store.delete(uuid));
}

/* ------------------------------------------------------------------ */
/* Last-known DTR state (offline dashboard data)                       */
/* ------------------------------------------------------------------ */

export function saveDtrState(state) {
    return withStore(STATE_STORE, 'readwrite', (store) => store.put(state, 'dtr-state'));
}

export async function getDtrState() {
    if (! supportsIdb()) return null;

    return (await withStore(STATE_STORE, 'readonly', (store) => store.get('dtr-state'))) ?? null;
}

export function saveDraft(draft) {
    return withStore(STATE_STORE, 'readwrite', (store) => store.put(draft, 'journal-draft'));
}

export async function getDraft() {
    if (! supportsIdb()) return null;

    return (await withStore(STATE_STORE, 'readonly', (store) => store.get('journal-draft'))) ?? null;
}

export function clearDraft() {
    return withStore(STATE_STORE, 'readwrite', (store) => store.delete('journal-draft'));
}

/* ------------------------------------------------------------------ */
/* Replay + sync-state broadcast                                       */
/* ------------------------------------------------------------------ */

export function onSyncChange(callback) {
    listeners.push(callback);
    callback(lastBroadcast.count, lastBroadcast.syncing);
}

let lastBroadcast = { count: 0, syncing: false };

async function broadcast() {
    const count = await queueCount();
    lastBroadcast = { count, syncing };

    for (const listener of listeners) {
        listener(count, syncing);
    }
}

export function registerQueueListener() {
    if (typeof document !== 'undefined') {
        document.addEventListener('ojt:queue-changed', () => broadcast());
    }
}

export async function refreshQueueUi() {
    await broadcast();
}

function notifyQueueChanged() {
    if (typeof document !== 'undefined') {
        document.dispatchEvent(new CustomEvent('ojt:queue-changed'));
    }
}

async function replayOne(item) {
    const headers = { 'Content-Type': 'application/json', Accept: 'application/json' };

    if (typeof document !== 'undefined') {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;

        if (token) {
            headers['X-CSRF-TOKEN'] = token;
        }
    }

    let response;

    try {
        response = await fetch(item.url, {
            method: 'POST',
            headers,
            credentials: 'same-origin',
            body: JSON.stringify(item.body),
        });
    } catch (networkError) {
        return 'offline';
    }

    if (response.ok) {
        return 'done';
    }

    if (response.status === 429 || response.status >= 500) {
        return 'offline'; // transient — retry later
    }

    if (response.status === 401 || response.status === 419) {
        return 'auth';
    }

    // 403 / 404 / 422 / other 4xx: permanent rejection.
    let payload = null;

    try {
        payload = await response.json();
    } catch (parseError) {
        payload = null;
    }

    // not_hired (403): the queued write can never succeed — drop it quietly;
    // the DTR pages themselves redirect the student to their applications.
    if (payload?.error === 'not_hired') {
        return { permanent: true, status: 403, error: 'not_hired' };
    }

    return { permanent: true, status: response.status, error: payload?.error ?? 'rejected' };
}

/**
 * Replay every queued write. Safe to call from the page, the service
 * worker, and simultaneously from both — each write is removed exactly
 * once by its uuid key, and the server de-dupes by client_uuid anyway.
 */
export async function replayQueue() {
    if (syncing) {
        return { done: 0, dropped: 0, remaining: null };
    }

    syncing = true;
    await broadcast();

    let done = 0;
    let dropped = 0;
    let authLost = false;

    try {
        for (const item of await getQueuedWrites()) {
            const outcome = await replayOne(item);

            if (outcome === 'offline') {
                // Keep order: stop at the first still-offline write.
                break;
            }

            if (outcome === 'auth') {
                authLost = true;
                break;
            }

            await removeQueuedWrite(item.uuid);
            notifyQueueChanged();

            if (outcome === 'done') {
                done += 1;
            } else {
                dropped += 1;
            }
        }
    } finally {
        syncing = false;
        await broadcast();
    }

    return { done, dropped, authLost, remaining: await queueCount() };
}
