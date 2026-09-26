<?php

namespace App\Models;

use Database\Factories\JobPostingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class JobPosting extends Model
{
    /** @use HasFactory<JobPostingFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (JobPosting $job) {
            $job->share_token ??= Str::random(40);
        });
    }

    protected $fillable = [
        'company_id',
        'title',
        'description',
        'requirements',
        'slots',
        'applications_count',
        'status',
    ];

    protected $casts = [
        'slots' => 'integer',
        'applications_count' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function hiredCount(): int
    {
        return $this->applications()->hired()->count();
    }

    public function hasOpenSlots(): bool
    {
        return $this->hiredCount() < $this->slots;
    }
}
