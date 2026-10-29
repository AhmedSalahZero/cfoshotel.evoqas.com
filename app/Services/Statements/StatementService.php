<?php

namespace App\Services\Statements;

use App\Calculations\FixedAssetsPayableEndBalance;
use App\Calculations\PropertyInsurancePayableEndBalance;
use App\Calculations\PropertyTaxesPayableEndBalance;
use App\Models\HospitalitySector;
use App\Support\HArr;
use App\Support\LegacyCarbon;
use App\Support\LegacyMath;
use App\Support\Statements;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — StatementService (the tables of the statement screens)
//  Location: app/Services/Statements/StatementService.php
//
//  One class for the 16 statement screens of the previous application. The lines come from the calculation (and from ReportItems);
//  here they are only put into tables the way that application put them: for each interval (monthly, quarterly, semi-annually,
//  annually) the dates are added together by sumIntervalsIndexes, each line gets one cell per date, and every cell is shown with
//  numberFormat(value, 0). Nothing is worked out again here.
//
//  The answer of present():
//    calculated   false when the inputs of the study are not complete enough to be calculated (the screen then explains it)
//    tabs         [{name, label}]  the intervals the screen can show
//    intervals    [{name, columns:[{label, year}], tables:[{key, title, rows:[{key, values, text}]}]}]
//    schedule     for a loan: {columns, rows, totals} instead of intervals
//  A table title or a row key is the English word the calculation uses; the screen translates the ones it knows.
// ══════════════════════════════════════════════════════════════════

class StatementService
{
    public const INTERVALS = ['monthly', 'quarterly', 'semi-annually', 'annually'];

    /** the key of the dashboard items each prepaid statement is read from */
    private const PREPAID = [
        'general' => 'prepaidExpenseStatementForGeneralForView',
        'marketing' => 'prepaidExpenseStatementForMarketingForView',
        'property' => 'prepaidExpenseStatementForPropertyForView',
        'energy' => 'prepaidExpenseStatementForEnergyForView',
    ];

    private const LOANS = [
        'property' => 'propertyLoanCalculations',
        'land' => 'landLoanCalculations',
        'hard-construction' => 'hardConstructionLoanCalculations',
        'ffe' => 'ffeLoanCalculations',
    ];

    public function __construct(private readonly ReportItems $items) {}

    public function present(HospitalitySector $study, string $family, string $type): array
    {
        $answer = [
            'family' => $family, 'type' => $type, 'types' => Statements::types($study, $family),
            'calculated' => false, 'kind' => 'periods', 'tabs' => [], 'intervals' => [],
        ];

        $c = $this->items->context($study);
        if ($c === null) {
            return $answer;
        }
        $answer['calculated'] = true;

        if ($family === 'loans') {
            return ['kind' => 'schedule'] + $this->schedule($c, $type) + $answer;
        }

        [$dates, $tables, $only] = match ($family) {
            'collection' => $this->collection($c, $type),
            'inventory' => $this->named($c, 'inventoryStatements', $type),
            'disposables' => $this->named($c, 'disposablePaymentStatements', $type),
            'prepaid' => $this->prepaid($c, $type),
            'fees' => $this->fees($c),
            'taxes' => $this->taxes($c, $type),
            'assets' => $this->assets($c, $type),
        };

        $answer['intervals'] = $this->intervals($c, $dates, $tables, $only);
        $answer['tabs'] = array_map(fn ($interval) => ['name' => $interval['name']], $answer['intervals']);

        return $answer;
    }

    // ── the families: each answers [the dates of the screen, the tables, the intervals it shows (null = all)] ──
    // a table is [key, title, closure(interval) => the lines of that interval]

    private function collection(array $c, string $type): array
    {
        $receivables = $c['dashboard']['collectionPoliciesAndReceivableEndBalances']['receivable_end_balance'] ?? [];
        if ($type === 'total') {
            $receivables['total']['total'] = sumSecondKeyInThreeDimArr([
                $receivables['rooms']['total'] ?? [], $receivables['foods']['total'] ?? [], $receivables['gaming']['total'] ?? [],
                $receivables['meetings']['total'] ?? [], $receivables['others']['total'] ?? [],
            ]);
        }
        $mine = $receivables[$type] ?? [];
        $tables = [];
        foreach (removeTotalTableIfFirstIsZero(array_keys($mine)) as $name) {
            $tables[] = [(string) $name, $this->words(convertZeroToTotalString($name)), fn ($interval) => $mine[$name]['intervalsReport'][$interval] ?? []];
        }

        return [$this->items->operationDates($c), $tables, null];
    }

    private function named(array $c, string $dashboardKey, string $type): array
    {
        $all = $c['dashboard'][$dashboardKey] ?? [];
        if ($type === 'total') {
            $all['total']['total'] = sumSecondKeyInThreeDimArr([$all['rooms']['total'] ?? [], $all['foods']['total'] ?? [], $all['gaming']['total'] ?? []]);
        }
        $mine = $all[$type] ?? [];
        $tables = [];
        foreach (array_keys($mine) as $name) {
            $tables[] = [(string) $name, $this->words($name), fn ($interval) => $mine[$name][$interval] ?? []];
        }

        return [$this->items->operationDates($c), $tables, null];
    }

    private function prepaid(array $c, string $type): array
    {
        if ($type === 'total') {
            $report['total'] = sumSecondKeyInThreeDimArr([
                $c['dashboard'][self::PREPAID['energy']]['total'] ?? [], $c['dashboard'][self::PREPAID['marketing']]['total'] ?? [],
                $c['dashboard'][self::PREPAID['property']]['total'] ?? [], $c['dashboard'][self::PREPAID['general']]['total'] ?? [],
            ]);
            $names = ['total'];
        } else {
            $report = $c['dashboard'][self::PREPAID[$type]] ?? [];
            $names = array_keys($report);
        }
        $tables = [];
        foreach ($names as $name) {
            $tables[] = [(string) $name, $this->words($name), fn ($interval) => $report[$name][$interval] ?? []];
        }

        return [$this->items->operationDates($c), $tables, null];
    }

    private function fees(array $c): array
    {
        $incentive = $this->items->forInterval($c, 'annually')['incentive_management_fees']['Incentive Management Fees'] ?? [];
        $statement = $c['study']->calculateManagementFeesStatement($this->items->operationDatesAsIndexes($c), $incentive, 'monthly', $c['dateIndexWithDate'], $c['dateWithDateIndex']);

        return [$this->items->operationDates($c), [['items', null, fn ($interval) => $statement[$interval] ?? []]], null];
    }

    private function taxes(array $c, string $type): array
    {
        $study = $c['study'];
        $m = $c['monthly'];
        $statement = [];

        if ($type === 'corporate') {
            $annual = $this->items->forInterval($c, 'annually')['taxes']['Corporate Taxes'] ?? [];
            $statement = $study->calculateCorporateTaxesStatement($this->items->operationDatesAsIndexes($c), $annual, 'monthly', $c['dateIndexWithDate'], $c['dateWithDateIndex']);
        } elseif ($study->propertyAcquisition && $type === 'property') {
            $statement = (new PropertyTaxesPayableEndBalance)->getPropertyTaxesPayableEndBalance($m['propertyTaxesAndPayments']['monthlyPropertyTaxesExpenses'] ?? [], $m['propertyTaxesAndPayments']['payments'] ?? [], $c['dateIndexWithDate'], $study);
        } elseif ($study->propertyAcquisition && $type === 'insurance') {
            $statement = (new PropertyInsurancePayableEndBalance)->getPropertyInsurancePayableEndBalance($m['propertyInsuranceAndPayments']['monthlyPropertyInsuranceExpenses'] ?? [], $m['propertyInsuranceAndPayments']['payments'] ?? [], $c['dateIndexWithDate'], $c['dateWithDateIndex'], $study);
        }

        return [$this->items->operationDates($c), [['items', null, fn ($interval) => $statement[$interval] ?? []]], null];
    }

    private function assets(array $c, string $type): array
    {
        $study = $c['study'];
        $dash = $c['dashboard'];
        $studyDates = $this->items->studyDates($c);

        if ($type === 'assets') {
            $m = $c['monthly'];
            $all = array_merge(['Property Building' => $m['propertyAssetsForBuilding'], 'Property FFE' => $m['propertyAssetsForFFE']], $m['ffeAssetItems']);
            $tables = [];
            foreach ($all as $name => $lines) {
                $tables[] = [(string) $name, (string) $name, fn () => $lines];
            }

            return [$studyDates, $tables, ['monthly']];
        }

        $cashOut = $dash['CashOutReport']['Acquisition And Development Payment'] ?? [];

        if ($type === 'payable') {
            $hard = $study->convertArrayOfIndexKeysToIndexAsDateStringWithItsOriginalValue($dash['hardConstructionExecutionAndPayment'] ?? [], $c['datesAsStringAndIndex']);
            $hard = HArr::sumAtDates([$hard, []], $studyDates);
            $payable = (new FixedAssetsPayableEndBalance)->calculateEndBalance($hard, $cashOut['Hard Construction Payment'] ?? [], $c['dateIndexWithDate'], $study, true);

            return [$studyDates, [['items', null, fn ($interval) => $payable[$interval] ?? []]], null];
        }

        // suppliers
        $propertyCost = [];
        $landCost = [];
        if ($study->propertyAcquisition) {
            $propertyCost = [$study->propertyAcquisition->getPropertyPurchaseDateFormatted() => $dash['totalPropertyPurchaseCost'] ?? 0];
        }
        if ($study->acquisition) {
            $landCost = [$study->acquisition->getLandPurchaseDateFormatted() => $dash['totalLandPurchaseCost'] ?? 0];
        }
        $ffe = $dash['ffeExecutionAndPayment'] ?? [];
        $soft = $dash['softConstructionExecutionAndPayment'] ?? [];
        $hard = $dash['hardConstructionExecutionAndPayment'] ?? [];
        $payments = [
            'property' => $cashOut['Property Payments'] ?? [], 'land' => $cashOut['Land Payments'] ?? [], 'hard' => $cashOut['Hard Construction Payment'] ?? [],
            'soft' => $cashOut['Soft Construction Payment'] ?? [], 'ffe' => $cashOut['FFE Payment'] ?? [],
        ];

        $statement = fn (array $purchases, array $paid) => $study->formatFixedAssetsSuppliersForView($studyDates, $purchases, $paid, $c['dateIndexWithDate'], $c['dateWithDateIndex']);
        $sets = [
            'Total Fixed Assets Supplier Statement' => $statement(sumFiveArrays($propertyCost, $ffe, $soft, $hard, $landCost), sumFiveArrays($payments['property'], $payments['land'], $payments['hard'], $payments['soft'], $payments['ffe'])),
            'Property Supplier Statement' => $study->propertyAcquisition ? $statement($propertyCost, $payments['property']) : [],
            'Land Supplier Statement' => $study->acquisition ? $statement($landCost, $payments['land']) : [],
            'Hard Construction Supplier Statement' => $statement($hard, $payments['hard']),
            'Soft Construction Supplier Statement' => $statement($soft, $payments['soft']),
            'FF&E Supplier Statement' => $statement($ffe, $payments['ffe']),
        ];
        $tables = [];
        foreach ($sets as $title => $set) {
            $tables[] = [$title, $title, fn ($interval) => $set[$interval] ?? []];
        }

        return [$studyDates, $tables, null];
    }

    /** The repayment schedule of one loan: a line per payment and the totals. */
    private function schedule(array $c, string $type): array
    {
        $result = $c['loans'][self::LOANS[$type]] ?? [];
        $dates = array_keys($result['beginning'] ?? []);
        $rows = [];
        foreach ($dates as $i => $date) {
            $rows[] = [
                'no' => $i + 1, 'date' => (string) $date,
                'beginning' => $this->cell($result['beginning'][$date] ?? 0), 'schedule' => $this->cell($result['schedulePayment'][$date] ?? 0),
                'interest' => $this->cell($result['interestAmount'][$date] ?? 0), 'principle' => $this->cell($result['principleAmount'][$date] ?? 0),
                'end' => $this->cell($result['endBalance'][$date] ?? 0),
            ];
        }
        $totals = $rows ? [
            'schedule' => $this->cell($result['totals']['totalSchedulePayment'] ?? 0),
            'interest' => $this->cell($result['totals']['totalInterestAmount'] ?? 0),
            'principle' => $this->cell($result['totals']['totalPrincipleAmount'] ?? 0),
        ] : null;

        return ['rows' => $rows, 'totals' => $totals];
    }

    // ── the way the previous application put dates and lines into a table ──

    /** @return list<array{name: string, dates: array<string, mixed>}> StatementTables::intervals */
    private function intervals(array $c, array $originalDates, array $tables, ?array $only): array
    {
        $study = $c['study'];
        $out = [];
        foreach ($only ?? self::INTERVALS as $name) {
            $dates = sumIntervalsIndexes($originalDates, $name, $study->financialYearStartMonth(), $c['dateIndexWithDate']);
            $dates = $study->convertArrayOfStringDatesToStringDatesAndDateIndex($dates, $c['dateIndexWithDate'], $c['dateWithDateIndex']);

            $out[] = [
                'name' => $name,
                'columns' => array_map(fn ($date) => ['label' => formatDateForView($date), 'year' => (int) LegacyCarbon::make($date)->format('Y')], array_keys($dates)),
                'tables' => array_map(fn (array $table) => [
                    'key' => $table[0], 'title' => $table[1], 'rows' => $this->rows($table[2]($name), $dates),
                ], $tables),
            ];
        }

        return $out;
    }

    /** StatementTables::rows: the name of the line, and one cell per date. */
    private function rows(iterable $lines, array $dates): array
    {
        $rows = [];
        foreach ($lines as $name => $datesAndValues) {
            $values = [];
            $text = [];
            foreach ($dates as $dateAsString => $dateAsIndex) {
                $value = getValueFromArrayStringAndIndex(is_array($datesAndValues) ? $datesAndValues : [], $dateAsString, $dateAsIndex, 0);
                $values[] = round((float) $value, 2);
                $text[] = LegacyMath::numberFormat($value, 0);
            }
            $rows[] = ['key' => (string) $name, 'values' => $values, 'text' => $text];
        }

        return $rows;
    }

    private function cell(mixed $value): string
    {
        return LegacyMath::numberFormat($value);
    }

    /** the word of a line as the previous application wrote it: underscores and hyphens read as spaces */
    private function words(mixed $name): string
    {
        return str_replace(['_', '-'], ' ', (string) $name);
    }
}
