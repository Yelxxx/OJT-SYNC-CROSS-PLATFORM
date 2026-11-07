<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\InternLeave;
use App\Notifications\LeaveStatusChangedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaveController extends Controller
{
    public function index(Request $request): View
    {
        $company = Auth::user()->company;
        abort_if(! $company, 403, 'Company profile not found.');

        $leaves = InternLeave::query()
            ->whereHas('student', fn ($q) => $q->where('company_id', $company->id))
            ->with('student.user')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = InternLeave::query()
            ->whereHas('student', fn ($q) => $q->where('company_id', $company->id))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('hr.leaves.index', [
            'leaves' => $leaves,
            'counts' => $counts,
            'currentStatus' => $request->input('status'),
        ]);
    }

    public function approve(Request $request, InternLeave $leave): RedirectResponse
    {
        $this->authorizeLeave($leave);

        $leave->update([
            'status' => InternLeave::STATUS_APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        $studentUser = $leave->student->user;
        $studentUser?->notify(new LeaveStatusChangedNotification(
            leave: $leave,
            reviewerName: Auth::user()->name
        ));

        return back()->with('status', 'Leave request approved.');
    }

    public function reject(Request $request, InternLeave $leave): RedirectResponse
    {
        $this->authorizeLeave($leave);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $leave->update([
            'status' => InternLeave::STATUS_REJECTED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        $studentUser = $leave->student->user;
        $studentUser?->notify(new LeaveStatusChangedNotification(
            leave: $leave,
            reviewerName: Auth::user()->name
        ));

        return back()->with('status', 'Leave request marked as rejected.');
    }

    public function download(InternLeave $leave): StreamedResponse
    {
        $this->authorizeLeave($leave);
        abort_if(! $leave->document_path || ! Storage::disk('local')->exists($leave->document_path), 404, 'File not found.');

        return Storage::disk('local')->download($leave->document_path, $leave->document_name);
    }

    private function authorizeLeave(InternLeave $leave): void
    {
        $companyId = Auth::user()->company_id;
        abort_if($leave->student->company_id !== $companyId, 403, 'Unauthorized.');
    }
}
