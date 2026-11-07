<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternLeave extends Model
{
    public const TYPE_SICK = 'sick';
    public const TYPE_ACADEMIC = 'academic';
    public const TYPE_EMERGENCY = 'emergency';
    public const TYPE_VACATION = 'vacation';

    public const TYPES = [
        self::TYPE_SICK => 'Sick / Medical Leave',
        self::TYPE_ACADEMIC => 'Academic / School Activity',
        self::TYPE_EMERGENCY => 'Family Emergency',
        self::TYPE_VACATION => 'Excused Absence / Personal',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pending Review',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_REJECTED => 'Rejected',
    ];

    protected $fillable = [
        'student_id',
        'leave_type',
        'start_date',
        'end_date',
        'reason',
        'document_path',
        'document_name',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}

