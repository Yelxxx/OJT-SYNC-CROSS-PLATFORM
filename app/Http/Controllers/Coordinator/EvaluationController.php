<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    public function index(Request $request): View
    {
        $evaluations = Evaluation::query()
            ->whereHas('student', fn ($query) => $query->where('coordinator_id', $request->user()->id))
            ->with('student.user', 'jobPosting.company')
            ->when($request->filled('q'), fn ($query) => $query->whereHas('student.user', fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%')))
            ->latest('submitted_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('coordinator.evaluations.index', [
            'evaluations' => $evaluations,
            'q' => $request->input('q'),
        ]);
    }
}
