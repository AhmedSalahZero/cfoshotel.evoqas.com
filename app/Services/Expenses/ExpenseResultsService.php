<?php

namespace App\Services\Expenses;

use App\Models\HospitalitySector;
use App\Services\Revenue\RevenueFiguresService;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — ExpenseResultsService (the calculated expenses of a study, year by year)
//  Location: app/Services/Expenses/ExpenseResultsService.php
//
//  The figures come from HospitalitySector::calculateRoomRevenueAndGuestCount() — the calculation of the previous application, carried over
//  line for line — and are the same figures that application feeds to its income statement and cash-out report:
//    directExpenses[department][line]['total'][date index]            the direct expenses of rooms, F&B, gaming, meetings and other revenue
//    'Undistributed Operating Expenses'[group][line]['total'][date]   general, sales & marketing, property and energy
//    CashOutReport['Management Fees']['Base Management Fees'][date]  the base management fee
//    startUpAndPreOperationExpensesTotals['payments'][date]           start-up cost and pre-operating expenses, as they are paid
//  This class adds the months of each calendar year together. The columns are every calendar year of the study (the pre-opening years too,
//  because start-up costs fall there). The incentive management fee is a percentage of EBITDA: the previous application works it out in the
//  income statement (step 12), so it is not on this page.
// ══════════════════════════════════════════════════════════════════

class ExpenseResultsService
{
    /** page key => [the key the calculation uses, the column that says the stream is used (null = always)] */
    public const DIRECT = [
        'rooms' => ['Rooms Direct Expenses', null],
        'foods' => ['Foods Direct Expenses', null],
        'casinos' => ['Gaming Direct Expenses', 'has_casino_section'],
        'meetings' => ['Meetings Direct Expenses', 'has_meeting_section'],
        'others' => ['Other Revenue Direct Expenses', 'has_other_section'],
    ];

    public const UNDISTRIBUTED = [
        'general' => 'General And Administrative Expenses',
        'marketing' => 'Sales And Marketing Expenses',
        'property' => 'Property Expenses',
        'energy' => 'Energy Expenses',
    ];

    public function __construct(private readonly RevenueFiguresService $figures) {}

    public function present(HospitalitySector $study): array
    {
        $years = $this->years($study);
        $result = $this->figures->result($study);

        if ($result === null) {
            return ['years' => $years, 'calculated' => false, 'groups' => [], 'totals' => []];
        }

        $yearOf = $this->yearOfDate($study);
        $groups = [
            ['key' => 'direct', 'sections' => $this->sections(self::DIRECT, $result['directExpenses'] ?? [], $study, $yearOf, $years)],
            ['key' => 'undistributed', 'sections' => $this->sections(array_map(fn ($label) => [$label, null], self::UNDISTRIBUTED), $result['Undistributed Operating Expenses'] ?? [], $study, $yearOf, $years)],
        ];

        $fees = $result['CashOutReport']['Management Fees']['Base Management Fees'] ?? [];
        $groups[] = ['key' => 'management', 'sections' => [[
            'key' => 'management', 'lines' => [['key' => 'base', 'values' => $this->byYear($fees, $yearOf, $years)]],
        ]]];
        $groups[2]['sections'][0]['total'] = $groups[2]['sections'][0]['lines'][0]['values'];

        $payments = $result['startUpAndPreOperationExpensesTotals']['payments'] ?? [];
        $groups[] = ['key' => 'startup', 'sections' => [[
            'key' => 'startup', 'lines' => [['key' => 'payments', 'values' => $this->byYear($payments, $yearOf, $years)]],
        ]]];
        $groups[3]['sections'][0]['total'] = $groups[3]['sections'][0]['lines'][0]['values'];

        foreach ($groups as &$group) {
            $group['total'] = $this->add(array_column($group['sections'], 'total'), $years);
        }
        unset($group);

        $operating = $this->add([$groups[0]['total'], $groups[1]['total'], $groups[2]['total']], $years);

        return ['years' => $years, 'calculated' => true, 'groups' => $groups, 'totals' => ['operating' => $operating]];
    }

    /** Every calendar year of the study (up to its last month): index (year minus the first year), the year, and the months of the study in it. */
    public function years(HospitalitySector $study): array
    {
        $first = null;
        $years = [];
        $last = $study->getStudyEndDateAsIndex();
        foreach ($study->getStudyDates() as $index => $date) {
            if ($last !== null && (int) $index > $last) {
                break;
            }
            $year = (int) (explode('-', (string) $date)[2] ?? 0);
            if ($year === 0) {
                continue;
            }
            $first ??= $year;
            $index = $year - $first;
            $years[$index] ??= ['index' => $index, 'year' => $year, 'months' => 0];
            $years[$index]['months']++;
        }

        return array_values($years);
    }

    /** date index => year index (the first calendar year of the study is 0, as in the previous application). */
    private function yearOfDate(HospitalitySector $study): array
    {
        $first = null;
        $map = [];
        $last = $study->getStudyEndDateAsIndex();
        foreach ($study->getStudyDates() as $index => $date) {
            if ($last !== null && (int) $index > $last) {
                break;
            }
            $year = (int) (explode('-', (string) $date)[2] ?? 0);
            $first ??= $year;
            $map[(int) $index] = $year - $first;
        }

        return $map;
    }

    private function sections(array $config, array $calculated, HospitalitySector $study, array $yearOf, array $years): array
    {
        $out = [];
        foreach ($config as $key => [$label, $used]) {
            if ($used !== null && ! $study->{$used}) {
                continue;
            }
            $lines = [];
            foreach ((array) ($calculated[$label] ?? []) as $name => $line) {
                $lines[] = ['key' => $this->lineKey((string) $name), 'values' => $this->byYear($this->monthly((array) $line), $yearOf, $years)];
            }
            $out[] = ['key' => $key, 'lines' => $lines, 'total' => $this->add(array_column($lines, 'values'), $years)];
        }

        return $out;
    }

    /**
     * The months of one line of the calculation. Most lines carry their own 'total' (the sum of the expenses in them); the fixed expenses and the
     * disposable expenses carry one list of months for each expense instead, and the months of all of them are added together.
     */
    private function monthly(array $line): array
    {
        if (isset($line['total']) && is_array($line['total'])) {
            return $line['total'];
        }
        $sum = [];
        foreach ($line as $months) {
            foreach ((array) $months as $date => $amount) {
                $sum[$date] = ($sum[$date] ?? 0.0) + (is_numeric($amount) ? (float) $amount : 0.0);
            }
        }

        return $sum;
    }

    /** The name the calculation gives a line, as a short key the screen has a word for. */
    private function lineKey(string $name): string
    {
        return match (true) {
            str_contains($name, 'Manpower') => 'manpower',
            str_contains($name, 'Disposable') => 'disposable',
            $name === 'variable_expenses_as_cost_per_night_sold' => 'night',
            $name === 'variable_expenses_as_cost_per_guest' => 'guest',
            $name === 'fixed_monthly_expenses' => 'fixed',
            str_contains($name, 'Variable Exp') || $name === 'variable_expenses_as_percentage_from_total_revenues' => 'percent',
            default => $name,
        };
    }

    /** Monthly figures (date index => amount) added together for each calendar year. */
    private function byYear(array $monthly, array $yearOf, array $years): array
    {
        $out = array_fill_keys(array_column($years, 'index'), 0.0);
        foreach ($monthly as $date => $amount) {
            $year = $yearOf[(int) $date] ?? null;
            if ($year !== null && is_numeric($amount)) {
                $out[$year] += (float) $amount;
            }
        }

        return $out;
    }

    private function add(array $lists, array $years): array
    {
        $out = array_fill_keys(array_column($years, 'index'), 0.0);
        foreach ($lists as $list) {
            foreach ($list as $year => $amount) {
                $out[$year] = ($out[$year] ?? 0.0) + (float) $amount;
            }
        }

        return $out;
    }
}
