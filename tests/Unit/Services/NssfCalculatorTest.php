<?php

namespace Tests\Unit\Services;

use App\Services\Payroll\NssfCalculator;
use PHPUnit\Framework\TestCase;

class NssfCalculatorTest extends TestCase
{
    public function test_deducts_five_percent_of_gross(): void
    {
        $calculator = new NssfCalculator;

        $this->assertSame(45_000.0, $calculator->calculate(900_000));
        $this->assertSame(0.0, $calculator->calculate(0));
    }
}
