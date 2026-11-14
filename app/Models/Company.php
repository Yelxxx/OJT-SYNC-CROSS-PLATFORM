<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const MOA_ACTIVE = 'active';
    public const MOA_PENDING_RENEWAL = 'pending_renewal';
    public const MOA_EXPIRED = 'expired';
    public const MOA_NONE = 'none';

    public const MOA_STATUSES = [
        self::MOA_ACTIVE => 'Active Institutional MOA',
        self::MOA_PENDING_RENEWAL => 'Pending MOA Renewal',
        self::MOA_EXPIRED => 'Expired MOA',
        self::MOA_NONE => 'No Institutional MOA (Requires Endorsement/Custom MOA)',
    ];

    public const STATUSES = [
        self::STATUS_PENDING => 'Pending Review',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_REJECTED => 'Rejected',
    ];

    protected static function booted(): void
    {
        static::creating(function (Company $company) {
            $company->share_token ??= Str::random(40);
        });
    }

    protected $fillable = [
        'name',
        'industry',
        'logo_path',
        'address',
        'latitude',
        'longitude',
        'contact_person',
        'contact_email',
        'phone',
        'website',
        'description',
        'rejection_reason',
        'is_active',
        'status',
        'moa_status',
        'moa_valid_until',
        'moa_file_path',
        'moa_notes',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_active' => 'boolean',
        'moa_valid_until' => 'date',
    ];

    public function hasValidMoa(): bool
    {
        if ($this->moa_status !== self::MOA_ACTIVE) {
            return false;
        }

        if ($this->moa_valid_until && $this->moa_valid_until->isPast()) {
            return false;
        }

        return true;
    }

    public function jobPostings(): HasMany
    {
        return $this->hasMany(JobPosting::class);
    }

    public function hrUsers(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
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

    /**
     * Companies visible to students in job browsing: approved and active only.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED)->where('is_active', true);
    }
}
