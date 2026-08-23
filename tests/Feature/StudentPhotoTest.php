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

class StudentPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_a_photo_for_a_student_at_their_school(): void
    {
        Storage::fake('public');
        Role::findOrCreate('admin');
        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');
        $student = Student::factory()->for($school)->create();

        $this->actingAs($admin)->post(route('students.photo.update', $student), [
            'photo' => UploadedFile::fake()->image('id.jpg'),
        ])->assertRedirect();

        $this->assertNotNull($student->fresh()->photo_path);
        Storage::disk('public')->assertExists($student->fresh()->photo_path);
    }

    public function test_parent_can_upload_a_photo_for_their_own_child(): void
    {
        Storage::fake('public');
        Role::findOrCreate('parent');
        $school = School::factory()->create();
        $parentUser = User::factory()->create(['school_id' => $school->id]);
        $parentUser->assignRole('parent');
        $guardian = Guardian::create(['user_id' => $parentUser->id]);
        $child = Student::factory()->for($school)->create();
        $guardian->students()->attach($child->id);

        $this->actingAs($parentUser)->post(route('students.photo.update', $child), [
            'photo' => UploadedFile::fake()->image('kid.jpg'),
        ])->assertRedirect();

        $this->assertNotNull($child->fresh()->photo_path);
    }

    public function test_parent_cannot_upload_a_photo_for_another_students_child(): void
    {
        Role::findOrCreate('parent');
        $school = School::factory()->create();
        $parentUser = User::factory()->create(['school_id' => $school->id]);
        $parentUser->assignRole('parent');
        Guardian::create(['user_id' => $parentUser->id]);
        $notMyChild = Student::factory()->for($school)->create();

        $this->actingAs($parentUser)->post(route('students.photo.update', $notMyChild), [
            'photo' => UploadedFile::fake()->image('nope.jpg'),
        ])->assertForbidden();

        $this->assertNull($notMyChild->fresh()->photo_path);
    }
}
