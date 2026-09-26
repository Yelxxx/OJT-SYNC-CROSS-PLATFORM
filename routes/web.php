<?php

use App\Http\Controllers\Coordinator\DashboardController as CoordinatorDashboardController;
use App\Http\Controllers\Coordinator\DtrController as CoordinatorDtrController;
use App\Http\Controllers\Coordinator\EvaluationController as CoordinatorEvaluationController;
use App\Http\Controllers\Coordinator\JournalController as CoordinatorJournalController;
use App\Http\Controllers\Coordinator\StudentController as CoordinatorStudentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvaluationFillController;
use App\Http\Controllers\Hr\ApplicationController as HrApplicationController;
use App\Http\Controllers\Hr\CompanyProfileController as HrCompanyProfileController;
use App\Http\Controllers\Hr\DashboardController as HrDashboardController;
use App\Http\Controllers\Hr\DtrVerificationController as HrDtrVerificationController;
use App\Http\Controllers\Hr\EvaluationController as HrEvaluationController;
use App\Http\Controllers\Hr\InternController as HrInternController;
use App\Http\Controllers\Hr\JobController as HrJobController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\Student\ApplicationController as StudentApplicationController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\DtrController as StudentDtrController;
use App\Http\Controllers\Student\JobController as StudentJobController;
use App\Http\Controllers\Student\JournalController as StudentJournalController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/manifest.webmanifest', function () {
    return response()->json([
        'name' => env('VITE_APP_NAME', 'OJT Sync'),
        'short_name' => env('VITE_APP_NAME', 'OJT Sync'),
        'description' => 'OJT Sync - On-the-Job Training Management System',
        'start_url' => '/dashboard',
        'display' => 'standalone',
        'background_color' => '#33954B',
        'theme_color' => '#33954B',
        'orientation' => 'portrait-primary',
        'icons' => [
            [
                'src' => asset('images/logo-192.png'),
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any maskable'
            ],
            [
                'src' => asset('images/logo-512.png'),
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any maskable'
            ]
        ]
    ]);
});

Route::get('/offline', function () {
    return view('offline');
})->name('offline');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/applications/{application}/resume', [ResumeController::class, 'download'])
        ->name('applications.resume');
    Route::get('/notifications/{notification}/read', [NotificationController::class, 'read'])
        ->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])
        ->name('notifications.markAllRead');
});

Route::middleware(['auth', 'verified', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
        Route::get('/jobs', [StudentJobController::class, 'index'])->name('jobs.index');
        Route::get('/jobs/{job}', [StudentJobController::class, 'show'])->name('jobs.show');
        Route::post('/jobs/{job}/apply', [StudentApplicationController::class, 'store'])->name('jobs.apply');
        Route::get('/applications', [StudentApplicationController::class, 'index'])->name('applications.index');
        Route::delete('/applications/{application}', [StudentApplicationController::class, 'destroy'])->name('applications.destroy');
        Route::get('/dtr', [StudentDtrController::class, 'index'])->name('dtr.index');
        Route::post('/dtr/checkin', [StudentDtrController::class, 'checkin'])->name('dtr.checkin');
        Route::post('/dtr/checkout', [StudentDtrController::class, 'checkout'])->name('dtr.checkout');
        Route::get('/journals', [StudentJournalController::class, 'index'])->name('journals.index');
        Route::get('/journals/create', [StudentJournalController::class, 'create'])->name('journals.create');
        Route::post('/journals', [StudentJournalController::class, 'store'])->name('journals.store');
        Route::delete('/journals/{journal}', [StudentJournalController::class, 'destroy'])->name('journals.destroy');
    });

Route::middleware(['auth', 'verified', 'role:coordinator'])
    ->prefix('coordinator')
    ->name('coordinator.')
    ->group(function () {
        Route::get('/dashboard', [CoordinatorDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dtr', [CoordinatorDtrController::class, 'index'])->name('dtr.index');
        Route::get('/journals', [CoordinatorJournalController::class, 'index'])->name('journals.index');
        Route::get('/evaluations', [CoordinatorEvaluationController::class, 'index'])->name('evaluations.index');
        Route::patch('/students/{student}', [CoordinatorStudentController::class, 'update'])->name('students.update');
    });

Route::middleware(['auth', 'verified', 'role:hr'])
    ->prefix('hr')
    ->name('hr.')
    ->group(function () {
        Route::get('/dashboard', [HrDashboardController::class, 'index'])->name('dashboard');
        Route::get('/interns', [HrInternController::class, 'index'])->name('interns.index');
        Route::get('/interns/checklist/{checklistItem}/toggle', [HrInternController::class, 'toggleChecklistItem'])->name('interns.checklist.toggle');
        Route::post('/interns/{student}/checklist', [HrInternController::class, 'addChecklistItem'])->name('interns.checklist.add');
        Route::get('/company/edit', [HrCompanyProfileController::class, 'edit'])->name('company.edit');
        Route::patch('/company', [HrCompanyProfileController::class, 'update'])->name('company.update');
        Route::get('/dtr', [HrDtrVerificationController::class, 'index'])->name('dtr.index');
        Route::post('/dtr/{entry}/verify', [HrDtrVerificationController::class, 'verify'])->name('dtr.verify');
        Route::get('/jobs/{job}/qr', [ShareController::class, 'jobQr'])->name('jobs.qr');
        Route::resource('jobs', HrJobController::class)->except(['show']);
        Route::get('/applications', [HrApplicationController::class, 'index'])->name('applications.index');
        Route::get('/applications/{application}', [HrApplicationController::class, 'show'])->name('applications.show');
        Route::patch('/applications/{application}', [HrApplicationController::class, 'update'])->name('applications.update');
        Route::post('/applications/{application}/notes', [HrApplicationController::class, 'addNote'])->name('applications.notes.store');
        Route::get('/evaluations', [HrEvaluationController::class, 'index'])->name('evaluations.index');
        Route::get('/evaluations/create', [HrEvaluationController::class, 'create'])->name('evaluations.create');
        Route::post('/evaluations', [HrEvaluationController::class, 'store'])->name('evaluations.store');
        Route::get('/evaluations/{evaluation}', [HrEvaluationController::class, 'show'])->name('evaluations.show');
    });

Route::get('/evaluations/{token}', [EvaluationFillController::class, 'show'])
    ->middleware('throttle:10,1')
    ->name('evaluations.show');
Route::post('/evaluations/{token}', [EvaluationFillController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('evaluations.store');

Route::get('/share/{token}', [ShareController::class, 'company'])
    ->name('share.company');

require __DIR__.'/auth.php';
