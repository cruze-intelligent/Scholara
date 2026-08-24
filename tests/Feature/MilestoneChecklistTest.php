<?php

namespace Tests\Feature;

use App\Models\MilestoneChecklist;
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
class MilestoneChecklistTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_record_and_edit_a_milestone_recorded_today(): void
    {
        Role::findOrCreate('teacher');
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');
        $student = Student::factory()->for($school)->create(['curriculum_level' => 'nursery']);

        $this->actingAs($teacher)->post(route('milestones.store'), [
            'student_id' => $student->id,
            'domain' => 'physical',
            'milestone_label' => 'Walks unaided',
        ])->assertRedirect(route('milestones.index'));

        $milestone = MilestoneChecklist::first();

        $this->actingAs($teacher)->put(route('milestones.update', $milestone), [
            'domain' => 'physical',
            'milestone_label' => 'Runs confidently',
        ])->assertRedirect(route('milestones.index'));

        $this->assertSame('Runs confidently', $milestone->fresh()->milestone_label);
    }

    public function test_cannot_edit_a_milestone_recorded_on_a_previous_day(): void
    {
        Role::findOrCreate('teacher');
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');
        $student = Student::factory()->for($school)->create(['curriculum_level' => 'nursery']);

        $milestone = MilestoneChecklist::create([
            'student_id' => $student->id, 'domain' => 'physical', 'milestone_label' => 'Walks unaided',
        ]);
        $milestone->created_at = now()->subDay();
        $milestone->save();

        $this->actingAs($teacher)->get(route('milestones.edit', $milestone))->assertStatus(422);
    }

    public function test_parent_cannot_record_milestones(): void
    {
        Role::findOrCreate('parent');
        $school = School::factory()->create();
        $parent = User::factory()->create(['school_id' => $school->id]);
        $parent->assignRole('parent');

        $this->actingAs($parent)->get(route('milestones.index'))->assertForbidden();
    }
}
