<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\DtrEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DtrController extends Controller
{
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
        ]);
    }

    public function checkin(Request $request): RedirectResponse
    {
        $student = Auth::user()->studentProfile;

        abort_if(! $student->company_id, 403, 'You cannot clock in until a company has been assigned to you.');
        abort_if($student->status !== 'active', 403, 'Your OJT status must be active to clock in.');

        abort_if(
            $student->dtrEntries()->whereNull('time_out')->exists(),
            409,
            'You already have an active time-in. Please time out first.',
        );

        DtrEntry::create([
            'student_id' => $student->id,
            'date' => now()->toDateString(),
            'time_in' => now(),
            'hours' => 0,
            'checkin_method' => 'manual',
        ]);

        return back()->with('status', 'Timed in successfully. Have a great shift!');
    }

    public function checkout(Request $request): RedirectResponse
    {
        $student = Auth::user()->studentProfile;

        $entry = $student->dtrEntries()->whereNull('time_out')->latest('time_in')->first();

        abort_if(! $entry, 409, 'You have no active time-in to time out from.');

        $validated = $request->validate([
            'task_summary' => ['nullable', 'string', 'max:1000'],
        ]);

        $timeOut = now();

        $entry->update([
            'time_out' => $timeOut,
            'hours' => round(abs($timeOut->diffInSeconds($entry->time_in)) / 3600, 2),
            'task_summary' => $validated['task_summary'] ?? null,
        ]);

        return back()->with('status', 'Timed out. Great work today!');
    }
}
