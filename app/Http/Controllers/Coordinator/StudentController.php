<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    public function update(Request $request, Student $student): RedirectResponse
    {
        abort_if(
            ! Auth::user()->supervisedStudents()->where('students.id', $student->id)->exists(),
            403,
            'This student is not assigned to you.',
        );

        $validated = $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'status' => ['required', 'in:pending,active,completed'],
        ]);

        $student->update($validated);

        return back()->with('status', 'Student updated.');
    }
}
