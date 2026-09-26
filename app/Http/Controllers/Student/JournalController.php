<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\EJournal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class JournalController extends Controller
{
    public function index(): View
    {
        $journals = Auth::user()
            ->studentProfile
            ->eJournals()
            ->latest('week')
            ->get();

        return view('student.journals.index', compact('journals'));
    }

    public function create(): View
    {
        $journals = Auth::user()->studentProfile->eJournals()->get();

        return view('student.journals.create', compact('journals'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'week' => ['required', 'integer', 'min:1', 'max:52'],
            'title' => ['required', 'string', 'max:255'],
            'entry' => ['required', 'string', 'max:5000'],
        ]);

        Auth::user()->studentProfile->eJournals()->create(
            array_merge($validated, ['submitted_at' => now()])
        );

        return redirect()
            ->route('student.journals.index')
            ->with('status', 'Journal entry submitted!');
    }

    public function destroy(EJournal $journal): RedirectResponse
    {
        abort_if($journal->student_id !== Auth::user()->studentProfile->id, 403);

        $journal->delete();

        return back()->with('status', 'Journal entry removed.');
    }
}
