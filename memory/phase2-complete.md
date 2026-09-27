---
name: phase2-complete
description: Phase 2 offline support (DTR + journals) implemented — queue, Background Sync, idempotent API
metadata:
  type: project
---

## Phase 2 — Offline Support: COMPLETED ✅ (2026-09-27)

### Backend
- **`routes/api.php` (new)** — session-auth JSON under `auth`+`verified`+`role:student`:
  - `GET  /api/student/dtr/state` — checked_in, active entry, total/required hours, server_time
  - `POST /api/student/dtr/checkin|checkout` — idempotent via `client_uuid`
  - `POST /api/student/journals` — idempotent via `client_uuid`
- **CSRF**: `validateCsrfTokens(except: ['api/*'])` in `bootstrap/app.php` — required for Background Sync replays (no token). Compensations: SameSite=lax cookies, session auth, server-recorded UUIDs.
- **Services**: `App\Services\DtrService` / `JournalService` — all rules live here once; web controllers + API controllers both delegate. Throws `App\Exceptions\DomainActionException(errorKey, message, status)`.
- **Idempotency**: migration `2026_09_27_000001_add_client_uuid_for_offline_sync.php` adds nullable unique `client_uuid` to `dtr_entries` + `e_journals`. Replays return the original row (pre-check + `UniqueConstraintViolationException` catch inside `DB::transaction`).
- **Conflict contract** (client branches on it): `409 {error: already_active|no_active_entry, idempotent: true}` = success-with-notice, drop from queue. `403/404/422` = permanent, drop. `429/5xx/network` = keep queued. `401/419` = stop, session lost.
- Offline timestamps: API accepts device `time_in`/`time_out`/`submitted_at` (`before:+5 minutes` guard, ≤24h shift guard, out-after-in guard).

### Frontend
- **`resources/js/offline-sync.js`** — shared module (page + SW): IndexedDB `ojt-sync` (stores: `queue` keyed by uuid, `state`), `queueWrite/replayQueue/queueCount`, `saveDtrState/getDtrState`, `saveDraft/getDraft/clearDraft`, `onSyncChange` broadcast.
- **`resources/js/sw.js` (new custom SW, injectManifest)** — precaches `build/offline.html` (+hashed assets); **NetworkFirst** navigation w/ 5s timeout → offline fallback (NEVER serve cached pages — would mask auth states); CacheFirst `/storage/*`, images, fonts; SWR for hashed assets; `sync` tag `ojt-offline-sync` → `replayQueue()`; message `REPLAY_QUEUE`/`SKIP_WAITING`.
- **`resources/js/app.js`** — `syncStatus` Alpine store (global chip, retry on click, auto-replay on online/load/60s interval, reload-on-sync via `body[data-sync-reload]`); `offlineDtr` component (JSON checkin/checkout, queue-on-failure, state hydration); `journalDraft` (autosave to IndexedDB, restore unless server re-rendered `old()` values, JSON submit, queue-on-failure).
- **Views**: navigation chip (desktop + mobile), `student/dtr/index.blade.php` (Alpine clock card), `student/journals/create.blade.php` (draft banner), `student/journals/index.blade.php` (pending banner).
- **Flag wiring fixed** (Phase 1 loose end): `config/pwa.php` ← `PWA_ENABLED`; layout meta `pwa-enabled`; `resources/js/registerSW.js` (now a real source file, registered via vite input) gates on meta + secure context, scope `/`. Layout no longer references a nonexistent source via `@vite`.

### Build config
- vite inputs: `app.css`, `app.js`, `registerSW.js`, `sw.js`, `offline.html`; `injectManifest.globPatterns` = `build/manifest.json`, `build/assets/*.{js,css}`, `build/*.html` (globDirectory `public`); `devOptions.enabled: false`. Tailwind content now includes `resources/js/**/*.js` (classes live in JS strings).

### Tests
- `tests/Feature/Api/DtrSyncTest.php` (10) + `tests/Feature/Api/JournalSyncTest.php` (6): auth guards, role guards, replay idempotency, conflict mapping, offline timestamps, 422 JSON.

### Next steps
- Flip `PWA_ENABLED=true` when QA passes (Phase 6 rollout).
- Phase 3: QR check-in tokens + scanner page.
- NOTE: `public/offline.html` and Blade `offline` route kept for compat; SW fallback uses the built `build/offline.html` from `resources/js/offline.html`.
