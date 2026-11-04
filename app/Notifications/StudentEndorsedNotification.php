<?php

namespace App\Notifications;

use App\Models\Company;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StudentEndorsedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Student $student,
        public Company $company,
        public ?string $endorsementNote = null,
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
            'company_id' => $this->company->id,
            'company_name' => $this->company->name,
            'endorsement_note' => $this->endorsementNote,
            'link' => route('student.jobs.index'),
        ];
    }
}
