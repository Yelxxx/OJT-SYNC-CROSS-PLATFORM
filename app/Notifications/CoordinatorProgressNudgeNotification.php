<?php

namespace App\Notifications;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CoordinatorProgressNudgeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Student $student,
        public string $coordinatorName,
        public string $nudgeMessage,
        public array $pacingData,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'student_id' => $this->student->id,
            'student_name' => $this->student->user->name,
            'coordinator_name' => $this->coordinatorName,
            'message' => $this->nudgeMessage,
            'status' => $this->pacingData['status'],
            'completed_hours' => $this->pacingData['completed_hours'],
            'required_pace' => $this->pacingData['required_pace_weekly'],
            'link' => route('student.dtr.index'),
        ];
    }
}
