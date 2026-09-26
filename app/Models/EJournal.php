<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EJournal extends Model
{
    protected $fillable = [
        'student_id',
        'week',
        'title',
        'entry',
        'submitted_at',
    ];

    protected $casts = [
        'week' => 'integer',
        'submitted_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
