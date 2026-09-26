<?php

namespace Tests\Feature\Student;

use App\Models\Application;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_withdraw_pending_application(): void
    {
        $student = Student::factory()->create();
        $this->actingAs($student->user);

        $job = JobPosting::factory()->create(['applications_count' => 1]);
        $application = Application::factory()->create([
            'job_posting_id' => $job->id,
            'student_id' => $student->id,
            'status' => Application::STATUS_PENDING,
        ]);

        $this->delete(route('student.applications.destroy', $application))->assertRedirect();

        $this->assertDatabaseMissing('applications', ['id' => $application->id]);
        $this->assertSame(0, $job->fresh()->applications_count);
    }

    public function test_student_cannot_withdraw_non_pending_application(): void
    {
        $student = Student::factory()->create();
        $this->actingAs($student->user);

        $job = JobPosting::factory()->create();
        $application = Application::factory()->create([
            'job_posting_id' => $job->id,
            'student_id' => $student->id,
            'status' => Application::STATUS_HIRED,
        ]);

        $this->delete(route('student.applications.destroy', $application))->assertStatus(403);

        $this->assertDatabaseHas('applications', ['id' => $application->id]);
    }

    public function test_student_jobs_can_be_searched_and_filtered(): void
    {
        $student = Student::factory()->create();
        $this->actingAs($student->user);

        $companyA = Company::factory()->create(['name' => 'Acme Corp']);
        $companyB = Company::factory()->create(['name' => 'Globex']);

        JobPosting::factory()->create(['company_id' => $companyA->id, 'title' => 'Software Engineer Intern']);
        JobPosting::factory()->create(['company_id' => $companyB->id, 'title' => 'Design Intern']);
        JobPosting::factory()->create(['company_id' => $companyA->id, 'title' => 'QA Intern', 'status' => 'closed']);

        $this->get(route('student.jobs.index', ['q' => 'engineer']))
            ->assertOk()
            ->assertSee('Software Engineer Intern')
            ->assertDontSee('Design Intern');

        $this->get(route('student.jobs.index', ['company' => $companyA->id]))
            ->assertOk()
            ->assertSee('Software Engineer Intern')
            ->assertDontSee('Design Intern');
    }
}
