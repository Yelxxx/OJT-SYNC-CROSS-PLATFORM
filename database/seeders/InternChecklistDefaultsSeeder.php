<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\InternChecklistItem;
use Illuminate\Database\Seeder;

class InternChecklistDefaultsSeeder extends Seeder
{
    /**
     * Adds the default checklist to every hired intern that does not have
     * one yet. Safe to run repeatedly (also wired into ApplicationObserver
     * for new hires).
     */
    public function run(): void
    {
        $hiredStudentIds = Application::query()
            ->hired()
            ->pluck('student_id')
            ->unique();

        foreach ($hiredStudentIds as $studentId) {
            InternChecklistItem::seedDefaultsForStudent($studentId);
        }
    }
}
