<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Company;
use App\Models\Student;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $students = Student::with(['user', 'company'])->withTotalHours()->latest()->get();

        $stats = [
            'students' => $students->count(),
            'activeStudents' => $students->where('status', 'active')->count(),
            'placedStudents' => $students->filter(fn (Student $s) => $s->company_id !== null)->count(),
            'applications' => Application::count(),
        ];

        $recentApplications = Application::with('student.user', 'jobPosting.company')
            ->latest('applied_at')
            ->limit(6)
            ->get();

        $studentStatusChart = collect(['pending' => 'Pending', 'active' => 'Active', 'completed' => 'Completed'])
            ->map(fn (string $label, string $key) => [
                'status' => $key,
                'label' => $label,
                'value' => $students->where('status', $key)->count(),
            ])
            ->filter(fn ($item) => $item['value'] > 0)
            ->values();

        $applicationsPerCompany = Application::query()
            ->join('job_postings', 'applications.job_posting_id', '=', 'job_postings.id')
            ->join('companies', 'job_postings.company_id', '=', 'companies.id')
            ->selectRaw('companies.name as label, count(*) as value')
            ->groupBy('companies.id', 'companies.name')
            ->orderByDesc('value')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label,
                'value' => (int) $row->value,
            ]);

        return view('coordinator.dashboard', [
            'students' => $students,
            'stats' => $stats,
            'recentApplications' => $recentApplications,
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
            'studentStatusChart' => $studentStatusChart,
            'applicationsPerCompany' => $applicationsPerCompany,
        ]);
    }
}
