<?php

namespace Tests\Feature\Hr;

use App\Models\Application;
use App\Models\Company;
use App\Models\DtrEntry;
use App\Models\Evaluation;
use App\Models\JobPosting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InternsTest extends TestCase
{
    use RefreshDatabase;

    private function hrUser(): User
    {
        $company = Company::factory()->create();
        $hr = User::factory()->create(['role' => User::ROLE_HR, 'company_id' => $company->id]);

        $this->actingAs($hr);

        return $hr;
    }

    public function test_hr_sees_hired_students_with_hours_progress(): void
    {
        $hr = $this->hrUser();
        $job = JobPosting::factory()->create(['company_id' => $hr->company_id]);

        $intern = Student::factory()->create(['company_id' => $hr->company_id]);
        Application::factory()->create([
            'job_posting_id' => $job->id,
            'student_id' => $intern->id,
            'status' => Application::STATUS_HIRED,
        ]);

        DtrEntry::create([
            'student_id' => $intern->id,
            'date' => now()->toDateString(),
            'time_in' => now()->setTime(8, 0),
            'time_out' => now()->setTime(17, 0),
            'hours' => 8,
            'task_summary' => 'Development work',
            'checkin_method' => 'manual',
        ]);

        $response = $this->get(route('hr.interns.index'));

        $response->assertOk()
            ->assertSee($intern->user->name)
            ->assertSee('8.0 / 300 h');
    }

    public function test_interns_page_does_not_show_non_hired_applicants(): void
    {
        $hr = $this->hrUser();
        $job = JobPosting::factory()->create(['company_id' => $hr->company_id]);

        $applicant = Student::factory()->create();
        Application::factory()->create([
            'job_posting_id' => $job->id,
            'student_id' => $applicant->id,
            'status' => Application::STATUS_PENDING,
        ]);

        $this->get(route('hr.interns.index'))
            ->assertOk()
            ->assertDontSee($applicant->user->name);
    }

    public function test_evaluated_intern_shows_submitted_state(): void
    {
        $hr = $this->hrUser();
        $job = JobPosting::factory()->create(['company_id' => $hr->company_id]);

        $intern = Student::factory()->create();
        Application::factory()->create([
            'job_posting_id' => $job->id,
            'student_id' => $intern->id,
            'status' => Application::STATUS_HIRED,
        ]);

        Evaluation::create([
            'student_id' => $intern->id,
            'job_posting_id' => $job->id,
            'token' => Str::random(40),
            'token_used' => true,
            'submitted_at' => now(),
            'punctuality' => 5,
            'quality_of_work' => 5,
            'initiative' => 5,
            'communication' => 5,
            'overall_score' => 5,
        ]);

        $this->get(route('hr.interns.index'))
            ->assertOk()
            ->assertSee('Submitted');
    }

    public function test_hr_users_are_notified_when_a_student_applies(): void
    {
        $hr = $this->hrUser();
        $job = JobPosting::factory()->create(['company_id' => $hr->company_id]);
        $student = Student::factory()->create();

        $this->actingAs($student->user)
            ->post(route('student.jobs.apply', $job), [
                'cover_letter' => 'I would love to intern here.',
            ])
            ->assertRedirect();

        $this->assertSame(1, $hr->unreadNotifications()->count());

        $data = $hr->unreadNotifications()->first()->data;
        $this->assertSame($student->user->name, $data['student_name']);
        $this->assertSame($job->title, $data['job_title']);
    }
}
