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
        $appliedIds = Auth::user()->studentProfile->applications()->pluck('job_posting_id');

        $jobs = JobPosting::with('company')
            ->open()
            ->whereNotIn('id', $appliedIds)
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%')->orWhere('description', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('company'), fn ($query) => $query->where('company_id', $request->integer('company')))
            ->latest()
            ->paginate(9)
            ->withQueryString();

        return view('student.jobs.index', [
            'jobs' => $jobs,
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

        $alreadyApplied = Auth::user()
            ->studentProfile
            ->applications()
            ->where('job_posting_id', $job->id)
            ->exists();

        return view('student.jobs.show', [
            'job' => $job->load('company'),
            'alreadyApplied' => $alreadyApplied,
        ]);
    }
}
