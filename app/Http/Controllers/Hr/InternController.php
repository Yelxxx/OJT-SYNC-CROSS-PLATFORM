<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Evaluation;
use App\Models\InternChecklistItem;
use App\Models\JobPosting;
use App\Models\Student;
use App\Notifications\ChecklistItemStatusChangedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InternController extends Controller
{
    public function index(): View
    {
        $company = Auth::user()->company;

        abort_if(! $company, 403, 'Your account is not linked to a company.');

        $jobIds = JobPosting::forCompany($company->id)->pluck('id');

        // Most recent hired application per student, so the page shows the
        // position the student was actually hired for.
        $hiredApplications = Application::whereIn('job_posting_id', $jobIds)
            ->hired()
            ->with(['student.user', 'jobPosting'])
            ->get()
            ->sortByDesc('applied_at')
            ->unique('student_id')
            ->values();

        $studentIds = $hiredApplications->pluck('student_id');

        $evaluatedStudentIds = Evaluation::whereIn('student_id', $studentIds)
            ->pluck('student_id');

        $interns = Student::whereIn('id', $studentIds)
            ->withTotalHours()
            ->with('checklistItems')
            ->get()
            ->map(function (Student $student) use ($hiredApplications, $evaluatedStudentIds): array {
                $application = $hiredApplications->firstWhere('student_id', $student->id);
                $checklist = $student->checklistItems;

                return [
                    'student' => $student,
                    'application' => $application,
                    'totalHours' => $student->totalHours(),
                    'progressPercent' => $student->hoursProgressPercent(),
                    'evaluationComplete' => $evaluatedStudentIds->contains($student->id),
                    'checklistDone' => $checklist->where('is_complete', true)->count(),
                    'checklistTotal' => $checklist->count(),
                ];
            })
            ->sortByDesc(fn (array $intern) => $intern['progressPercent'])
            ->values();

        $stats = [
            'interns' => $interns->count(),
            'completed' => $interns->where('progressPercent', '>=', 100)->count(),
            'awaitingEvaluation' => $interns->where('evaluationComplete', false)->count(),
        ];

        return view('hr.interns.index', [
            'company' => $company,
            'interns' => $interns,
            'stats' => $stats,
        ]);
    }

    public function toggleChecklistItem(Request $request, InternChecklistItem $checklistItem): RedirectResponse
    {
        $this->authorizeStudent($checklistItem->student_id);

        $checklistItem->update([
            'is_complete' => ! $checklistItem->is_complete,
            'completed_at' => ! $checklistItem->is_complete ? now() : null,
        ]);

        $studentUser = $checklistItem->student->user;
        if ($studentUser) {
            $studentUser->notify(new ChecklistItemStatusChangedNotification(
                item: $checklistItem,
                companyName: Auth::user()->company->name ?? 'Company HR'
            ));
        }

        return back()->with('status', $checklistItem->is_complete
            ? '"'.$checklistItem->title.'" marked complete.'
            : '"'.$checklistItem->title.'" reopened.');
    }

    public function addChecklistItem(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeStudent($student->id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $student->checklistItems()->create(['title' => $validated['title']]);

        return back()->with('status', 'Requirement added to the checklist.');
    }

    public function downloadChecklistDocument(InternChecklistItem $checklistItem): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorizeStudent($checklistItem->student_id);
        abort_if(! $checklistItem->file_path || ! \Illuminate\Support\Facades\Storage::disk('local')->exists($checklistItem->file_path), 404, 'File not found.');

        return \Illuminate\Support\Facades\Storage::disk('local')->download($checklistItem->file_path, $checklistItem->file_name);
    }

    private function authorizeStudent(int $studentId): void
    {
        $companyId = Auth::user()->company_id;

        $belongsToCompany = Application::where('student_id', $studentId)
            ->hired()
            ->whereHas('jobPosting', fn ($q) => $q->where('company_id', $companyId))
            ->exists();

        abort_if(! $belongsToCompany, 403, 'This intern was not hired by your company.');
    }
}
