<?php

namespace App\Calculations;

use MathPHP\Finance;

/**
 * Internal rate of return (IRR) and net present value (NPV) of a free-cash-flow series.
 *
 * DECISION (owner, Step 06): the IRR is calculated by the MathPHP library (Finance::irr), everywhere in the application.
 * That is what the study dashboard of the previous CFO Hotels used. The previous application also contained a home-made
 * IRR search (moving the rate by 0.001% at a time) but it was switched off in the dashboard, so it was not carried over:
 * one single IRR in the whole application means the same cash flows always give the same answer.
 *
 * The library must be installed:  composer require markrogoyski/math-php
 */
class CalculateIrrService
{
    /**
     * Internal rate of return, as a fraction (0.15 = 15%). Same as =IRR() in a spreadsheet: the first value is the
     * investment (usually negative) at time zero, the next values come one period (year) later each.
     *
     * Returns NAN when there is no answer (for example when the cash flows are all positive or all negative, or there is
     * only one value). Use is_nan() before showing it.
     *
     * @param  array  $freeCashFlow  the cash flow of each year, first year first (the keys do not matter)
     */
    public function calculateIrr(array $freeCashFlow): float
    {
        return Finance::irr(array_values($freeCashFlow));
    }

    /**
     * Net present value of a free-cash-flow series. The first value is discounted one year, the second two years, and so on.
     *
     * @param  float  $costOfFundRate  in percent, for example 12 for 12%
     */
    public function calculateNetPresentValue(array $freeCashFlow, float $costOfFundRate): float
    {
        $netPresentValues = [];
        $costOfFundRate = $costOfFundRate / 100;
        $index = 1;
        foreach ($freeCashFlow as $date => $value) {
            $netPresentValues[$date] = $value / (pow((1 + $costOfFundRate), $index));
            $index++;
        }

        return array_sum($netPresentValues);
    }
}
