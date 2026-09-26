<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\JobPosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class JobController extends Controller
{
    public function index(Request $request): View
    {
        $jobs = JobPosting::forCompany(Auth::user()->company_id)
            ->withCount('applications')
            ->when($request->filled('q'), fn ($query) => $query->where('title', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('hr.jobs.index', [
            'jobs' => $jobs,
            'filters' => [
                'q' => $request->input('q'),
                'status' => $request->input('status'),
            ],
        ]);
    }

    public function create(): View
    {
        return view('hr.jobs.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'slots' => ['required', 'integer', 'min:1', 'max:99'],
            'status' => ['required', 'in:open,closed'],
        ]);

        JobPosting::create([
            'company_id' => Auth::user()->company_id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'requirements' => $validated['requirements'] ?? null,
            'slots' => $validated['slots'],
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('hr.jobs.index')
            ->with('status', 'Job posting created.');
    }

    public function edit(JobPosting $job): View
    {
        $this->authorizeOwnership($job);

        return view('hr.jobs.edit', compact('job'));
    }

    public function update(Request $request, JobPosting $job): RedirectResponse
    {
        $this->authorizeOwnership($job);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'slots' => ['required', 'integer', 'min:1', 'max:99'],
            'status' => ['required', 'in:open,closed'],
        ]);

        $job->update($validated);

        return redirect()
            ->route('hr.jobs.index')
            ->with('status', 'Job posting updated.');
    }

    public function destroy(JobPosting $job): RedirectResponse
    {
        $this->authorizeOwnership($job);

        $job->delete();

        return redirect()
            ->route('hr.jobs.index')
            ->with('status', 'Job posting removed.');
    }

    private function authorizeOwnership(JobPosting $job): void
    {
        abort_if($job->company_id !== Auth::user()->company_id, 403, 'This job posting belongs to another company.');
    }
}
