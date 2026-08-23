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

class AssessmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_teacher_can_create_an_assessment_and_enter_scores(): void
    {
        Role::findOrCreate('teacher');

        $school = School::factory()->create();
        $class = SchoolClass::factory()->for($school)->create();
        $subject = Subject::factory()->for($school)->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');
        TeacherSubjectAssignment::create(['teacher_id' => $teacher->id, 'subject_id' => $subject->id, 'school_class_id' => $class->id]);
        $student = Student::factory()->for($school)->create(['school_class_id' => $class->id]);

        $response = $this->actingAs($teacher)->post(route('assessments.store'), [
            'assignment_id' => TeacherSubjectAssignment::first()->id,
            'type' => 'MOT',
            'term' => 'Term 2 2026',
            'max_score' => 50,
        ]);

        $assessment = \App\Models\Assessment::first();
        $response->assertRedirect(route('assessments.show', $assessment));
        $this->assertSame($subject->id, $assessment->subject_id);

        $scoreResponse = $this->actingAs($teacher)->post(route('assessments.scores.store', $assessment), [
            'scores' => [$student->id => 25],
        ]);

        $scoreResponse->assertRedirect();
        $score = $assessment->scores()->first();
        $this->assertSame('25.00', $score->raw_score);
        $this->assertSame(50.0, (float) $score->scaled_score);
    }

    public function test_teacher_cannot_create_assessment_for_an_unassigned_subject(): void
    {
        Role::findOrCreate('teacher');

        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');

        $response = $this->actingAs($teacher)->post(route('assessments.store'), [
            'assignment_id' => 999,
            'type' => 'MOT',
            'term' => 'Term 2 2026',
            'max_score' => 50,
        ]);

        $response->assertNotFound();
    }

    public function test_non_teacher_role_cannot_access_assessments(): void
    {
        Role::findOrCreate('learner');

        $school = School::factory()->create();
        $learner = User::factory()->create(['school_id' => $school->id]);
        $learner->assignRole('learner');

        $this->actingAs($learner)->get(route('assessments.index'))->assertForbidden();
    }

    public function test_admin_sees_every_assessment_at_their_school_not_just_their_own_assignments(): void
    {
        Role::findOrCreate('teacher');
        Role::findOrCreate('admin');

        $school = School::factory()->create();
        $class = SchoolClass::factory()->for($school)->create();
        $subject = Subject::factory()->for($school)->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');
        TeacherSubjectAssignment::create(['teacher_id' => $teacher->id, 'subject_id' => $subject->id, 'school_class_id' => $class->id]);

        \App\Models\Assessment::create([
            'school_id' => $school->id,
            'subject_id' => $subject->id,
            'school_class_id' => $class->id,
            'type' => 'MOT',
            'term' => 'Term 2 2026',
            'max_score' => 50,
        ]);

        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        // Admin has no teaching assignments of their own — before the fix this returned an
        // empty list despite being "authorized" via the role:teacher|admin route middleware.
        $response = $this->actingAs($admin)->get(route('assessments.index'));

        $response->assertOk();
        $response->assertViewHas('assessments', fn ($assessments) => $assessments->count() === 1);
    }
}
