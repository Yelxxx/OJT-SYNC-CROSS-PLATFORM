<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\JournalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class JournalSyncController extends Controller
{
    public function __construct(private readonly JournalService $journalService) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_uuid' => ['required', 'uuid'],
            'week' => ['required', 'integer', 'min:1', 'max:52'],
            'title' => ['required', 'string', 'max:255'],
            'entry' => ['required', 'string', 'max:5000'],
            'submitted_at' => ['nullable', 'date', 'before:+5 minutes'],
        ]);

        $journal = $this->journalService->submit(
            $this->student(),
            [
                'week' => $validated['week'],
                'title' => $validated['title'],
                'entry' => $validated['entry'],
            ],
            clientUuid: $validated['client_uuid'],
            submittedAt: isset($validated['submitted_at'])
                ? Carbon::parse($validated['submitted_at'])
                : null,
        );

        return response()->json([
            'message' => 'Journal entry submitted!',
            'journal' => [
                'id' => $journal->id,
                'client_uuid' => $journal->client_uuid,
                'week' => $journal->week,
                'title' => $journal->title,
                'submitted_at' => $journal->submitted_at->toIso8601String(),
            ],
        ], 201);
    }

    private function student(): Student
    {
        $student = Auth::user()?->studentProfile;

        if (! $student) {
            abort(403, 'Only students can submit journal entries.');
        }

        return $student;
    }
}
