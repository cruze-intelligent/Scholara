<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentCsvTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_students_from_csv(): void
    {
        Role::findOrCreate('admin');

        $school = School::factory()->create();
        $class = SchoolClass::factory()->for($school)->create(['name' => 'Primary 5']);
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        $csv = "admission_no,first_name,last_name,dob,gender,curriculum_level,school_class\n"
            ."ADM-001,Jane,Doe,2015-04-02,female,primary,Primary 5\n"
            .",John,Smith,2016-01-10,male,primary,\n"
            .",,Missing First Name,,,,\n";

        $file = UploadedFile::fake()->createWithContent('students.csv', $csv);

        $response = $this->actingAs($admin)->post(route('students.import.store'), ['file' => $file]);

        $response->assertRedirect(route('users.index'));
        $this->assertSame(2, Student::count());

        $jane = Student::where('first_name', 'Jane')->first();
        $this->assertSame($class->id, $jane->school_class_id);
        $this->assertSame('ADM-001', $jane->admission_no);

        $john = Student::where('first_name', 'John')->first();
        $this->assertNull($john->school_class_id);
        $this->assertNotEmpty($john->admission_no);
    }

    public function test_admin_can_export_students_to_csv(): void
    {
        Role::findOrCreate('admin');

        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');
        Student::factory()->for($school)->create(['first_name' => 'Amina']);

        $response = $this->actingAs($admin)->get(route('students.export'));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('Amina', $response->streamedContent());
    }

    public function test_non_admin_cannot_import_students(): void
    {
        Role::findOrCreate('teacher');

        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');

        $csv = "admission_no,first_name,last_name,dob,gender,curriculum_level,school_class\n,X,Y,,,primary,\n";
        $file = UploadedFile::fake()->createWithContent('students.csv', $csv);

        $this->actingAs($teacher)->post(route('students.import.store'), ['file' => $file])->assertForbidden();
    }
}
