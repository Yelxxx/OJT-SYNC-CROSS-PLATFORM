<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * DTR (time in / out, scan) is a hired-student feature: a student gains a
 * company (and with it the ability to clock hours) only when HR marks an
 * application hired — the ApplicationObserver then sets student.company_id.
 * Unhired students get a friendly redirect instead of cryptic 403s from the
 * check-in service's no_company guard.
 */
class EnsureHired
{
    public function handle(Request $request, Closure $next): Response
    {
        $student = $request->user()?->studentProfile;

        if (! $student || $student->company_id === null) {
            $message = 'You can record time in / out once a company has hired you.';

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'not_hired',
                    'message' => $message,
                    'idempotent' => false,
                ], 403);
            }

            // Non-GET pages/forms can't redirect meaningfully back — keep the
            // historical 403 contract for them.
            if (! $request->isMethod('get')) {
                abort(403, $message);
            }

            return redirect()
                ->route('student.applications.index')
                ->with('error', $message);
        }

        return $next($request);
    }
}
