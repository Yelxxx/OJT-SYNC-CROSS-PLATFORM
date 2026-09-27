<?php

use App\Http\Controllers\Api\DtrSyncController;
use App\Http\Controllers\Api\JournalSyncController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Offline Sync API
|--------------------------------------------------------------------------
|
| Session-authenticated JSON endpoints the PWA uses to queue writes while
| offline (see CROSS_PLATFORM_PLAN.md, Phase 2). They intentionally reuse
| the web session + cookie instead of Sanctum tokens: same-origin PWA,
| fewer moving parts. CSRF is skipped for api/* on purpose — Background
| Sync replays originate from the service worker without a token — and is
| compensated by SameSite=lax cookies, session auth, and server-recorded
| idempotency UUIDs on every write.
|
*/

Route::middleware(['auth', 'verified', 'role:student'])->prefix('student')->group(function () {
    Route::get('/dtr/state', [DtrSyncController::class, 'state'])->name('api.student.dtr.state');
    Route::post('/dtr/checkin', [DtrSyncController::class, 'checkin'])->name('api.student.dtr.checkin');
    Route::post('/dtr/checkout', [DtrSyncController::class, 'checkout'])->name('api.student.dtr.checkout');
    Route::post('/journals', [JournalSyncController::class, 'store'])->name('api.student.journals.store');
});
