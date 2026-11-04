<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Student;
use App\Notifications\StudentEndorsedNotification;
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
            'endorsed_company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'], // fallback compatibility
            'endorsement_note' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'in:pending,active,completed'],
        ]);

        $targetCompanyId = $validated['endorsed_company_id'] ?? $validated['company_id'] ?? null;

        $updateData = [
            'endorsed_company_id' => $targetCompanyId,
            'endorsement_note' => $targetCompanyId ? ($validated['endorsement_note'] ?? $student->endorsement_note) : null,
            'endorsed_at' => $targetCompanyId ? ($student->endorsed_at ?? now()) : null,
        ];

        // Coordinator can only certify completion once required hours are reached,
        // but cannot unilaterally hire or activate a student into a company.
        if (isset($validated['status']) && $validated['status'] === 'completed') {
            $updateData['status'] = 'completed';
        }

        $student->update($updateData);

        $targetCompany = $targetCompanyId ? Company::find($targetCompanyId) : null;
        if ($targetCompany && $student->user) {
            $student->user->notify(new StudentEndorsedNotification(
                student: $student,
                company: $targetCompany,
                endorsementNote: $updateData['endorsement_note'] ?? null
            ));
        }

        $companyName = $targetCompany?->name;
        $message = $companyName
            ? "Student successfully endorsed to {$companyName} with priority recommendation."
            : "Student recommendation cleared.";

        return back()->with('status', $message);
    }
}
