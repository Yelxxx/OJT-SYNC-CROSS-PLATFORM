<?php

namespace App\Services;

use App\Models\EJournal;
use App\Models\Student;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class JournalService
{
    /**
     * Submit a journal entry.
     *
     * $clientUuid makes offline replays idempotent; $submittedAt lets an
     * offline replay preserve when the student actually wrote the entry.
     *
     * @param  array{week: int, title: string, entry: string}  $data
     */
    public function submit(Student $student, array $data, ?string $clientUuid = null, ?Carbon $submittedAt = null): EJournal
    {
        return DB::transaction(function () use ($student, $data, $clientUuid, $submittedAt) {
            if ($clientUuid !== null) {
                $existing = $student->eJournals()->where('client_uuid', $clientUuid)->first();

                if ($existing) {
                    return $existing;
                }
            }

            try {
                return $student->eJournals()->create(array_merge($data, [
                    'client_uuid' => $clientUuid,
                    'submitted_at' => $submittedAt ?? now(),
                ]));
            } catch (UniqueConstraintViolationException) {
                return $student->eJournals()->where('client_uuid', $clientUuid)->firstOrFail();
            }
        });
    }
}
