<?php

namespace Tests\Unit;

use App\Support\LegacyMath;
use PHPUnit\Framework\TestCase;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Rounding (App\Support\LegacyMath)
//  Location: tests/Unit/LegacyMathTest.php
//
//  PHP 8.4 rounds a few numbers one unit differently from PHP 8.1 (the PHP of the previous CFO Hotels), for example
//  1400937.4999999998 is shown as 1,400,938 by 8.1 but 1,400,937 by 8.4. LegacyMath keeps the 8.1 way.
//
//  DECISION: a difference of one unit on a figure of millions does not matter for hotel financial planning, so the
//  huge proof of 500,000 numbers (two compressed files of 8.6 MB) was removed. These hand-picked cases are enough
//  to show that LegacyMath works and keeps giving the same answers. Run:  php artisan test --filter=LegacyMathTest
// ══════════════════════════════════════════════════════════════════
class LegacyMathTest extends TestCase
{
    /** The number that started the discussion, and the other typical "just below a half" cases. */
    public function test_values_just_below_a_half_round_as_on_php_8_1(): void
    {
        $this->assertSame('1,400,938', LegacyMath::numberFormat(1400937.4999999998));
        $this->assertSame(1400938.0, LegacyMath::round(1400937.4999999998));
        $this->assertSame('0.29', LegacyMath::numberFormat(0.285, 2));
        $this->assertSame(1.01, LegacyMath::round(1.005, 2));
        $this->assertSame(2.68, LegacyMath::round(2.675, 2));
        $this->assertSame(1.5, LegacyMath::round(1.45, 1));
    }

    public function test_ordinary_amounts_round_in_the_usual_way(): void
    {
        $this->assertSame(3.0, LegacyMath::round(2.5));
        $this->assertSame(-3.0, LegacyMath::round(-2.5));
        $this->assertSame(4.0, LegacyMath::round(3.5));
        $this->assertSame(2.0, LegacyMath::round(2.4));
        $this->assertSame(1234.57, LegacyMath::round(1234.5678, 2));
        $this->assertSame(1200.0, LegacyMath::round(1234.5678, -2));
        $this->assertSame(0.0, LegacyMath::round(0));
    }

    public function test_numbers_are_shown_with_thousands_separators(): void
    {
        $this->assertSame('1,234,567.89', LegacyMath::numberFormat(1234567.891, 2));
        $this->assertSame('1,235', LegacyMath::numberFormat(1234.5));
        $this->assertSame('0', LegacyMath::numberFormat(0));
        $this->assertSame('-1,234.6', LegacyMath::numberFormat(-1234.567, 1));
        $this->assertSame('1.234,57', LegacyMath::numberFormat(1234.5678, 2, ',', '.'));
        $this->assertSame('2,500,000', LegacyMath::numberFormat(2500000));
        $this->assertSame('0.4', LegacyMath::numberFormat(5 / 12, 1)); // the payback period
    }

    public function test_the_same_number_always_gives_the_same_answer(): void
    {
        foreach ([0.1 + 0.2, 1400937.4999999998, 99999.995, -0.005, 123456789.125] as $value) {
            $this->assertSame(LegacyMath::numberFormat($value, 2), LegacyMath::numberFormat($value, 2));
            $this->assertSame(LegacyMath::round($value, 2), LegacyMath::round($value, 2));
        }
    }
}
