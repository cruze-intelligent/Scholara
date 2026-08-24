<?php

namespace Tests\Feature;

use App\Models\DailyActivityLog;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * No feature test coverage existed for this module at all before this file — flagged by the
 * original audit ("entire nursery module has no tests").
 */
class DailyActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_log_and_edit_todays_entry(): void
    {
        Role::findOrCreate('teacher');
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');
        $student = Student::factory()->for($school)->create(['curriculum_level' => 'nursery']);

        $this->actingAs($teacher)->post(route('daily-activity-logs.store'), [
            'student_id' => $student->id,
            'date' => now()->toDateString(),
            'meals' => 'breakfast, lunch',
            'bathroom_breaks' => 2,
        ])->assertRedirect(route('daily-activity-logs.index'));

        $log = DailyActivityLog::first();

        $this->actingAs($teacher)->put(route('daily-activity-logs.update', $log), [
            'meals' => 'breakfast, lunch, snack',
            'bathroom_breaks' => 3,
        ])->assertRedirect(route('daily-activity-logs.index'));

        $this->assertSame(3, $log->fresh()->bathroom_breaks);
        $this->assertSame(['breakfast', 'lunch', 'snack'], $log->fresh()->meals);
    }

    public function test_cannot_edit_a_log_from_a_previous_day(): void
    {
        Role::findOrCreate('teacher');
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');
        $student = Student::factory()->for($school)->create(['curriculum_level' => 'nursery']);

        $log = DailyActivityLog::create([
            'student_id' => $student->id, 'date' => now()->subDay(), 'logged_by' => $teacher->id,
        ]);
        $log->created_at = now()->subDay();
        $log->save();

        $this->actingAs($teacher)->get(route('daily-activity-logs.edit', $log))->assertStatus(422);
    }

    public function test_a_different_teacher_cannot_edit_someone_elses_log(): void
    {
        Role::findOrCreate('teacher');
        $school = School::factory()->create();
        $teacherA = User::factory()->create(['school_id' => $school->id]);
        $teacherA->assignRole('teacher');
        $teacherB = User::factory()->create(['school_id' => $school->id]);
        $teacherB->assignRole('teacher');
        $student = Student::factory()->for($school)->create(['curriculum_level' => 'nursery']);

        $log = DailyActivityLog::create([
            'student_id' => $student->id, 'date' => now(), 'logged_by' => $teacherA->id,
        ]);

        $this->actingAs($teacherB)->get(route('daily-activity-logs.edit', $log))->assertForbidden();
    }
}
