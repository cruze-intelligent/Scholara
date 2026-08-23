<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\MedicationAdministration;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Notifications\MedicationAdministered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MedicationAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_nurse_can_log_administration_and_guardian_is_notified(): void
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

        $this->actingAs($nurse)->post(route('medications.store'), [
            'student_id' => $student->id,
            'medication_name' => 'Paracetamol',
            'dose' => '250mg',
            'route' => 'oral',
            'checked_right_patient' => '1',
            'checked_right_drug' => '1',
            'checked_right_dose' => '1',
            'checked_right_route' => '1',
            'checked_right_time' => '1',
        ])->assertRedirect(route('medications.index'));

        $administration = MedicationAdministration::first();
        $this->assertTrue($administration->five_rights_checked);

        Notification::assertSentTo($guardianUser, MedicationAdministered::class);
    }

    public function test_incomplete_checks_are_reflected_in_five_rights_checked(): void
    {
        Role::findOrCreate('nurse');

        $school = School::factory()->create();
        $nurse = User::factory()->create(['school_id' => $school->id]);
        $nurse->assignRole('nurse');
        $student = Student::factory()->for($school)->create();

        $this->actingAs($nurse)->post(route('medications.store'), [
            'student_id' => $student->id,
            'medication_name' => 'Ibuprofen',
            'dose' => '100mg',
            'route' => 'oral',
            'checked_right_patient' => '1',
        ]);

        $this->assertFalse(MedicationAdministration::first()->five_rights_checked);
    }

    public function test_nurse_cannot_log_administration_for_a_student_at_another_school(): void
    {
        Role::findOrCreate('nurse');

        $school = School::factory()->create();
        $nurse = User::factory()->create(['school_id' => $school->id]);
        $nurse->assignRole('nurse');

        $otherSchool = School::factory()->create();
        $otherStudent = Student::factory()->for($otherSchool)->create();

        $this->actingAs($nurse)->post(route('medications.store'), [
            'student_id' => $otherStudent->id,
            'medication_name' => 'Paracetamol',
            'dose' => '250mg',
            'route' => 'oral',
        ])->assertSessionHasErrors('student_id');

        $this->assertSame(0, MedicationAdministration::count());
    }

    public function test_nurse_only_sees_administrations_from_their_own_school(): void
    {
        Role::findOrCreate('nurse');

        $school = School::factory()->create();
        $nurse = User::factory()->create(['school_id' => $school->id]);
        $nurse->assignRole('nurse');
        $ownStudent = Student::factory()->for($school)->create();

        $otherSchool = School::factory()->create();
        $otherStudent = Student::factory()->for($otherSchool)->create();

        MedicationAdministration::create([
            'student_id' => $ownStudent->id,
            'medication_name' => 'Own-school med',
            'dose' => '1',
            'route' => 'oral',
            'administered_by' => $nurse->id,
            'administered_at' => now(),
        ]);
        MedicationAdministration::create([
            'student_id' => $otherStudent->id,
            'medication_name' => 'Other-school med',
            'dose' => '1',
            'route' => 'oral',
            'administered_by' => $nurse->id,
            'administered_at' => now(),
        ]);

        $response = $this->actingAs($nurse)->get(route('medications.index'));

        $response->assertSee('Own-school med');
        $response->assertDontSee('Other-school med');
    }
}
