<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_class_teacher_can_take_attendance(): void
    {
        Role::findOrCreate('teacher');

        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');
        $class = SchoolClass::factory()->for($school)->create(['teacher_id' => $teacher->id]);
        $student = Student::factory()->for($school)->create(['school_class_id' => $class->id, 'gender' => 'female']);

        $response = $this->actingAs($teacher)->post(route('attendance.store'), [
            'school_class_id' => $class->id,
            'date' => now()->toDateString(),
            'status' => [$student->id => 'present'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('attendance_records', [
            'school_class_id' => $class->id,
            'student_id' => $student->id,
            'status' => 'present',
        ]);
    }

    public function test_teacher_cannot_take_attendance_for_another_teachers_class(): void
    {
        Role::findOrCreate('teacher');

        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');
        $otherTeacher = User::factory()->create(['school_id' => $school->id]);
        $otherTeacher->assignRole('teacher');
        $class = SchoolClass::factory()->for($school)->create(['teacher_id' => $otherTeacher->id]);
        $student = Student::factory()->for($school)->create(['school_class_id' => $class->id]);

        $response = $this->actingAs($teacher)->post(route('attendance.store'), [
            'school_class_id' => $class->id,
            'date' => now()->toDateString(),
            'status' => [$student->id => 'present'],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('attendance_records', ['student_id' => $student->id]);
    }
}
