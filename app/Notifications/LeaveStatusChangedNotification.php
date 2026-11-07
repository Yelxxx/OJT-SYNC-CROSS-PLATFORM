<?php

namespace App\Notifications;

use App\Models\InternLeave;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public InternLeave $leave,
        public string $reviewerName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'leave_id' => $this->leave->id,
            'leave_type' => InternLeave::TYPES[$this->leave->leave_type] ?? $this->leave->leave_type,
            'start_date' => $this->leave->start_date->format('M d, Y'),
            'end_date' => $this->leave->end_date->format('M d, Y'),
            'status' => $this->leave->status,
            'reviewer_name' => $this->reviewerName,
            'rejection_reason' => $this->leave->rejection_reason,
            'link' => route('student.leaves.index'),
        ];
    }
}
