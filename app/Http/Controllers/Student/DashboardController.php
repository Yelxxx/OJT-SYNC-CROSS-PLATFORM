<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $student = Auth::user()->studentProfile()->with('company')->withTotalHours()->firstOrFail();

        $applications = $student->applications()
            ->with('jobPosting.company')
            ->latest('applied_at')
            ->get();

        $statusCounts = $applications->groupBy('status')->map->count();

        $applicationStatusChart = collect(\App\Models\Application::STATUSES)
            ->map(fn (string $label, string $key) => [
                'status' => $key,
                'label' => $label,
                'value' => $statusCounts[$key] ?? 0,
            ])
            ->filter(fn ($item) => $item['value'] > 0)
            ->values();

        return view('student.dashboard', [
            'student' => $student,
            'applications' => $applications,
            'totalHours' => $student->totalHours(),
            'applicationStatusChart' => $applicationStatusChart,
        ]);
    }
}
