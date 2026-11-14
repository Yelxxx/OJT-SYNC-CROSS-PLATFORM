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
     * Clock a student in. ('manual' button check-ins only — the QR scan
     * feature was removed and can be reintroduced later with its own
     * checkin_method value.)
     */
    public function checkin(Student $student): DtrEntry
    {
        if (! $student->company_id) {
            throw new DomainActionException('no_company', 'You cannot clock in until a company has been assigned to you.', 403);
        }

        if (! $student->is_cleared_for_deployment) {
            throw new DomainActionException(
                'not_cleared',
                'Deployment Clearance Required: You cannot clock in until your Practicum Coordinator has verified and approved your Pre-Deployment requirements.',
                403
            );
        }

        if ($student->status !== 'active') {
            throw new DomainActionException('inactive', 'Your OJT status must be active to clock in.', 403);
        }

        return DB::transaction(function () use ($student) {
            $activeEntry = $student->dtrEntries()
                ->whereNull('time_out')
                ->latest('time_in')
                ->lockForUpdate()
                ->first();

            // Auto-recovery: If active shift is from a previous calendar day or older than 14 hours,
            // auto-close it with standard shift hours instead of bricking check-in.
            if ($activeEntry) {
                $hoursDiff = now()->diffInHours($activeEntry->time_in);
                $isDifferentDay = $activeEntry->date->isBefore(now()->toDateString());

                if ($isDifferentDay || $hoursDiff >= 14) {
                    // Auto-close: capped at 8 working hours (e.g. 8h shift + 1h lunch)
                    $autoTimeOut = $activeEntry->time_in->copy()->addHours(9);
                    $activeEntry->update([
                        'time_out' => $autoTimeOut,
                        'hours' => 8.00,
                        'break_deducted' => true,
                        'is_auto_closed' => true,
                        'verification_status' => DtrEntry::VERIFICATION_UNVERIFIED,
                        'dispute_reason' => 'Auto-closed by system due to missing time-out. Pending HR verification.',
                    ]);
                } else {
                    throw new DomainActionException('already_active', 'You already have an active time-in. Please time out first.', 409);
                }
            }

            return DtrEntry::create([
                'student_id' => $student->id,
                'date' => now()->toDateString(),
                'time_in' => now(),
                'hours' => 0,
                'checkin_method' => 'manual',
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

            $rawSeconds = abs($timeOut->diffInSeconds($entry->time_in));
            $grossHours = $rawSeconds / 3600;

            // Smart Shift Rule: Mandatory 1-hour unpaid lunch break deduction for shifts >= 5 hours
            $breakDeducted = false;
            if ($grossHours >= 5.0) {
                $netHours = max(4.0, $grossHours - 1.0);
                $breakDeducted = true;
            } else {
                $netHours = $grossHours;
            }

            $entry->update([
                'time_out' => $timeOut,
                'hours' => round($netHours, 2),
                'break_deducted' => $breakDeducted,
                'task_summary' => $taskSummary ?? $entry->task_summary,
            ]);

            return $entry;
        });
    }
}
