<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\InternChecklistItem;
use App\Models\Student;
use App\Notifications\ChecklistItemReviewedNotification;
use App\Notifications\StudentDeploymentClearedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClearanceController extends Controller
{
    /**
     * View all student advisees' pre-deployment clearance dossiers.
     */
    public function index(): View
    {
        $coordinator = Auth::user();

        $students = $coordinator->supervisedStudents()
            ->with(['user', 'company', 'checklistItems'])
            ->get()
            ->map(function (Student $student) {
                $student->clearance_data = $student->checkClearanceReadiness();
                return $student;
            });

        return view('coordinator.clearance.index', [
            'students' => $students,
            'clearedCount' => $students->where('is_cleared_for_deployment', true)->count(),
            'pendingCount' => $students->where('is_cleared_for_deployment', false)->count(),
        ]);
    }

    /**
     * Show an individual student's clearance dossier.
     */
    public function show(Student $student): View
    {
        abort_unless(
            Auth::user()->supervisedStudents()->where('students.id', $student->id)->exists(),
            403,
            'This student is not assigned to your supervision.'
        );

        // Ensure default items exist
        InternChecklistItem::seedDefaultsForStudent($student->id);

        $student->load(['user', 'company', 'checklistItems.reviewer']);
        $clearanceData = $student->checkClearanceReadiness();

        return view('coordinator.clearance.show', [
            'student' => $student,
            'checklistItems' => $student->checklistItems,
            'company' => $student->company,
            'clearanceData' => $clearanceData,
        ]);
    }

    /**
     * Review an individual checklist item (Approve or Reject).
     */
    public function reviewItem(Request $request, InternChecklistItem $item): RedirectResponse
    {
        $student = $item->student;
        abort_unless(
            Auth::user()->supervisedStudents()->where('students.id', $student->id)->exists(),
            403,
            'Unauthorized checklist item.'
        );

        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $status = $validated['status'];
        $isComplete = ($status === 'approved');

        $item->update([
            'status' => $status,
            'is_complete' => $isComplete,
            'completed_at' => $isComplete ? now() : null,
            'rejection_reason' => $status === 'rejected' ? $validated['rejection_reason'] : null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        // Notify student
        if ($student->user) {
            $student->user->notify(new ChecklistItemReviewedNotification(
                item: $item,
                reviewerName: Auth::user()->name,
                status: $status,
                rejectionReason: $validated['rejection_reason'] ?? null
            ));
        }

        $message = $status === 'approved'
            ? "'{$item->title}' approved successfully."
            : "'{$item->title}' marked as revision requested.";

        return back()->with('status', $message);
    }

    /**
     * Grant or Revoke Official Deployment Clearance for a student.
     */
    public function toggleDeploymentClearance(Request $request, Student $student): RedirectResponse
    {
        abort_unless(
            Auth::user()->supervisedStudents()->where('students.id', $student->id)->exists(),
            403,
            'This student is not assigned to your supervision.'
        );

        $validated = $request->validate([
            'is_cleared' => ['required', 'boolean'],
        ]);

        $isCleared = (bool) $validated['is_cleared'];

        $student->update([
            'is_cleared_for_deployment' => $isCleared,
            'cleared_at' => $isCleared ? now() : null,
            'cleared_by' => $isCleared ? Auth::id() : null,
        ]);

        if ($student->user) {
            $student->user->notify(new StudentDeploymentClearedNotification(
                student: $student,
                coordinatorName: Auth::user()->name,
                isCleared: $isCleared
            ));
        }

        $msg = $isCleared
            ? "Student {$student->user->name} has been officially CLEARED for deployment. DTR time clock is now unlocked."
            : "Deployment clearance for {$student->user->name} has been revoked.";

        return back()->with('status', $msg);
    }

    /**
     * Download an uploaded clearance document.
     */
    public function downloadItem(InternChecklistItem $item): StreamedResponse
    {
        $student = $item->student;
        abort_unless(
            Auth::user()->supervisedStudents()->where('students.id', $student->id)->exists(),
            403,
            'Unauthorized checklist item.'
        );

        abort_if(! $item->file_path || ! Storage::disk('local')->exists($item->file_path), 404, 'File not found.');

        return Storage::disk('local')->download($item->file_path, $item->file_name);
    }
}

