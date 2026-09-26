<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\JobPosting;
use App\Notifications\ApplicationStatusChanged;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Auth::user()->company_id;

        $jobs = JobPosting::forCompany($companyId)->latest()->get();
        $jobIds = $jobs->pluck('id');

        $applications = Application::whereIn('job_posting_id', $jobIds)
            ->with('student.user', 'jobPosting')
            ->when($request->filled('job') && $request->integer('job') > 0, fn ($q) => $q->where('job_posting_id', $request->integer('job')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('applied_at')
            ->paginate(10)
            ->withQueryString();

        return view('hr.applications.index', [
            'jobs' => $jobs,
            'applications' => $applications,
            'filters' => [
                'job' => $request->input('job'),
                'status' => $request->input('status'),
            ],
        ]);
    }

    public function show(Request $request, Application $application): View
    {
        abort_if(
            $application->jobPosting->company_id !== Auth::user()->company_id,
            403,
            'This application belongs to another company.',
        );

        $application->load('student.user', 'jobPosting.company', 'notes.user', 'statusHistory.user');

        return view('hr.applications.show', ['application' => $application]);
    }

    public function update(Request $request, Application $application): RedirectResponse
    {
        abort_if(
            $application->jobPosting->company_id !== Auth::user()->company_id,
            403,
            'This application belongs to another company.',
        );

        $validated = $request->validate([
            'status' => ['required', 'in:pending,interview,hired,rejected'],
            'rejection_reason' => [
                'nullable',
                'string',
                'max:1000',
                'required_if:status,'.Application::STATUS_REJECTED,
            ],
            'interview_at' => [
                'nullable',
                'date',
                'after:now',
                'required_if:status,'.Application::STATUS_INTERVIEW,
            ],
        ]);

        $validated['rejection_reason'] =
            $validated['rejection_reason'] ?? ($validated['status'] === Application::STATUS_REJECTED ? $application->rejection_reason : null);

        // Never allow hiring beyond the job's slot count.
        if (
            $validated['status'] === Application::STATUS_HIRED
            && $application->status !== Application::STATUS_HIRED
            && ! $application->jobPosting->hasOpenSlots()
        ) {
            return back()->with('error', "All {$application->jobPosting->slots} slots for this position are already filled. Increase the slots or reject this applicant.");
        }

        $previousStatus = $application->status;

        DB::transaction(function () use ($application, $validated, $previousStatus): void {
            $application->update([
                'status' => $validated['status'],
                'rejection_reason' => $validated['rejection_reason'],
                'interview_at' => $validated['interview_at'] ?? ($validated['status'] === Application::STATUS_INTERVIEW ? $application->interview_at : null),
            ]);

            if ($validated['status'] !== $previousStatus) {
                ApplicationStatusHistory::create([
                    'application_id' => $application->id,
                    'user_id' => Auth::id(),
                    'from_status' => $previousStatus,
                    'to_status' => $validated['status'],
                    'note' => $validated['rejection_reason'],
                ]);

                $application->student->user->notify(
                    new ApplicationStatusChanged($application, $previousStatus),
                );
            }
        });

        $message = match ($validated['status']) {
            Application::STATUS_INTERVIEW => 'Application moved to interview.',
            Application::STATUS_HIRED => 'Student marked as hired!',
            Application::STATUS_REJECTED => 'Application rejected.',
            default => 'Application status updated.',
        };

        return back()->with('status', $message);
    }

    public function addNote(Request $request, Application $application): RedirectResponse
    {
        abort_if(
            $application->jobPosting->company_id !== Auth::user()->company_id,
            403,
            'This application belongs to another company.',
        );

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $application->notes()->create([
            'user_id' => Auth::id(),
            'body' => $validated['body'],
        ]);

        return back()->with('status', 'Note added.');
    }
}
