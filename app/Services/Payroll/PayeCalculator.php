<?php

namespace App\Services\Payroll;

/**
 * Uganda URA resident-individual monthly PAYE bands. docs/ARCHITECTURE.md
 * and docs/ROADMAP.md name "PAYE deduction calculation" with no figures
 * given anywhere in the docs — the bands below are current published URA
 * rates at the time this was written, not something confirmed against the
 * project's compliance research. Verify against current URA guidance
 * before relying on this for real payroll — same caveat as
 * App\Services\Payments\FakePaymentGateway. See docs/DECISIONS.md.
 */
class PayeCalculator
{
    public function calculate(float $monthlyGross): float
    {
        $income = max(0.0, $monthlyGross);

        return round(match (true) {
            $income <= 235_000 => 0.0,
            $income <= 335_000 => ($income - 235_000) * 0.10,
            $income <= 410_000 => 10_000 + ($income - 335_000) * 0.20,
            $income <= 10_000_000 => 25_000 + ($income - 410_000) * 0.30,
            default => 25_000 + (10_000_000 - 410_000) * 0.30 + ($income - 10_000_000) * 0.40,
        }, 2);
    }
}
