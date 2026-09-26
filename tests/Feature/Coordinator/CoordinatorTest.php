<?php

namespace Tests\Feature\Coordinator;

use App\Models\Company;
use App\Models\DtrEntry;
use App\Models\EJournal;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoordinatorTest extends TestCase
{
    use RefreshDatabase;

    private function coordinator(): User
    {
        $user = User::factory()->create(['role' => User::ROLE_COORDINATOR]);
        $this->actingAs($user);

        return $user;
    }

    public function test_coordinator_can_view_time_records(): void
    {
        $this->coordinator();
        $student = Student::factory()->create();

        DtrEntry::create([
            'student_id' => $student->id,
            'date' => now()->toDateString(),
            'time_in' => now()->subHours(3),
            'time_out' => now(),
            'hours' => 3,
            'checkin_method' => 'manual',
        ]);

        $this->get(route('coordinator.dtr.index'))
            ->assertOk()
            ->assertSee($student->user->name);
    }

    public function test_coordinator_can_view_journals(): void
    {
        $this->coordinator();
        $student = Student::factory()->create();

        EJournal::create([
            'student_id' => $student->id,
            'week' => 1,
            'title' => 'Week One',
            'entry' => 'Content.',
        ]);

        $this->get(route('coordinator.journals.index'))
            ->assertOk()
            ->assertSee('Week One');
    }

    public function test_coordinator_can_assign_company_to_student(): void
    {
        $coordinator = $this->coordinator();
        $student = Student::factory()->create(['company_id' => null, 'coordinator_id' => $coordinator->id]);
        $company = Company::factory()->create();

        $this->patch(route('coordinator.students.update', $student), [
            'company_id' => $company->id,
            'status' => 'active',
        ])->assertRedirect();

        $student->refresh();

        $this->assertSame($company->id, $student->company_id);
        $this->assertSame('active', $student->status);
    }
}
