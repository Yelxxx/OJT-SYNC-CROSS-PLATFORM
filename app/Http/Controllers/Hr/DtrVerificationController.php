<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\DtrEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DtrVerificationController extends Controller
{
    public function index(Request $request): View
    {
        $company = Auth::user()->company;

        abort_if(! $company, 403, 'Your account is not linked to a company.');

        $entries = DtrEntry::query()
            ->whereHas('student', fn ($q) => $q->where('company_id', $company->id))
            ->whereNotNull('time_out')
            ->with('student.user')
            ->when($request->filled('status'), fn ($q) => $q->where('verification_status', $request->string('status')))
            ->latest('date')
            ->paginate(15)
            ->withQueryString();

        $counts = DtrEntry::query()
            ->whereHas('student', fn ($q) => $q->where('company_id', $company->id))
            ->whereNotNull('time_out')
            ->selectRaw('verification_status, count(*) as total')
            ->groupBy('verification_status')
            ->pluck('total', 'verification_status');

        return view('hr.dtr.index', [
            'entries' => $entries,
            'counts' => $counts,
            'filters' => ['status' => $request->input('status')],
        ]);
    }

    public function verify(Request $request, DtrEntry $entry): RedirectResponse
    {
        $this->authorizeEntry($entry);

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(DtrEntry::VERIFICATION_STATUSES))],
        ]);

        $entry->update([
            'verification_status' => $validated['status'],
            'verified_by' => $validated['status'] === DtrEntry::VERIFICATION_UNVERIFIED ? null : Auth::id(),
            'verified_at' => $validated['status'] === DtrEntry::VERIFICATION_UNVERIFIED ? null : now(),
        ]);

        $message = match ($validated['status']) {
            DtrEntry::VERIFICATION_VERIFIED => 'Hours verified.',
            DtrEntry::VERIFICATION_DISPUTED => 'Entry disputed — the student and coordinator will be notified to resolve it.',
            default => 'Verification removed.',
        };

        return back()->with('status', $message);
    }

    private function authorizeEntry(DtrEntry $entry): void
    {
        abort_if(
            $entry->student->company_id !== Auth::user()->company_id,
            403,
            'This DTR entry belongs to a student of another company.',
        );
    }
}
