<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Student;
use App\Models\User;
use App\Notifications\CoordinatorProgressNudgeNotification;
use App\Notifications\NewDirectMessageNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PacingController extends Controller
{
    /**
     * Display the Early Warning & Pacing Radar dashboard.
     */
    public function index(Request $request): View
    {
        $coordinator = Auth::user();
        $selectedFilter = $request->query('filter', 'all'); // all, at_risk, slow, on_track, completed

        $students = $coordinator->supervisedStudents()
            ->with(['user', 'company', 'dtrEntries'])
            ->withTotalHours()
            ->get()
            ->map(function (Student $student) {
                $student->pacing = $student->computePacingAnalysis();
                return $student;
            });

        $counts = [
            'all' => $students->count(),
            'at_risk' => $students->where('pacing.status', 'at_risk')->count(),
            'slow' => $students->where('pacing.status', 'slow')->count(),
            'on_track' => $students->where('pacing.status', 'on_track')->count(),
            'completed' => $students->where('pacing.status', 'completed')->count(),
        ];

        $filteredStudents = match ($selectedFilter) {
            'at_risk' => $students->where('pacing.status', 'at_risk'),
            'slow' => $students->where('pacing.status', 'slow'),
            'on_track' => $students->where('pacing.status', 'on_track'),
            'completed' => $students->where('pacing.status', 'completed'),
            default => $students,
        };

        return view('coordinator.pacing.index', [
            'students' => $filteredStudents->values(),
            'counts' => $counts,
            'currentFilter' => $selectedFilter,
        ]);
    }

    /**
     * Update student target timeline dates.
     */
    public function updateTimeline(Request $request, Student $student): RedirectResponse
    {
        abort_unless(
            Auth::user()->supervisedStudents()->where('students.id', $student->id)->exists(),
            403,
            'Unauthorized student access.'
        );

        $validated = $request->validate([
            'deployment_date' => ['nullable', 'date'],
            'target_end_date' => ['nullable', 'date', 'after_or_equal:deployment_date'],
            'required_hours' => ['required', 'integer', 'min:50', 'max:1000'],
        ]);

        $student->update($validated);

        return back()->with('status', "Timeline and hours target updated for {$student->user->name}.");
    }

    /**
     * Send an Intervention Nudge to the student (and optionally their HR mentor).
     */
    public function nudge(Request $request, Student $student): RedirectResponse
    {
        $coordinator = Auth::user();
        abort_unless(
            $coordinator->supervisedStudents()->where('students.id', $student->id)->exists(),
            403,
            'Unauthorized student access.'
        );

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'notify_hr' => ['nullable', 'boolean'],
        ]);

        $pacing = $student->computePacingAnalysis();
        $customMessage = $validated['message'];

        // 1. Notify Student via Notification Bell
        if ($student->user) {
            $student->user->notify(new CoordinatorProgressNudgeNotification(
                student: $student,
                coordinatorName: $coordinator->name,
                nudgeMessage: $customMessage,
                pacingData: $pacing
            ));

            // 2. Also log as official direct message thread
            $directMsg = Message::create([
                'sender_id' => $coordinator->id,
                'recipient_id' => $student->user->id,
                'student_id' => $student->id,
                'body' => "⚠️ [Pacing Notice from Practicum Coordinator]:\n" . $customMessage,
            ]);
            $student->user->notify(new NewDirectMessageNotification($directMsg));
        }

        // 3. Optional: Alert Company HR Mentor
        if (! empty($validated['notify_hr']) && $student->company_id) {
            $hrUsers = User::where('company_id', $student->company_id)->get();
            foreach ($hrUsers as $hr) {
                $hr->notify(new CoordinatorProgressNudgeNotification(
                    student: $student,
                    coordinatorName: $coordinator->name,
                    nudgeMessage: "Progress Advisory for Intern {$student->user->name}: {$customMessage}",
                    pacingData: $pacing
                ));

                // Direct message to HR
                $hrMsg = Message::create([
                    'sender_id' => $coordinator->id,
                    'recipient_id' => $hr->id,
                    'student_id' => $student->id,
                    'body' => "⚠️ [Intern Progress Inquiry - {$student->user->name}]:\n" . $customMessage,
                ]);
                $hr->notify(new NewDirectMessageNotification($hrMsg));
            }
        }

        $student->update(['last_nudge_at' => now()]);

        return back()->with('status', "Intervention notice successfully sent to {$student->user->name}.");
    }
}
