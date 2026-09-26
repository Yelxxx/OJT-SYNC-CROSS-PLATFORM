<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JobPosting;
use App\Models\User;
use App\Notifications\NewApplicationReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(): View
    {
        $applications = Auth::user()
            ->studentProfile
            ->applications()
            ->with('jobPosting.company')
            ->latest('applied_at')
            ->get();

        return view('student.applications.index', compact('applications'));
    }

    public function store(Request $request, JobPosting $job): RedirectResponse
    {
        $student = Auth::user()->studentProfile;

        abort_if($job->status !== 'open', 403, 'This job posting is no longer open.');
        abort_if(
            $student->applications()->where('job_posting_id', $job->id)->exists(),
            403,
            'You have already applied to this job.',
        );

        $validated = $request->validate([
            'cover_letter' => ['nullable', 'string', 'max:2000'],
            'resume' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:2048'],
        ]);

        // Stored on the private local disk so resumes are only reachable
        // through the authenticated download route.
        $resumePath = $request->hasFile('resume')
            ? $request->file('resume')->store('resumes')
            : null;

        $application = Application::create([
            'job_posting_id' => $job->id,
            'student_id' => $student->id,
            'resume_path' => $resumePath,
            'cover_letter' => $validated['cover_letter'] ?? null,
            'status' => Application::STATUS_PENDING,
            'applied_at' => now(),
        ]);

        $job->increment('applications_count');

        $job->company->hrUsers()
            ->where('is_active', true)
            ->each(fn (User $hr) => $hr->notify(new NewApplicationReceived($application)));

        return redirect()
            ->route('student.applications.index')
            ->with('status', 'Application submitted successfully!');
    }

    public function destroy(Application $application): RedirectResponse
    {
        $student = Auth::user()->studentProfile;

        abort_if($application->student_id !== $student->id, 403, 'This application does not belong to you.');
        abort_if($application->status !== Application::STATUS_PENDING, 403, 'Only pending applications can be withdrawn.');

        $application->delete();

        $application->jobPosting()->decrement('applications_count');

        return back()->with('status', 'Application withdrawn.');
    }
}
