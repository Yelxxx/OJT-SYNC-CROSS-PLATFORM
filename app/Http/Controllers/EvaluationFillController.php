<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvaluationFillController extends Controller
{
    public function show(string $token): View
    {
        $evaluation = Evaluation::with('student.user', 'jobPosting.company')
            ->where('token', $token)
            ->firstOrFail();

        if ($evaluation->isComplete()) {
            return view('evaluations.completed', ['evaluation' => $evaluation]);
        }

        return view('evaluations.fill', ['evaluation' => $evaluation]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        
        Evaluation::where('token', $token)->firstOrFail();

        $validated = $request->validate([
            'punctuality' => ['required', 'integer', 'between:1,5'],
            'quality_of_work' => ['required', 'integer', 'between:1,5'],
            'initiative' => ['required', 'integer', 'between:1,5'],
            'communication' => ['required', 'integer', 'between:1,5'],
            'comments' => ['nullable', 'string', 'max:2000'],
        ]);

        $overall = round(array_sum([
            $validated['punctuality'],
            $validated['quality_of_work'],
            $validated['initiative'],
            $validated['communication'],
        ]) / 4, 2);

        
        
        $claimed = Evaluation::where('token', $token)
            ->where('token_used', false)
            ->update([
                'punctuality' => $validated['punctuality'],
                'quality_of_work' => $validated['quality_of_work'],
                'initiative' => $validated['initiative'],
                'communication' => $validated['communication'],
                'comments' => $validated['comments'] ?? null,
                'overall_score' => $overall,
                'token_used' => true,
                'submitted_at' => now(),
            ]);

        abort_unless($claimed, 409, 'This evaluation has already been submitted.');

        return redirect()
            ->route('evaluations.show', $token)
            ->with('status', 'Thank you! Your evaluation has been submitted.');
    }
}
