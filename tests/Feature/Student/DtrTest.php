<?php

namespace Tests\Feature\Student;

use App\Models\Company;
use App\Models\DtrEntry;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DtrTest extends TestCase
{
    use RefreshDatabase;

    private function studentActor(): Student
    {
        $company = Company::factory()->create();
        $student = Student::factory()->create(['company_id' => $company->id]);

        $this->actingAs($student->user);

        return $student;
    }

    public function test_student_can_view_dtr_page(): void
    {
        $this->studentActor();

        $this->get(route('student.dtr.index'))->assertOk();
    }

    public function test_unhired_student_is_redirected_from_dtr_page_with_explanation(): void
    {
        // DTR only makes sense once a company hired the student; the page
        // sends them to their applications with a friendly notice instead.
        $student = Student::factory()->create(['company_id' => null]);
        $this->actingAs($student->user);

        $this->get(route('student.dtr.index'))
            ->assertRedirect(route('student.applications.index'))
            ->assertSessionHas('error', 'You can record time in / out once a company has hired you.');
    }

    public function test_student_cannot_check_in_without_assigned_company(): void
    {
        $student = Student::factory()->create(['company_id' => null]);
        $this->actingAs($student->user);

        $this->post(route('student.dtr.checkin'))->assertStatus(403);

        $this->assertSame(0, $student->dtrEntries()->count());
    }

    public function test_student_cannot_check_in_when_not_active(): void
    {
        $company = Company::factory()->create();
        $student = Student::factory()->create(['company_id' => $company->id, 'status' => 'pending']);
        $this->actingAs($student->user);

        $this->post(route('student.dtr.checkin'))->assertStatus(403);

        $this->assertSame(0, $student->dtrEntries()->count());
    }

    public function test_student_can_check_in(): void
    {
        $student = $this->studentActor();

        $this->post(route('student.dtr.checkin'))
            ->assertRedirect();

        $this->assertDatabaseHas('dtr_entries', [
            'student_id' => $student->id,
            'checkin_method' => 'manual',
        ]);
        $this->assertNull(DtrEntry::first()->time_out);
    }

    public function test_student_cannot_check_in_twice_while_active(): void
    {
        $student = $this->studentActor();

        $this->post(route('student.dtr.checkin'))->assertRedirect();
        $this->post(route('student.dtr.checkin'))->assertStatus(409);

        $this->assertSame(1, $student->dtrEntries()->count());
    }

    public function test_student_can_check_out(): void
    {
        $student = $this->studentActor();

        $entry = DtrEntry::create([
            'student_id' => $student->id,
            'date' => now()->toDateString(),
            'time_in' => now()->subHours(2),
            'hours' => 0,
            'checkin_method' => 'manual',
        ]);

        $this->post(route('student.dtr.checkout'), ['task_summary' => 'Built a feature'])
            ->assertRedirect();

        $entry->refresh();

        $this->assertNotNull($entry->time_out);
        $this->assertSame(2.0, (float) $entry->hours);
        $this->assertSame('Built a feature', $entry->task_summary);
    }

    public function test_student_cannot_check_out_without_active_entry(): void
    {
        $this->studentActor();

        $this->post(route('student.dtr.checkout'))->assertStatus(409);
    }
}
