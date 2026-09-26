<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\EJournal;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JournalController extends Controller
{
    public function index(Request $request): View
    {
        $journals = EJournal::with('student.user')
            ->when($request->filled('q'), fn ($query) => $query->whereHas('student.user', fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%')))
            ->latest('submitted_at')
            ->paginate(20)
            ->withQueryString();

        return view('coordinator.journals.index', [
            'journals' => $journals,
            'q' => $request->input('q'),
        ]);
    }
}
