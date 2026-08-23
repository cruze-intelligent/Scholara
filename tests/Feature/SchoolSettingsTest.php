<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SchoolSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_set_the_schools_offered_levels(): void
    {
        Role::findOrCreate('admin');
        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->put(route('school-settings.update'), [
            'levels' => ['primary', 'lower_secondary'],
        ])->assertRedirect(route('school-settings.edit'));

        $school->refresh();
        $this->assertSame(['primary', 'lower_secondary'], $school->levels());
        $this->assertTrue($school->offersLevel('primary'));
        $this->assertFalse($school->offersLevel('nursery'));
    }

    public function test_unconfigured_school_offers_every_level(): void
    {
        $school = School::factory()->create();

        $this->assertTrue($school->offersLevel('nursery'));
        $this->assertTrue($school->offersLevel('upper_secondary'));
    }

    public function test_non_admin_cannot_change_school_settings(): void
    {
        Role::findOrCreate('teacher');
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');

        $this->actingAs($teacher)->get(route('school-settings.edit'))->assertForbidden();
    }
}
