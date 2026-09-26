<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ApplicationStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        public Application $application,
        public string $previousStatus,
    ) {}

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
            'company_name' => $this->application->jobPosting->company->name,
            'previous_status' => $this->previousStatus,
            'status' => $this->application->status,
            'interview_at' => $this->application->interview_at?->toISOString(),
            'rejection_reason' => $this->application->rejection_reason,
        ];
    }
}
