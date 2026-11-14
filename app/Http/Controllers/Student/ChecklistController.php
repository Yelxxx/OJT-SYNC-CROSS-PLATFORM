<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\InternChecklistItem;
use App\Models\User;
use App\Notifications\ChecklistDocumentUploadedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChecklistController extends Controller
{
    public function index(): View
    {
        $student = Auth::user()->studentProfile;
        abort_if(! $student, 403, 'Student profile not found.');

        // Seed defaults if any are missing
        InternChecklistItem::seedDefaultsForStudent($student->id);

        $checklistItems = $student->checklistItems()->get();
        $company = $student->company;
        $clearanceData = $student->checkClearanceReadiness();

        return view('student.checklist.index', [
            'checklistItems' => $checklistItems,
            'company' => $company,
            'completedCount' => $clearanceData['approved_count'],
            'totalCount' => $clearanceData['total_count'],
            'clearanceData' => $clearanceData,
        ]);
    }

    public function upload(Request $request, InternChecklistItem $item): RedirectResponse
    {
        $student = Auth::user()->studentProfile;
        abort_if($item->student_id !== $student?->id, 403, 'Unauthorized checklist item.');

        $validated = $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'], // up to 10MB
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // If old file exists, clean it up
        if ($item->file_path && Storage::disk('local')->exists($item->file_path)) {
            Storage::disk('local')->delete($item->file_path);
        }

        $file = $request->file('document');
        $fileName = $file->getClientOriginalName();
        $storedPath = $file->store("checklists/{$student->id}", 'local');

        $item->update([
            'file_path' => $storedPath,
            'file_name' => $fileName,
            'status' => InternChecklistItem::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'notes' => $validated['notes'] ?? $item->notes,
            'rejection_reason' => null,
        ]);

        // Notify Coordinator
        if ($student->coordinator) {
            $student->coordinator->notify(new ChecklistDocumentUploadedNotification(
                item: $item,
                studentName: Auth::user()->name
            ));
        }

        // Notify Company HR users
        if ($student->company_id) {
            $hrUsers = User::where('company_id', $student->company_id)->get();
            foreach ($hrUsers as $hrUser) {
                $hrUser->notify(new ChecklistDocumentUploadedNotification(
                    item: $item,
                    studentName: Auth::user()->name
                ));
            }
        }

        return back()->with('status', "Document '{$fileName}' uploaded successfully.");
    }

    public function download(InternChecklistItem $item): StreamedResponse
    {
        $student = Auth::user()->studentProfile;
        abort_if($item->student_id !== $student?->id, 403, 'Unauthorized checklist item.');
        abort_if(! $item->file_path || ! Storage::disk('local')->exists($item->file_path), 404, 'File not found.');

        return Storage::disk('local')->download($item->file_path, $item->file_name);
    }
}
