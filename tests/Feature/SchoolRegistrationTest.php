<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SchoolRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(): array
    {
        return [
            'school_name' => 'Greenhill Academy',
            'school_address' => 'Kampala, Uganda',
            'subdomain' => 'greenhill',
            'admin_name' => 'Jane Admin',
            'admin_email' => 'jane@greenhill.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
    }

    public function test_registering_a_school_creates_it_pending_review_with_an_unverified_admin(): void
    {
        Role::findOrCreate('admin');

        $this->post(route('register'), $this->validPayload())->assertRedirect(route('dashboard', absolute: false));

        $school = School::where('subdomain', 'greenhill')->firstOrFail();
        $this->assertSame('pending_review', $school->status);
        $this->assertNotNull($school->registration_number);
        $this->assertFalse($school->isAccessible());

        $admin = User::where('email', 'jane@greenhill.test')->firstOrFail();
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertSame($school->id, $admin->school_id);
        $this->assertNull($admin->email_verified_at);
    }

    public function test_a_pending_schools_verified_admin_is_still_redirected_to_the_status_page(): void
    {
        Role::findOrCreate('admin');

        $school = School::factory()->create(['status' => 'pending_review']);
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('school-status.show'));
    }

    public function test_a_trial_school_within_its_window_can_access_the_app(): void
    {
        Role::findOrCreate('admin');

        $school = School::factory()->create(['status' => 'trial', 'trial_ends_at' => now()->addDays(10)]);
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    }

    public function test_a_school_whose_trial_expired_with_no_paid_subscription_is_blocked(): void
    {
        Role::findOrCreate('admin');

        $school = School::factory()->create(['status' => 'trial', 'trial_ends_at' => now()->subDay()]);
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('school-status.show'));
    }
}
