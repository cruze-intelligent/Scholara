<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_enrolling_a_student_auto_provisions_a_parent_login(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('parent');

        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('students.store'), [
            'first_name' => 'Amina',
            'last_name' => 'Okello',
            'curriculum_level' => 'primary',
            'guardian_name' => 'Grace Okello',
            'guardian_phone' => '0700123456',
        ]);

        $student = Student::where('first_name', 'Amina')->first();
        $response->assertRedirect(route('students.show', $student));

        $guardianUser = User::where('phone', '0700123456')->first();
        $this->assertNotNull($guardianUser);
        $this->assertTrue($guardianUser->hasRole('parent'));
        $this->assertTrue($guardianUser->guardian->students->contains($student->id));
    }

    public function test_a_sibling_reuses_the_existing_guardian_login_by_phone(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('parent');

        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('students.store'), [
            'first_name' => 'Amina', 'curriculum_level' => 'primary',
            'guardian_name' => 'Grace Okello', 'guardian_phone' => '0700123456',
        ]);

        $this->actingAs($admin)->post(route('students.store'), [
            'first_name' => 'Brian', 'curriculum_level' => 'primary',
            'guardian_name' => 'Grace Okello', 'guardian_phone' => '0700123456',
        ]);

        $this->assertSame(1, User::where('phone', '0700123456')->count());
        $guardianUser = User::where('phone', '0700123456')->first();
        $this->assertSame(2, $guardianUser->guardian->students->count());
    }

    public function test_enrolling_a_student_requires_a_guardian_phone_or_email(): void
    {
        Role::findOrCreate('admin');

        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('students.store'), [
            'first_name' => 'Amina',
            'curriculum_level' => 'primary',
            'guardian_name' => 'Grace Okello',
        ])->assertSessionHasErrors();

        $this->assertSame(0, Student::count());
    }

    public function test_admin_can_edit_an_existing_students_curriculum_level(): void
    {
        Role::findOrCreate('admin');

        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');
        $student = Student::factory()->for($school)->create(['curriculum_level' => 'nursery']);

        $this->actingAs($admin)->put(route('students.update', $student), [
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'curriculum_level' => 'primary',
        ])->assertRedirect(route('students.show', $student));

        $this->assertSame('primary', $student->fresh()->curriculum_level);
    }

    public function test_admin_can_retroactively_link_a_guardian_to_an_unlinked_student(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('parent');

        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');
        $student = Student::factory()->for($school)->create();

        $this->actingAs($admin)->post(route('students.guardians.store', $student), [
            'guardian_name' => 'Peter Okello',
            'guardian_email' => 'peter@example.com',
        ])->assertRedirect(route('students.edit', $student));

        $guardianUser = User::where('email', 'peter@example.com')->first();
        $this->assertTrue($guardianUser->guardian->students->contains($student->id));
    }

    public function test_non_admin_cannot_enroll_a_student(): void
    {
        Role::findOrCreate('teacher');

        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');

        $this->actingAs($teacher)->post(route('students.store'), [
            'first_name' => 'X', 'curriculum_level' => 'primary',
            'guardian_name' => 'Y', 'guardian_phone' => '0700000000',
        ])->assertForbidden();
    }

    public function test_parent_can_log_in_with_either_phone_or_email(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('parent');

        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('students.store'), [
            'first_name' => 'Amina', 'curriculum_level' => 'primary',
            'guardian_name' => 'Grace Okello', 'guardian_phone' => '0700123456',
        ]);

        $guardianUser = User::where('phone', '0700123456')->first();
        $guardianUser->update(['password' => bcrypt('secret123')]);

        $this->post('/login', ['email' => '0700123456', 'password' => 'secret123'])->assertRedirect(route('dashboard'));
        $this->post('/logout');

        $this->post('/login', ['email' => '0700123456', 'password' => 'wrong'])->assertSessionHasErrors();
    }
}
