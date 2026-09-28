<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\DtrEntry;
use App\Models\Student;
use App\Models\User;
use App\Services\CheckinTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanCheckinTest extends TestCase
{
    use RefreshDatabase;

    private CheckinTokenService $tokens;

    private Company $company;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tokens = app(CheckinTokenService::class);
        $this->company = Company::factory()->create();
        $this->student = Student::factory()->create(['company_id' => $this->company->id]);
    }

    public function test_scan_checkin_requires_authentication(): void
    {
        $this->postJson('/api/dtr/scan-checkin', [
            'payload' => $this->tokens->payloadFor($this->company->id),
        ])->assertUnauthorized();
    }

    public function test_non_students_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'hr', 'company_id' => $this->company->id]));

        $this->postJson('/api/dtr/scan-checkin', [
            'payload' => $this->tokens->payloadFor($this->company->id),
        ])->assertStatus(403);
    }

    public function test_valid_scan_checks_in_with_qr_method(): void
    {
        $this->actingAs($this->student->user);

        $this->postJson('/api/dtr/scan-checkin', [
            'payload' => $this->tokens->payloadFor($this->company->id),
        ])
            ->assertCreated()
            ->assertJsonPath('entry.checkin_method', 'qr')
            ->assertJsonPath('message', fn ($message) => $message !== '');

        $this->assertSame(1, $this->student->dtrEntries()->count());
        $this->assertNull($this->student->dtrEntries()->first()->time_out);
    }

    public function test_expired_token_is_rejected_with_410(): void
    {
        $this->actingAs($this->student->user);

        // Current window minus more than the grace period.
        $staleWindow = $this->tokens->currentWindow() - (CheckinTokenService::GRACE_WINDOWS + 1);

        $this->postJson('/api/dtr/scan-checkin', [
            'payload' => $this->tokens->payloadFor($this->company->id, $staleWindow),
        ])
            ->assertStatus(410)
            ->assertJsonPath('error', 'expired');

        $this->assertSame(0, $this->student->dtrEntries()->count());
    }

    public function test_token_from_the_future_is_rejected(): void
    {
        $this->actingAs($this->student->user);

        $this->postJson('/api/dtr/scan-checkin', [
            'payload' => $this->tokens->payloadFor($this->company->id, $this->tokens->currentWindow() + 1),
        ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'invalid');

        $this->assertSame(0, $this->student->dtrEntries()->count());
    }

    public function test_tampered_token_is_rejected(): void
    {
        $this->actingAs($this->student->user);

        $payload = $this->tokens->payloadFor($this->company->id);

        // Deterministically replace the HMAC with a different fixed value
        // (suffix-clobbering is flaky: a real signature ending in the
        // replacement chars would make the "tampered" payload identical).
        $tampered = preg_replace(
            '/token=[0-9a-f]+$/',
            'token='.str_repeat('0', 64),
            $payload,
        );

        if ($tampered === $payload) {
            $tampered = preg_replace('/token=[0-9a-f]+$/', 'token='.str_repeat('1', 64), $payload);
        }

        $this->postJson('/api/dtr/scan-checkin', [
            'payload' => $tampered,
        ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'invalid');

        $this->assertSame(0, $this->student->dtrEntries()->count());
    }

    public function test_replayed_token_by_same_student_is_rejected_within_window(): void
    {
        $this->actingAs($this->student->user);

        $payload = $this->tokens->payloadFor($this->company->id);

        $this->postJson('/api/dtr/scan-checkin', ['payload' => $payload])->assertCreated();

        // Photograph the QR and rescan it while the window is still valid.
        $this->postJson('/api/dtr/scan-checkin', ['payload' => $payload])
            ->assertStatus(409)
            ->assertJsonPath('error', 'reused');

        $this->assertSame(1, $this->student->dtrEntries()->count());
    }

    public function test_reused_nonce_is_released_when_checkin_fails_so_retry_works(): void
    {
        $this->actingAs($this->student->user);

        // Put the student in a state where check-in is blocked.
        $this->student->update(['status' => 'inactive']);

        $payload = $this->tokens->payloadFor($this->company->id);

        $this->postJson('/api/dtr/scan-checkin', ['payload' => $payload])->assertStatus(403);

        // Fix the blocking state and rescan the SAME still-valid token.
        // (The guard caches studentProfile across in-test sub-requests; a
        // real request re-fetches it, so drop the stale relation.)
        $this->student->update(['status' => 'active']);
        $this->student->user->unsetRelation('studentProfile');

        $this->postJson('/api/dtr/scan-checkin', ['payload' => $payload])
            ->assertCreated()
            ->assertJsonPath('entry.checkin_method', 'qr');
    }

    public function test_other_students_may_use_the_same_displayed_token(): void
    {
        $this->actingAs($this->student->user);
        $payload = $this->tokens->payloadFor($this->company->id);
        $this->postJson('/api/dtr/scan-checkin', ['payload' => $payload])->assertCreated();

        $classmate = Student::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($classmate->user);

        $this->postJson('/api/dtr/scan-checkin', ['payload' => $payload])
            ->assertCreated()
            ->assertJsonPath('entry.checkin_method', 'qr');
    }

    public function test_token_signed_for_another_company_is_rejected(): void
    {
        $this->actingAs($this->student->user);

        $otherCompany = Company::factory()->create();

        $this->postJson('/api/dtr/scan-checkin', [
            'payload' => $this->tokens->payloadFor($otherCompany->id),
        ])
            ->assertStatus(403)
            ->assertJsonPath('error', 'wrong_company');

        $this->assertSame(0, $this->student->dtrEntries()->count());
    }

    public function test_garbage_payload_is_rejected(): void
    {
        $this->actingAs($this->student->user);

        $this->postJson('/api/dtr/scan-checkin', ['payload' => 'https://example.com/whatever'])
            ->assertStatus(422)
            ->assertJsonPath('error', 'invalid');

        $this->postJson('/api/dtr/scan-checkin', ['payload' => 'not a qr payload at all'])
            ->assertStatus(422)
            ->assertJsonPath('error', 'invalid');
    }

    public function test_scan_checkin_conflict_when_already_checked_in_is_idempotent(): void
    {
        $this->actingAs($this->student->user);

        DtrEntry::create([
            'student_id' => $this->student->id,
            'date' => now()->toDateString(),
            'time_in' => now()->subHour(),
            'hours' => 0,
            'checkin_method' => 'manual',
        ]);

        $payload = $this->tokens->payloadFor($this->company->id);

        $this->postJson('/api/dtr/scan-checkin', ['payload' => $payload])
            ->assertStatus(409)
            ->assertJsonPath('error', 'already_active')
            ->assertJsonPath('idempotent', true);

        $this->assertSame(1, $this->student->dtrEntries()->count());
    }

    public function test_payload_missing_required_fields_is_rejected(): void
    {
        $this->actingAs($this->student->user);

        $this->postJson('/api/dtr/scan-checkin', [])->assertStatus(422);

        // Valid UUID-ish but not our scheme.
        $this->postJson('/api/dtr/scan-checkin', ['payload' => 'ojtsync://checkin?company=1'])
            ->assertStatus(422)
            ->assertJsonPath('error', 'invalid');
    }

    public function test_window_rollover_keeps_recent_token_valid(): void
    {
        // Freeze time near a window boundary: the token is signed for the
        // window that is about to end, verification happens in the next one.
        $edge = now()->startOfMinute()->addSeconds(55);
        $this->travelTo($edge);

        $payload = $this->tokens->payloadFor($this->company->id);

        $this->travelTo($edge->copy()->addSeconds(10)); // next window

        $this->actingAs($this->student->user);

        $this->postJson('/api/dtr/scan-checkin', ['payload' => $payload])
            ->assertCreated()
            ->assertJsonPath('entry.checkin_method', 'qr');
    }
}
