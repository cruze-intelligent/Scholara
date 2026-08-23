<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\ClinicVisit;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_academics_report_averages_scores_by_subject_and_term(): void
    {
        Role::findOrCreate('teacher');
        $school = School::factory()->create();
        $class = SchoolClass::factory()->for($school)->create();
        $subject = Subject::factory()->for($school)->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');
        $studentA = Student::factory()->for($school)->create();
        $studentB = Student::factory()->for($school)->create();

        $assessment = Assessment::create([
            'school_id' => $school->id, 'subject_id' => $subject->id, 'school_class_id' => $class->id,
            'type' => 'EOT', 'term' => 'Term 2 2026', 'max_score' => 100,
        ]);
        AssessmentScore::create(['assessment_id' => $assessment->id, 'student_id' => $studentA->id, 'raw_score' => 80, 'scaled_score' => 80, 'recorded_by' => $teacher->id, 'recorded_at' => now()]);
        AssessmentScore::create(['assessment_id' => $assessment->id, 'student_id' => $studentB->id, 'raw_score' => 40, 'scaled_score' => 40, 'recorded_by' => $teacher->id, 'recorded_at' => now()]);

        $response = $this->actingAs($teacher)->get(route('reports.academics'));

        $response->assertOk();
        $response->assertSee($subject->name);
        $response->assertSee('60'); // (80+40)/2 average
        $response->assertSee($studentB->last_name); // below-60 list includes the weaker student
    }

    public function test_health_report_groups_clinic_visits_by_reason(): void
    {
        Role::findOrCreate('nurse');
        $school = School::factory()->create();
        $nurse = User::factory()->create(['school_id' => $school->id]);
        $nurse->assignRole('nurse');
        $student = Student::factory()->for($school)->create();

        ClinicVisit::create(['student_id' => $student->id, 'reason' => 'Headache', 'outcome' => 'returned_to_class', 'logged_by' => $nurse->id, 'occurred_at' => now()]);
        ClinicVisit::create(['student_id' => $student->id, 'reason' => 'Headache', 'outcome' => 'returned_to_class', 'logged_by' => $nurse->id, 'occurred_at' => now()]);

        $response = $this->actingAs($nurse)->get(route('reports.health'));

        $response->assertOk();
        $response->assertSee('Headache');
    }

    public function test_non_teacher_cannot_access_academics_report(): void
    {
        Role::findOrCreate('librarian');
        $school = School::factory()->create();
        $librarian = User::factory()->create(['school_id' => $school->id]);
        $librarian->assignRole('librarian');

        $this->actingAs($librarian)->get(route('reports.academics'))->assertForbidden();
    }
}
