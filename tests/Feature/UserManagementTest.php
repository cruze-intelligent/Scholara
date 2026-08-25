<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): array
    {
        Role::findOrCreate('admin');
        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        return [$admin, $school];
    }

    public function test_admin_can_create_a_parent_linked_to_an_existing_child(): void
    {
        Role::findOrCreate('parent');
        [$admin, $school] = $this->makeAdmin();
        $child = Student::factory()->for($school)->create();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Jane Parent',
            'email' => 'jane@example.com',
            'role' => 'parent',
            'relationship_to_student' => 'mother',
            'child_ids' => [$child->id],
        ]);

        $response->assertRedirect(route('users.index'));

        $user = User::where('email', 'jane@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('parent'));
        $this->assertNotNull($user->guardian);
        $this->assertTrue($user->guardian->students->contains($child));
    }

    public function test_creating_a_parent_without_any_child_fails_validation(): void
    {
        Role::findOrCreate('parent');
        [$admin] = $this->makeAdmin();

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Jane Parent',
            'email' => 'jane@example.com',
            'role' => 'parent',
            'relationship_to_student' => 'mother',
        ])->assertSessionHasErrors('child_ids');

        $this->assertSame(0, User::where('email', 'jane@example.com')->count());
    }

    public function test_admin_can_create_a_parent_with_a_brand_new_child(): void
    {
        Role::findOrCreate('parent');
        [$admin] = $this->makeAdmin();

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Jane Parent',
            'email' => 'jane@example.com',
            'role' => 'parent',
            'relationship_to_student' => 'mother',
            'new_child_first_name' => 'Baby',
            'new_child_last_name' => 'Parent',
            'new_child_gender' => 'female',
            'new_child_curriculum_level' => 'nursery',
        ])->assertRedirect(route('users.index'));

        $child = Student::where('first_name', 'Baby')->firstOrFail();
        $this->assertSame('nursery', $child->curriculum_level);

        $user = User::where('email', 'jane@example.com')->firstOrFail();
        $this->assertTrue($user->guardian->students->contains($child));
    }

    public function test_admin_can_create_a_learner_linked_to_an_existing_unlinked_student(): void
    {
        Role::findOrCreate('learner');
        [$admin, $school] = $this->makeAdmin();
        $student = Student::factory()->for($school)->create();

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Kid Learner',
            'email' => 'kid@example.com',
            'role' => 'learner',
            'learner_student_id' => $student->id,
        ])->assertRedirect(route('users.index'));

        $user = User::where('email', 'kid@example.com')->firstOrFail();
        $this->assertSame($user->id, $student->fresh()->user_id);
    }

    public function test_admin_can_create_a_staff_member_with_a_profile(): void
    {
        Role::findOrCreate('teacher');
        [$admin] = $this->makeAdmin();

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Mr Teacher',
            'email' => 'teacher2@example.com',
            'role' => 'teacher',
            'role_title' => 'Class Teacher',
            'monthly_gross_salary' => 900000,
        ])->assertRedirect(route('users.index'));

        $user = User::where('email', 'teacher2@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('teacher'));
        $this->assertNotNull($user->staffProfile);
        $this->assertEquals(900000, $user->staffProfile->monthly_gross_salary);
    }

    public function test_admin_can_upload_a_staff_photo_and_a_new_childs_photo(): void
    {
        Storage::fake('public');
        Role::findOrCreate('teacher');
        Role::findOrCreate('parent');
        [$admin] = $this->makeAdmin();

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Mr Teacher',
            'email' => 'teacher3@example.com',
            'role' => 'teacher',
            'photo' => UploadedFile::fake()->image('staff.jpg'),
        ]);

        $staffUser = User::where('email', 'teacher3@example.com')->firstOrFail();
        $this->assertNotNull($staffUser->staffProfile->photo_path);

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Jane Parent',
            'email' => 'jane2@example.com',
            'role' => 'parent',
            'relationship_to_student' => 'mother',
            'new_child_first_name' => 'Baby',
            'new_child_last_name' => 'Two',
            'new_child_gender' => 'female',
            'new_child_curriculum_level' => 'primary',
            'new_child_photo' => UploadedFile::fake()->image('child.jpg'),
        ]);

        $child = Student::where('first_name', 'Baby')->where('last_name', 'Two')->firstOrFail();
        $this->assertNotNull($child->photo_path);
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $user = User::factory()->create(['is_active' => false, 'password' => bcrypt('password')]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_can_deactivate_and_reactivate_a_user(): void
    {
        [$admin, $school] = $this->makeAdmin();
        $staff = User::factory()->create(['school_id' => $school->id]);

        $this->actingAs($admin)->patch(route('users.toggle-active', $staff));
        $this->assertFalse($staff->fresh()->is_active);

        $this->actingAs($admin)->patch(route('users.toggle-active', $staff));
        $this->assertTrue($staff->fresh()->is_active);
    }

    public function test_admin_cannot_deactivate_their_own_account(): void
    {
        [$admin] = $this->makeAdmin();

        $this->actingAs($admin)->patch(route('users.toggle-active', $admin))->assertStatus(422);
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        Role::findOrCreate('teacher');
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');

        $this->actingAs($teacher)->get(route('users.index'))->assertForbidden();
    }

    public function test_admin_can_tag_a_teacher_as_class_teacher_and_later_remove_the_tag(): void
    {
        Role::findOrCreate('teacher');
        Role::findOrCreate('class_teacher');
        [$admin] = $this->makeAdmin();

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Ms Homeroom',
            'email' => 'homeroom@example.com',
            'role' => 'teacher',
            'tags' => ['class_teacher'],
        ])->assertRedirect(route('users.index'));

        $teacher = User::where('email', 'homeroom@example.com')->firstOrFail();
        $this->assertTrue($teacher->hasRole('teacher'));
        $this->assertTrue($teacher->hasRole('class_teacher'));

        // Reassigning the following year without the tag drops it — a class teacher's
        // distinction can change without touching their base teacher role.
        $this->actingAs($admin)->put(route('users.update', $teacher), [
            'name' => 'Ms Homeroom',
            'email' => 'homeroom@example.com',
            'role' => 'teacher',
        ])->assertRedirect(route('users.index'));

        $teacher->refresh();
        $this->assertTrue($teacher->hasRole('teacher'));
        $this->assertFalse($teacher->hasRole('class_teacher'));
    }

    public function test_admin_cannot_edit_a_user_from_another_school(): void
    {
        [$admin] = $this->makeAdmin();
        $otherSchool = School::factory()->create();
        $otherUser = User::factory()->create(['school_id' => $otherSchool->id]);

        $this->actingAs($admin)->get(route('users.edit', $otherUser))->assertForbidden();
    }
}
