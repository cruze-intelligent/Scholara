<?php

namespace Tests\Feature;

use App\Models\ClinicVisit;
use App\Models\Guardian;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Notifications\ClinicVisitLogged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClinicVisitTest extends TestCase
{
    use RefreshDatabase;

    public function test_nurse_can_log_a_visit_and_guardian_is_notified(): void
    {
        Notification::fake();
        Role::findOrCreate('nurse');

        $school = School::factory()->create();
        $nurse = User::factory()->create(['school_id' => $school->id]);
        $nurse->assignRole('nurse');

        $student = Student::factory()->for($school)->create();
        $guardianUser = User::factory()->create(['school_id' => $school->id]);
        $guardian = Guardian::create(['user_id' => $guardianUser->id]);
        $student->guardians()->attach($guardian);

        $this->actingAs($nurse)->post(route('clinic-visits.store'), [
            'student_id' => $student->id,
            'reason' => 'Headache',
            'outcome' => 'returned_to_class',
        ])->assertRedirect(route('clinic-visits.index'));

        $this->assertSame(1, ClinicVisit::count());
        Notification::assertSentTo($guardianUser, ClinicVisitLogged::class);
    }

    public function test_nurse_cannot_log_a_visit_for_a_student_at_another_school(): void
    {
        Role::findOrCreate('nurse');

        $school = School::factory()->create();
        $nurse = User::factory()->create(['school_id' => $school->id]);
        $nurse->assignRole('nurse');

        $otherSchool = School::factory()->create();
        $otherStudent = Student::factory()->for($otherSchool)->create();

        $this->actingAs($nurse)->post(route('clinic-visits.store'), [
            'student_id' => $otherStudent->id,
            'reason' => 'Headache',
            'outcome' => 'returned_to_class',
        ])->assertSessionHasErrors('student_id');

        $this->assertSame(0, ClinicVisit::count());
    }

    public function test_nurse_only_sees_visits_from_their_own_school(): void
    {
        Role::findOrCreate('nurse');

        $school = School::factory()->create();
        $nurse = User::factory()->create(['school_id' => $school->id]);
        $nurse->assignRole('nurse');
        $ownStudent = Student::factory()->for($school)->create();

        $otherSchool = School::factory()->create();
        $otherStudent = Student::factory()->for($otherSchool)->create();

        ClinicVisit::create([
            'student_id' => $ownStudent->id,
            'reason' => 'Own-school reason',
            'outcome' => 'returned_to_class',
            'logged_by' => $nurse->id,
            'occurred_at' => now(),
        ]);
        ClinicVisit::create([
            'student_id' => $otherStudent->id,
            'reason' => 'Other-school reason',
            'outcome' => 'returned_to_class',
            'logged_by' => $nurse->id,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($nurse)->get(route('clinic-visits.index'));

        $response->assertSee('Own-school reason');
        $response->assertDontSee('Other-school reason');
    }
}
