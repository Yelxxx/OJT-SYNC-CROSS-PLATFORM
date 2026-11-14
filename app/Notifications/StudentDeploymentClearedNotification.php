<?php

namespace App\Notifications;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StudentDeploymentClearedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Student $student,
        public string $coordinatorName,
        public bool $isCleared,
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
            'is_cleared' => $this->isCleared,
            'coordinator_name' => $this->coordinatorName,
            'company_name' => $this->student->company?->name ?? 'Host Company',
            'link' => route('student.dtr.index'),
        ];
    }
}

