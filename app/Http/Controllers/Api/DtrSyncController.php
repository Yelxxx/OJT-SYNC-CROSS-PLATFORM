<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\DomainActionException;
use App\Http\Controllers\Controller;
use App\Models\DtrEntry;
use App\Models\Student;
use App\Services\DtrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DtrSyncController extends Controller
{
    /**
     * Error keys the client treats as "accepted, do not retry".
     *
     * The check-in already happened (e.g. a replay arrived after the server
     * recorded it, or the student clocked in from another device), so the
     * queued write is a no-op — surfaced as a notice, never an error banner.
     */
    private const IDEMPOTENT_CONFLICTS = ['already_active', 'no_active_entry'];

    public function __construct(private readonly DtrService $dtrService) {}

    public function state(): JsonResponse
    {
        $student = $this->student();

        $activeEntry = $student->dtrEntries()->whereNull('time_out')->latest('time_in')->first();

        return response()->json([
            'checked_in' => $activeEntry !== null,
            'active_entry' => $activeEntry ? [
                'id' => $activeEntry->id,
                'date' => $activeEntry->date->toDateString(),
                'time_in' => $activeEntry->time_in->toIso8601String(),
            ] : null,
            'total_hours' => $student->totalHours(),
            'required_hours' => $student->required_hours,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function checkin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_uuid' => ['required', 'uuid'],
            'time_in' => ['nullable', 'date', 'before:+5 minutes'],
        ]);

        try {
            $entry = $this->dtrService->checkin(
                $this->student(),
                clientUuid: $validated['client_uuid'],
                timeIn: isset($validated['time_in']) ? Carbon::parse($validated['time_in']) : null,
            );
        } catch (DomainActionException $e) {
            return $this->errorResponse($e);
        }

        return response()->json([
            'message' => 'Timed in successfully. Have a great shift!',
            'entry' => $this->serializeEntry($entry),
        ], 201);
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_uuid' => ['required', 'uuid'],
            'task_summary' => ['nullable', 'string', 'max:1000'],
            'time_out' => ['nullable', 'date', 'before:+5 minutes'],
        ]);

        try {
            $entry = $this->dtrService->checkout(
                $this->student(),
                clientUuid: $validated['client_uuid'],
                taskSummary: $validated['task_summary'] ?? null,
                timeOut: isset($validated['time_out']) ? Carbon::parse($validated['time_out']) : null,
            );
        } catch (DomainActionException $e) {
            return $this->errorResponse($e);
        }

        return response()->json([
            'message' => 'Timed out. Great work today!',
            'entry' => $this->serializeEntry($entry),
        ], 201);
    }

    private function student(): Student
    {
        $student = Auth::user()?->studentProfile;

        if (! $student) {
            abort(403, 'Only students can record DTR entries.');
        }

        return $student;
    }

    private function serializeEntry(DtrEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'client_uuid' => $entry->client_uuid,
            'date' => $entry->date->toDateString(),
            'time_in' => $entry->time_in->toIso8601String(),
            'time_out' => $entry->time_out?->toIso8601String(),
            'hours' => (float) $entry->hours,
            'task_summary' => $entry->task_summary,
            'checkin_method' => $entry->checkin_method,
        ];
    }

    private function errorResponse(DomainActionException $e): JsonResponse
    {
        return response()->json([
            'error' => $e->errorKey,
            'message' => $e->getMessage(),
            'idempotent' => in_array($e->errorKey, self::IDEMPOTENT_CONFLICTS, true),
        ], $e->status);
    }
}
