<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\InternLeave;
use App\Models\User;
use App\Notifications\NewLeaveRequestNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaveController extends Controller
{
    public function index(): View
    {
        $student = Auth::user()->studentProfile;
        abort_if(! $student, 403, 'Student profile not found.');

        $leaves = $student->leaves()->latest()->paginate(10);

        return view('student.leaves.index', [
            'leaves' => $leaves,
            'company' => $student->company,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $student = Auth::user()->studentProfile;
        abort_if(! $student, 403, 'Student profile not found.');
        abort_if(! $student->company_id, 403, 'You must be assigned to a company to file leave.');

        $validated = $request->validate([
            'leave_type' => ['required', 'in:'.implode(',', array_keys(InternLeave::TYPES))],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:1000'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ]);

        $filePath = null;
        $fileName = null;

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $fileName = $file->getClientOriginalName();
            $filePath = $file->store("leaves/{$student->id}", 'local');
        }

        $leave = $student->leaves()->create([
            'leave_type' => $validated['leave_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'],
            'document_path' => $filePath,
            'document_name' => $fileName,
            'status' => InternLeave::STATUS_PENDING,
        ]);

        // Notify HR representatives
        $hrUsers = User::where('company_id', $student->company_id)->get();
        foreach ($hrUsers as $hrUser) {
            $hrUser->notify(new NewLeaveRequestNotification(
                leave: $leave,
                studentName: Auth::user()->name
            ));
        }

        // Also notify the coordinator if assigned
        if ($student->coordinator_id) {
            $coordinatorUser = User::find($student->coordinator_id);
            $coordinatorUser?->notify(new NewLeaveRequestNotification(
                leave: $leave,
                studentName: Auth::user()->name
            ));
        }

        return back()->with('status', 'Absence / Leave request submitted successfully.');
    }

    public function download(InternLeave $leave): StreamedResponse
    {
        $student = Auth::user()->studentProfile;
        abort_if($leave->student_id !== $student?->id, 403, 'Unauthorized.');
        abort_if(! $leave->document_path || ! Storage::disk('local')->exists($leave->document_path), 404, 'File not found.');

        return Storage::disk('local')->download($leave->document_path, $leave->document_name);
    }
}
