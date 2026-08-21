<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IncidentReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_report_does_not_store_a_reporter(): void
    {
        Role::findOrCreate('parent');

        $school = School::factory()->create();
        $parent = User::factory()->create(['school_id' => $school->id]);
        $parent->assignRole('parent');

        $this->actingAs($parent)->post(route('incidents.store'), [
            'category' => 'bullying',
            'description' => 'Reported anonymously for safety.',
            'anonymous' => '1',
        ])->assertRedirect(route('incidents.index'));

        $this->assertDatabaseHas('incident_reports', [
            'category' => 'bullying',
            'anonymous' => true,
            'reporter_id' => null,
        ]);
    }

    public function test_staff_can_triage_a_report(): void
    {
        Role::findOrCreate('admin');

        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('incidents.store'), [
            'category' => 'other',
            'description' => 'Broken window in classroom 3.',
        ]);

        $incident = \App\Models\IncidentReport::first();

        $this->actingAs($admin)->patch(route('incidents.status', $incident), [
            'status' => 'in_review',
        ])->assertRedirect();

        $this->assertSame('in_review', $incident->fresh()->status);
    }
}
