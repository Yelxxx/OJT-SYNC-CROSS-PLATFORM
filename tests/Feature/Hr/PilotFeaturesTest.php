<?php

namespace Tests\Feature\Hr;

use App\Models\Application;
use App\Models\Company;
use App\Models\DtrEntry;
use App\Models\InternChecklistItem;
use App\Models\JobPosting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PilotFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function hrUser(): User
    {
        $company = Company::factory()->create();
        $hr = User::factory()->create(['role' => User::ROLE_HR, 'company_id' => $company->id]);

        $this->actingAs($hr);

        return $hr;
    }

    // ── Hire handoff ────────────────────────────────────────────────

    public function test_hiring_a_student_assigns_company_and_seeds_checklist(): void
    {
        $hr = $this->hrUser();
        $job = JobPosting::factory()->create(['company_id' => $hr->company_id]);
        $student = Student::factory()->create(['company_id' => null]);
        $application = Application::factory()->create([
            'job_posting_id' => $job->id,
            'student_id' => $student->id,
        ]);

        $this->patch(route('hr.applications.update', $application), [
            'status' => Application::STATUS_HIRED,
        ])->assertRedirect();

        $student->refresh();

        $this->assertSame($hr->company_id, $student->company_id);
        $this->assertSame(4, $student->checklistItems()->count());
    }

    public function test_hiring_beyond_available_slots_is_blocked(): void
    {
        $hr = $this->hrUser();
        $job = JobPosting::factory()->create(['company_id' => $hr->company_id, 'slots' => 1]);

        $hired = Application::factory()->create([
            'job_posting_id' => $job->id,
            'status' => Application::STATUS_HIRED,
        ]);

        $second = Application::factory()->create([
            'job_posting_id' => $job->id,
        ]);

        $this->patch(route('hr.applications.update', $second), [
            'status' => Application::STATUS_HIRED,
        ])->assertSessionHas('error');

        $this->assertSame(Application::STATUS_PENDING, $second->fresh()->status);
        $this->assertSame(1, $job->hiredCount());
    }

    public function test_hiring_when_own_slot_is_free_allows_rehire_of_same_applicant(): void
    {
        $hr = $this->hrUser();
        $job = JobPosting::factory()->create(['company_id' => $hr->company_id, 'slots' => 1]);

        $application = Application::factory()->create([
            'job_posting_id' => $job->id,
        ]);

        $this->patch(route('hr.applications.update', $application), [
            'status' => Application::STATUS_HIRED,
        ])->assertRedirect();

        $this->assertSame(Application::STATUS_HIRED, $application->fresh()->status);
    }

    // ── Checklist ───────────────────────────────────────────────────

    public function test_hr_can_toggle_and_extend_checklist(): void
    {
        $hr = $this->hrUser();
        $job = JobPosting::factory()->create(['company_id' => $hr->company_id]);
        $student = Student::factory()->create();
        Application::factory()->create([
            'job_posting_id' => $job->id,
            'student_id' => $student->id,
            'status' => Application::STATUS_HIRED,
        ]);

        InternChecklistItem::seedDefaultsForStudent($student->id);
        $item = $student->checklistItems()->first();

        $this->get(route('hr.interns.checklist.toggle', $item))->assertRedirect();

        $this->assertTrue($item->fresh()->is_complete);
        $this->assertNotNull($item->fresh()->completed_at);

        // Custom requirement
        $this->post(route('hr.interns.checklist.add', $student), [
            'title' => 'Company ID photo',
        ])->assertRedirect();

        $this->assertDatabaseHas('intern_checklist_items', [
            'student_id' => $student->id,
            'title' => 'Company ID photo',
        ]);
    }

    public function test_hr_cannot_touch_another_companies_intern_checklist(): void
    {
        $this->hrUser();

        $otherCompany = Company::factory()->create();
        $otherHr = User::factory()->create(['role' => User::ROLE_HR, 'company_id' => $otherCompany->id]);
        $job = JobPosting::factory()->create(['company_id' => $otherCompany->id]);
        $student = Student::factory()->create();
        Application::factory()->create([
            'job_posting_id' => $job->id,
            'student_id' => $student->id,
            'status' => Application::STATUS_HIRED,
        ]);

        InternChecklistItem::seedDefaultsForStudent($student->id);
        $item = $student->checklistItems()->first();

        $this->get(route('hr.interns.checklist.toggle', $item))->assertStatus(403);
        $this->post(route('hr.interns.checklist.add', $student), ['title' => 'x'])->assertStatus(403);
    }

    // ── DTR verification ────────────────────────────────────────────

    public function test_hr_can_verify_and_dispute_intern_hours(): void
    {
        $hr = $this->hrUser();
        $student = Student::factory()->create(['company_id' => $hr->company_id]);

        $entry = DtrEntry::create([
            'student_id' => $student->id,
            'date' => now()->toDateString(),
            'time_in' => now()->setTime(9, 0),
            'time_out' => now()->setTime(17, 0),
            'hours' => 8,
            'checkin_method' => 'manual',
        ]);

        $this->get(route('hr.dtr.index'))->assertOk()->assertSee($student->user->name);

        $this->post(route('hr.dtr.verify', $entry), ['status' => 'verified'])->assertRedirect();
        $this->assertSame('verified', $entry->fresh()->verification_status);
        $this->assertSame($hr->id, $entry->fresh()->verified_by);

        $this->post(route('hr.dtr.verify', $entry), ['status' => 'disputed'])->assertRedirect();
        $this->assertSame('disputed', $entry->fresh()->verification_status);

        $this->post(route('hr.dtr.verify', $entry), ['status' => 'unverified'])->assertRedirect();
        $this->assertSame('unverified', $entry->fresh()->verification_status);
        $this->assertNull($entry->fresh()->verified_by);
    }

    public function test_hr_cannot_verify_other_companies_entries(): void
    {
        $this->hrUser();

        $otherCompany = Company::factory()->create();
        $student = Student::factory()->create(['company_id' => $otherCompany->id]);

        $entry = DtrEntry::create([
            'student_id' => $student->id,
            'date' => now()->toDateString(),
            'time_in' => now()->setTime(9, 0),
            'time_out' => now()->setTime(17, 0),
            'hours' => 8,
            'checkin_method' => 'manual',
        ]);

        $this->post(route('hr.dtr.verify', $entry), ['status' => 'verified'])->assertStatus(403);
    }

    // ── Company profile + branding ──────────────────────────────────

    public function test_hr_can_update_company_profile_and_logo(): void
    {
        Storage::fake('public');

        $hr = $this->hrUser();
        $logo = tempnam(sys_get_temp_dir(), 'logo').'.png';
        file_put_contents($logo, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));

        $this->get(route('hr.company.edit'))->assertOk()->assertSee($hr->company->name);

        $this->patch(route('hr.company.update'), [
            'name' => 'Acme Corp',
            'address' => '123 Main St',
            'contact_email' => 'hr@acme.test',
            'description' => 'We build things.',
            'logo' => new UploadedFile($logo, 'logo.png', 'image/png', null, true),
        ])->assertRedirect()->assertSessionHas('status');

        $hr->company->refresh();

        $this->assertSame('Acme Corp', $hr->company->name);
        $this->assertNotNull($hr->company->logo_path);
        Storage::disk('public')->assertExists($hr->company->logo_path);
    }

    // ── Public share page ───────────────────────────────────────────

    public function test_public_share_page_lists_open_jobs(): void
    {
        $company = Company::factory()->create();
        $job = JobPosting::factory()->create([
            'company_id' => $company->id,
            'title' => 'Laravel Intern',
        ]);
        JobPosting::factory()->create(['company_id' => $company->id, 'status' => 'closed']);

        $this->get(route('share.company', $company->share_token))
            ->assertOk()
            ->assertSee($company->name)
            ->assertSee('Laravel Intern')
            ->assertDontSee('QA Intern');
    }

    public function test_share_page_rejects_unknown_tokens(): void
    {
        $this->get(route('share.company', 'nope'))->assertNotFound();
    }

    public function test_hr_can_view_job_qr_page(): void
    {
        $hr = $this->hrUser();
        $job = JobPosting::factory()->create(['company_id' => $hr->company_id]);

        $response = $this->get(route('hr.jobs.qr', $job));

        $response->assertOk()->assertSee('<svg', false);
    }

    public function test_job_qr_page_is_company_scoped(): void
    {
        $this->hrUser();
        $otherCompany = Company::factory()->create();
        $job = JobPosting::factory()->create(['company_id' => $otherCompany->id]);

        $this->get(route('hr.jobs.qr', $job))->assertStatus(403);
    }
}
