<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\JobPosting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class JobController extends Controller
{
    public function index(Request $request): View
    {
        $student = Auth::user()->studentProfile;
        $studentProgram = $student?->program;
        $appliedIds = $student ? $student->applications()->pluck('job_posting_id') : collect();

        $jobs = JobPosting::with('company')
            ->open()
            ->whereNotIn('id', $appliedIds)
            // Automated Matchmaking: only jobs open to all or matching the student's program
            ->when($studentProgram, function ($query, $program) {
                $query->where(function ($q) use ($program) {
                    $q->whereNull('eligible_programs')
                        ->orWhereJsonContains('eligible_programs', \App\Constants\AcademicProgram::PROGRAM_ALL)
                        ->orWhereJsonContains('eligible_programs', $program);
                });
            })
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%')->orWhere('description', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('company'), fn ($query) => $query->where('company_id', $request->integer('company')))
            ->latest()
            ->paginate(9)
            ->withQueryString();

        return view('student.jobs.index', [
            'jobs' => $jobs,
            'studentProgram' => $studentProgram,
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
            'filters' => [
                'q' => $request->input('q'),
                'company' => $request->input('company'),
            ],
        ]);
    }

    public function show(JobPosting $job): View
    {
        abort_if($job->status !== 'open', 404);

        $student = Auth::user()->studentProfile;
        $studentProgram = $student?->program;

        // Auto-check eligibility
        if (! $job->isEligibleFor($studentProgram)) {
            abort(403, 'This internship opening is not open to your degree program ('.\App\Constants\AcademicProgram::label($studentProgram).').');
        }

        $alreadyApplied = $student ? $student->applications()->where('job_posting_id', $job->id)->exists() : false;

        return view('student.jobs.show', [
            'job' => $job->load('company'),
            'alreadyApplied' => $alreadyApplied,
        ]);
    }
}
