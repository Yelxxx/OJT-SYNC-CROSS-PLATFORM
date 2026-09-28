<?php

namespace App\Services;

use App\Exceptions\DomainActionException;
use App\Models\DtrEntry;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DtrService
{
    /**
     * Clock a student in.
     *
     * $checkinMethod records how the check-in happened: 'manual' (button) or
     * 'qr' (scan-proven, set by ScanCheckinController).
     */
    public function checkin(Student $student, ?string $checkinMethod = null): DtrEntry
    {
        if (! $student->company_id) {
            throw new DomainActionException('no_company', 'You cannot clock in until a company has been assigned to you.', 403);
        }

        if ($student->status !== 'active') {
            throw new DomainActionException('inactive', 'Your OJT status must be active to clock in.', 403);
        }

        return DB::transaction(function () use ($student, $checkinMethod) {
            if ($student->dtrEntries()->whereNull('time_out')->exists()) {
                throw new DomainActionException('already_active', 'You already have an active time-in. Please time out first.', 409);
            }

            return DtrEntry::create([
                'student_id' => $student->id,
                'date' => now()->toDateString(),
                'time_in' => now(),
                'hours' => 0,
                'checkin_method' => $checkinMethod ?? 'manual',
            ]);
        });
    }

    /**
     * Clock a student out.
     */
    public function checkout(Student $student, ?string $taskSummary = null): DtrEntry
    {
        return DB::transaction(function () use ($student, $taskSummary) {
            $entry = $student->dtrEntries()
                ->whereNull('time_out')
                ->latest('time_in')
                ->lockForUpdate()
                ->first();

            if (! $entry) {
                throw new DomainActionException('no_active_entry', 'You have no active time-in to time out from.', 409);
            }

            $timeOut = now();

            if ($timeOut->lte($entry->time_in)) {
                throw new DomainActionException('invalid_time_out', 'Time out must be after the time in.', 422);
            }

            if ($timeOut->diffInHours($entry->time_in) > 24) {
                throw new DomainActionException('invalid_time_out', 'A single shift cannot exceed 24 hours. Ask your coordinator to fix this entry.', 422);
            }

            $entry->update([
                'time_out' => $timeOut,
                'hours' => round(abs($timeOut->diffInSeconds($entry->time_in)) / 3600, 2),
                'task_summary' => $taskSummary ?? $entry->task_summary,
            ]);

            return $entry;
        });
    }
}
