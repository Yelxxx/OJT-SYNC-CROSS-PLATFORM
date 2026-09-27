<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JobPosting;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $company = Auth::user()->company;

        abort_if(! $company, 403, 'Your account is not linked to a company.');

        $jobIds = JobPosting::forCompany($company->id)->pluck('id');

        $stats = [
            'jobs' => JobPosting::forCompany($company->id)->count(),
            'openJobs' => JobPosting::forCompany($company->id)->open()->count(),
            'applications' => Application::whereIn('job_posting_id', $jobIds)->count(),
            'hired' => Application::whereIn('job_posting_id', $jobIds)->hired()->count(),
            'pendingReview' => Application::whereIn('job_posting_id', $jobIds)->pending()->count(),
        ];

        $recentApplications = Application::whereIn('job_posting_id', $jobIds)
            ->with('student.user', 'jobPosting')
            ->latest('applied_at')
            ->limit(6)
            ->get();

        $statusCounts = Application::whereIn('job_posting_id', $jobIds)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $applicationStatusChart = collect(Application::STATUSES)
            ->map(fn (string $label, string $key) => [
                'status' => $key,
                'label' => $label,
                'value' => (int) ($statusCounts[$key] ?? 0),
            ])
            ->filter(fn ($item) => $item['value'] > 0)
            ->values();

        // NB: job_postings already has a real `applications_count` counter
        // column (maintained on apply/withdraw), so we must NOT also use
        // ->withCount('applications') here — on Postgres the duplicate name
        // makes `order by applications_count` ambiguous (SQLSTATE 42702).
        $applicationsPerJob = JobPosting::forCompany($company->id)
            ->orderByDesc('applications_count')
            ->limit(5)
            ->get()
            ->map(fn (JobPosting $job) => [
                'label' => $job->title,
                'value' => (int) $job->applications_count,
            ]);

        return view('hr.dashboard', [
            'company' => $company,
            'stats' => $stats,
            'recentApplications' => $recentApplications,
            'applicationStatusChart' => $applicationStatusChart,
            'applicationsPerJob' => $applicationsPerJob,
        ]);
    }
}
