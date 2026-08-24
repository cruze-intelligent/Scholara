<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Models\WowMoment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * No feature test coverage existed for this module at all before this file — flagged by the
 * original audit ("entire nursery module has no tests").
 */
class WowMomentTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_share_and_edit_a_moment_from_today(): void
    {
        Role::findOrCreate('teacher');
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');
        $student = Student::factory()->for($school)->create(['curriculum_level' => 'nursery']);

        $this->actingAs($teacher)->post(route('wow-moments.store'), [
            'student_id' => $student->id,
            'caption' => 'Frist tim shared a toy',
        ])->assertRedirect(route('wow-moments.index'));

        $moment = WowMoment::first();

        $this->actingAs($teacher)->put(route('wow-moments.update', $moment), [
            'caption' => 'First time shared a toy',
        ])->assertRedirect(route('wow-moments.index'));

        $this->assertSame('First time shared a toy', $moment->fresh()->caption);
    }

    public function test_a_different_teacher_cannot_edit_someone_elses_moment(): void
    {
        Role::findOrCreate('teacher');
        $school = School::factory()->create();
        $teacherA = User::factory()->create(['school_id' => $school->id]);
        $teacherA->assignRole('teacher');
        $teacherB = User::factory()->create(['school_id' => $school->id]);
        $teacherB->assignRole('teacher');
        $student = Student::factory()->for($school)->create(['curriculum_level' => 'nursery']);

        $moment = WowMoment::create([
            'student_id' => $student->id, 'teacher_id' => $teacherA->id, 'caption' => 'X',
        ]);

        $this->actingAs($teacherB)->get(route('wow-moments.edit', $moment))->assertForbidden();
    }

    public function test_admin_can_delete_any_moment_regardless_of_day(): void
    {
        Role::findOrCreate('teacher');
        Role::findOrCreate('admin');
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');
        $student = Student::factory()->for($school)->create(['curriculum_level' => 'nursery']);

        $moment = WowMoment::create([
            'student_id' => $student->id, 'teacher_id' => $teacher->id, 'caption' => 'X',
        ]);
        $moment->created_at = now()->subWeek();
        $moment->save();

        $this->actingAs($admin)->delete(route('wow-moments.destroy', $moment))->assertRedirect();

        $this->assertDatabaseMissing('wow_moments', ['id' => $moment->id]);
    }
}
