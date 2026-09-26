<?php

namespace App\Observers;

use App\Models\Application;
use App\Models\InternChecklistItem;

class ApplicationObserver
{
    /**
     * When a student is hired, they are effectively interning at the
     * company. Wire the relationship and prepare their onboarding.
     */
    public function updated(Application $application): void
    {
        if (! $application->isHired()) {
            return;
        }

        $companyId = $application->jobPosting->company_id;

        if ($application->student->company_id !== $companyId) {
            $application->student->update(['company_id' => $companyId]);
        }

        InternChecklistItem::seedDefaultsForStudent($application->student_id);
    }
}
