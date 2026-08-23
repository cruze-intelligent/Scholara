<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\AttendanceRecord;
use App\Models\Notice;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LearnerPortalTest extends TestCase
{
    use RefreshDatabase;

    private function makeLearner(): array
    {
        Role::findOrCreate('learner');
        $school = School::factory()->create();
        $class = SchoolClass::factory()->for($school)->create();
        $learnerUser = User::factory()->create(['school_id' => $school->id]);
        $learnerUser->assignRole('learner');
        $student = Student::factory()->for($school)->create(['school_class_id' => $class->id, 'user_id' => $learnerUser->id]);
        $staffUser = User::factory()->create(['school_id' => $school->id]);

        return [$learnerUser, $student, $school, $class, $staffUser];
    }

    public function test_learner_sees_their_own_assessment_scores(): void
    {
        [$learnerUser, $student, $school, $class, $staffUser] = $this->makeLearner();
        $subject = Subject::factory()->for($school)->create();
        $assessment = Assessment::create([
            'school_id' => $school->id,
            'subject_id' => $subject->id,
            'school_class_id' => $class->id,
            'type' => 'MOT',
            'term' => 'Term 2 2026',
            'max_score' => 50,
        ]);
        AssessmentScore::create([
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
            'raw_score' => 40,
            'scaled_score' => 80,
            'recorded_by' => $staffUser->id,
            'recorded_at' => now(),
        ]);

        $response = $this->actingAs($learnerUser)->get(route('learner.assessments'));

        $response->assertOk();
        $response->assertSee($subject->name);
    }

    public function test_learner_sees_their_own_attendance(): void
    {
        [$learnerUser, $student, , $class, $staffUser] = $this->makeLearner();

        AttendanceRecord::create([
            'school_class_id' => $class->id,
            'student_id' => $student->id,
            'date' => now(),
            'status' => 'present',
            'recorded_by' => $staffUser->id,
        ]);

        $response = $this->actingAs($learnerUser)->get(route('learner.attendance'));

        $response->assertOk();
        $response->assertSee('Present');
    }

    public function test_learner_sees_published_notices_from_their_school_only(): void
    {
        [$learnerUser, , $school, , $staffUser] = $this->makeLearner();

        Notice::create([
            'school_id' => $school->id,
            'author_id' => $staffUser->id,
            'title' => 'Own school notice',
            'body' => 'Hello',
            'published_at' => now(),
        ]);

        $otherSchool = School::factory()->create();
        $otherStaffUser = User::factory()->create(['school_id' => $otherSchool->id]);
        Notice::create([
            'school_id' => $otherSchool->id,
            'author_id' => $otherStaffUser->id,
            'title' => 'Other school notice',
            'body' => 'Hello',
            'published_at' => now(),
        ]);

        $response = $this->actingAs($learnerUser)->get(route('learner.notices'));

        $response->assertSee('Own school notice');
        $response->assertDontSee('Other school notice');
    }

    public function test_non_learner_cannot_access_learner_routes(): void
    {
        Role::findOrCreate('teacher');
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');

        $this->actingAs($teacher)->get(route('learner.assessments'))->assertForbidden();
    }
}
