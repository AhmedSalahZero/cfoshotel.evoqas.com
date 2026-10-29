<?php

namespace App\Services\Statements;

use App\Calculations\CalculateFixedLoanAtEndService;
use App\Calculations\CalculateProfitsEquationsService;
use App\Calculations\ProjectsUnderProgress;
use App\Models\HospitalitySector;
use App\Services\Revenue\RevenueFiguresService;
use App\Support\HArr;
use App\Support\StudyCache;
use Illuminate\Support\Arr;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — ReportItems (the lines the statements and the reports are made from)
//  Location: app/Services/Statements/ReportItems.php
//
//  The previous application worked these lines out inside its big controller (onlyMonthlyDashboardItems and formatDashboardReportItems):
//  revenue, direct and undistributed expenses, gross profit, EBITDA, depreciation, loan interest, EBIT, EBT, corporate taxes, net profit,
//  and the fixed assets the depreciation and the property taxes and insurance come from. They are carried over here line for line,
//  once, because the statements (step 11) and the income statement and cash reports (step 12) all read the same lines.
//
//  context()      everything that does not depend on the interval (the calculation, the loans, the monthly lines) — kept for the request
//  forInterval()  the lines for monthly, quarterly, semi-annually or annually (only the incentive fee and the taxes differ)
// ══════════════════════════════════════════════════════════════════

class ReportItems
{
    /** relation of the study that holds the rows of each revenue model (same list as the previous application) */
    private const RELATIONS = [
        'SalesChannel' => 'salesChannels',
        'Room' => 'rooms',
        'Food' => 'foods',
        'Meeting' => 'meetings',
        'Casino' => 'casinos',
        'Other' => 'others',
    ];

    /** @var array<int, array|null> */
    private array $contexts = [];

    public function __construct(private readonly RevenueFiguresService $figures) {}

    /** The calculation of the study and the monthly lines, or null when the study cannot be calculated yet. */
    public function context(HospitalitySector $study): ?array
    {
        if (array_key_exists($study->id, $this->contexts)) {
            return $this->contexts[$study->id];
        }

        $dashboard = $this->figures->result($study);
        if ($dashboard === null) {
            return $this->contexts[$study->id] = null;
        }

        $study->bindDateTables();

        // the loan schedules and the monthly lines take as long as the calculation itself: kept (without the study object) until study data is written
        $parts = StudyCache::remember($study->id, 'report-context', function () use ($study, $dashboard) {
            $datesAsStringAndIndex = $study->getDatesAsStringAndIndex();
            $dateIndexWithDate = app('dateIndexWithDate');
            $dateWithDateIndex = app('dateWithDateIndex');

            $fixedAssetsLoan = (new CalculateFixedLoanAtEndService($study))->calculateFixedAssetsLoans($study, $datesAsStringAndIndex, $dateIndexWithDate, $dateWithDateIndex);

            $context = [
                'study' => $study,
                'dashboard' => $dashboard,
                'loans' => $fixedAssetsLoan,
                'datesAsStringAndIndex' => $datesAsStringAndIndex,
                'dateIndexWithDate' => $dateIndexWithDate,
                'dateWithDateIndex' => $dateWithDateIndex,
                'datesIndexWithYearIndex' => app('datesIndexWithYearIndex'),
                'yearIndexWithYear' => app('yearIndexWithYear'),
                'dateWithMonthNumber' => app('dateWithMonthNumber'),
                'operationStart' => (int) $study->getOperationStartDateAsIndex(),
            ];
            $context['monthly'] = $this->monthlyLines($context);

            return array_diff_key($context, ['study' => 1, 'dashboard' => 1]);
        });

        $context = $parts + ['study' => $study, 'dashboard' => $dashboard];

        return $this->contexts[$study->id] = $context;
    }

    /** The lines of one interval (the previous application's formatDashboardReportItems). */
    public function forInterval(array $c, string $intervalName = 'monthly'): array
    {
        /** @var HospitalitySector $study */
        $study = $c['study'];
        $m = $c['monthly'];
        $profit = $m['calculateProfitsEquationsService'];

        $incentive = $study->calculateIncentiveManagementFeesAmounts($m['ebitda']['values'], $intervalName, $c['datesIndexWithYearIndex'], $c['dateIndexWithDate'], $c['dateWithDateIndex']);
        $ebit = $profit->__calculateEBIT($m['ebitda']['values'] ?? [], $m['propertyMonthlyDepreciation'], $incentive, $m['totalHotelRevenue']);
        $ebt = $profit->__calculateEBT($ebit['values'] ?? [], $m['totalLoanInterest'], $m['totalHotelRevenue']);
        $corporateTaxes = $study->calculateCorporateTaxes($ebt['values'], $intervalName, $c['dateIndexWithDate'], $c['dateWithDateIndex']);
        $netProfit = $profit->__calculateNetProfit($ebt['values'] ?? [], $corporateTaxes, $m['totalHotelRevenue']);
        $totalPropertyTaxesAndInsurance = sumTwoArray($m['propertyTaxes'], $m['propertyInsurance']);

        return [
            'totalOfFFEItemForFFE' => $m['totalOfFFEItemForFFE'],
            'propertyAssetsForBuilding' => $m['propertyAssetsForBuilding'],
            'propertyAssetsForFFE' => $m['propertyAssetsForFFE'],
            'projectUnderProgressConstruction' => $m['projectUnderProgressConstruction'],
            'projectUnderProgressFFE' => $m['projectUnderProgressFFE'],
            'ffeAssetItems' => $m['ffeAssetItems'],
            'hotelRevenue' => $m['hotelRevenue'],
            'directExpenses' => $m['directExpenses'],
            'DepartmentsGrossProfit' => $m['departmentsGrossProfit'],
            'undistributedOperatingExpenses' => $m['undistributedOperatingExpenses'],
            'other_deductions' => [
                'other deductions' => arrayMergeTwoDimArray($m['totalOtherDeduction'], [
                    'subItems' => [
                        'Base Management Fees' => $m['baseManagementFees'],
                        'Property Taxes & Insurance Expenses' => $totalPropertyTaxesAndInsurance,
                    ],
                ]),
            ],
            'EBITDA' => ['Earnings Before Interest Taxes Depreciation & Amortization [ EBITDA ]' => $m['ebitda']['values'] ?? []],
            'incentive_management_fees' => ['Incentive Management Fees' => $incentive],
            'depreciation' => ['Depreciation & Amortization Expenses' => $m['propertyMonthlyDepreciation']],
            'EBIT' => ['Earnings Before Interest Taxes [ EBIT ]' => $ebit['values'] ?? []],
            'Interest Expenses' => [
                'Loan Interest Expenses' => arrayMergeTwoDimArray($m['totalLoanInterest'], [
                    'subItems' => [
                        'Property Loan Interest Expenses' => $m['propertyLoanInterestAmounts'],
                        'Land Loan Interest Expenses' => $m['landLoanInterestAmounts'],
                        'Construction Loan Interest Expenses' => $m['hardConstructionLoanInterestAmountsForIncomeStatement'],
                        'FF&E Loan Interest Expenses' => $m['ffeLoanInterestAmountsForIncomeStatement'],
                    ],
                ]),
            ],
            'EBT' => ['Earnings Before Taxes [ EBT ]' => $ebt['values'] ?? []],
            'taxes' => ['Corporate Taxes' => $corporateTaxes],
            'net_profit' => ['Net Profit' => $netProfit['values'] ?? []],
        ];
    }

    /** The months of the operation as the statements of taxes and fees want them: [date string => date index]. */
    public function operationDatesAsIndexes(array $c): array
    {
        $study = $c['study'];
        $operationMonths = $study->getOperationDurationPerMonth($c['datesAsStringAndIndex'], $c['datesIndexWithYearIndex'], $c['yearIndexWithYear'], $c['dateIndexWithDate'], $c['dateWithMonthNumber']);

        return $study->convertArrayOfStringDatesToStringDatesAndDateIndex(array_flip($operationMonths), $c['dateIndexWithDate'], $c['dateWithDateIndex']);
    }

    /** The dates of the months of the study (the previous application's getOnlyDatesOfActiveStudy). */
    public function studyDates(array $c): array
    {
        $study = $c['study'];
        $perYear = $study->getStudyDurationPerYear($c['datesAsStringAndIndex'], $c['datesIndexWithYearIndex'], $c['yearIndexWithYear'], $c['dateIndexWithDate'], $c['dateWithMonthNumber'], true, true, false);

        return $study->getOnlyDatesOfActiveStudy($perYear, $c['dateIndexWithDate']);
    }

    /** The dates of the months of the operation (the previous application's getOnlyDatesOfActiveOperation). */
    public function operationDates(array $c): array
    {
        $study = $c['study'];
        $perYear = $study->getOperationDurationPerYear($c['datesAsStringAndIndex'], $c['datesIndexWithYearIndex'], $c['yearIndexWithYear'], $c['dateIndexWithDate'], $c['dateWithMonthNumber']);

        return $study->getOnlyDatesOfActiveOperation($perYear, $c['dateIndexWithDate']);
    }

    /** The previous application's findTotalOfFFEFixedAssets: the FF&E items added together, date by date. */
    public function totalOfFfeAssets(array $ffeAsset, array $studyDates): array
    {
        $total = [];
        $initialTotalGross = array_column($ffeAsset, 'initial_total_gross');
        $finalTotalGross = array_column($ffeAsset, 'final_total_gross');
        $finalTotalAccumulated = array_column($ffeAsset, 'accumulated_depreciation');
        $finalTotalOfEndBalance = array_column($ffeAsset, 'end_balance');
        $finalTotalOfTotalDepreciation = array_column($ffeAsset, 'total_monthly_depreciation');
        $finalTotalOfReplacementCost = array_column($ffeAsset, 'replacement_cost');
        $count = count($finalTotalGross);

        foreach ($studyDates as $dateAsIndex) {
            $gross = $accumulated = $endBalance = $initial = $depreciation = $replacement = 0;
            for ($i = 0; $i < $count; $i++) {
                $gross += $finalTotalGross[$i][$dateAsIndex] ?? 0;
                $initial += $initialTotalGross[$i][$dateAsIndex] ?? 0;
                $accumulated += $finalTotalAccumulated[$i][$dateAsIndex] ?? 0;
                $endBalance += $finalTotalOfEndBalance[$i][$dateAsIndex] ?? 0;
                $depreciation += $finalTotalOfTotalDepreciation[$i][$dateAsIndex] ?? 0;
                $replacement += $finalTotalOfReplacementCost[$i][$dateAsIndex] ?? 0;
            }
            $total['initial_total_gross'][$dateAsIndex] = $initial;
            $total['final_total_gross'][$dateAsIndex] = $gross;
            $total['accumulated_depreciation'][$dateAsIndex] = $accumulated;
            $total['end_balance'][$dateAsIndex] = $endBalance;
            $total['total_monthly_depreciation'][$dateAsIndex] = $depreciation;
            $total['replacement_cost'][$dateAsIndex] = $replacement;
        }

        return $total;
    }

    // ── the monthly lines (the previous application's onlyMonthlyDashboardItems) ──

    private function monthlyLines(array $c): array
    {
        /** @var HospitalitySector $study */
        $study = $c['study'];
        $dashboard = $c['dashboard'];
        $loans = $c['loans'];
        $operationStart = $c['operationStart'];
        $underProgress = new ProjectsUnderProgress;
        $studyDates = $this->studyDates($c);
        $studyEnd = $study->getStudyEndDateAsIndex();

        $ffeExecutionAndPayment = $dashboard['ffeExecutionAndPayment'] ?? [];
        $ffeWithdrawalInterest = $dashboard['ffeLoanWithdrawalInterest'] ?? [];
        $landLoanInterest = $loans['landLoanInterestAmounts'];
        $propertyLoanInterest = $loans['propertyLoanInterestAmounts'];
        $hardLoanInterest = $loans['hardConstructionLoanInterestAmounts'];
        $ffeLoanInterest = $loans['ffeLoanInterestAmounts'];

        $hardExecution = $dashboard['hardConstructionExecutionAndPayment'] ?? [];
        $softExecution = $dashboard['softConstructionExecutionAndPayment'] ?? [];
        $hardInterest = $dashboard['hardConstructionLoanInterestAmounts'] ?? [];
        $hardWithdrawalInterest = $dashboard['hardWithdrawalInterestAmount'] ?? [];

        $totalHotelRevenueSubItems = [];
        foreach ([
            'monthlyRevenuePerRoom' => ['Total Rooms Revenue', true, 'Room'],
            'fAndBFacilityRevenue' => ['Total F&B Revenues', false, 'Food'],
            'casinoFacilityRevenue' => ['Total Gaming Revenues', false, 'Casino'],
            'meetingFacilityRevenue' => ['Total Meeting Spaces Revenues', false, 'Meeting'],
            'otherRevenueFacilityRevenue' => ['Total Other Revenues', false, 'Other'],
        ] as $key => [$title, $hasTotalKey, $modelName]) {
            $totalHotelRevenueSubItems = array_merge($totalHotelRevenueSubItems, $this->formatReport($dashboard, $key, $title, $modelName, $study, $hasTotalKey));
        }

        $hotelRevenue = [];
        $hotelRevenue['Total Revenues'] = getTotalOfArraysOf2Depth($totalHotelRevenueSubItems);
        $hotelRevenue['Total Revenues']['subItems'] = $totalHotelRevenueSubItems;

        $directExpenses = $this->formatDirectExpense($dashboard['directExpenses']);
        $undistributed = $this->formatDirectExpense($dashboard['Undistributed Operating Expenses'] ?? [], 'Undistributed Operating Expenses');
        $departmentsGrossProfit = $this->grossProfit($hotelRevenue, $directExpenses);

        $profit = new CalculateProfitsEquationsService;
        $totalGrossProfit = removeKeyFromArray($departmentsGrossProfit['Departments Gross Profit'] ?? [], 'subItems');
        $totalUndistributed = removeKeyFromArray($undistributed['Undistributed Operating Expenses'] ?? [], 'subItems');

        $propertyAcquisition = $study->propertyAcquisition;
        $underProgressFfe = $underProgress->calculateForFFE($ffeExecutionAndPayment, $ffeLoanInterest, $ffeWithdrawalInterest, $study, $operationStart, $c['datesAsStringAndIndex'], $c['datesIndexWithYearIndex'], $c['yearIndexWithYear'], $c['dateIndexWithDate'], $c['dateWithMonthNumber']);
        $transferredFfe = array_key_last($underProgressFfe['transferred_date_and_vales'] ?? []);

        $ffe = $study->ffe;
        $ffeAssetItems = [];
        $totalOfFfe = [];
        if ($ffe && ! is_null($transferredFfe)) {
            $ffeAssetItems = $ffe->calculateFFEAssetsForFFE($transferredFfe, Arr::last($underProgressFfe['transferred_date_and_vales'] ?? [], null, 0), $studyDates, $studyEnd);
            $totalOfFfe = $this->totalOfFfeAssets($ffeAssetItems, $studyDates);
        }

        $assetsBuilding = [];
        $assetsFfe = [];
        if ($propertyAcquisition) {
            $assetsBuilding = $propertyAcquisition->calculatePropertyAssetsForBuilding($hardExecution, $softExecution, $hardInterest, $hardWithdrawalInterest, $operationStart, $c['datesAsStringAndIndex'], $c['datesIndexWithYearIndex'], $c['yearIndexWithYear'], $c['dateIndexWithDate'], $dashboard['propertyBuildingCapitalizedInterest'] ?? []);
            $assetsFfe = $propertyAcquisition->calculatePropertyAssetsForFFE($c['datesAsStringAndIndex'], $c['datesIndexWithYearIndex'], $c['yearIndexWithYear'], $c['dateIndexWithDate'], $dashboard['propertyFFECapitalizedInterest'] ?? []);
        }

        $depreciation = HArr::sumAtDates([
            $assetsBuilding['total_monthly_depreciation'] ?? [],
            $assetsFfe['total_monthly_depreciation'] ?? [],
            $totalOfFfe['total_monthly_depreciation'] ?? [],
            $dashboard['startUpAndPreOperationExpensesTotals']['total_monthly_depreciation'] ?? [],
        ], $studyDates);
        $depreciation = $study->removeDatesAfterDate($depreciation, $study->getStudyEndDateAsIndex());
        $depreciation = $study->removeDatesBeforeDateOneDim($depreciation, $study->getOperationStartDateAsIndex());

        $underProgressConstruction = $underProgress->calculateForConstruction($hardExecution, $softExecution, $hardInterest, $hardWithdrawalInterest, $study, $operationStart, $c['datesAsStringAndIndex'], $c['datesIndexWithYearIndex'], $c['yearIndexWithYear'], $c['dateIndexWithDate']);
        $transferredConstruction = array_key_last($underProgressConstruction['transferred_date_and_vales'] ?? []);

        $hardInterestForIncome = [];
        $ffeInterestForIncome = [];
        if (! is_null($transferredConstruction)) {
            $hardInterestForIncome = zeroValueForIndexesBeforeIndex($hardLoanInterest, $transferredConstruction);
        }
        if (! is_null($transferredFfe)) {
            $ffeInterestForIncome = zeroValueForIndexesBeforeIndex($ffeLoanInterest, $transferredFfe);
        }

        $totalLoanInterest = sumFourArray($propertyLoanInterest, $landLoanInterest, $hardLoanInterest, $ffeLoanInterest);
        $totalHotelRevenue = removeKeyFromArray($hotelRevenue['Total Revenues'] ?? [], 'subItems');
        $baseFees = $study->calculateBaseManagementFeesAmounts($totalHotelRevenue, $c['datesIndexWithYearIndex']);

        $taxes = $study->calculatePropertyTaxes($assetsBuilding);
        $propertyTaxes = $taxes['monthlyPropertyTaxesExpenses'] ?? [];
        $insurance = $study->calculatePropertyInsurance($studyDates, $assetsBuilding, $assetsFfe, $totalOfFfe);
        $propertyInsurance = $insurance['monthlyPropertyInsuranceExpenses'] ?? [];
        $totalOtherDeduction = sumTwoArray($baseFees, sumTwoArray($propertyTaxes, $propertyInsurance));
        $ebitda = $profit->__calculateEBITDA($totalGrossProfit, $totalUndistributed, $totalOtherDeduction, $totalHotelRevenue);

        return [
            'totalOfFFEItemForFFE' => $totalOfFfe,
            'ebitda' => $ebitda,
            'propertyMonthlyDepreciation' => $depreciation,
            'calculateProfitsEquationsService' => $profit,
            'totalLoanInterest' => $totalLoanInterest,
            'totalHotelRevenue' => $totalHotelRevenue,
            'projectUnderProgressConstruction' => $underProgressConstruction,
            'projectUnderProgressFFE' => $underProgressFfe,
            'ffeAssetItems' => $ffeAssetItems,
            'hotelRevenue' => $hotelRevenue,
            'directExpenses' => $directExpenses,
            'departmentsGrossProfit' => $departmentsGrossProfit,
            'undistributedOperatingExpenses' => $undistributed,
            'totalOtherDeduction' => $totalOtherDeduction,
            'baseManagementFees' => $baseFees,
            'propertyTaxes' => $propertyTaxes,
            'propertyInsurance' => $propertyInsurance,
            'propertyLoanInterestAmounts' => $propertyLoanInterest,
            'landLoanInterestAmounts' => $landLoanInterest,
            'hardConstructionLoanInterestAmountsForIncomeStatement' => $hardInterestForIncome,
            'ffeLoanInterestAmountsForIncomeStatement' => $ffeInterestForIncome,
            'propertyAssetsForBuilding' => $assetsBuilding,
            'propertyAssetsForFFE' => $assetsFfe,
            'withdrawalInterestOfHardConstruction' => $hardWithdrawalInterest,
            'propertyTaxesAndPayments' => $taxes,
            'propertyInsuranceAndPayments' => $insurance,
        ];
    }

    // ── small builders of the previous application's controller ──

    public function formatReport(array $dashboardItems, string $key, string $title, string $modelName, HospitalitySector $study, bool $withTotalKey): array
    {
        $subItems = [];
        $modelClass = '\\App\\Models\\'.$modelName;
        $exists = class_exists($modelClass);

        foreach ($dashboardItems[$key] ?? [] as $identifier => $values) {
            $item = $exists ? $study->{self::RELATIONS[$modelName]}->where($modelClass::getIdentifierColumnName(), '=', $identifier)->first() : null;
            if ($identifier != 'total' && $identifier != 'totalOfEachYear') {
                $subItems[$item ? $item->getName() : $identifier] = $withTotalKey ? ($values['total'] ?? []) : $values;
            }
        }

        $formatted = [];
        $formatted[$title] = getTotalOfArraysOf2Depth($subItems);
        $formatted[$title]['subItems'] = $subItems;

        return $formatted;
    }

    private function formatDirectExpense(array $items, string $mainKey = 'Departmental Expenses'): array
    {
        $sub2 = [];
        $sub3 = [];
        $subItems = [];
        foreach ($items as $names => $sections) {
            foreach ($sections as $sectionName => $values) {
                foreach ($values as $identifier => $datesAndValues) {
                    if (is_numeric($identifier)) {
                        $subItems[$names][$sectionName][$identifier] = $datesAndValues;
                    }
                }
                $sub2[$names][$sectionName] = sumAllOfDates($subItems[$names][$sectionName] ?? []);
                $sub2[$names][$sectionName]['subItems'] = $subItems[$names][$sectionName] ?? [];
            }
            $sub3[$names] = sumAllOfDates($sub2[$names] ?? []);
            $sub3[$names]['subItems'] = $sub2[$names];
        }

        $result[$mainKey] = sumAllOfDates($sub3);
        $result[$mainKey]['subItems'] = $sub3;

        return $result;
    }

    private function grossProfit(array $revenues, array $expenses): array
    {
        $result = [];
        $pairs = [
            'Rooms Gross Profit' => ['Total Rooms Revenue', 'Rooms Direct Expenses'],
            'F&B Gross Profit' => ['Total F&B Revenues', 'Foods Direct Expenses'],
            'Gaming Gross Profit' => ['Total Gaming Revenues', 'Gaming Direct Expenses'],
            'Meeting Spaces Gross Profit' => ['Total Meeting Spaces Revenues', 'Meeting Direct Expenses'],
            'Other Revenues Gross Profit' => ['Total Other Revenues', 'Other Revenue Direct Expenses'],
        ];

        foreach ($revenues['Total Revenues'] ?? [] as $indexDate => $value) {
            if (is_numeric($indexDate)) {
                $result['Departments Gross Profit'][$indexDate] = $value - ($expenses['Departmental Expenses'][$indexDate] ?? 0);
            }
            if ($indexDate == 'subItems') {
                foreach ($pairs as $name => [$revenueKey, $expenseKey]) {
                    $result['Departments Gross Profit']['subItems'][$name] = subtractTwoArray(
                        $revenues['Total Revenues']['subItems'][$revenueKey] ?? [],
                        $expenses['Departmental Expenses']['subItems'][$expenseKey] ?? []
                    );
                }
            }
        }

        return $result;
    }
}
