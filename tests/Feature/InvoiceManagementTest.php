<?php

namespace Tests\Feature;

use App\Models\Invoice;
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
}
