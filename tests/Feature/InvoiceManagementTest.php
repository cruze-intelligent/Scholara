<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_bursar_can_create_an_invoice(): void
    {
        Role::findOrCreate('bursar');
        $school = School::factory()->create();
        $bursar = User::factory()->create(['school_id' => $school->id]);
        $bursar->assignRole('bursar');
        $student = Student::factory()->for($school)->create();

        $this->actingAs($bursar)->post(route('invoices.store'), [
            'student_id' => $student->id,
            'term' => 'Term 2 2026',
            'amount_due' => 150000,
            'due_date' => now()->addWeek()->toDateString(),
        ])->assertRedirect(route('invoices.index'));

        $this->assertSame(1, Invoice::count());
    }

    public function test_bursar_can_record_a_cash_payment_and_invoice_becomes_paid(): void
    {
        Role::findOrCreate('bursar');
        $school = School::factory()->create();
        $bursar = User::factory()->create(['school_id' => $school->id]);
        $bursar->assignRole('bursar');
        $student = Student::factory()->for($school)->create();

        $invoice = Invoice::create([
            'student_id' => $student->id,
            'term' => 'Term 2 2026',
            'amount_due' => 100000,
            'due_date' => now()->addWeek(),
            'status' => 'unpaid',
        ]);

        $this->actingAs($bursar)->post(route('invoices.record-payment', $invoice), [
            'amount' => 100000,
            'method' => 'cash',
        ])->assertRedirect(route('invoices.show', $invoice));

        $this->assertSame('paid', $invoice->fresh()->status);
    }

    public function test_bursar_cannot_manage_an_invoice_from_another_school(): void
    {
        Role::findOrCreate('bursar');
        $school = School::factory()->create();
        $bursar = User::factory()->create(['school_id' => $school->id]);
        $bursar->assignRole('bursar');

        $otherSchool = School::factory()->create();
        $otherStudent = Student::factory()->for($otherSchool)->create();
        $otherInvoice = Invoice::create([
            'student_id' => $otherStudent->id,
            'term' => 'Term 2 2026',
            'amount_due' => 100000,
            'due_date' => now()->addWeek(),
            'status' => 'unpaid',
        ]);

        $this->actingAs($bursar)->get(route('invoices.show', $otherInvoice))->assertForbidden();
    }

    public function test_non_bursar_cannot_access_invoice_management(): void
    {
        Role::findOrCreate('teacher');
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');

        $this->actingAs($teacher)->get(route('invoices.index'))->assertForbidden();
    }

    public function test_bursar_and_paying_guardian_can_download_a_receipt_for_a_completed_payment(): void
    {
        Role::findOrCreate('bursar');
        Role::findOrCreate('parent');

        $school = School::factory()->create();
        $bursar = User::factory()->create(['school_id' => $school->id]);
        $bursar->assignRole('bursar');
        $student = Student::factory()->for($school)->create();

        $parentUser = User::factory()->create(['school_id' => $school->id]);
        $parentUser->assignRole('parent');
        $guardian = Guardian::create(['user_id' => $parentUser->id]);
        $guardian->students()->attach($student->id);

        $invoice = Invoice::create([
            'student_id' => $student->id, 'term' => 'Term 2 2026', 'amount_due' => 100000,
            'due_date' => now()->addWeek(), 'status' => 'paid',
        ]);
        $payment = $invoice->payments()->create([
            'amount' => 100000, 'currency' => 'UGX', 'method' => 'cash', 'status' => Payment::STATUS_COMPLETED,
            'provider' => 'manual', 'paid_at' => now(),
        ]);

        $this->actingAs($bursar)->get(route('invoices.payments.receipt', [$invoice, $payment]))->assertOk();
        $this->actingAs($parentUser)->get(route('invoices.payments.receipt', [$invoice, $payment]))->assertOk();
    }

    public function test_unrelated_guardian_cannot_download_someone_elses_receipt(): void
    {
        Role::findOrCreate('parent');

        $school = School::factory()->create();
        $student = Student::factory()->for($school)->create();
        $invoice = Invoice::create([
            'student_id' => $student->id, 'term' => 'Term 2 2026', 'amount_due' => 100000,
            'due_date' => now()->addWeek(), 'status' => 'paid',
        ]);
        $payment = $invoice->payments()->create([
            'amount' => 100000, 'currency' => 'UGX', 'method' => 'cash', 'status' => Payment::STATUS_COMPLETED,
            'provider' => 'manual', 'paid_at' => now(),
        ]);

        $parentUser = User::factory()->create(['school_id' => $school->id]);
        $parentUser->assignRole('parent');
        Guardian::create(['user_id' => $parentUser->id]);

        $this->actingAs($parentUser)->get(route('invoices.payments.receipt', [$invoice, $payment]))->assertForbidden();
    }
}
