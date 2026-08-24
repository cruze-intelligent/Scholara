<?php

namespace Tests\Feature;

use App\Models\PayrollRun;
use App\Models\School;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\Payroll\NssfCalculator;
use App\Services\Payroll\PayeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PayrollRunTest extends TestCase
{
    use RefreshDatabase;

    public function test_generating_a_payroll_run_computes_payslips_for_staff_with_a_salary(): void
    {
        Role::findOrCreate('hr');

        $school = School::factory()->create();
        $hr = User::factory()->create(['school_id' => $school->id]);
        $hr->assignRole('hr');

        $staffUser = User::factory()->create(['school_id' => $school->id]);
        $staffProfile = StaffProfile::create([
            'user_id' => $staffUser->id,
            'role_title' => 'Teacher',
            'monthly_gross_salary' => 900_000,
        ]);

        $run = PayrollRun::create(['school_id' => $school->id, 'period_start' => now(), 'period_end' => now()->addMonth(), 'status' => 'draft']);

        $this->actingAs($hr)->post(route('payroll-runs.generate', $run))->assertRedirect();

        $payslip = $run->fresh()->payslips()->first();
        $paye = (new PayeCalculator)->calculate(900_000);
        $nssf = (new NssfCalculator)->calculate(900_000);

        $this->assertNotNull($payslip);
        $this->assertEquals($paye, (float) $payslip->paye);
        $this->assertEquals($nssf, (float) $payslip->nssf);
        $this->assertEquals(900_000 - $paye - $nssf, (float) $payslip->net_pay);
        $this->assertSame('approved', $run->fresh()->status);
    }

    public function test_staff_without_a_salary_are_skipped(): void
    {
        Role::findOrCreate('hr');

        $school = School::factory()->create();
        $hr = User::factory()->create(['school_id' => $school->id]);
        $hr->assignRole('hr');

        $staffUser = User::factory()->create(['school_id' => $school->id]);
        StaffProfile::create(['user_id' => $staffUser->id, 'role_title' => 'Librarian']);

        $run = PayrollRun::create(['school_id' => $school->id, 'period_start' => now(), 'period_end' => now()->addMonth(), 'status' => 'draft']);

        $this->actingAs($hr)->post(route('payroll-runs.generate', $run));

        $this->assertSame(0, $run->fresh()->payslips()->count());
    }

    public function test_a_draft_run_can_be_edited_and_deleted(): void
    {
        Role::findOrCreate('hr');
        $school = School::factory()->create();
        $hr = User::factory()->create(['school_id' => $school->id]);
        $hr->assignRole('hr');

        $run = PayrollRun::create(['school_id' => $school->id, 'period_start' => now(), 'period_end' => now()->addMonth(), 'status' => 'draft']);
        $newEnd = now()->addMonths(2);

        $this->actingAs($hr)->put(route('payroll-runs.update', $run), [
            'period_start' => $run->period_start->toDateString(),
            'period_end' => $newEnd->toDateString(),
        ])->assertRedirect(route('payroll-runs.show', $run));

        $this->assertTrue($run->fresh()->period_end->isSameDay($newEnd));

        $this->actingAs($hr)->delete(route('payroll-runs.destroy', $run))->assertRedirect(route('payroll-runs.index'));
        $this->assertDatabaseMissing('payroll_runs', ['id' => $run->id]);
    }

    public function test_an_approved_run_can_no_longer_be_edited_or_deleted(): void
    {
        Role::findOrCreate('hr');
        $school = School::factory()->create();
        $hr = User::factory()->create(['school_id' => $school->id]);
        $hr->assignRole('hr');

        $run = PayrollRun::create(['school_id' => $school->id, 'period_start' => now(), 'period_end' => now()->addMonth(), 'status' => 'approved']);

        $this->actingAs($hr)->get(route('payroll-runs.edit', $run))->assertStatus(422);
        $this->actingAs($hr)->delete(route('payroll-runs.destroy', $run))->assertStatus(422);
        $this->assertDatabaseHas('payroll_runs', ['id' => $run->id]);
    }
}
