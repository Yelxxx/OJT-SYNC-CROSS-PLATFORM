<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evaluation extends Model
{
    protected $fillable = [
        'student_id',
        'job_posting_id',
        'token',
        'token_used',
        'punctuality',
        'quality_of_work',
        'initiative',
        'communication',
        'overall_score',
        'comments',
        'submitted_at',
    ];

    protected $casts = [
        'token_used' => 'boolean',
        'punctuality' => 'integer',
        'quality_of_work' => 'integer',
        'initiative' => 'integer',
        'communication' => 'integer',
        'overall_score' => 'decimal:2',
        'submitted_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function isComplete(): bool
    {
        return $this->token_used && $this->submitted_at !== null;
    }
}
