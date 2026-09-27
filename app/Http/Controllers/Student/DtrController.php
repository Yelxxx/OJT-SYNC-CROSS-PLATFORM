<?php

namespace App\Http\Controllers\Student;

use App\Exceptions\DomainActionException;
use App\Http\Controllers\Controller;
use App\Services\DtrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DtrController extends Controller
{
    public function __construct(private readonly DtrService $dtrService) {}

    public function index(): View
    {
        $student = Auth::user()->studentProfile;

        $entries = $student->dtrEntries()
            ->latest('date')
            ->latest('time_in')
            ->limit(30)
            ->get();

        $activeEntry = $student->dtrEntries()->whereNull('time_out')->latest('time_in')->first();

        return view('student.dtr.index', [
            'entries' => $entries,
            'activeEntry' => $activeEntry,
            'totalHours' => $student->totalHours(),
            'requiredHours' => $student->required_hours,
        ]);
    }

    public function checkin(Request $request): RedirectResponse
    {
        $student = Auth::user()->studentProfile;

        try {
            $this->dtrService->checkin($student);
        } catch (DomainActionException $e) {
            abort($e->status, $e->getMessage());
        }

        return back()->with('status', 'Timed in successfully. Have a great shift!');
    }

    public function checkout(Request $request): RedirectResponse
    {
        $student = Auth::user()->studentProfile;

        $validated = $request->validate([
            'task_summary' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->dtrService->checkout($student, taskSummary: $validated['task_summary'] ?? null);
        } catch (DomainActionException $e) {
            abort($e->status, $e->getMessage());
        }

        return back()->with('status', 'Timed out. Great work today!');
    }
}
