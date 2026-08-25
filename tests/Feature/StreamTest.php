<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Stream;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StreamTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_remove_a_stream(): void
    {
        Role::findOrCreate('admin');
        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('streams.store'), ['name' => 'Blue'])
            ->assertRedirect(route('streams.index'));

        $stream = Stream::where('name', 'Blue')->firstOrFail();
        $this->assertSame($school->id, $stream->school_id);

        $this->actingAs($admin)->delete(route('streams.destroy', $stream))->assertRedirect();
        $this->assertDatabaseMissing('streams', ['id' => $stream->id]);
    }

    public function test_non_admin_cannot_manage_streams(): void
    {
        Role::findOrCreate('teacher');
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');

        $this->actingAs($teacher)->post(route('streams.store'), ['name' => 'Blue'])->assertForbidden();
    }

    public function test_a_student_can_be_enrolled_into_a_stream(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('parent');
        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');
        $stream = Stream::create(['school_id' => $school->id, 'name' => 'Green']);

        $this->actingAs($admin)->post(route('students.store'), [
            'first_name' => 'Amina',
            'curriculum_level' => 'primary',
            'stream_id' => $stream->id,
            'guardian_name' => 'Parent',
            'guardian_phone' => '0700000000',
        ])->assertRedirect();

        $student = Student::where('first_name', 'Amina')->firstOrFail();
        $this->assertSame($stream->id, $student->stream_id);
    }
}
