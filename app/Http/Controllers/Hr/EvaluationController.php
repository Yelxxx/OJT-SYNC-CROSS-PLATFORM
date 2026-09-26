<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Evaluation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    public function index(): View
    {
        $jobIds = Auth::user()->company->jobPostings()->pluck('id');

        $evaluations = Evaluation::whereIn('job_posting_id', $jobIds)
            ->with('student.user', 'jobPosting')
            ->latest()
            ->paginate(15);

        return view('hr.evaluations.index', compact('evaluations'));
    }

    public function create(): View
    {
        $jobIds = Auth::user()->company->jobPostings()->pluck('id');

        
        $hired = Application::whereIn('job_posting_id', $jobIds)
            ->hired()
            ->with('student.user', 'jobPosting')
            ->get()
            ->groupBy('student_id')
            ->map(fn ($apps) => $apps->first());

        
        $evaluatedStudentIds = Evaluation::whereIn('job_posting_id', $jobIds)->pluck('student_id');

        return view('hr.evaluations.create', [
            'hired' => $hired->reject(fn ($app) => $evaluatedStudentIds->contains($app->student_id)),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'job_posting_id' => ['required', 'integer', 'exists:job_postings,id'],
        ]);

        $companyId = Auth::user()->company_id;

        $hired = Application::where('student_id', $validated['student_id'])
            ->where('job_posting_id', $validated['job_posting_id'])
            ->hired()
            ->whereHas('jobPosting', fn ($q) => $q->where('company_id', $companyId))
            ->exists();

        abort_if(! $hired, 403, 'This student was not hired for a job in your company.');

        $exists = Evaluation::where('student_id', $validated['student_id'])
            ->where('job_posting_id', $validated['job_posting_id'])
            ->exists();

        abort_if($exists, 409, 'An evaluation already exists for this student.');

        $evaluation = Evaluation::create([
            'student_id' => $validated['student_id'],
            'job_posting_id' => $validated['job_posting_id'],
            'token' => Str::random(40),
        ]);

        return redirect()
            ->route('hr.evaluations.show', $evaluation)
            ->with('status', 'Evaluation created. Share the link with the student supervisor.');
    }

    public function show(Evaluation $evaluation): View
    {
        abort_if(
            $evaluation->jobPosting->company_id !== Auth::user()->company_id,
            403,
            'This evaluation belongs to another company.',
        );

        return view('hr.evaluations.show', [
            'evaluation' => $evaluation->load('student.user', 'jobPosting'),
        ]);
    }
}
