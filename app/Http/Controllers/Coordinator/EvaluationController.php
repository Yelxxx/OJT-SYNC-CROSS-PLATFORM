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

    public function print(Request $request, Evaluation $evaluation): View
    {
        abort_if(
            $evaluation->student->coordinator_id !== $request->user()->id,
            403,
            'This evaluation belongs to a student not assigned to you.',
        );

        abort_unless($evaluation->isComplete(), 404, 'This evaluation has not been completed yet.');

        return view('evaluations.print', [
            'evaluation' => $evaluation->load('student.user', 'jobPosting.company'),
            'student' => $evaluation->student,
            'company' => $evaluation->jobPosting?->company,
        ]);
    }
}
