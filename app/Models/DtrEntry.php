<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DtrEntry extends Model
{
    protected $fillable = [
        'student_id',
        'date',
        'time_in',
        'time_out',
        'hours',
        'task_summary',
        'checkin_method',
        'verification_status',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'date' => 'date',
        'time_in' => 'datetime',
        'time_out' => 'datetime',
        'hours' => 'decimal:2',
        'verified_at' => 'datetime',
    ];

    public const VERIFICATION_UNVERIFIED = 'unverified';

    public const VERIFICATION_VERIFIED = 'verified';

    public const VERIFICATION_DISPUTED = 'disputed';

    public const VERIFICATION_STATUSES = [
        self::VERIFICATION_UNVERIFIED => 'Unverified',
        self::VERIFICATION_VERIFIED => 'Verified',
        self::VERIFICATION_DISPUTED => 'Disputed',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isActive(): bool
    {
        return $this->time_out === null;
    }

    public function isVerified(): bool
    {
        return $this->verification_status === self::VERIFICATION_VERIFIED;
    }
}
