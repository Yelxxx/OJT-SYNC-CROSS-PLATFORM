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

        // CSRF is validated everywhere. (The api/* exemption from the offline
        // sync era is gone along with the QR scan-checkin API — the app is
        // standard web forms again.)
    })
    // Must stay present even though nothing custom is configured: calling
    // withExceptions() is what registers the exception handler binding.
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
