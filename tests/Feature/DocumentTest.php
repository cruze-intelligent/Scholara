<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Guardian;
use App\Models\School;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_nurse_can_attach_a_dosage_sheet_and_the_childs_parent_can_view_it(): void
    {
        Storage::fake('public');
        Role::findOrCreate('nurse');
        Role::findOrCreate('parent');

        $school = School::factory()->create();
        $nurse = User::factory()->create(['school_id' => $school->id]);
        $nurse->assignRole('nurse');
        $student = Student::factory()->for($school)->create();

        $parentUser = User::factory()->create(['school_id' => $school->id]);
        $parentUser->assignRole('parent');
        $guardian = Guardian::create(['user_id' => $parentUser->id]);
        $guardian->students()->attach($student->id);

        $response = $this->actingAs($nurse)->post(route('students.documents.store', $student), [
            'title' => 'Asthma inhaler dosage sheet',
            'file' => UploadedFile::fake()->create('dosage.pdf', 50, 'application/pdf'),
        ]);

        $response->assertRedirect(route('students.documents.index', $student));
        $document = Document::first();
        $this->assertSame('medical', $document->category);

        $this->actingAs($parentUser)->get(route('students.documents.index', $student))
            ->assertViewHas('documents', fn ($docs) => $docs->count() === 1);

        $this->actingAs($parentUser)->get(route('documents.download', $document))->assertOk();
    }

    public function test_unrelated_parent_cannot_view_another_childs_medical_documents(): void
    {
        Role::findOrCreate('parent');

        $school = School::factory()->create();
        $student = Student::factory()->for($school)->create();

        $parentUser = User::factory()->create(['school_id' => $school->id]);
        $parentUser->assignRole('parent');
        Guardian::create(['user_id' => $parentUser->id]);

        $this->actingAs($parentUser)->get(route('students.documents.index', $student))->assertForbidden();
    }

    public function test_hr_can_attach_a_staff_document_and_the_staff_member_can_view_their_own(): void
    {
        Storage::fake('public');
        Role::findOrCreate('hr');
        Role::findOrCreate('teacher');

        $school = School::factory()->create();
        $hr = User::factory()->create(['school_id' => $school->id]);
        $hr->assignRole('hr');

        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');
        StaffProfile::create(['user_id' => $teacher->id, 'role_title' => 'Teacher', 'monthly_gross_salary' => 500000]);

        $response = $this->actingAs($hr)->post(route('users.documents.store', $teacher), [
            'title' => 'Signed employment contract',
            'file' => UploadedFile::fake()->create('contract.pdf', 50, 'application/pdf'),
        ]);
        $response->assertRedirect(route('users.documents.index', $teacher));

        $this->actingAs($teacher)->get(route('users.documents.index', $teacher))
            ->assertViewHas('documents', fn ($docs) => $docs->count() === 1);
    }

    public function test_a_different_staff_member_cannot_view_someone_elses_documents(): void
    {
        Role::findOrCreate('teacher');

        $school = School::factory()->create();
        $teacherA = User::factory()->create(['school_id' => $school->id]);
        $teacherA->assignRole('teacher');
        $teacherB = User::factory()->create(['school_id' => $school->id]);
        $teacherB->assignRole('teacher');

        $this->actingAs($teacherB)->get(route('users.documents.index', $teacherA))->assertForbidden();
    }
}
