<?php

namespace App\Services;

use App\Models\EJournal;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class JournalService
{
    /**
     * Submit a journal entry.
     *
     * @param  array{week: int, title: string, entry: string}  $data
     */
    public function submit(Student $student, array $data): EJournal
    {
        return DB::transaction(function () use ($student, $data) {
            return $student->eJournals()->create(array_merge($data, [
                'submitted_at' => now(),
            ]));
        });
    }
}
