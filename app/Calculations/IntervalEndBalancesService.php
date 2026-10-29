<?php

namespace App\Calculations;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — IntervalEndBalancesService (the balance at the end of each interval)
//  Location: app/Calculations/IntervalEndBalancesService.php
//
//  A balance (cash, receivables, loans, equity, ...) is not summed over a quarter or a year: the figure of the closing month of the interval is the one that counts.
//  Port of the previous application's app/ReadyFunctions/IntervalEndBalancesService.php, with the same rule:
//    · quarterly: the months 3, 6, 9 and 12;  semi-annually: the months 6 and 12;  annually: the month 12 (financial year starting in January);
//    · an interval that is not complete at the end of the study (no closing month in it) gives no figure;
//    · monthly: the figures as they are.
//  The one difference: the result is always keyed by the date index (the old one was keyed by the date text for quarters and half years,
//  and by the index for years, and its callers had to cope with both).
//  Financial year starting in April or July: the old class gave quarters without March (a TODO in its code) and nothing at all for half years and years,
//  so the balance sheet and the ratios stopped with an error. In the old application the sums of the other statements (IntervalSummationOperations) do not
//  follow the start month either: a slip in its test (`$financialYearStartMonth = '01'` assigns instead of comparing) makes every study use calendar quarters,
//  half years and years. So the balances close in the calendar months, whatever the start month, and line up with the other statements.
// ══════════════════════════════════════════════════════════════════

class IntervalEndBalancesService
{
    private const MONTHS = [
        'quarterly' => [3, 6, 9, 12],
        'semi-annually' => [6, 12],
        'annually' => [12],
    ];

    /**
     * @param  array<int|string, float|int>  $dateValues  date (or date index) => balance
     * @return array<int, float|int>  date index => balance, for the closing months only
     */
    public function __calculate(array $dateValues, string $financialYearStartMonth, string $intervalName, array $dateIndexWithDate = []): array
    {
        if ($intervalName === 'monthly') {
            return $dateValues;
        }
        // the start month of the financial year does not move the periods: the sums of the study (IntervalSummationOperations) always use calendar quarters,
        // half years and years (see the note above), and the balances must close in the same months as the sums
        $months = self::MONTHS[$intervalName] ?? null;
        if (! $months) {
            return [];
        }
        $dateIndexWithDate = $dateIndexWithDate ?: app('dateIndexWithDate');
        $indexOfDate = array_flip($dateIndexWithDate);
        $result = [];
        foreach ($dateValues as $date => $value) {
            $dateString = is_numeric($date) ? $dateIndexWithDate[$date] : $date;
            $month = (int) (explode('-', $dateString)[1] ?? 0);
            if (in_array($month, $months, true)) {
                $result[is_numeric($date) ? $date : ($indexOfDate[$dateString] ?? $dateString)] = $value;
            }
        }

        return $result;
    }
}
