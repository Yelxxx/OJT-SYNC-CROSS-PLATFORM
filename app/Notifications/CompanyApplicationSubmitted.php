<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CompanyApplicationSubmitted extends Notification
{
    use Queueable;

    public function __construct(public Company $company) {}

    /**
     * Database channel like the rest of the app's notifications; the admin
     * dashboard bell links straight to the review screen.
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
            'company_id' => $this->company->id,
            'company_name' => $this->company->name,
            'contact_person' => $this->company->contact_person,
            'contact_email' => $this->company->contact_email,
            'message' => 'New company application: '.$this->company->name,
            'link' => route('admin.companies.index'),
        ];
    }
}
