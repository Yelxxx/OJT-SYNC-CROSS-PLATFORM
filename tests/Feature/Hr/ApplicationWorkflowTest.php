<?php

namespace Tests\Feature\Hr;

use App\Models\Application;
use App\Models\ApplicationNote;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function hrUser(): User
    {
        $company = Company::factory()->create();
        $hr = User::factory()->create(['role' => User::ROLE_HR, 'company_id' => $company->id]);

        $this->actingAs($hr);

        return $hr;
    }

    private function applicationFor(User $hr, string $status = Application::STATUS_PENDING): Application
    {
        $job = JobPosting::factory()->create(['company_id' => $hr->company_id]);
        $student = Student::factory()->create();

        return Application::factory()->create([
            'job_posting_id' => $job->id,
            'student_id' => $student->id,
            'status' => $status,
        ]);
    }

    public function test_moving_to_interview_requires_a_future_interview_date(): void
    {
        $hr = $this->hrUser();
        $application = $this->applicationFor($hr);

        $this->patch(route('hr.applications.update', $application), [
            'status' => Application::STATUS_INTERVIEW,
        ])->assertSessionHasErrors('interview_at');

        $this->patch(route('hr.applications.update', $application), [
            'status' => Application::STATUS_INTERVIEW,
            'interview_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $application->refresh();

        $this->assertSame(Application::STATUS_INTERVIEW, $application->status);
        $this->assertNotNull($application->interview_at);
    }

    public function test_rejecting_requires_a_reason_and_notifies_the_student(): void
    {
        $hr = $this->hrUser();
        $application = $this->applicationFor($hr);
        $studentUser = $application->student->user;

        $this->patch(route('hr.applications.update', $application), [
            'status' => Application::STATUS_REJECTED,
        ])->assertSessionHasErrors('rejection_reason');

        $this->patch(route('hr.applications.update', $application), [
            'status' => Application::STATUS_REJECTED,
            'rejection_reason' => 'We chose a candidate with more PHP experience.',
        ])->assertRedirect();

        $application->refresh();

        $this->assertSame(Application::STATUS_REJECTED, $application->status);
        $this->assertSame('We chose a candidate with more PHP experience.', $application->rejection_reason);

        $this->assertSame(1, $studentUser->fresh()->unreadNotifications()->count());

        $data = $studentUser->unreadNotifications()->first()->data;
        $this->assertSame(Application::STATUS_REJECTED, $data['status']);
        $this->assertSame('We chose a candidate with more PHP experience.', $data['rejection_reason']);
    }

    public function test_status_change_is_recorded_in_history(): void
    {
        $hr = $this->hrUser();
        $application = $this->applicationFor($hr);

        $this->patch(route('hr.applications.update', $application), [
            'status' => Application::STATUS_HIRED,
        ])->assertRedirect();

        $this->assertDatabaseHas('application_status_histories', [
            'application_id' => $application->id,
            'from_status' => Application::STATUS_PENDING,
            'to_status' => Application::STATUS_HIRED,
            'user_id' => $hr->id,
        ]);

        $this->assertSame(1, $application->statusHistory()->count());
    }

    public function test_no_notification_when_status_is_unchanged(): void
    {
        $hr = $this->hrUser();
        $application = $this->applicationFor($hr);
        $studentUser = $application->student->user;

        $this->patch(route('hr.applications.update', $application), [
            'status' => Application::STATUS_PENDING,
        ])->assertRedirect();

        $this->assertSame(0, $studentUser->fresh()->unreadNotifications()->count());
    }

    public function test_hr_can_add_a_note_to_an_application(): void
    {
        $hr = $this->hrUser();
        $application = $this->applicationFor($hr);

        $this->post(route('hr.applications.notes.store', $application), [
            'body' => 'Strong portfolio — schedule interview Thursday.',
        ])->assertRedirect();

        $this->assertDatabaseHas('application_notes', [
            'application_id' => $application->id,
            'user_id' => $hr->id,
            'body' => 'Strong portfolio — schedule interview Thursday.',
        ]);
    }

    public function test_hr_cannot_access_another_companys_application(): void
    {
        $this->hrUser();
        $application = $this->applicationFor(User::factory()->create([
            'role' => User::ROLE_HR,
            'company_id' => Company::factory()->create()->id,
        ]));

        $this->get(route('hr.applications.show', $application))->assertStatus(403);
        $this->patch(route('hr.applications.update', $application), [
            'status' => Application::STATUS_REJECTED,
            'rejection_reason' => 'nope',
        ])->assertStatus(403);
    }

    public function test_resumes_are_served_through_authenticated_route_only(): void
    {
        Storage::fake('local');

        $hr = $this->hrUser();
        $job = JobPosting::factory()->create(['company_id' => $hr->company_id]);
        $student = Student::factory()->create();
        $application = Application::factory()->create([
            'job_posting_id' => $job->id,
            'student_id' => $student->id,
        ]);

        $this->actingAs($student->user);

        $path = UploadedFile::fake()->createWithContent('resume.pdf', 'fake pdf')->store('resumes');
        $application->update(['resume_path' => $path]);

        $response = $this->get(route('applications.resume', $application));

        $response->assertOk();

        // HR from the same company can also download it.
        $this->actingAs($hr);
        $this->get(route('applications.resume', $application))->assertOk();

        // A student who does not own the application cannot.
        $otherStudent = Student::factory()->create();
        $this->actingAs($otherStudent->user);
        $this->get(route('applications.resume', $application))->assertStatus(403);

        // Guests cannot either.
        $this->post(route('logout'));
        $this->get(route('applications.resume', $application))->assertRedirect(route('login'));
    }

    public function test_student_receives_notification_when_hired(): void
    {
        $hr = $this->hrUser();
        $application = $this->applicationFor($hr);
        $studentUser = $application->student->user;

        $this->patch(route('hr.applications.update', $application), [
            'status' => Application::STATUS_HIRED,
        ])->assertRedirect();

        $data = $studentUser->unreadNotifications()->first()->data;

        $this->assertSame(Application::STATUS_HIRED, $data['status']);
        $this->assertSame($application->jobPosting->title, $data['job_title']);
    }

    public function test_note_requires_body(): void
    {
        $hr = $this->hrUser();
        $application = $this->applicationFor($hr);

        $this->post(route('hr.applications.notes.store', $application), [
            'body' => '',
        ])->assertSessionHasErrors('body');

        $this->assertSame(0, ApplicationNote::count());
    }
}
