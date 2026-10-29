<?php

namespace App\Services\Reports;

use App\Models\HospitalitySector;
use App\Support\LegacyMath;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — ComparisonService (the comparison of two studies)
//  Location: app/Services/Reports/ComparisonService.php
//
//  The investment feasibility cards of the two studies side by side with their variance, then, for revenues, gross profit, EBITDA,
//  EBIT, EBT and net profit, the yearly figures (in thousands) of each study with the variance amount and the variance %.
//  Ported from HospitalitySectorController@viewComparingDashboard. Two small differences from the previous application:
//    - a variance % where the second study's figure is zero is 0 (the previous application stopped with an error);
//    - the year columns are named from the first study's calendar (the previous one used the second study's, by accident of the order it loaded them).
// ══════════════════════════════════════════════════════════════════

class ComparisonService
{
    /** the six comparing cards: [key, key in the dashboard data] */
    public const CARDS = [
        ['revenues', 'total_revenues'], ['grossProfit', 'grossProfitDepartmentChartData'], ['ebitda', 'ebitda'],
        ['ebit', 'ebit'], ['ebt', 'ebt'], ['netProfit', 'net_profit'],
    ];

    public function __construct(private readonly StudyAnalysis $analysis) {}

    /** @return array<string, mixed>|null  null when one of the studies cannot be calculated yet */
    public function compare(HospitalitySector $first, HospitalitySector $second): ?array
    {
        // each study works with its own calendar
        $a = $this->analysis->dashboard($first->bindDateTables());
        $b = $this->analysis->dashboard($second->bindDateTables());
        $first->bindDateTables();
        if ($a === null || $b === null) {
            return null;
        }

        $investment = [];
        foreach ($a['card_items'] as $index => $item) {
            $firstValue = number_unformat($item['value']);
            $secondValue = number_unformat($b['card_items'][$index]['value'] ?? '0');
            $variance = $firstValue - $secondValue;
            $percentage = $secondValue ? (($firstValue / $secondValue) - 1) * 100 : 0;
            $isPercentage = str_contains($item['title'], '%');
            $decimals = $isPercentage ? 2 : 0;
            $suffix = $isPercentage ? ' %' : '';
            $investment[] = [
                'key' => $item['title'],
                'title' => $item['title'],
                'text' => [
                    LegacyMath::numberFormat($firstValue, $decimals).$suffix, LegacyMath::numberFormat($secondValue, $decimals).$suffix,
                    LegacyMath::numberFormat($variance, $decimals).$suffix, LegacyMath::numberFormat($percentage, 2).' %',
                ],
                'values' => [$firstValue, $secondValue, $variance, $percentage],
            ];
        }

        $indexes = array_keys($a['total_revenues']);
        $dateIndexWithDate = $first->getDatesIndexesHelper()['dateIndexWithDate'];
        $columns = array_map(fn ($index) => ['label' => 'Yr-'.explode('-', $dateIndexWithDate[$index])[2], 'year' => (int) explode('-', $dateIndexWithDate[$index])[2]], $indexes);

        $cards = [];
        foreach (self::CARDS as [$key, $source]) {
            $rows = ['first' => [], 'second' => [], 'variance' => [], 'percent' => []];
            $texts = ['first' => [], 'second' => [], 'variance' => [], 'percent' => []];
            foreach ($indexes as $index) {
                $x = $a[$source][$index] ?? 0;
                $y = $b[$source][$index] ?? 0;
                $percent = $y ? ($x / $y - 1) * 100 : 0;
                foreach (['first' => $x, 'second' => $y, 'variance' => $x - $y] as $way => $value) {
                    $rows[$way][] = $value / 1000;
                    $texts[$way][] = LegacyMath::numberFormat($value / 1000, 0);
                }
                $rows['percent'][] = $percent;
                $texts['percent'][] = LegacyMath::numberFormat($percent, 2).' %';
            }
            $cards[] = [
                'key' => $key,
                'columns' => $columns,
                'rows' => array_map(fn (string $way) => ['key' => $way, 'label' => match ($way) { 'first' => $first->getName(), 'second' => $second->getName(), default => '' }, 'values' => $rows[$way], 'text' => $texts[$way]], array_keys($rows)),
            ];
        }

        return [
            'names' => [$first->getName(), $second->getName()],
            'currency' => $first->getMainFunctionalCurrencyFormatted(),
            'investment' => $investment,
            'cards' => $cards,
        ];
    }
}
