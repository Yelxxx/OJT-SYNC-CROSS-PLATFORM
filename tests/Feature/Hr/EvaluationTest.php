<?php

namespace Tests\Feature\Hr;

use App\Models\Application;
use App\Models\Company;
use App\Models\Evaluation;
use App\Models\JobPosting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EvaluationTest extends TestCase
{
    use RefreshDatabase;

    private function hrUser(): User
    {
        $company = Company::factory()->create();
        $hr = User::factory()->create(['role' => User::ROLE_HR, 'company_id' => $company->id]);

        $this->actingAs($hr);

        return $hr;
    }

    private function hiredStudentFor(JobPosting $job): Student
    {
        $student = Student::factory()->create();
        Application::factory()->create([
            'job_posting_id' => $job->id,
            'student_id' => $student->id,
            'status' => Application::STATUS_HIRED,
        ]);

        return $student;
    }

    public function test_hr_can_generate_evaluation_for_hired_student(): void
    {
        $hr = $this->hrUser();
        $job = JobPosting::factory()->create(['company_id' => $hr->company_id]);
        $student = $this->hiredStudentFor($job);

        $this->get(route('hr.evaluations.create'))->assertOk();

        $this->post(route('hr.evaluations.store'), [
            'student_id' => $student->id,
            'job_posting_id' => $job->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('evaluations', [
            'student_id' => $student->id,
            'job_posting_id' => $job->id,
        ]);
    }

    public function test_evaluation_link_can_be_filled_publicly(): void
    {
        $evaluation = Evaluation::create([
            'student_id' => Student::factory()->create()->id,
            'job_posting_id' => JobPosting::factory()->create()->id,
            'token' => Str::random(40),
        ]);

        $this->get(route('evaluations.show', $evaluation->token))->assertOk();

        $this->post(route('evaluations.store', $evaluation->token), [
            'punctuality' => 5,
            'quality_of_work' => 4,
            'initiative' => 4,
            'communication' => 5,
            'comments' => 'Great intern!',
        ])->assertRedirect(route('evaluations.show', $evaluation->token));

        $evaluation->refresh();

        $this->assertTrue($evaluation->isComplete());
        $this->assertSame(4.5, (float) $evaluation->overall_score);
        $this->assertSame('Great intern!', $evaluation->comments);
    }

    public function test_evaluation_link_cannot_be_used_twice(): void
    {
        $evaluation = Evaluation::create([
            'student_id' => Student::factory()->create()->id,
            'job_posting_id' => JobPosting::factory()->create()->id,
            'token' => Str::random(40),
        ]);

        $payload = [
            'punctuality' => 5,
            'quality_of_work' => 5,
            'initiative' => 5,
            'communication' => 5,
        ];

        $this->post(route('evaluations.store', $evaluation->token), $payload)->assertRedirect();
        $this->post(route('evaluations.store', $evaluation->token), $payload)->assertStatus(409);
    }
}
