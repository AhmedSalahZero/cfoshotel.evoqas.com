<?php

namespace App\Calculations;

use App\Support\LegacyCarbon;
use App\Support\LegacyMath;

/**
 * Payback period: how long until the money the owners put in has come back.
 * Ported from the previous CFO Hotels (App\ReadyFunctions\CalculatePaybackPeriodService).
 */
class CalculatePaybackPeriodService
{
    /**
     * @param  int  $studyDurationInYears  used for the "More Than 7 Years" wording
     * @param  array  $accumulatedFreeCashFlowForEquity  month by month, first month first
     * @param  string  $studyStartDateAsString  first month of the study, like 01-01-2026
     * @param  float  $totalEquityInjection  the money the owners put in
     * @return array one entry: [month the money is back (like "Mar 2031") => "5.2 Years"]; when it never comes back
     *               within the study the month is empty and the text is "More Than 7 Years"
     */
    public function __calculate(int $studyDurationInYears, array $accumulatedFreeCashFlowForEquity, string $studyStartDateAsString, float $totalEquityInjection)
    {
        $paybackPeriod = 1;
        $paybackDate = null;
        $isMoreThan = true;
        foreach ($accumulatedFreeCashFlowForEquity as $accumulated) {
            if ($accumulated < 0) {
                $paybackPeriod++;
            } else {
                if ($accumulated >= $totalEquityInjection) {
                    $isMoreThan = false;
                    $paybackDate = (LegacyCarbon::make($studyStartDateAsString)->addMonths($paybackPeriod - 1)->format('M Y'));
                    break;
                } else {
                    $paybackPeriod++;
                }
            }
        }

        $paybackPeriod = LegacyMath::numberFormat($paybackPeriod / 12, 1).' Years';
        if ($isMoreThan) {
            $paybackPeriod = __('More Than ').$studyDurationInYears.' Years';
        }

        return [
            $paybackDate => $paybackPeriod,
        ];
    }

    /** The longest payback the screens will print; beyond it they say "More than 50 years". */
    public const LONGEST_ESTIMATE_YEARS = 50;

    /**
     * Payback that also answers when the money is NOT back inside the study (a hotel with a 10-year study may repay in 15).
     *
     *  - inside the study  → the exact month, same answer as __calculate();
     *  - beyond the study  → an ESTIMATE: the last 12 months' cash flow is carried forward, year after year growing at
     *                        $yearlyGrowth, until the money still missing is covered;
     *  - never             → the last year brings in nothing (or too little, once discounted) to ever cover it;
     *  - capped            → the estimate is longer than LONGEST_ESTIMATE_YEARS.
     *
     * For the discounted payback pass the DISCOUNTED accumulated cash and the growth net of the discount rate:
     * (1 + growth) / (1 + required return) - 1.
     *
     * @param  float  $yearlyGrowth  a fraction: 0.02 means 2% a year (may be negative)
     * @return array{status: string, years: ?float, months: ?int, date: ?string, estimated: bool}
     *               status = 'within' | 'estimated' | 'never' | 'capped'
     */
    public function estimate(int $studyDurationInYears, array $accumulatedFreeCashFlowForEquity, string $studyStartDateAsString, float $totalEquityInjection, float $yearlyGrowth = 0.0): array
    {
        $exact = $this->__calculate($studyDurationInYears, $accumulatedFreeCashFlowForEquity, $studyStartDateAsString, $totalEquityInjection);
        $date = array_key_first($exact);

        if ($date !== null && $date !== '') {
            $months = 1;
            foreach ($accumulatedFreeCashFlowForEquity as $accumulated) {   // the same walk as __calculate(): months until the money is back
                if ($accumulated >= 0 && $accumulated >= $totalEquityInjection) {
                    break;
                }
                $months++;
            }

            return ['status' => 'within', 'years' => round($months / 12, 1), 'months' => $months, 'date' => $date, 'estimated' => false];
        }

        $never = ['status' => 'never', 'years' => null, 'months' => null, 'date' => null, 'estimated' => true];
        $accumulated = array_values($accumulatedFreeCashFlowForEquity);
        $count = count($accumulated);
        if ($count === 0) {
            return $never;
        }

        // the cash the last twelve months brought in (scaled to a year when the study has fewer months)
        $window = min(12, $count);
        $before = $count > 12 ? (float) $accumulated[$count - 13] : 0.0;
        $lastYear = ((float) $accumulated[$count - 1] - $before) * 12 / $window;
        $missing = $totalEquityInjection - (float) $accumulated[$count - 1];

        if ($lastYear <= 0) {
            return $never;
        }

        $next = $lastYear * (1 + $yearlyGrowth);            // the first year after the study
        if ($next <= 0) {
            return $never;
        }

        if (abs($yearlyGrowth) < 1.0E-12) {
            $extraYears = $missing / $next;
        } else {
            $argument = 1 + $missing * $yearlyGrowth / $next;
            if ($argument <= 0) {
                return $never;                               // the shrinking flow never adds up to what is missing
            }
            $extraYears = log($argument) / log(1 + $yearlyGrowth);
        }

        $studyMonths = max($count, $studyDurationInYears * 12);
        $months = $studyMonths + (int) ceil($extraYears * 12 - 1.0E-9);
        $years = round($months / 12, 1);

        if ($months / 12 > self::LONGEST_ESTIMATE_YEARS) {
            return ['status' => 'capped', 'years' => null, 'months' => null, 'date' => null, 'estimated' => true];
        }

        return [
            'status' => 'estimated',
            'years' => $years,
            'months' => $months,
            'date' => LegacyCarbon::make($studyStartDateAsString)->addMonths($months - 1)->format('M Y'),
            'estimated' => true,
        ];
    }
}
