<?php

namespace App\Notifications;

use App\Models\InternChecklistItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ChecklistDocumentUploadedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public InternChecklistItem $item,
        public string $studentName,
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
            'item_id' => $this->item->id,
            'title' => $this->item->title,
            'file_name' => $this->item->file_name,
            'student_name' => $this->studentName,
            'notes' => $this->item->notes,
            'link' => route('hr.interns.index'),
        ];
    }
}
