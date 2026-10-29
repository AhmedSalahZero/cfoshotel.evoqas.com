<?php

namespace Tests\Unit;

use App\Calculations\InstallmentMethod;
use App\Calculations\LandAcquisitionCostAndPayment;
use PHPUnit\Framework\TestCase;

// ══════════════════════════════════════════════════════════════════
//  Buying land or a property by installments (hand-checkable)
//  Location: tests/Unit/InstallmentPaymentsTest.php
//  Run:      php artisan test --filter=InstallmentPaymentsTest
//
//  A purchase of 306: 30 % down at month 0, 20 % at month 6, the other 50 % in 4 installments every quarter after that.
//  Before the fix only the first installment was ever recorded (the date never moved), and the loan withdrawal of a later payment
//  was worked out against "the previous month" instead of "the previous payment".
// ══════════════════════════════════════════════════════════════════

class InstallmentPaymentsTest extends TestCase
{
    public function test_every_installment_has_its_own_date(): void
    {
        $payments = (new InstallmentMethod)->__calculate(0, 306.0, 30, 50, 4, 'quarterly', 20, 6);

        $this->assertSame([0, 6, 9, 12, 15, 18], array_keys($payments));
        $this->assertEqualsWithDelta([91.8, 61.2, 38.25, 38.25, 38.25, 38.25], array_values($payments), 0.0001);
        $this->assertEqualsWithDelta(306.0, array_sum($payments), 0.0001);
    }

    public function test_monthly_installments_follow_each_other(): void
    {
        $payments = (new InstallmentMethod)->__calculate(2, 120.0, 40, 60, 3, 'monthly', 0, 0);

        $this->assertSame([2, 3, 4, 5], array_keys($payments));
        $this->assertEqualsWithDelta([48.0, 24.0, 24.0, 24.0], array_values($payments), 0.0001);
    }

    public function test_one_installment_is_unchanged(): void
    {
        $payments = (new InstallmentMethod)->__calculate(0, 100.0, 30, 50, 1, 'monthly', 20, 0);

        $this->assertEqualsWithDelta([0 => 50.0, 1 => 50.0], $payments, 0.0001);
    }

    public function test_the_loan_takes_over_once_the_equity_is_used_up(): void
    {
        // 80 % equity of 306 = 244.8; payments 91.8, 61.2, 38.25 (x4): the equity runs out inside the installment at month 15
        $payments = [0 => 91.8, 6 => 61.2, 9 => 38.25, 12 => 38.25, 15 => 38.25, 18 => 38.25];

        $withdrawal = (new LandAcquisitionCostAndPayment)->calculateLandLoanWithdrawal($payments, 306.0, 80);

        $this->assertSame([15, 18], array_keys($withdrawal));
        $this->assertEqualsWithDelta(22.95, $withdrawal[15], 0.0001);
        $this->assertEqualsWithDelta(38.25, $withdrawal[18], 0.0001);
        $this->assertEqualsWithDelta(306.0 * 0.2, array_sum($withdrawal), 0.0001);
    }

    public function test_all_cash_equity_needs_no_loan(): void
    {
        $this->assertSame([], (new LandAcquisitionCostAndPayment)->calculateLandLoanWithdrawal([0 => 100.0], 100.0, 100));
    }
}
