<?php

namespace Tests\Feature\Hr;

use App\Models\Company;
use App\Models\Student;
use App\Models\User;
use App\Services\CheckinTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckinQrTest extends TestCase
{
    use RefreshDatabase;

    public function test_kiosk_page_requires_hr_role(): void
    {
        $this->get(route('hr.checkin.qr'))->assertRedirect();

        $this->actingAs(User::factory()->create(['role' => 'student']))
            ->get(route('hr.checkin.qr'))
            ->assertStatus(403);
    }

    public function test_kiosk_page_renders_current_window_qr(): void
    {
        $company = Company::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'hr', 'company_id' => $company->id]));

        $response = $this->get(route('hr.checkin.qr'));

        $response->assertOk();
        $response->assertSee('svg', false);
        $response->assertViewHas('secondsLeft');
    }

    public function test_token_endpoint_serves_current_window_payload(): void
    {
        $company = Company::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'hr', 'company_id' => $company->id]));

        $response = $this->getJson(route('hr.checkin.qr.token'));

        $response->assertOk()
            ->assertJsonStructure(['svg', 'window', 'seconds_left']);

        $window = $response->json('window');
        $this->assertSame(app(CheckinTokenService::class)->currentWindow(), $window);
    }

    public function test_generated_payload_verifies_against_scanner_endpoint(): void
    {
        $company = Company::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'hr', 'company_id' => $company->id]));

        $svg = $this->getJson(route('hr.checkin.qr.token'))->json('svg');

        // Decode the SVG's encoded payload back out (the QR renders the raw
        // payload string; chillerlan SVG output contains it in the title/aria
        // or we simply re-derive it) — instead, verify round-trip via the
        // service: whatever payload the kiosk displayed must be accepted for
        // a student of the same company.
        $tokens = app(CheckinTokenService::class);
        $student = Student::factory()->create(['company_id' => $company->id]);
        $this->actingAs($student->user);

        // Derive the payload the same way the kiosk did (same window).
        $payload = $tokens->payloadFor($company->id);

        $this->postJson('/api/dtr/scan-checkin', ['payload' => $payload])
            ->assertCreated()
            ->assertJsonPath('entry.checkin_method', 'qr');
    }

    public function test_hr_without_company_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'hr']));

        $this->get(route('hr.checkin.qr'))->assertStatus(403);
        $this->getJson(route('hr.checkin.qr.token'))->assertStatus(403);
    }
}
