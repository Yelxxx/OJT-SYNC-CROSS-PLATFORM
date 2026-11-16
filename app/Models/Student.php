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
        'deployment_date',
        'target_end_date',
        'last_nudge_at',
    ];

    protected $casts = [
        'required_hours' => 'integer',
        'endorsed_at' => 'datetime',
        'is_cleared_for_deployment' => 'boolean',
        'cleared_at' => 'datetime',
        'deployment_date' => 'date',
        'target_end_date' => 'date',
        'last_nudge_at' => 'datetime',
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
     * Compute real-time OJT pacing and early warning risk indicators.
     *
     * @return array{
     *     status: 'completed'|'at_risk'|'slow'|'on_track'|'unplaced',
     *     status_label: string,
     *     badge_class: string,
     *     progress_percent: int,
     *     remaining_hours: float,
     *     completed_hours: float,
     *     target_end_date: ?\Illuminate\Support\Carbon,
     *     days_remaining: int,
     *     weeks_remaining: float,
     *     required_pace_weekly: float,
     *     days_since_last_clockin: ?int,
     *     risk_reasons: list<string>
     * }
     */
    public function computePacingAnalysis(): array
    {
        $completedHours = $this->totalHours();
        $targetHours = max(1, $this->required_hours);
        $remainingHours = max(0.0, $targetHours - $completedHours);
        $progressPercent = (int) round(($completedHours / $targetHours) * 100);

        if ($progressPercent >= 100 || $this->status === 'completed') {
            return [
                'status' => 'completed',
                'status_label' => 'Completed',
                'badge_class' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                'progress_percent' => 100,
                'remaining_hours' => 0.0,
                'completed_hours' => $completedHours,
                'target_end_date' => $this->target_end_date,
                'days_remaining' => 0,
                'weeks_remaining' => 0.0,
                'required_pace_weekly' => 0.0,
                'days_since_last_clockin' => null,
                'risk_reasons' => [],
            ];
        }

        if (! $this->company_id) {
            return [
                'status' => 'unplaced',
                'status_label' => 'Unplaced',
                'badge_class' => 'bg-gray-100 text-gray-700 border-gray-300',
                'progress_percent' => 0,
                'remaining_hours' => (float) $targetHours,
                'completed_hours' => 0.0,
                'target_end_date' => $this->target_end_date,
                'days_remaining' => 0,
                'weeks_remaining' => 0.0,
                'required_pace_weekly' => 0.0,
                'days_since_last_clockin' => null,
                'risk_reasons' => ['No host company assigned yet.'],
            ];
        }

        // Determine target completion date (defaults to 10 weeks from deployment or 60 days from now)
        $targetEnd = $this->target_end_date
            ?? ($this->deployment_date ? $this->deployment_date->copy()->addWeeks(10) : now()->addDays(60));

        $daysRemaining = max(1, (int) now()->diffInDays($targetEnd, false));
        $weeksRemaining = max(0.2, $daysRemaining / 7.0);
        $requiredPaceWeekly = round($remainingHours / $weeksRemaining, 1);

        // Check recent activity: date of latest DTR entry
        $latestDtr = $this->dtrEntries()->latest('date')->first();
        $daysSinceLastClockin = $latestDtr
            ? (int) now()->diffInDays($latestDtr->date)
            : ($this->deployment_date ? (int) now()->diffInDays($this->deployment_date) : 14);

        $riskReasons = [];

        // Inactivity check: No clock-in for 7+ days
        if ($daysSinceLastClockin >= 7) {
            $riskReasons[] = "Inactive: No DTR clock-in logged in the last {$daysSinceLastClockin} days.";
        }

        // Velocity check: Unrealistic pace needed to finish before semester ends (> 45 hrs/week)
        if ($requiredPaceWeekly > 45.0) {
            $riskReasons[] = "Critical Velocity: Needs {$requiredPaceWeekly} hrs/week to complete before deadline.";
        }

        // Timeline check: Low completion (<40%) with less than 4 weeks left
        if ($weeksRemaining <= 4.0 && $progressPercent < 40) {
            $riskReasons[] = "Late Schedule: Only {$progressPercent}% completed with {$daysRemaining} days remaining.";
        }

        if (! empty($riskReasons) || $requiredPaceWeekly > 45.0 || $daysSinceLastClockin >= 10) {
            $status = 'at_risk';
            $statusLabel = 'At Risk';
            $badgeClass = 'bg-rose-100 text-rose-800 border-rose-300';
        } elseif ($requiredPaceWeekly > 35.0 || $daysSinceLastClockin >= 5) {
            $status = 'slow';
            $statusLabel = 'Pacing Slow';
            $badgeClass = 'bg-amber-100 text-amber-800 border-amber-300';
            if ($requiredPaceWeekly > 35.0) {
                $riskReasons[] = "Pace lagging: Requires {$requiredPaceWeekly} hrs/week to stay on schedule.";
            }
        } else {
            $status = 'on_track';
            $statusLabel = 'On Track';
            $badgeClass = 'bg-emerald-100 text-emerald-800 border-emerald-300';
        }

        return [
            'status' => $status,
            'status_label' => $statusLabel,
            'badge_class' => $badgeClass,
            'progress_percent' => $progressPercent,
            'remaining_hours' => $remainingHours,
            'completed_hours' => $completedHours,
            'target_end_date' => $targetEnd,
            'days_remaining' => $daysRemaining,
            'weeks_remaining' => round($weeksRemaining, 1),
            'required_pace_weekly' => $requiredPaceWeekly,
            'days_since_last_clockin' => $daysSinceLastClockin,
            'risk_reasons' => $riskReasons,
        ];
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
