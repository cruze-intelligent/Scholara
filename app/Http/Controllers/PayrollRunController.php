<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use App\Models\PayrollRun;
use App\Models\StaffProfile;
use App\Services\Payroll\NssfCalculator;
use App\Services\Payroll\PayeCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollRunController extends Controller
{
    public function index(): View
    {
        $payrollRuns = PayrollRun::withCount('payslips')->latest('period_end')->get();

        return view('payroll-runs.index', compact('payrollRuns'));
    }

    public function create(): View
    {
        return view('payroll-runs.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        $payrollRun = PayrollRun::create([...$validated, 'status' => 'draft']);

        return redirect()->route('payroll-runs.show', $payrollRun)->with('status', 'Payroll run created.');
    }

    public function show(PayrollRun $payrollRun): View
    {
        $payrollRun->load('payslips.staffProfile.user');

        return view('payroll-runs.show', compact('payrollRun'));
    }

    /**
     * Generate one payslip per staff member with a set salary, using
     * PayeCalculator/NssfCalculator — see docs/DECISIONS.md for the
     * documented-default rates used.
     */
    public function generate(PayrollRun $payrollRun, PayeCalculator $paye, NssfCalculator $nssf): RedirectResponse
    {
        abort_if($payrollRun->status !== 'draft', 422, 'Only draft payroll runs can be generated.');

        $staff = StaffProfile::whereHas('user', fn ($q) => $q->where('school_id', $payrollRun->school_id))
            ->whereNotNull('monthly_gross_salary')
            ->get();

        foreach ($staff as $staffProfile) {
            $gross = (float) $staffProfile->monthly_gross_salary;
            $payeAmount = $paye->calculate($gross);
            $nssfAmount = $nssf->calculate($gross);

            Payslip::updateOrCreate(
                ['payroll_run_id' => $payrollRun->id, 'staff_profile_id' => $staffProfile->id],
                [
                    'gross_pay' => $gross,
                    'paye' => $payeAmount,
                    'nssf' => $nssfAmount,
                    'net_pay' => $gross - $payeAmount - $nssfAmount,
                ]
            );
        }

        $payrollRun->update(['status' => 'approved']);

        return back()->with('status', "Generated {$staff->count()} payslip(s).");
    }
}
