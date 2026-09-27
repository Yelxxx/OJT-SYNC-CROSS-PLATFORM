<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class JournalSyncTest extends TestCase
{
    use RefreshDatabase;

    private function studentActor(): Student
    {
        $company = Company::factory()->create();
        $student = Student::factory()->create(['company_id' => $company->id]);
        $this->actingAs($student->user);

        return $student;
    }

    private function uuid(): string
    {
        return (string) Str::uuid();
    }

    private function validPayload(): array
    {
        return [
            'client_uuid' => $this->uuid(),
            'week' => 3,
            'title' => 'Week 3: Offline draft',
            'entry' => 'Wrote this on the bus with no signal.',
        ];
    }

    public function test_store_requires_authentication(): void
    {
        $this->postJson('/api/student/journals', ['client_uuid' => $this->uuid()])
            ->assertUnauthorized();
    }

    public function test_non_students_are_forbidden(): void
    {
        $coordinator = User::factory()->create(['role' => User::ROLE_COORDINATOR]);
        $this->actingAs($coordinator);

        $this->postJson('/api/student/journals', $this->validPayload())->assertStatus(403);
    }

    public function test_store_creates_journal_with_sync_metadata(): void
    {
        $student = $this->studentActor();
        $uuid = $this->uuid();

        $this->postJson('/api/student/journals', [
            'client_uuid' => $uuid,
            'week' => 3,
            'title' => 'Week 3: Offline draft',
            'entry' => 'Wrote this on the bus with no signal.',
            'submitted_at' => now()->subHour()->toIso8601String(),
        ])->assertCreated()
            ->assertJsonPath('journal.client_uuid', $uuid)
            ->assertJsonPath('journal.week', 3);

        $journal = $student->eJournals()->first();
        $this->assertSame($uuid, $journal->client_uuid);
        $this->assertTrue($journal->submitted_at->gt(now()->subHours(2)));
        $this->assertTrue($journal->submitted_at->lt(now()->subMinutes(30)));
    }

    public function test_replayed_journal_with_same_uuid_is_idempotent(): void
    {
        $student = $this->studentActor();
        $payload = $this->validPayload();

        $this->postJson('/api/student/journals', $payload)->assertCreated();
        $this->postJson('/api/student/journals', $payload)->assertCreated();

        $this->assertSame(1, $student->eJournals()->count());
    }

    public function test_submitted_at_defaults_to_now_when_absent(): void
    {
        $student = $this->studentActor();

        $this->postJson('/api/student/journals', $this->validPayload())->assertCreated();

        $journal = $student->eJournals()->first();
        $this->assertTrue($journal->submitted_at->diffInSeconds(now()) < 60);
    }

    public function test_validation_errors_return_422_json(): void
    {
        $this->studentActor();

        $this->postJson('/api/student/journals', ['client_uuid' => $this->uuid()])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['week', 'title', 'entry']);

        $this->postJson('/api/student/journals', array_merge($this->validPayload(), ['week' => 99]))
            ->assertStatus(422);
    }
}
