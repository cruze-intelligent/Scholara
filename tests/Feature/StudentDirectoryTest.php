<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_search_the_student_directory_by_name(): void
    {
        Role::findOrCreate('librarian');

        $school = School::factory()->create();
        $librarian = User::factory()->create(['school_id' => $school->id]);
        $librarian->assignRole('librarian');
        Student::factory()->for($school)->create(['first_name' => 'Amina']);
        Student::factory()->for($school)->create(['first_name' => 'Brian']);

        $response = $this->actingAs($librarian)->get(route('students.index', ['search' => 'Amina']));

        $response->assertViewHas('students', fn ($students) => $students->total() === 1);
    }

    public function test_librarian_can_tag_a_student_as_a_defaulter_but_not_a_fee_defaulter(): void
    {
        Role::findOrCreate('librarian');

        $school = School::factory()->create();
        $librarian = User::factory()->create(['school_id' => $school->id]);
        $librarian->assignRole('librarian');
        $student = Student::factory()->for($school)->create();

        $this->actingAs($librarian)->post(route('students.tags.store', $student), [
            'tag' => 'library_defaulter',
            'note' => 'Overdue: Things Fall Apart',
        ])->assertRedirect();

        $this->assertDatabaseHas('student_tags', ['student_id' => $student->id, 'tag' => 'library_defaulter']);

        $this->actingAs($librarian)->post(route('students.tags.store', $student), [
            'tag' => 'fee_defaulter',
        ])->assertSessionHasErrors('tag');
        $this->assertDatabaseMissing('student_tags', ['student_id' => $student->id, 'tag' => 'fee_defaulter']);
    }

    public function test_admin_can_view_any_students_profile_and_remove_any_tag(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('librarian');

        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');
        $librarian = User::factory()->create(['school_id' => $school->id]);
        $librarian->assignRole('librarian');
        $student = Student::factory()->for($school)->create();
        $tag = StudentTag::create(['school_id' => $school->id, 'student_id' => $student->id, 'tag' => 'library_defaulter', 'tagged_by' => $librarian->id]);

        $this->actingAs($admin)->get(route('students.show', $student))->assertOk();
        $this->actingAs($admin)->delete(route('students.tags.destroy', $tag))->assertRedirect();
        $this->assertDatabaseMissing('student_tags', ['id' => $tag->id]);
    }

    public function test_parent_can_view_their_own_childs_profile_with_analytics_but_not_a_strangers(): void
    {
        Role::findOrCreate('parent');

        $school = School::factory()->create();
        $ownChild = Student::factory()->for($school)->create();
        $strangerChild = Student::factory()->for($school)->create();

        $parentUser = User::factory()->create(['school_id' => $school->id]);
        $parentUser->assignRole('parent');
        $guardian = Guardian::create(['user_id' => $parentUser->id]);
        $guardian->students()->attach($ownChild->id);

        $this->actingAs($parentUser)->get(route('students.show', $ownChild))->assertOk();
        $this->actingAs($parentUser)->get(route('students.show', $strangerChild))->assertForbidden();
    }

    public function test_non_staff_non_guardian_cannot_browse_the_directory(): void
    {
        Role::findOrCreate('learner');

        $school = School::factory()->create();
        $learnerUser = User::factory()->create(['school_id' => $school->id]);
        $learnerUser->assignRole('learner');

        $this->actingAs($learnerUser)->get(route('students.index'))->assertForbidden();
    }
}
