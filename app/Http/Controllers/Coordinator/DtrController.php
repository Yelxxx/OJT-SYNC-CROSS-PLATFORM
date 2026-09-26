<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\DtrEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DtrController extends Controller
{
    public function index(Request $request): View
    {
        $entries = DtrEntry::with('student.user')
            ->when($request->filled('q'), fn ($query) => $query->whereHas('student.user', fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%')))
            ->latest('date')
            ->latest('time_in')
            ->paginate(25)
            ->withQueryString();

        return view('coordinator.dtr.index', [
            'entries' => $entries,
            'q' => $request->input('q'),
        ]);
    }
}
