<?php

namespace Tests\Feature\Student;

use App\Models\EJournal;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_create_journal_entry(): void
    {
        $student = Student::factory()->create();
        $this->actingAs($student->user);

        $this->post(route('student.journals.store'), [
            'week' => 1,
            'title' => 'First Week',
            'entry' => 'Learned about the codebase and met the team.',
        ])->assertRedirect(route('student.journals.index'));

        $this->assertDatabaseHas('e_journals', [
            'student_id' => $student->id,
            'week' => 1,
            'title' => 'First Week',
        ]);
    }

    public function test_student_can_delete_own_journal_entry(): void
    {
        $student = Student::factory()->create();
        $this->actingAs($student->user);

        $journal = EJournal::create([
            'student_id' => $student->id,
            'week' => 2,
            'title' => 'Week Two',
            'entry' => 'Content here.',
        ]);

        $this->delete(route('student.journals.destroy', $journal))->assertRedirect();

        $this->assertDatabaseMissing('e_journals', ['id' => $journal->id]);
    }

    public function test_student_cannot_delete_another_students_journal(): void
    {
        $student = Student::factory()->create();
        $this->actingAs($student->user);

        $other = Student::factory()->create();
        $journal = EJournal::create([
            'student_id' => $other->id,
            'week' => 1,
            'title' => 'Not mine',
            'entry' => 'Content.',
        ]);

        $this->delete(route('student.journals.destroy', $journal))->assertStatus(403);
    }
}
