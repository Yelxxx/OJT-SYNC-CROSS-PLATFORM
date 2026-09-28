<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\CheckinTokenException;
use App\Exceptions\DomainActionException;
use App\Http\Controllers\Controller;
use App\Services\CheckinTokenService;
use App\Services\DtrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * QR scan check-in (CROSS_PLATFORM_PLAN.md, Phase 3).
 *
 * POST /api/dtr/scan-checkin { payload } — the student's scanner page
 * submits the decoded QR payload. The server verifies the rotating token,
 * proves the student belongs to the company that signed it, and clocks them
 * in with checkin_method = 'qr' so HR can see the entry was scan-proven.
 */
class ScanCheckinController extends Controller
{
    private const IDEMPOTENT_CONFLICTS = ['already_active', 'no_active_entry'];

    public function __construct(
        private readonly CheckinTokenService $tokens,
        private readonly DtrService $dtrService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // ojtsync://checkin?company=..&window=..&token=..
            'payload' => ['required', 'string', 'max:500'],
        ]);

        $student = Auth::user()?->studentProfile;

        if (! $student) {
            return response()->json([
                'error' => 'not_student',
                'message' => 'Only students can scan the check-in QR.',
                'idempotent' => false,
            ], 403);
        }

        try {
            ['company_id' => $tokenCompanyId, 'window' => $tokenWindow] = $this->tokens->verify($validated['payload'], $student->id);
        } catch (CheckinTokenException $e) {
            return response()->json([
                'error' => $e->errorKey,
                'message' => $e->getMessage(),
                'idempotent' => false,
            ], $e->status);
        }

        if ((int) $student->company_id !== $tokenCompanyId) {
            $this->tokens->releaseNonce($tokenCompanyId, $tokenWindow, $student->id);

            return response()->json([
                'error' => 'wrong_company',
                'message' => 'This check-in code belongs to another company.',
                'idempotent' => false,
            ], 403);
        }

        try {
            $entry = $this->dtrService->checkin($student, checkinMethod: 'qr');
        } catch (DomainActionException $e) {
            // The token was never consumed by a successful check-in, so the
            // student may rescan the same (still valid) code after fixing
            // whatever blocked them.
            $this->tokens->releaseNonce($tokenCompanyId, $tokenWindow, $student->id);

            return response()->json([
                'error' => $e->errorKey,
                'message' => $e->getMessage(),
                'idempotent' => in_array($e->errorKey, self::IDEMPOTENT_CONFLICTS, true),
            ], $e->status);
        }

        return response()->json([
            'message' => 'Timed in via QR. Have a great shift!',
            'entry' => [
                'id' => $entry->id,
                'date' => $entry->date->toDateString(),
                'time_in' => $entry->time_in->toIso8601String(),
                'checkin_method' => $entry->checkin_method,
            ],
        ], 201);
    }
}
