<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PinnedItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_pin_and_unpin_a_tab_to_their_dashboard(): void
    {
        Role::findOrCreate('admin');
        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('pins.store', 'reports.academics'))->assertRedirect();
        $this->assertDatabaseHas('pinned_items', ['user_id' => $admin->id, 'key' => 'reports.academics']);

        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertSee('Academic Trends');

        $this->actingAs($admin)->delete(route('pins.destroy', 'reports.academics'))->assertRedirect();
        $this->assertDatabaseMissing('pinned_items', ['user_id' => $admin->id, 'key' => 'reports.academics']);
    }

    public function test_dismissing_the_calendar_widget_hides_it_for_that_user_only(): void
    {
        Role::findOrCreate('admin');
        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');
        $otherAdmin = User::factory()->create(['school_id' => $school->id]);
        $otherAdmin->assignRole('admin');

        $this->actingAs($admin)->post(route('pins.store', 'calendar_dismissed'))->assertRedirect();

        $this->assertTrue($admin->fresh()->hasPinned('calendar_dismissed'));
        $this->assertFalse($otherAdmin->fresh()->hasPinned('calendar_dismissed'));
    }

    public function test_pinning_an_invalid_key_is_rejected(): void
    {
        Role::findOrCreate('admin');
        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('pins.store', 'not valid!'))->assertNotFound();
    }
}
