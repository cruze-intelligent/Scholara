<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolSubscription;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    private function makeSuperAdmin(): User
    {
        Role::findOrCreate('super_admin');
        $superAdmin = User::factory()->create(['school_id' => null]);
        $superAdmin->assignRole('super_admin');

        return $superAdmin;
    }

    public function test_super_admin_can_approve_a_pending_school_which_then_becomes_accessible(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        Role::findOrCreate('admin');
        $school = School::factory()->create(['status' => 'pending_review']);
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        $this->actingAs($superAdmin)->post(route('super-admin.schools.approve', $school))->assertRedirect();

        $school->refresh();
        $this->assertSame('trial', $school->status);
        $this->assertNotNull($school->trial_ends_at);
        $this->assertTrue($school->isAccessible());

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    }

    public function test_non_super_admin_cannot_access_the_super_admin_section(): void
    {
        Role::findOrCreate('admin');
        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->get(route('super-admin.schools'))->assertForbidden();
    }

    public function test_super_admin_sees_platform_wide_aggregate_counts_not_a_single_schools_data(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        Student::factory()->for($schoolA)->create();
        Student::factory()->for($schoolB)->create();

        $response = $this->actingAs($superAdmin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('totalStudents', 2);
        $response->assertViewHas('totalSchools', 2);
    }

    public function test_marking_a_subscription_paid_makes_a_trial_expired_school_accessible_again(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        Role::findOrCreate('admin');
        $school = School::factory()->create(['status' => 'trial', 'trial_ends_at' => now()->subDay()]);
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        $this->actingAs($superAdmin)->post(route('super-admin.schools.subscriptions.generate', $school))->assertRedirect();
        $subscription = SchoolSubscription::where('school_id', $school->id)->firstOrFail();
        $this->assertSame('pending', $subscription->status);

        $this->actingAs($superAdmin)->post(route('super-admin.subscriptions.mark-paid', $subscription))->assertRedirect();

        $this->assertTrue($school->fresh()->isAccessible());
        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    }
}
