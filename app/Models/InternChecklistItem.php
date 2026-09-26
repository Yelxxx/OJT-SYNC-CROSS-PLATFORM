<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternChecklistItem extends Model
{
    public const DEFAULTS = [
        'Signed MOA',
        'Submit valid ID',
        'OR/CR of transport (if transportation allowance)',
        'Fill out onboarding forms',
    ];

    protected $fillable = [
        'student_id',
        'title',
        'is_complete',
        'completed_at',
    ];

    protected $casts = [
        'is_complete' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Create any missing default onboarding items for a hired student.
     */
    public static function seedDefaultsForStudent(int $studentId): void
    {
        $existing = static::where('student_id', $studentId)->pluck('title');

        foreach (static::DEFAULTS as $title) {
            if (! $existing->contains($title)) {
                static::create([
                    'student_id' => $studentId,
                    'title' => $title,
                ]);
            }
        }
    }
}
