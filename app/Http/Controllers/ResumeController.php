<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResumeController extends Controller
{
    public function download(Application $application): StreamedResponse
    {
        $user = Auth::user();

        abort_if(! $application->resume_path, 404, 'No resume was uploaded for this application.');

        $isOwningStudent = $user->isStudent()
            && $user->studentProfile
            && $user->studentProfile->id === $application->student_id;

        $isCompanyHr = $user->isHr()
            && $user->company_id === $application->jobPosting->company_id;

        abort_unless($isOwningStudent || $isCompanyHr, 403, 'You do not have access to this resume.');

        // New uploads live on the private local disk; older uploads were
        // stored on the public disk, so fall back to that for legacy files.
        $disk = Storage::exists($application->resume_path) ? 'local' : 'public';

        abort_unless(Storage::disk($disk)->exists($application->resume_path), 404, 'Resume file not found.');

        return Storage::disk($disk)->download($application->resume_path);
    }
}
