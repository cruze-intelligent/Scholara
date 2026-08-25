<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Guardian;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportCardTest extends TestCase
{
    use RefreshDatabase;

    private function seedScore(School $school, SchoolClass $class, Subject $subject, Student $student): void
    {
        $assessment = Assessment::create([
            'school_id' => $school->id, 'subject_id' => $subject->id, 'school_class_id' => $class->id,
            'type' => 'EOT', 'term' => 'Term 2 2026', 'max_score' => 100,
        ]);
        $recorder = User::factory()->create(['school_id' => $school->id]);
        AssessmentScore::create([
            'assessment_id' => $assessment->id, 'student_id' => $student->id,
            'raw_score' => 70, 'scaled_score' => 70, 'recorded_by' => $recorder->id, 'recorded_at' => now(),
        ]);
    }

    public function test_learner_can_download_their_own_report_card(): void
    {
        Role::findOrCreate('learner');

        $school = School::factory()->create();
        $class = SchoolClass::factory()->for($school)->create();
        $subject = Subject::factory()->for($school)->create();
        $learnerUser = User::factory()->create(['school_id' => $school->id]);
        $learnerUser->assignRole('learner');
        $student = Student::factory()->for($school)->create(['school_class_id' => $class->id, 'user_id' => $learnerUser->id]);
        $this->seedScore($school, $class, $subject, $student);

        $this->actingAs($learnerUser)->get(route('students.report-card', $student))->assertOk();
    }

    public function test_parent_can_download_their_childs_report_card(): void
    {
        Role::findOrCreate('parent');

        $school = School::factory()->create();
        $class = SchoolClass::factory()->for($school)->create();
        $subject = Subject::factory()->for($school)->create();
        $student = Student::factory()->for($school)->create(['school_class_id' => $class->id]);
        $this->seedScore($school, $class, $subject, $student);

        $parentUser = User::factory()->create(['school_id' => $school->id]);
        $parentUser->assignRole('parent');
        $guardian = Guardian::create(['user_id' => $parentUser->id]);
        $guardian->students()->attach($student->id);

        $this->actingAs($parentUser)->get(route('students.report-card', $student))->assertOk();
    }

    public function test_assigned_teacher_can_download_but_an_unassigned_one_cannot(): void
    {
        Role::findOrCreate('teacher');

        $school = School::factory()->create();
        $class = SchoolClass::factory()->for($school)->create();
        $subject = Subject::factory()->for($school)->create();
        $student = Student::factory()->for($school)->create(['school_class_id' => $class->id]);
        $this->seedScore($school, $class, $subject, $student);

        $assignedTeacher = User::factory()->create(['school_id' => $school->id]);
        $assignedTeacher->assignRole('teacher');
        TeacherSubjectAssignment::create(['teacher_id' => $assignedTeacher->id, 'subject_id' => $subject->id, 'school_class_id' => $class->id]);

        $unassignedTeacher = User::factory()->create(['school_id' => $school->id]);
        $unassignedTeacher->assignRole('teacher');

        $this->actingAs($assignedTeacher)->get(route('students.report-card', $student))->assertOk();
        $this->actingAs($unassignedTeacher)->get(route('students.report-card', $student))->assertForbidden();
    }

    public function test_unrelated_parent_cannot_download_a_different_childs_report_card(): void
    {
        Role::findOrCreate('parent');

        $school = School::factory()->create();
        $student = Student::factory()->for($school)->create();

        $parentUser = User::factory()->create(['school_id' => $school->id]);
        $parentUser->assignRole('parent');
        Guardian::create(['user_id' => $parentUser->id]);

        $this->actingAs($parentUser)->get(route('students.report-card', $student))->assertForbidden();
    }
}
