<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Notifications\PaymentReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardian_is_notified_when_bursar_records_a_payment(): void
    {
        Role::findOrCreate('bursar');
        Role::findOrCreate('parent');
        $school = School::factory()->create();
        $bursar = User::factory()->create(['school_id' => $school->id]);
        $bursar->assignRole('bursar');
        $guardianUser = User::factory()->create(['school_id' => $school->id]);
        $guardianUser->assignRole('parent');
        $guardian = Guardian::create(['user_id' => $guardianUser->id]);
        $student = Student::factory()->for($school)->create();
        $guardian->students()->attach($student->id);

        $invoice = Invoice::create([
            'student_id' => $student->id, 'term' => 'Term 2 2026',
            'amount_due' => 50000, 'due_date' => now()->addWeek(), 'status' => 'unpaid',
        ]);

        $this->actingAs($bursar)->post(route('invoices.record-payment', $invoice), [
            'amount' => 50000, 'method' => 'cash',
        ]);

        $this->assertCount(1, $guardianUser->fresh()->notifications);
        $this->assertSame(PaymentReceived::class, $guardianUser->fresh()->notifications->first()->type);
    }

    public function test_user_can_clear_a_single_notification(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id]);
        $user->notify(new \App\Notifications\IncidentStatusUpdated(
            \App\Models\IncidentReport::create(['school_id' => $school->id, 'category' => 'other', 'description' => 'x', 'status' => 'open'])
        ));

        $notificationId = $user->notifications->first()->id;

        $this->actingAs($user)->delete(route('notifications.destroy', $notificationId));

        $this->assertCount(0, $user->fresh()->notifications);
    }

    public function test_user_can_clear_all_notifications(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id]);
        $incident = \App\Models\IncidentReport::create(['school_id' => $school->id, 'category' => 'other', 'description' => 'x', 'status' => 'open']);
        $user->notify(new \App\Notifications\IncidentStatusUpdated($incident));
        $user->notify(new \App\Notifications\IncidentStatusUpdated($incident));

        $this->actingAs($user)->delete(route('notifications.destroy-all'));

        $this->assertCount(0, $user->fresh()->notifications);
    }

    public function test_user_can_mark_all_notifications_read(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id]);
        $incident = \App\Models\IncidentReport::create(['school_id' => $school->id, 'category' => 'other', 'description' => 'x', 'status' => 'open']);
        $user->notify(new \App\Notifications\IncidentStatusUpdated($incident));

        $this->actingAs($user)->patch(route('notifications.read-all'));

        $this->assertCount(0, $user->fresh()->unreadNotifications);
    }

    public function test_reporter_is_notified_when_incident_status_changes(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('teacher');
        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');
        $reporter = User::factory()->create(['school_id' => $school->id]);
        $reporter->assignRole('teacher');

        $incident = \App\Models\IncidentReport::create([
            'school_id' => $school->id, 'reporter_id' => $reporter->id, 'anonymous' => false, 'category' => 'other',
            'description' => 'test', 'status' => 'open',
        ]);

        $this->actingAs($admin)->patch(route('incidents.status', $incident), ['status' => 'resolved']);

        $this->assertCount(1, $reporter->fresh()->notifications);
    }

    public function test_anonymous_reporter_is_not_notified(): void
    {
        Role::findOrCreate('admin');
        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        $incident = \App\Models\IncidentReport::create([
            'school_id' => $school->id, 'reporter_id' => null, 'anonymous' => true, 'category' => 'other',
            'description' => 'test', 'status' => 'open',
        ]);

        $this->actingAs($admin)->patch(route('incidents.status', $incident), ['status' => 'resolved'])
            ->assertRedirect();
    }
}
