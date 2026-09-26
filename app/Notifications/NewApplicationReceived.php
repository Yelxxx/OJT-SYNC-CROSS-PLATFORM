<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewApplicationReceived extends Notification
{
    use Queueable;

    public function __construct(public Application $application) {}

    /**
     * Deliver via database only; mail would leak student contact
     * details to unverified company HR accounts.
     *
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
            'application_id' => $this->application->id,
            'job_posting_id' => $this->application->job_posting_id,
            'job_title' => $this->application->jobPosting->title,
            'student_name' => $this->application->student->user->name,
            'program' => $this->application->student->program,
        ];
    }
}
