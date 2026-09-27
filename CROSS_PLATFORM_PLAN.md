# OJT Sync — Cross-Platform Transition Plan (Android + Web)

Goal: make OJT Sync work as an installable Android experience **without a Play Store release and without rewriting the Laravel/Blade app**. iOS is explicitly out of scope.

**Target platforms:** Android (Chrome) + existing web.
**Distribution:** PWA install from Chrome (primary) + directly distributed signed APK via a thin wrapper (secondary). No Play Store, no App Store.

---

## Why PWA-first (and not React Native / Flutter)

All three must-haves are supported by the current stack once the app is a PWA:

| Must-have | PWA path | Native-only feature? |
|---|---|---|
| Offline DTR + journals | Service worker + IndexedDB + Background Sync | No |
| Push notifications | Web Push (VAPID) in Chrome on Android | No |
| QR scan via camera | `getUserMedia` + BarcodeDetector API | No |

A separate mobile codebase would triple the surface area (auth, roles, DTR logic, journals) for zero capability gain on Android-only. The backend stays Laravel; the frontend stays Blade + Alpine + Tailwind, hardened for mobile.

---

## Phase 0 — Baseline & audit (~1 day) ✅ DONE

- [x] Confirm production HTTPS (PWA + camera + push all require a secure context — Render provides this by default).
- [x] Audit the mobile-critical flows: DTR check-in/out, journal create, notifications, application status.
- [x] Verify `public/build` asset versioning (Vite hashed filenames are already committed per the Dockerfile) — this is what the service worker will precache.
- [x] Pick a feature-flag mechanism — `PWA_ENABLED` env → `config/pwa.php` → `pwa-enabled` meta tag; service worker ships dark until QA passes.

## Phase 1 — PWA foundation: installable app (~2 days) ✅ DONE

- [x] `manifest.webmanifest` (Blade-served route so it can embed `VITE_APP_NAME`): `name`, `short_name`, `display: standalone`, `theme_color`/`background_color` from the Tailwind palette, maskable + regular icons (192/512).
- [x] `start_url: /dashboard` — the existing role-aware `DashboardController` redirects students/coordinators/HR correctly, so one manifest serves all three roles.
- [x] Service worker via **`vite-plugin-pwa`** — custom SW (`resources/js/sw.js`, injectManifest) that precaches the offline fallback page; runtime caches `/storage/*`, images, fonts; hashed assets revalidate in the background; auto-update (skipWaiting + clientsClaim).
- [x] `beforeinstallprompt` handled by the `installPrompt` Alpine component in the layout; `appinstalled` cleans up.
- [ ] CI: Lighthouse PWA installability check (still manual — run against the Render deploy).

**Definition of done:** Chrome on Android shows install prompt; installed app opens standalone with its own icon; offline navigation shows the fallback page.

## Phase 2 — Offline support: DTR + journals (~3–4 days) ✅ DONE (2026-09-27)

Offline writes use JSON endpoints the page (or service worker) can queue.

- [x] Create `routes/api.php` sharing the authenticated web session (no Sanctum):
  - `POST /api/student/dtr/checkin`, `POST /api/student/dtr/checkout`
  - `POST /api/student/journals`
  - Rules extracted into `app/Services/DtrService.php` + `app/Services/JournalService.php`; web and API controllers reuse them (no duplicated rules).
  - CSRF exempted for `api/*` in `bootstrap/app.php` (Background Sync has no token) — compensated by SameSite=lax session cookies, session auth, and idempotency keys.
- [x] Client offline layer (Alpine + IndexedDB, `resources/js/offline-sync.js`):
  - queue offline check-in/checkout/journals with timestamps + client-generated UUID idempotency keys; server records them via unique index on `client_uuid` (migration `2026_09_27_000001`);
  - Background Sync (`sync` tag `ojt-offline-sync`) replays the queue when connectivity returns; fallbacks: replay-on-load, `online` event, 60s interval;
  - sync status surfaced in the UI (global chip: "2 waiting to sync" / "Syncing…" / "Synced").
- [x] Cache last-known DTR state (`GET /api/student/dtr/state` → IndexedDB) so an offline visit shows real data.
- [x] Conflict policy: **server is source of truth.** `already_active` / `no_active_entry` conflicts return `{idempotent: true}` → no-op with a user-visible notice, never an error banner. Replay can never duplicate an entry (unique index + transactional re-check).
- [x] Journal drafts autosave locally (`journalDraft` Alpine component) so offline writing never loses text; server-re-rendered values always win over a stored draft.

**Definition of done:** airplane-mode check-in → reconnect → entry appears exactly once with correct totals — covered by `tests/Feature/Api/DtrSyncTest.php` + `tests/Feature/Api/JournalSyncTest.php`.

## Phase 3 — QR check-in via camera (~3 days)

The existing `ShareController::jobQr` encodes job share URLs; check-in needs its own scheme.

- [ ] Backend: per-company **signed, rotating check-in token** (e.g. 60-second TOTP-style window, or 15-minute rotating signed URL) rendered as a QR the company displays at the worksite. Verify server-side in the check-in endpoint; reject expired/reused tokens.
  - Route: `POST /api/dtr/scan-checkin` — proves the student was physically at the company instead of trusting the button.
- [ ] Frontend: scanner page at `student/dtr/scan`:
  - `BarcodeDetector` API (supported in Chrome on Android) with `zxing-wasm` fallback;
  - `getUserMedia` camera permission flow with a graceful "camera unavailable → manual check-in still works" path;
  - decode → auto-submit the check-in with the scanned token.
- [ ] Company side: page to display the live rotating QR fullscreen (auto-refreshes each window).
- [ ] Tests: token expiry, reuse rejection, cross-company token rejection.

**Definition of done:** student scans the company's screen and is checked in within one camera open.

## Phase 4 — Push notifications (~3 days)

A `notifications` table already exists and app notifications flow through Laravel notifications — extend, don't replace.

- [ ] Install `laravel-notification-channels/webpush`; add a `push_subscriptions` table (endpoint, keys, expiration) linked to users.
- [ ] Generate and store VAPID keys (`php artisan webpush:vapid`); add keys to `.env.example` + Render env vars.
- [ ] Add `->toWebPush(...)` (or a shared webpush trait) to the existing notification classes for the events that matter on mobile:
  - new job posting (students),
  - application status change (student),
  - DTR verified (student),
  - new journal / DTR submitted (coordinator, HR).
- [ ] Client:
  - contextual permission prompt (ask right after first check-in, not on page load);
  - subscription endpoint (`POST /api/push/subscribe`) storing the subscription for the logged-in user;
  - service worker `push` + `notificationclick` handlers that deep-link to the relevant page (`/student/dtr`, `/coordinator/journals`, …).
- [ ] Queue note: pushes must go through the queue worker (already runs via `queue:listen`) so a Render free-tier cold start doesn't block the HTTP response.

**Definition of done:** phone shows a lock-screen notification on application status change; tapping opens the installed app on the right page.

## Phase 5 — Android APK without the Play Store (~2 days, optional but recommended)

Primary install path stays "Add to Home Screen" from Chrome — nothing to distribute, updates are automatic via the service worker. For users who want a "real app" icon/experience:

- [ ] Wrap the deployed PWA in a thin native shell. Two equivalent options — pick one:
  - **Bubblewrap/PWABuilder** → generates a signed APK from the live manifest (Trusted Web Activity); or
  - **Capacitor** project pointing at the production URL → debug/release APK via Android Studio.
- [ ] Generate a release keystore, store it + passwords outside the repo, document the signing command.
- [ ] Distribute the signed APK directly (download link behind student/coordinator login, or plain file share). Recipients enable "install unknown apps" once.
- [ ] Document the update story: PWA updates ship with the site; APK shell is only rebuilt if the wrapper itself changes (rare).
- [ ] Push inside the wrapper still uses the same web-push subscription (Chrome WebView) — no Firebase project needed.

**Definition of done:** a tester installs the APK from a link and gets the same experience + notifications as the browser install.

## Phase 6 — QA, tests, rollout (~2 days)

- [x] Extend the test suite: API check-in/out + idempotency, offline timestamps, journal replay (✅ `tests/Feature/Api/`); remaining: scan-token validation (Phase 3), push subscription endpoints (Phase 4).
- [ ] Device matrix: one mid-range Android phone (Chrome), plus Chrome DevTools device emulation for regressions.
- [ ] Run the full offline + scan + push checklist on the Render deployment (free tier cold-start behavior included).
- [ ] Rollout behind `PWA_ENABLED`: students → coordinators → HR. Watch logs for sync-replay conflicts during the first week.

---

## Key decisions & risks

| Decision/Risk | Call |
|---|---|
| No SPA rewrite | Blade + Alpine stays; offline layer is additive (IndexedDB queue + JSON endpoints) |
| `start_url` for 3 roles | Single `/dashboard` entry; existing role redirect handles it |
| Session auth for API endpoints | Reuse the web session/CSRF rather than introducing Sanctum tokens — fewer moving parts, same-origin PWA; CSRF skipped for `api/*` only, compensated as above |
| SQLite local / Postgres prod | No schema change beyond `client_uuid` columns; idempotency keys use a unique index on both |
| Render free tier cold starts | Push and queue work must be async (queue worker) or latency after cold start will look like breakage |
| BarcodeDetector support | Chrome on Android yes; `zxing-wasm` fallback covers the rest |
| Resume storage on local disk | Fine for single-instance Render; revisit if horizontal scaling ever lands (S3 disk) |
| No Play Store | No store review, no account, but users must sideload the APK and updates don't self-apply to the wrapper — acceptable per scope |
| SW navigation caching | NetworkFirst (never the precached welcome page) so logged-in/logged-out states stay truthful; offline fallback page only when the network fails |

## Effort summary

| Phase | Estimate |
|---|---|
| 0. Audit | 1 day ✅ |
| 1. PWA installable | 2 days ✅ |
| 2. Offline writes | 3–4 days ✅ |
| 3. QR check-in | 3 days |
| 4. Push | 3 days |
| 5. Sideload APK | 2 days |
| 6. QA + rollout | 2 days |
| **Total** | **~16–17 working days** |

Phases 1–2 deliver the core transition; 3–4 deliver the must-have capabilities; 5 is optional polish; 6 gates the flag flip.
