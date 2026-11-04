<?php

namespace App\Notifications;

use App\Models\InternChecklistItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ChecklistItemStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public InternChecklistItem $item,
        public string $companyName,
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
            'is_complete' => (bool) $this->item->is_complete,
            'company_name' => $this->companyName,
            'link' => route('student.checklist.index'),
        ];
    }
}
