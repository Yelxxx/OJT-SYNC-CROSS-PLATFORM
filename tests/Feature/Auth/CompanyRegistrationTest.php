<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use App\Notifications\CompanyApplicationSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CompanyRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('company.register'));

        $response->assertStatus(200);
        $response->assertSee('Partner with CvSU');
    }

    public function test_company_can_apply_and_starts_pending_and_inactive(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Notification::fake();

        $response = $this->post(route('company.register.store'), $this->validPayload());

        $response->assertRedirect(route('company.register'));

        $company = Company::where('name', 'Acme Testing Corporation')->firstOrFail();
        $this->assertSame(Company::STATUS_PENDING, $company->status);
        $this->assertFalse($company->is_active);

        $hr = User::where('email', 'hr@acme.test')->firstOrFail();
        $this->assertSame(User::ROLE_HR, $hr->role);
        $this->assertFalse($hr->is_active);
        $this->assertSame($company->id, $hr->company_id);

        Notification::assertSentTo($admin, CompanyApplicationSubmitted::class);
    }

    public function test_pending_company_hr_cannot_log_in(): void
    {
        $company = Company::create([
            'name' => 'Pending Corp',
            'status' => Company::STATUS_PENDING,
            'is_active' => false,
        ]);

        User::factory()->create([
            'role' => User::ROLE_HR,
            'is_active' => false,
            'company_id' => $company->id,
            'email' => 'hr@pending.test',
        ]);

        $response = $this->post('/login', [
            'email' => 'hr@pending.test',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_admin_can_approve_a_pending_company(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $company = Company::create([
            'name' => 'Approve Me Corp',
            'status' => Company::STATUS_PENDING,
            'is_active' => false,
        ]);

        $hr = User::factory()->unverified()->create([
            'role' => User::ROLE_HR,
            'is_active' => false,
            'company_id' => $company->id,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.companies.approve', $company));

        $response->assertRedirect();

        $company->refresh();
        $this->assertSame(Company::STATUS_APPROVED, $company->status);
        $this->assertTrue($company->is_active);

        $this->assertTrue((bool) $hr->refresh()->is_active);
    }

    public function test_approved_company_hr_can_log_in(): void
    {
        $company = Company::create([
            'name' => 'Approved Corp',
            'status' => Company::STATUS_APPROVED,
            'is_active' => true,
        ]);

        User::factory()->create([
            'role' => User::ROLE_HR,
            'is_active' => true,
            'company_id' => $company->id,
            'email' => 'hr@approved.test',
        ]);

        $this->post('/login', [
            'email' => 'hr@approved.test',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
    }

    public function test_admin_cannot_review_the_same_application_twice(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $company = Company::create([
            'name' => 'Already Reviewed Corp',
            'status' => Company::STATUS_APPROVED,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.companies.approve', $company))
            ->assertNotFound();
    }

    public function test_non_admin_cannot_access_the_review_screen(): void
    {
        $hr = User::factory()->create(['role' => User::ROLE_HR]);

        $this->actingAs($hr)
            ->get(route('admin.companies.index'))
            ->assertForbidden();
    }

    /**
     * @return array<string, string>
     */
    private function validPayload(): array
    {
        return [
            'company_name' => 'Acme Testing Corporation',
            'company_address' => '123 Test St, General Trias, Cavite',
            'company_phone' => '(046) 555-0000',
            'company_website' => 'https://acme.test',
            'company_description' => 'We make testing tools.',
            'contact_person' => 'Jane Doe',
            'email' => 'hr@acme.test',
            'password' => 'Sup3r-Secret!',
            'password_confirmation' => 'Sup3r-Secret!',
        ];
    }
}
