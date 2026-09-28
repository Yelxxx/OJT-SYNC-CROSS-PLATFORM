<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\DtrEntry;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DtrSyncTest extends TestCase
{
    use RefreshDatabase;

    private function studentActor(): Student
    {
        $company = Company::factory()->create();
        $student = Student::factory()->create(['company_id' => $company->id]);
        $this->actingAs($student->user);

        return $student;
    }

    public function test_sync_endpoints_require_authentication(): void
    {
        $this->postJson('/api/student/dtr/checkin', ['client_uuid' => $uuid = $this->uuid()])->assertUnauthorized();
        $this->postJson('/api/student/dtr/checkout', ['client_uuid' => $uuid])->assertUnauthorized();
        $this->getJson('/api/student/dtr/state')->assertUnauthorized();
    }

    public function test_non_students_are_forbidden(): void
    {
        $coordinator = User::factory()->create(['role' => 'coordinator']);
        $this->actingAs($coordinator);

        $this->getJson('/api/student/dtr/state')->assertStatus(403);
    }

    public function test_checkin_creates_entry_with_sync_method(): void
    {
        $student = $this->studentActor();

        $this->postJson('/api/student/dtr/checkin', ['client_uuid' => $this->uuid()])
            ->assertCreated()
            ->assertJsonPath('entry.checkin_method', 'sync');

        $this->assertSame(1, $student->dtrEntries()->count());
    }

    public function test_replayed_checkin_with_same_uuid_is_idempotent(): void
    {
        $student = $this->studentActor();
        $uuid = $this->uuid();

        $this->postJson('/api/student/dtr/checkin', ['client_uuid' => $uuid])->assertCreated();

        // The Background Sync replay or double-tap hits the same key.
        $this->postJson('/api/student/dtr/checkin', ['client_uuid' => $uuid])->assertCreated();

        $this->assertSame(1, $student->dtrEntries()->count());
    }

    public function test_checkin_conflict_is_marked_idempotent_so_client_drops_it(): void
    {
        $student = $this->studentActor();

        $this->postJson('/api/student/dtr/checkin', ['client_uuid' => $this->uuid()])->assertCreated();

        // Offline, then the student managed to clock in from another device.
        $this->postJson('/api/student/dtr/checkin', ['client_uuid' => $this->uuid()])
            ->assertStatus(409)
            ->assertJsonPath('error', 'already_active')
            ->assertJsonPath('idempotent', true);
    }

    public function test_checkin_without_company_maps_to_not_hired_payload(): void
    {
        // DTR is gated behind being hired: unhired students get the
        // middleware's not_hired payload (the offline queue drops it
        // quietly) rather than the service's no_company error.
        $student = Student::factory()->create(['company_id' => null]);
        $this->actingAs($student->user);

        $this->postJson('/api/student/dtr/checkin', ['client_uuid' => $this->uuid()])
            ->assertStatus(403)
            ->assertJsonPath('error', 'not_hired')
            ->assertJsonPath('idempotent', false);

        $this->getJson('/api/student/dtr/state')
            ->assertStatus(403)
            ->assertJsonPath('error', 'not_hired');
    }

    public function test_checkout_records_offline_device_timestamp(): void
    {
        $student = $this->studentActor();

        $entry = DtrEntry::create([
            'student_id' => $student->id,
            'date' => now()->subDay()->toDateString(),
            'time_in' => now()->subDay()->setHour(8),
            'hours' => 0,
            'checkin_method' => 'manual',
        ]);

        $timeOut = now()->subDay()->setHour(17)->toIso8601String();

        $this->postJson('/api/student/dtr/checkout', [
            'client_uuid' => $this->uuid(),
            'time_out' => $timeOut,
            'task_summary' => 'Offline shift',
        ])->assertCreated();

        $entry->refresh();

        $this->assertSame(9.0, (float) $entry->hours);
        $this->assertSame('Offline shift', $entry->task_summary);
    }

    public function test_replayed_checkout_after_entry_closed_is_idempotent_noop(): void
    {
        $student = $this->studentActor();

        $entry = DtrEntry::create([
            'student_id' => $student->id,
            'client_uuid' => $uuid = $this->uuid(),
            'date' => now()->toDateString(),
            'time_in' => now()->subHours(2),
            'hours' => 2.0,
            'time_out' => now()->subHour(),
            'checkin_method' => 'sync',
        ]);

        // Replay of a checkout that already went through (same idempotency key).
        $this->postJson('/api/student/dtr/checkout', ['client_uuid' => $uuid])
            ->assertCreated();

        $entry->refresh();

        $this->assertSame(2.0, (float) $entry->hours);
    }

    public function test_checkout_without_active_entry_reports_conflict(): void
    {
        $this->studentActor();

        $this->postJson('/api/student/dtr/checkout', ['client_uuid' => $this->uuid()])
            ->assertStatus(409)
            ->assertJsonPath('error', 'no_active_entry')
            ->assertJsonPath('idempotent', true);
    }

    public function test_state_reports_checked_in_status_and_totals(): void
    {
        $student = $this->studentActor();

        DtrEntry::create([
            'student_id' => $student->id,
            'date' => now()->toDateString(),
            'time_in' => now()->subHours(2),
            'hours' => 0,
            'checkin_method' => 'manual',
        ]);

        $this->getJson('/api/student/dtr/state')
            ->assertOk()
            ->assertJsonPath('checked_in', true)
            ->assertJsonPath('required_hours', 300)
            ->assertJsonStructure(['total_hours', 'active_entry', 'server_time']);
    }

    public function test_validation_errors_return_422_json(): void
    {
        $this->studentActor();

        $this->postJson('/api/student/dtr/checkin', [])->assertStatus(422);
        $this->postJson('/api/student/dtr/checkout', [])->assertStatus(422);
    }

    private function uuid(): string
    {
        return (string) Str::uuid();
    }
}
