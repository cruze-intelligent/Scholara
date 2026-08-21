<?php

namespace Tests\Unit\Services;

use App\Services\Payroll\PayeCalculator;
use PHPUnit\Framework\TestCase;

class PayeCalculatorTest extends TestCase
{
    public function test_income_at_or_below_first_band_is_untaxed(): void
    {
        $calculator = new PayeCalculator;

        $this->assertSame(0.0, $calculator->calculate(235_000));
        $this->assertSame(0.0, $calculator->calculate(0));
    }

    public function test_second_band_is_ten_percent_of_the_excess(): void
    {
        $calculator = new PayeCalculator;

        $this->assertSame(5_000.0, $calculator->calculate(285_000));
    }

    public function test_third_band_adds_flat_amount_plus_twenty_percent(): void
    {
        $calculator = new PayeCalculator;

        // 10,000 + 20% of (400,000 - 335,000)
        $this->assertSame(23_000.0, $calculator->calculate(400_000));
    }

    public function test_fourth_band_adds_flat_amount_plus_thirty_percent(): void
    {
        $calculator = new PayeCalculator;

        // 25,000 + 30% of (900,000 - 410,000)
        $this->assertSame(172_000.0, $calculator->calculate(900_000));
    }
}
