<?php

namespace Tests\Feature;

use App\Models\GatePass;
use App\Models\Guardian;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GatePassTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_can_request_a_pass_for_their_own_child_and_admin_can_approve_and_log_departure(): void
    {
        Role::findOrCreate('parent');
        Role::findOrCreate('admin');

        $school = School::factory()->create();
        $parentUser = User::factory()->create(['school_id' => $school->id]);
        $parentUser->assignRole('parent');
        $guardian = Guardian::create(['user_id' => $parentUser->id]);
        $child = Student::factory()->for($school)->create();
        $guardian->students()->attach($child->id);

        $response = $this->actingAs($parentUser)->post(route('gate-passes.store'), [
            'student_id' => $child->id,
            'reason' => 'Dental appointment',
        ]);
        $response->assertRedirect(route('gate-passes.index'));

        $gatePass = GatePass::first();
        $this->assertSame('pending', $gatePass->status);

        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->patch(route('gate-passes.approve', $gatePass), ['status' => 'approved'])
            ->assertRedirect();
        $this->assertSame('approved', $gatePass->fresh()->status);

        $this->actingAs($admin)->patch(route('gate-passes.depart', $gatePass))->assertRedirect();
        $this->assertNotNull($gatePass->fresh()->departed_at);

        $this->actingAs($admin)->patch(route('gate-passes.return', $gatePass))->assertRedirect();
        $this->assertNotNull($gatePass->fresh()->returned_at);
    }

    public function test_parent_cannot_request_a_pass_for_a_child_that_isnt_theirs(): void
    {
        Role::findOrCreate('parent');

        $school = School::factory()->create();
        $parentUser = User::factory()->create(['school_id' => $school->id]);
        $parentUser->assignRole('parent');
        Guardian::create(['user_id' => $parentUser->id]);
        $otherChild = Student::factory()->for($school)->create();

        $this->actingAs($parentUser)->post(route('gate-passes.store'), [
            'student_id' => $otherChild->id,
            'reason' => 'X',
        ])->assertForbidden();
    }

    public function test_cannot_log_departure_before_approval(): void
    {
        Role::findOrCreate('admin');

        $school = School::factory()->create();
        $student = Student::factory()->for($school)->create();
        $requester = User::factory()->create(['school_id' => $school->id]);
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        $gatePass = GatePass::create([
            'school_id' => $school->id, 'student_id' => $student->id, 'requested_by' => $requester->id,
            'reason' => 'X', 'status' => 'pending',
        ]);

        $this->actingAs($admin)->patch(route('gate-passes.depart', $gatePass))->assertStatus(422);
    }

    public function test_parent_only_sees_their_own_childs_gate_passes(): void
    {
        Role::findOrCreate('parent');

        $school = School::factory()->create();
        $childA = Student::factory()->for($school)->create();
        $childB = Student::factory()->for($school)->create();
        $requester = User::factory()->create(['school_id' => $school->id]);

        GatePass::create(['school_id' => $school->id, 'student_id' => $childA->id, 'requested_by' => $requester->id, 'reason' => 'A', 'status' => 'pending']);
        GatePass::create(['school_id' => $school->id, 'student_id' => $childB->id, 'requested_by' => $requester->id, 'reason' => 'B', 'status' => 'pending']);

        $parentUser = User::factory()->create(['school_id' => $school->id]);
        $parentUser->assignRole('parent');
        $guardian = Guardian::create(['user_id' => $parentUser->id]);
        $guardian->students()->attach($childA->id);

        $response = $this->actingAs($parentUser)->get(route('gate-passes.index'));

        $response->assertViewHas('gatePasses', fn ($passes) => $passes->total() === 1);
    }
}
