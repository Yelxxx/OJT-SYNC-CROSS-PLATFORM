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
        'student_no',
        'program',
        'year_level',
        'required_hours',
        'status',
    ];

    protected $casts = [
        'required_hours' => 'integer',
    ];

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
}
