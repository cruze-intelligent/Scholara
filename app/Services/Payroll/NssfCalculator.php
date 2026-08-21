<?php

namespace App\Services\Payroll;

/**
 * Uganda NSSF Act employee contribution rate. See the same caveat as
 * PayeCalculator — verify against current NSSF guidance before relying on
 * this for real payroll. Employer's matching 10% isn't modeled since
 * `payslips` only has an employee-side `nssf` deduction column.
 */
class NssfCalculator
{
    public const EMPLOYEE_RATE = 0.05;

    public function calculate(float $monthlyGross): float
    {
        return round(max(0.0, $monthlyGross) * self::EMPLOYEE_RATE, 2);
    }
}
