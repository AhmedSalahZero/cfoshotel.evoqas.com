<?php

namespace App\Services\Reports;

use App\Models\DepartmentExpense;
use App\Models\HospitalitySector;
use App\Services\Statements\ReportItems;
use App\Support\HArr;
use App\Support\LegacyCarbon;
use App\Support\LegacyMath;
use App\Support\StudyCache;
use Illuminate\Support\Facades\Log;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — ReportService (the income statement, the cash in / out flow, the balance sheet and the ratios, as the screens show them)
//  Location: app/Services/Reports/ReportService.php
//
//  present() answers  { report, calculated, kind, tabs, intervals }  for the three expandable statements (kind 'tree') and
//  { report, calculated, kind: 'ratios', columns, groups } for the ratios.
//  A tree row is { key, label, level, parent, values, text }:
//    level 0  the total of a block       (always shown)
//    level 1  a main line                (always shown; opens its lines)
//    level 2  a line of a main line      (hidden until its parent is opened)
//    level 3  a line of a line           (income statement only, hidden until its parent is opened)
//  Rows are made exactly as the previous application's TreeRows made them (components one-sub-table, table-with-no-sub, two-subs-table
//  and the cash in / out view), including which figures are summed over an interval and which are read at the end of it.
// ══════════════════════════════════════════════════════════════════

class ReportService
{
    public const REPORTS = ['income', 'cash', 'balance', 'ratios'];

    private const INCOME = [
        ['one', 'hotelRevenue'], ['two', 'directExpenses'], ['none', 'DepartmentsGrossProfit'], ['two', 'undistributedOperatingExpenses'],
        ['one', 'other_deductions'], ['none', 'EBITDA'], ['none', 'incentive_management_fees'], ['none', 'depreciation'], ['none', 'EBIT'],
        ['two', 'Interest Expenses'], ['none', 'EBT'], ['none', 'taxes'], ['none', 'net_profit'],
    ];

    private const BALANCE = [
        ['one', 'Fixed Assets'], ['one', 'Accumulated Depreciation'], ['one', 'Net Fixed Assets'], ['one', 'Projects Under Progress'],
        ['none', 'Total Long Term Assets'], ['none', 'Total Cash & Banks'], ['one', "Customers' Receivables"], ['one', 'Disposables Inventory'],
        ['none', 'Other Debtors'], ['none', 'Total Current Assets'], ['none', 'Total Assets'], ['one', "Suppliers' Payables"],
        ['one', "Fixed Assets' Payables"], ['one', 'Taxes Payables'], ['none', 'Current Portion Of Long Term Debt'], ['one', 'Other Creditors'],
        ['none', 'Total Current Liabilities'], ['none', 'Working Capital'], ['none', 'Total Investment'], ['one', 'Long Term Loans'],
        ['one', 'Owners Equity'], ['none', 'Check Error'],
    ];

    public function __construct(
        private readonly FinancialReports $reports,
        private readonly StudyAnalysis $analysis,
        private readonly ReportItems $items,
    ) {}

    /** The page of a financial statement, kept (plain rows and numbers) until study data is written. A study that cannot be calculated is not kept. */
    public function present(HospitalitySector $study, string $report): array
    {
        return StudyCache::remember($study->id, 'page-'.$report.'-'.app()->getLocale(), fn () => $this->build($study, $report), fn (array $page) => ! empty($page['calculated']));
    }

    /** While building: one line in storage/logs/laravel.log, starting with REPORT, for each step of a statement page. */
    private function lap(string $what, float $since): void
    {
        try {
            Log::info(sprintf('REPORT %s: %.2fs', $what, microtime(true) - $since));
        } catch (\Throwable) {
            // a log line must never stop a screen
        }
    }

    private function build(HospitalitySector $study, string $report): array
    {
        $answer = ['report' => $report, 'calculated' => false, 'kind' => $report === 'ratios' ? 'ratios' : 'tree', 'tabs' => [], 'intervals' => []];

        if ($report === 'ratios') {
            $ratios = $this->analysis->ratios($study);
            if ($ratios === null) {
                return $answer + ['columns' => [], 'groups' => []];
            }

            return ['calculated' => true] + $answer + $this->ratioTable($ratios);
        }

        $lap = microtime(true);
        $cash = $this->reports->cashInOut($study);
        if ($cash === null) {
            return $answer;
        }
        $this->lap($report.': cash in/out and statement items', $lap);
        $answer['calculated'] = true;
        $c = $cash['c'];
        $study->load('departmentExpenses');

        if ($report === 'balance') {
            $sheet = $this->reports->balanceSheet($study);
            $original = $sheet['study_dates'];
            $sheetItems = $sheet['balance_sheet_items'];
            $datesFor = fn (string $interval) => $this->dates(intervalsEndBalance($original, $interval, $study->financialYearStartMonth(), $c['dateIndexWithDate']), $study, $c);
            $rowsFor = function (string $interval, array $dates) use ($study, $sheetItems, $c) {
                $rows = [];
                foreach (self::BALANCE as [$kind, $key]) {
                    array_push($rows, ...$this->tree($kind, $key, $sheetItems[$key] ?? [], $interval, $dates, $study, $c, 'intervalsEndBalance'));
                }

                return $rows;
            };
        } elseif ($report === 'cash') {
            $original = $cash['studyDates'];
            $cashItems = $cash['reportItems'];
            $datesFor = fn (string $interval) => $this->dates(sumIntervalsIndexes($original, $interval, $study->financialYearStartMonth(), $c['dateIndexWithDate']), $study, $c);
            $rowsFor = function (string $interval, array $dates) use ($study, $cashItems, $c) {
                $rows = $this->cashBlock('in', $cashItems['cashInReport'] ?? [], $interval, $dates, $study, $c);
                $rows[] = ['key' => 'gap', 'gap' => true, 'level' => 0, 'parent' => null, 'label' => '', 'values' => [], 'text' => []];
                array_push($rows, ...$this->cashBlock('out', $cashItems['cashOutReport'] ?? [], $interval, $dates, $study, $c));
                array_push($rows, ...$this->tree('none', 'netCash', $cashItems['netCash'] ?? [], $interval, $dates, $study, $c));

                return $rows;
            };
        } else {
            $perYear = $study->getOperationDurationPerYear($c['datesAsStringAndIndex'], $c['datesIndexWithYearIndex'], $c['yearIndexWithYear'], $c['dateIndexWithDate'], $c['dateWithMonthNumber']);
            $original = $study->getOnlyDatesOfActiveOperation($perYear, $c['dateIndexWithDate']);
            $incomeItems = $cash['finalReportItems'];
            $datesFor = fn (string $interval) => $this->dates(sumIntervalsIndexes($original, $interval, $study->financialYearStartMonth(), $c['dateIndexWithDate']), $study, $c);
            $rowsFor = function (string $interval, array $dates) use ($study, $incomeItems, $c) {
                $rows = [];
                foreach (self::INCOME as [$kind, $key]) {
                    $data = isset($incomeItems[$interval][$key]) ? $incomeItems[$interval][$key] : ($incomeItems['monthly'][$key] ?? []);
                    if ($key === 'taxes') {
                        // the cash report adds the payments statement next to the taxes; the income statement shows the taxes alone
                        $data = array_intersect_key($data, ['Corporate Taxes' => true]);
                    }
                    array_push($rows, ...$this->tree($kind, $key, $data, $interval, $dates, $study, $c));
                }

                return $rows;
            };
        }

        foreach (getIntervalFormatted() as $interval => $label) {
            $lap = microtime(true);
            $dates = $datesFor($interval);
            $answer['intervals'][] = [
                'name' => $interval,
                'columns' => $this->columns($dates, $interval),
                'rows' => $rowsFor($interval, $dates),
            ];
            $this->lap($report.': rows for '.$interval, $lap);
        }
        $answer['tabs'] = array_map(fn ($interval) => ['name' => $interval['name']], $answer['intervals']);

        return $answer;
    }

    /** [date as text => date index] for the columns of an interval */
    private function dates(array $summed, HospitalitySector $study, array $c): array
    {
        return $study->convertArrayOfStringDatesToStringDatesAndDateIndex($summed, $c['dateIndexWithDate'], $c['dateWithDateIndex']);
    }

    private function columns(array $dates, string $interval): array
    {
        $columns = [];
        foreach (array_keys($dates) as $fullDate) {
            $year = (int) explode('-', $fullDate)[2];
            $columns[] = ['label' => $interval === 'annually' ? 'Yr-'.$year : formatDateForView($fullDate), 'year' => $year];
        }

        return $columns;
    }

    // ── the rows (TreeRows) ──

    /** one figure line: the values and the text of each column */
    private function line(string $key, string $label, int $level, ?string $parent, array $items, array $dates, array $extra = []): array
    {
        $values = [];
        $text = [];
        foreach ($dates as $dateAsString => $dateAsIndex) {
            $value = getValueFromArrayStringAndIndex($items, $dateAsString, $dateAsIndex, 0);
            $values[] = $value;
            $text[] = LegacyMath::numberFormat($value, 0);
        }

        return ['key' => $key, 'label' => $label, 'level' => $level, 'parent' => $parent, 'values' => $values, 'text' => $text] + $extra;
    }

    /** the three table components of the previous application: 'one' (one-sub-table), 'none' (table-with-no-sub) and 'two' (two-subs-table) */
    private function tree(string $kind, string $section, array $reportData, string $interval, array $dates, HospitalitySector $study, array $c, string $sumMethod = 'sumIntervalsIndexes'): array
    {
        $start = $study->financialYearStartMonth();
        $dateIndexWithDate = $c['dateIndexWithDate'];
        $rows = [];
        foreach ($reportData as $reportName => $block) {
            $top = $section.'/'.$reportName;
            $items = $sumMethod(removeKeyFromArray($block, 'subItems'), $interval, $start, $dateIndexWithDate);
            if ($kind === 'none' && $reportName === 'Accumulated Net Cash') {
                $items = HArr::accumulateArray($reportData['Net Cash Report']);
            }
            $rows[] = $this->line($top, (string) $reportName, 0, null, $items, $dates);

            foreach ($block['subItems'] ?? [] as $subName => $sub) {
                $main = $top.'/'.$subName;
                if ($kind === 'one' && $subName === 'Net Profit') {
                    $subItems = sumIntervalsIndexes(removeKeyFromArray($sub, 'subItems'), $interval, $start, $dateIndexWithDate);
                } elseif ($kind === 'one' && $subName === 'Retained Earnings') {
                    $netProfit = sumIntervalsIndexes($reportData['Owners Equity']['subItems']['Net Profit'] ?? [], $interval, $start, $dateIndexWithDate);
                    $subItems = $study->calculateRetainedEarning($netProfit, $interval);
                } else {
                    $subItems = ($kind === 'two' ? 'sumIntervalsIndexes' : $sumMethod)(removeKeyFromArray($sub, 'subItems'), $interval, $start, $dateIndexWithDate);
                }
                $rows[] = $this->line($main, (string) $subName, 1, $top, $subItems, $dates, ['expandable' => (bool) count($sub['subItems'] ?? [])]);

                foreach ($sub['subItems'] ?? [] as $name => $leaf) {
                    $leafKey = $main.'/'.$name;
                    $leafItems = ($kind === 'two' ? 'sumIntervalsIndexes' : $sumMethod)(removeKeyFromArray($leaf, 'subItems'), $interval, $start, $dateIndexWithDate);
                    if ($kind !== 'two') {
                        $rows[] = $this->line($leafKey, (string) $name, 2, $main, $leafItems, $dates);

                        continue;
                    }
                    $rows[] = $this->line($leafKey, str_replace('_', ' ', (string) $name), 2, $main, $leafItems, $dates, ['expandable' => (bool) count($leaf['subItems'] ?? [])]);
                    foreach ($leaf['subItems'] ?? [] as $itemId => $values) {
                        $itemItems = sumIntervalsIndexes(removeKeyFromArray($values, 'subItems'), $interval, $start, $dateIndexWithDate);
                        $rows[] = $this->line($leafKey.'/'.$itemId, DepartmentExpense::getNameFromId($itemId, $study->departmentExpenses), 3, $leafKey, $itemItems, $dates, ['item' => true]);
                    }
                }
            }
        }

        return $rows;
    }

    /** the cash in or the cash out block of the cash in / out report */
    private function cashBlock(string $way, array $reportData, string $interval, array $dates, HospitalitySector $study, array $c): array
    {
        $start = $study->financialYearStartMonth();
        $dateIndexWithDate = $c['dateIndexWithDate'];
        $rows = [];
        foreach ($reportData as $reportName => $block) {
            $top = $way.'/'.$reportName;
            $rows[] = $this->line($top, (string) $reportName, 0, null, sumIntervalsIndexes(removeKeyFromArray($block, 'subItems'), $interval, $start, $dateIndexWithDate), $dates);
            foreach ($block['subItems'] ?? [] as $subName => $sub) {
                $main = $top.'/'.$subName;
                $rows[] = $this->line($main, (string) $subName, 1, $top, sumIntervalsIndexes(removeKeyFromArray($sub, 'subItems'), $interval, $start, $dateIndexWithDate), $dates, ['expandable' => (bool) count($sub['subItems'] ?? [])]);
                $total = $way === 'in' ? 'Total '.(explode(' ', (string) $subName)[1] ?? '') : 'Total Collection';
                foreach ($sub['subItems'] ?? [] as $name => $values) {
                    $rows[] = $this->line($main.'/'.$name, DepartmentExpense::getNameFromId($name, $study->departmentExpenses, $total), 2, $main, sumIntervalsIndexes(removeKeyFromArray($values, 'subItems'), $interval, $start, $dateIndexWithDate), $dates, ['item' => true]);
                }
            }
        }

        return $rows;
    }

    // ── the ratios ──

    private function ratioTable(array $ratios): array
    {
        $dates = $ratios['dates'];
        $columns = [];
        foreach (array_keys($dates) as $fullDate) {
            $columns[] = ['label' => formatDateForView($fullDate), 'year' => (int) LegacyCarbon::make($fullDate)->format('Y')];
        }

        $groups = [];
        foreach ($ratios['ratio_analysis_report'] as $mainName => $mainData) {
            $lines = [];
            foreach ($mainData as $subName => $sub) {
                $decimals = $sub['decimals'] ?? 0;
                $multiplier = $mainName == 'Profitability Ratios' ? 100 : 1;
                if ($subName == 'Working Capital') {
                    $decimals = 0;
                }
                $values = [];
                $text = [];
                foreach ($dates as $dateAsString => $dateAsIndex) {
                    $raw = $sub['data'][$dateAsIndex] ?? null;
                    $values[] = $raw === null ? 0 : $raw * $multiplier;
                    $text[] = $raw === null ? '0' : LegacyMath::numberFormat($raw * $multiplier, $decimals);
                }
                $lines[] = ['key' => (string) $subName, 'label' => (string) $subName, 'description' => $sub['description'] ?? '-', 'mark' => $sub['mark'] ?? '', 'values' => $values, 'text' => $text];
            }
            $groups[] = ['key' => (string) $mainName, 'label' => (string) $mainName, 'rows' => $lines];
        }

        return ['columns' => $columns, 'groups' => $groups];
    }
}
