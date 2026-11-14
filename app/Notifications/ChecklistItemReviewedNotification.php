<?php

namespace App\Notifications;

use App\Models\InternChecklistItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ChecklistItemReviewedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public InternChecklistItem $item,
        public string $reviewerName,
        public string $status,
        public ?string $rejectionReason = null,
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
            'status' => $this->status,
            'reviewer_name' => $this->reviewerName,
            'rejection_reason' => $this->rejectionReason,
            'link' => route('student.checklist.index'),
        ];
    }
}

