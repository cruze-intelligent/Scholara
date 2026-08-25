<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CalendarEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_a_calendar_event(): void
    {
        Role::findOrCreate('admin');

        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('calendar.store'), [
            'title' => 'Term 3 begins',
            'category' => 'term_start',
            'start_date' => now()->addMonth()->toDateString(),
        ]);

        $response->assertRedirect(route('calendar.index'));
        $this->assertDatabaseHas('calendar_events', [
            'school_id' => $school->id,
            'title' => 'Term 3 begins',
            'created_by' => $admin->id,
        ]);
    }

    public function test_non_admin_cannot_add_a_calendar_event(): void
    {
        Role::findOrCreate('teacher');

        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');

        $this->actingAs($teacher)->post(route('calendar.store'), [
            'title' => 'Term 3 begins',
            'category' => 'term_start',
            'start_date' => now()->addMonth()->toDateString(),
        ])->assertForbidden();
    }

    public function test_every_role_can_view_the_calendar(): void
    {
        Role::findOrCreate('teacher');

        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');

        CalendarEvent::factory()->for($school)->create([
            'created_by' => $teacher->id,
            'title' => 'Mid-term break',
            'category' => 'holiday',
            'start_date' => now()->addWeek(),
        ]);

        $response = $this->actingAs($teacher)->get(route('calendar.index'));

        $response->assertOk();
        $response->assertViewHas('upcoming', fn ($events) => $events->count() === 1);
    }

    public function test_a_school_only_sees_its_own_calendar_events(): void
    {
        Role::findOrCreate('admin');

        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $adminA = User::factory()->create(['school_id' => $schoolA->id]);
        $adminA->assignRole('admin');
        $userB = User::factory()->create(['school_id' => $schoolB->id]);

        CalendarEvent::factory()->for($schoolA)->create(['created_by' => $adminA->id, 'start_date' => now()->addWeek()]);
        CalendarEvent::factory()->for($schoolB)->create(['created_by' => $userB->id, 'start_date' => now()->addWeek()]);

        $response = $this->actingAs($adminA)->get(route('calendar.index'));

        $response->assertViewHas('upcoming', fn ($events) => $events->count() === 1);
    }
}
