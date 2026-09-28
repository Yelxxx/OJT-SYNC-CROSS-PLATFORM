<?php

use App\Http\Controllers\Api\ScanCheckinController;
use App\Http\Middleware\EnsureHired;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API (JSON) endpoints
|--------------------------------------------------------------------------
|
| QR scan check-in (CROSS_PLATFORM_PLAN.md, Phase 3): the student's scanner
| page submits the decoded rotating token. CSRF is skipped for api/* (see
| bootstrap/app.php); this endpoint is session-authenticated, JSON-only and
| idempotent, which keeps a double-submitted scan harmless.
|
*/

Route::middleware(['auth', 'verified', 'role:student', EnsureHired::class])
    ->post('/dtr/scan-checkin', [ScanCheckinController::class, 'store'])
    ->name('api.dtr.scan-checkin');
