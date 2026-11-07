<?php

namespace App\Notifications;

use App\Models\InternLeave;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewLeaveRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        public InternLeave $leave,
        public string $studentName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'leave_id' => $this->leave->id,
            'student_name' => $this->studentName,
            'leave_type' => InternLeave::TYPES[$this->leave->leave_type] ?? $this->leave->leave_type,
            'start_date' => $this->leave->start_date->format('M d, Y'),
            'end_date' => $this->leave->end_date->format('M d, Y'),
            'reason' => $this->leave->reason,
            'link' => route('hr.leaves.index'),
        ];
    }
}
