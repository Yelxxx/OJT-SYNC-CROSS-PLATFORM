<?php

use App\Http\Middleware\EnsureHired;
use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Render terminates TLS at its edge and forwards to the container over
        // plain HTTP, so Laravel must trust X-Forwarded-Proto to generate
        // https:// asset and route URLs (otherwise the browser blocks the
        // http:// assets as mixed content).
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->alias([
            'role' => EnsureRole::class,
            'hired' => EnsureHired::class,
        ]);

        // Offline sync endpoints (routes/api.php) must accept posts from both
        // the page and the service worker's Background Sync replay, which has
        // no access to the CSRF token. This is safe because the session cookie
        // is SameSite=lax (cross-site form posts never carry it), every
        // replayed write carries a server-recorded idempotency UUID, and the
        // endpoints are JSON-only and session-authenticated.
        $middleware->validateCsrfTokens(except: ['api/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
