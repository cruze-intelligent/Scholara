<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\Resource;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_teacher_can_upload_and_the_class_can_download(): void
    {
        Storage::fake('public');
        Role::findOrCreate('teacher');
        Role::findOrCreate('learner');

        $school = School::factory()->create();
        $class = SchoolClass::factory()->for($school)->create();
        $subject = Subject::factory()->for($school)->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');
        TeacherSubjectAssignment::create(['teacher_id' => $teacher->id, 'subject_id' => $subject->id, 'school_class_id' => $class->id]);

        $learnerUser = User::factory()->create(['school_id' => $school->id]);
        $learnerUser->assignRole('learner');
        Student::factory()->for($school)->create(['school_class_id' => $class->id, 'user_id' => $learnerUser->id]);

        $response = $this->actingAs($teacher)->post(route('resources.store'), [
            'assignment_id' => TeacherSubjectAssignment::first()->id,
            'title' => 'Week 4 fractions worksheet',
            'file' => UploadedFile::fake()->create('worksheet.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect(route('resources.index'));
        $resource = Resource::first();
        $this->assertSame('worksheet.pdf', $resource->original_filename);
        Storage::disk('public')->assertExists($resource->file_path);

        $this->actingAs($learnerUser)->get(route('resources.index'))
            ->assertViewHas('resources', fn ($resources) => $resources->count() === 1);

        $this->actingAs($learnerUser)->get(route('resources.download', $resource))->assertOk();
    }

    public function test_teacher_cannot_upload_for_an_unassigned_subject(): void
    {
        Role::findOrCreate('teacher');

        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');

        $response = $this->actingAs($teacher)->post(route('resources.store'), [
            'assignment_id' => 999,
            'title' => 'X',
            'file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
        ]);

        $response->assertNotFound();
    }

    public function test_parent_only_sees_resources_for_their_own_childs_class(): void
    {
        Role::findOrCreate('parent');

        $school = School::factory()->create();
        $classA = SchoolClass::factory()->for($school)->create();
        $classB = SchoolClass::factory()->for($school)->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);

        Resource::create([
            'school_id' => $school->id, 'teacher_id' => $teacher->id, 'school_class_id' => $classA->id,
            'title' => 'Class A notes', 'file_path' => 'resources/a.pdf',
        ]);
        Resource::create([
            'school_id' => $school->id, 'teacher_id' => $teacher->id, 'school_class_id' => $classB->id,
            'title' => 'Class B notes', 'file_path' => 'resources/b.pdf',
        ]);

        $parentUser = User::factory()->create(['school_id' => $school->id]);
        $parentUser->assignRole('parent');
        $guardian = Guardian::create(['user_id' => $parentUser->id]);
        $child = Student::factory()->for($school)->create(['school_class_id' => $classA->id]);
        $guardian->students()->attach($child->id);

        $response = $this->actingAs($parentUser)->get(route('resources.index'));

        $response->assertViewHas('resources', function ($resources) {
            return $resources->count() === 1 && $resources->first()->title === 'Class A notes';
        });
    }

    public function test_a_different_teacher_cannot_delete_someone_elses_resource(): void
    {
        Role::findOrCreate('teacher');

        $school = School::factory()->create();
        $class = SchoolClass::factory()->for($school)->create();
        $teacherA = User::factory()->create(['school_id' => $school->id]);
        $teacherA->assignRole('teacher');
        $teacherB = User::factory()->create(['school_id' => $school->id]);
        $teacherB->assignRole('teacher');

        $resource = Resource::create([
            'school_id' => $school->id, 'teacher_id' => $teacherA->id, 'school_class_id' => $class->id,
            'title' => 'Notes', 'file_path' => 'resources/a.pdf',
        ]);

        $this->actingAs($teacherB)->delete(route('resources.destroy', $resource))->assertForbidden();
    }
}
