<?php

namespace App\Notifications;

use App\Models\DtrEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DtrStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public DtrEntry $entry,
        public string $status,
        public ?string $remarks = null,
        public ?string $actorName = null,
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
            'entry_id' => $this->entry->id,
            'date' => $this->entry->date->format('M d, Y'),
            'hours' => (float) $this->entry->hours,
            'status' => $this->status,
            'remarks' => $this->remarks,
            'actor_name' => $this->actorName,
            'student_name' => $this->entry->student->user->name,
            'link' => $notifiable->isStudent() ? route('student.dtr.index') : route('coordinator.dtr.index'),
        ];
    }
}
