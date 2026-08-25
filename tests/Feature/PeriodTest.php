<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_schedule_a_period_and_the_class_teacher_sees_it(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('teacher');

        $school = School::factory()->create();
        $class = SchoolClass::factory()->for($school)->create();
        $subject = Subject::factory()->for($school)->create();
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');
        $assignment = TeacherSubjectAssignment::create(['teacher_id' => $teacher->id, 'subject_id' => $subject->id, 'school_class_id' => $class->id]);

        $response = $this->actingAs($admin)->post(route('periods.store'), [
            'teacher_subject_assignment_id' => $assignment->id,
            'day_of_week' => 'monday',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'room' => 'Room 4',
        ]);

        $response->assertRedirect(route('periods.index'));

        $response = $this->actingAs($teacher)->get(route('periods.index'));
        $response->assertViewHas('periods', fn ($periods) => $periods->get('monday')?->count() === 1);
    }

    public function test_overlapping_period_for_the_same_teacher_is_rejected(): void
    {
        Role::findOrCreate('admin');

        $school = School::factory()->create();
        $class = SchoolClass::factory()->for($school)->create();
        $subject = Subject::factory()->for($school)->create();
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $assignment = TeacherSubjectAssignment::create(['teacher_id' => $teacher->id, 'subject_id' => $subject->id, 'school_class_id' => $class->id]);

        $this->actingAs($admin)->post(route('periods.store'), [
            'teacher_subject_assignment_id' => $assignment->id,
            'day_of_week' => 'monday', 'start_time' => '09:00', 'end_time' => '10:00',
        ]);

        $response = $this->actingAs($admin)->post(route('periods.store'), [
            'teacher_subject_assignment_id' => $assignment->id,
            'day_of_week' => 'monday', 'start_time' => '09:30', 'end_time' => '10:30',
        ]);

        $response->assertStatus(422);
    }

    public function test_learner_only_sees_periods_for_their_own_class(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('learner');

        $school = School::factory()->create();
        $classA = SchoolClass::factory()->for($school)->create();
        $classB = SchoolClass::factory()->for($school)->create();
        $subject = Subject::factory()->for($school)->create();
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');
        $teacher = User::factory()->create(['school_id' => $school->id]);

        $assignmentA = TeacherSubjectAssignment::create(['teacher_id' => $teacher->id, 'subject_id' => $subject->id, 'school_class_id' => $classA->id]);
        $assignmentB = TeacherSubjectAssignment::create(['teacher_id' => $teacher->id, 'subject_id' => $subject->id, 'school_class_id' => $classB->id]);

        $this->actingAs($admin)->post(route('periods.store'), [
            'teacher_subject_assignment_id' => $assignmentA->id,
            'day_of_week' => 'monday', 'start_time' => '09:00', 'end_time' => '10:00',
        ]);
        $this->actingAs($admin)->post(route('periods.store'), [
            'teacher_subject_assignment_id' => $assignmentB->id,
            'day_of_week' => 'monday', 'start_time' => '11:00', 'end_time' => '12:00',
        ]);

        $learnerUser = User::factory()->create(['school_id' => $school->id]);
        $learnerUser->assignRole('learner');
        Student::factory()->for($school)->create(['school_class_id' => $classA->id, 'user_id' => $learnerUser->id]);

        $response = $this->actingAs($learnerUser)->get(route('periods.index'));
        $response->assertViewHas('periods', fn ($periods) => $periods->get('monday')?->count() === 1);
    }

    public function test_non_admin_cannot_schedule_a_period(): void
    {
        Role::findOrCreate('teacher');

        $school = School::factory()->create();
        $class = SchoolClass::factory()->for($school)->create();
        $subject = Subject::factory()->for($school)->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');
        $assignment = TeacherSubjectAssignment::create(['teacher_id' => $teacher->id, 'subject_id' => $subject->id, 'school_class_id' => $class->id]);

        $this->actingAs($teacher)->post(route('periods.store'), [
            'teacher_subject_assignment_id' => $assignment->id,
            'day_of_week' => 'monday', 'start_time' => '09:00', 'end_time' => '10:00',
        ])->assertForbidden();
    }
}
