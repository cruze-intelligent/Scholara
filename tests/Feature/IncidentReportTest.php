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

    public function test_reporter_can_edit_their_own_open_report(): void
    {
        Role::findOrCreate('parent');
        $school = School::factory()->create();
        $parent = User::factory()->create(['school_id' => $school->id]);
        $parent->assignRole('parent');

        $this->actingAs($parent)->post(route('incidents.store'), [
            'category' => 'other', 'description' => 'Original.',
        ]);
        $incident = \App\Models\IncidentReport::first();

        $this->actingAs($parent)->put(route('incidents.update', $incident), [
            'category' => 'bullying', 'description' => 'Corrected.',
        ])->assertRedirect(route('incidents.index'));

        $this->assertSame('bullying', $incident->fresh()->category);
    }

    public function test_reporter_cannot_edit_once_triage_has_started(): void
    {
        Role::findOrCreate('parent');
        Role::findOrCreate('admin');
        $school = School::factory()->create();
        $parent = User::factory()->create(['school_id' => $school->id]);
        $parent->assignRole('parent');
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        $this->actingAs($parent)->post(route('incidents.store'), [
            'category' => 'other', 'description' => 'Original.',
        ]);
        $incident = \App\Models\IncidentReport::first();
        $this->actingAs($admin)->patch(route('incidents.status', $incident), ['status' => 'in_review']);

        $this->actingAs($parent)->get(route('incidents.edit', $incident))->assertStatus(422);
    }

    public function test_hr_only_sees_reports_they_personally_filed_not_the_whole_school(): void
    {
        Role::findOrCreate('hr');
        Role::findOrCreate('admin');
        $school = School::factory()->create();
        $hr = User::factory()->create(['school_id' => $school->id]);
        $hr->assignRole('hr');
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('incidents.store'), ['category' => 'other', 'description' => 'Not HRs.']);
        $this->actingAs($hr)->post(route('incidents.store'), ['category' => 'other', 'description' => 'HRs own report.']);

        $response = $this->actingAs($hr)->get(route('incidents.index'));

        $response->assertViewHas('incidents', fn ($incidents) => $incidents->total() === 1);
    }

    public function test_hr_cannot_pick_an_arbitrary_student_when_filing_a_report(): void
    {
        Role::findOrCreate('hr');
        $school = School::factory()->create();
        $hr = User::factory()->create(['school_id' => $school->id]);
        $hr->assignRole('hr');
        \App\Models\Student::factory()->for($school)->create();

        $response = $this->actingAs($hr)->get(route('incidents.create'));

        $response->assertViewHas('students', fn ($students) => $students->isEmpty());
    }

    public function test_only_admin_can_delete_a_report(): void
    {
        Role::findOrCreate('parent');
        Role::findOrCreate('admin');
        $school = School::factory()->create();
        $parent = User::factory()->create(['school_id' => $school->id]);
        $parent->assignRole('parent');
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        $this->actingAs($parent)->post(route('incidents.store'), [
            'category' => 'other', 'description' => 'To delete.',
        ]);
        $incident = \App\Models\IncidentReport::first();

        $this->actingAs($parent)->delete(route('incidents.destroy', $incident))->assertForbidden();
        $this->actingAs($admin)->delete(route('incidents.destroy', $incident))->assertRedirect();

        $this->assertDatabaseMissing('incident_reports', ['id' => $incident->id]);
    }
}
