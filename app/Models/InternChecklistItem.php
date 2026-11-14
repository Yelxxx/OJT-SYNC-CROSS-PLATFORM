<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternChecklistItem extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING => 'Not Submitted',
        self::STATUS_SUBMITTED => 'Under Review',
        self::STATUS_APPROVED => 'Approved & Cleared',
        self::STATUS_REJECTED => 'Revision Requested',
    ];

    /**
     * Standard institutional Pre-Deployment clearance requirements.
     */
    public const DEFAULTS = [
        'Parent / Guardian Consent & Notarized Waiver',
        'Medical Examination & Physical Fitness Clearance',
        'Practicum Endorsement Letter (Signed by Coordinator)',
        'Proof of Student Internship Insurance Coverage',
        'Company Acceptance Letter & Training Plan',
    ];

    protected $fillable = [
        'student_id',
        'title',
        'is_complete',
        'status',
        'completed_at',
        'file_path',
        'file_name',
        'submitted_at',
        'notes',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'is_complete' => 'boolean',
        'completed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED || $this->is_complete;
    }

    /**
     * Create any missing default onboarding / clearance items for a student.
     */
    public static function seedDefaultsForStudent(int $studentId): void
    {
        $existing = static::where('student_id', $studentId)->pluck('title');

        foreach (static::DEFAULTS as $title) {
            if (! $existing->contains($title)) {
                static::create([
                    'student_id' => $studentId,
                    'title' => $title,
                    'status' => self::STATUS_PENDING,
                    'is_complete' => false,
                ]);
            }
        }
    }
}
