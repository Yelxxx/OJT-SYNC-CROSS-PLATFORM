<?php

namespace App\Models;

use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'coordinator_id',
        'company_id',
        'endorsed_company_id',
        'endorsement_note',
        'endorsed_at',
        'student_no',
        'program',
        'year_level',
        'required_hours',
        'status',
        'is_cleared_for_deployment',
        'cleared_at',
        'cleared_by',
    ];

    protected $casts = [
        'required_hours' => 'integer',
        'endorsed_at' => 'datetime',
        'is_cleared_for_deployment' => 'boolean',
        'cleared_at' => 'datetime',
    ];

    public function clearedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cleared_by');
    }

    /**
     * Check if the student meets all conditions to be deployed to company duty.
     */
    public function checkClearanceReadiness(): array
    {
        $checklist = $this->checklistItems;
        $totalRequired = $checklist->count();
        $approvedCount = $checklist->filter->isApproved()->count();

        $allDocsApproved = $totalRequired > 0 && $approvedCount >= $totalRequired;
        $companyHasMoa = $this->company?->hasValidMoa() ?? false;
        $isCleared = (bool) $this->is_cleared_for_deployment;

        return [
            'is_cleared' => $isCleared,
            'all_docs_approved' => $allDocsApproved,
            'approved_count' => $approvedCount,
            'total_count' => $totalRequired,
            'company_has_moa' => $companyHasMoa,
            'company_moa_status' => $this->company?->moa_status ?? 'none',
            'can_be_cleared' => $allDocsApproved && $this->company_id !== null,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function endorsedCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'endorsed_company_id');
    }

    public function isEndorsedFor(int|Company $company): bool
    {
        $companyId = $company instanceof Company ? $company->id : $company;

        return $this->endorsed_company_id !== null && $this->endorsed_company_id === (int) $companyId;
    }

    public function hasEndorsement(): bool
    {
        return $this->endorsed_company_id !== null;
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function dtrEntries(): HasMany
    {
        return $this->hasMany(DtrEntry::class);
    }

    public function eJournals(): HasMany
    {
        return $this->hasMany(EJournal::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(InternChecklistItem::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(InternLeave::class);
    }

    /**
     * Eager-load the summed completed hours into a `total_hours` attribute.
     */
    public function scopeWithTotalHours(Builder $query): Builder
    {
        return $query->withSum(['dtrEntries as total_hours' => function (Builder $q) {
            $q->whereNotNull('time_out');
        }], 'hours');
    }

    /**
     * Total completed OJT hours across all clocked-out DTR entries.
     */
    public function totalHours(): float
    {
        if (array_key_exists('total_hours', $this->attributes) && $this->attributes['total_hours'] !== null) {
            return (float) $this->attributes['total_hours'];
        }

        return (float) $this->dtrEntries()
            ->whereNotNull('time_out')
            ->sum('hours');
    }

    public function hoursProgressPercent(): int
    {
        $target = max(1, $this->required_hours);

        return (int) round(($this->totalHours() / $target) * 100);
    }

    /**
     * Active/hired application for this student.
     */
    public function hiredApplication(): ?Application
    {
        return $this->applications()
            ->hired()
            ->with('jobPosting.company')
            ->latest('applied_at')
            ->first();
    }

    /**
     * Get the student's OJT competencies and verified experience portfolio.
     */
    public function competencyPortfolio(): array
    {
        $hiredApp = $this->hiredApplication();
        $job = $hiredApp?->jobPosting;
        $skills = $job?->skills ?? [];

        // Fallback default competencies if none explicitly tagged
        if (empty($skills)) {
            $skills = [
                'Professional Work Ethics & Punctuality',
                'Core Departmental Operations',
                'Business Communication & Team Collaboration',
                'Task Execution & Problem Solving',
            ];
        }

        $verifiedHours = (float) $this->dtrEntries()
            ->where('verification_status', DtrEntry::VERIFICATION_VERIFIED)
            ->sum('hours');

        $totalHours = $this->totalHours();
        $skillCount = max(1, count($skills));

        // Distributed verified hours across competencies
        $hoursPerSkill = round($verifiedHours / $skillCount, 1);

        // Retrieve latest evaluation if complete
        $evaluation = $this->evaluations()->where('token_used', true)->latest('submitted_at')->first();

        return [
            'job' => $job,
            'company' => $this->company ?? $job?->company,
            'skills' => $skills,
            'verifiedHours' => $verifiedHours,
            'totalHours' => $totalHours,
            'requiredHours' => $this->required_hours ?? 300,
            'hoursPerSkill' => $hoursPerSkill,
            'evaluation' => $evaluation,
            'completionPercent' => $this->hoursProgressPercent(),
        ];
    }
}
