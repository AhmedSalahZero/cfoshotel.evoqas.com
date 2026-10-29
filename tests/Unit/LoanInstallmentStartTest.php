<?php

namespace Tests\Unit;

use App\Support\HDate;
use PHPUnit\Framework\TestCase;

// ══════════════════════════════════════════════════════════════════
//  Loan installments: when the first installment falls (hand-checkable)
//  Location: tests/Unit/LoanInstallmentStartTest.php
//  Run:      php artisan test --filter=LoanInstallmentStartTest
//
//  The loan schedule has one date every installment interval (1, 3 or 6 months), keyed by month number. The first installment falls
//  one interval after the loan start (plus the grace period, rounded up to whole intervals). Counted in months.
//  Before the fix quarterly and semi-annual loans looked for a date that does not exist and stopped the whole revenue calculation.
// ══════════════════════════════════════════════════════════════════

class LoanInstallmentStartTest extends TestCase
{
    public function test_monthly_loan_is_unchanged(): void
    {
        $this->assertSame(1, HDate::installmentStartShift(0, 1));
        $this->assertSame(13, HDate::installmentStartShift(12, 1));
    }

    public function test_quarterly_loan_first_installment_after_three_months(): void
    {
        $this->assertSame(3, HDate::installmentStartShift(0, 3));
    }

    public function test_semi_annual_loan_first_installment_after_six_months(): void
    {
        $this->assertSame(6, HDate::installmentStartShift(0, 6));
    }

    public function test_grace_period_is_rounded_up_to_whole_intervals(): void
    {
        $this->assertSame(9, HDate::installmentStartShift(4, 3));   // 4 months of grace -> 6, plus one quarter
        $this->assertSame(9, HDate::installmentStartShift(6, 3));   // exact
        $this->assertSame(18, HDate::installmentStartShift(12, 6)); // 12 + 6
    }

    public function test_the_first_installment_date_exists_in_the_schedule(): void
    {
        // A 12-month quarterly loan starting at month 100 has dates 100, 103, 106, 109, 112.
        $dates = [100 => 'a', 103 => 'b', 106 => 'c', 109 => 'd', 112 => 'e'];
        $flip = array_flip($dates);
        $this->assertSame('b', HDate::getDateAfterIndex($dates, $flip, 'a', HDate::installmentStartShift(0, 3)));
    }
}
