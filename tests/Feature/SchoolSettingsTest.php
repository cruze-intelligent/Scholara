<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolSubscription;
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

    public function test_admin_can_see_their_own_subscription_status_and_billing_history(): void
    {
        Role::findOrCreate('admin');
        $school = School::factory()->create(['status' => 'trial', 'trial_ends_at' => now()->addDays(10)]);
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');
        SchoolSubscription::create([
            'school_id' => $school->id,
            'period_start' => now()->subDays(90),
            'period_end' => now()->subDay(),
            'student_count' => 10,
            'rate_per_student' => SchoolSubscription::RATE_PER_STUDENT_UGX,
            'amount' => 30000,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($admin)->get(route('school-settings.edit'));

        $response->assertOk();
        $response->assertViewHas('subscriptions', fn ($subs) => $subs->count() === 1);
        $response->assertSee('30,000');
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
