<?php

namespace App\Services\Reports;

use App\Calculations\CalculateIrrService;
use App\Calculations\CalculatePaybackPeriodService;
use App\Calculations\RatioAnalysisService;
use App\Models\HospitalitySector;
use App\Support\HArr;
use App\Support\LegacyMath;
use Illuminate\Support\Arr;
use MathPHP\Finance;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — StudyAnalysis (the ratios and the dashboard of a study)
//  Location: app/Services/Reports/StudyAnalysis.php
//
//  Carried over from viewRatioAnalysisReport and viewStudyDashboard of the previous application's controller.
//  The IRR is the MathPHP one (owner decision of step 06), the same as everywhere else in the application.
// ══════════════════════════════════════════════════════════════════

class StudyAnalysis
{
    /** @var array<int, array|null> */
    private array $dashboards = [];

    public function __construct(private readonly FinancialReports $reports) {}

    /**
     * viewRatioAnalysisReport.
     *
     * @return array{ratio_analysis_report: array, dates: array}|null
     */
    public function ratios(HospitalitySector $study): ?array
    {
        $sheet = $this->reports->balanceSheet($study);
        if ($sheet === null) {
            return null;
        }
        $c = $sheet['cashInOutReport']['c'];
        $dateIndexWithDate = $c['dateIndexWithDate'];
        $start = $study->financialYearStartMonth();
        $studyDates = $sheet['study_dates'];
        $annuallyReport = $sheet['cashInOutReport']['finalReportItems']['annually'] ?? [];
        $items = $sheet['balance_sheet_items'];

        $annual = fn (array $values) => sumIntervalsIndexes($values, 'annually', $start, $dateIndexWithDate);
        $endBalance = fn (array $values) => intervalsEndBalance($values, 'annually', $start, $dateIndexWithDate);
        $disposable = fn (string $department) => $annual(removeKeyFromArray($annuallyReport['directExpenses']['Departmental Expenses']['subItems'][$department]['subItems']['Disposable Expense'] ?? [], 'subItems'));

        $roomsDisposableCost = $disposable('Rooms Direct Expenses');
        $foodsDisposableCost = $disposable('Foods Direct Expenses');
        $gamingDisposableCost = $disposable('Gaming Direct Expenses');
        $disposableCost = HArr::sumAtDates([$roomsDisposableCost, $foodsDisposableCost, $gamingDisposableCost], getDateFromThreeArrays($roomsDisposableCost, $foodsDisposableCost, $gamingDisposableCost));

        $salesRevenues = $annual(removeKeyFromArray($annuallyReport['hotelRevenue']['Total Revenues'] ?? [], 'subItems'));
        $EBT = $annual($annuallyReport['EBT']['Earnings Before Taxes [ EBT ]'] ?? []);
        $corporateTaxes = $annuallyReport['taxes']['Corporate Taxes'] ?? [];
        $netProfit = HArr::subtractAtDates([$EBT, $corporateTaxes], getDateFromTwoArrays($EBT, $corporateTaxes));

        $EBIT = $study->convertArrayOfIndexKeysToDateStringAsIndexWithItsOriginalValue($annuallyReport['EBIT']['Earnings Before Interest Taxes [ EBIT ]'] ?? [], $dateIndexWithDate);
        $EBIT = $annual($EBIT);
        $EBITDA = $study->convertArrayOfIndexKeysToDateStringAsIndexWithItsOriginalValue($annuallyReport['EBITDA']['Earnings Before Interest Taxes Depreciation & Amortization [ EBITDA ]'] ?? [], $dateIndexWithDate);
        $EBITDA = $annual($EBITDA);
        $grossProfit = $annual(removeKeyFromArray($annuallyReport['DepartmentsGrossProfit']['Departments Gross Profit'] ?? [], 'subItems'));

        $totalInvestment = $endBalance($items['Total Investment']['Total Investment'] ?? []);
        $totalCurrentLiabilities = $endBalance($items['Total Current Liabilities']['Total Current Liabilities'] ?? []);
        $totalLongLiabilities = $endBalance(removeKeyFromArray($items['Long Term Loans']['Long Term Loans'] ?? [], 'subItems'));
        $totalLiabilities = HArr::sumAtDates([$totalCurrentLiabilities, $totalLongLiabilities], getDateFromTwoArrays($totalCurrentLiabilities, $totalLongLiabilities));
        $totalCurrentAssets = $endBalance($items['Total Current Assets']['Total Current Assets'] ?? []);
        $totalAssets = $endBalance($items['Total Assets']['Total Assets'] ?? []);
        $ownersEquity = $endBalance(removeKeyFromArray($items['Owners Equity']['Owners Equity'] ?? [], 'subItems'));
        $cashAndBanks = $endBalance($items['Total Cash & Banks']['Total Cash & Banks'] ?? []);
        $customersReceivablesAndChecks = $endBalance(removeKeyFromArray($items['Customers\' Receivables']['Customers\' Receivables'] ?? [], 'subItems'));
        $workingCapital = $endBalance($items['Working Capital']['Working Capital'] ?? []);
        $supplierPayablesAndChecks = $endBalance(removeKeyFromArray($items['Suppliers\' Payables']['Suppliers\' Payables'] ?? [], 'subItems'));
        $inventory = $endBalance(removeKeyFromArray($items['Disposables Inventory']['Disposables Inventory'] ?? [], 'subItems'));

        $dates = sumIntervalsAndPreserveOriginalDay($studyDates, 'annually', $start, $dateIndexWithDate);
        $dates = $study->convertArrayOfStringDatesToStringDatesAndDateIndex($dates, $dateIndexWithDate, $c['dateWithDateIndex']);

        $report = (new RatioAnalysisService)->__calculate(
            $dates, $salesRevenues, $EBIT, $EBITDA, $grossProfit, $totalInvestment, $totalLiabilities, $totalAssets,
            $ownersEquity, $cashAndBanks, $customersReceivablesAndChecks,
            $totalCurrentAssets, $totalCurrentLiabilities, $netProfit, $workingCapital, $supplierPayablesAndChecks, $inventory,
            $disposableCost
        );

        return ['ratio_analysis_report' => $report, 'dates' => $dates];
    }

    /**
     * viewStudyDashboard: the cards and the data of the charts (what the old page drew with its charts).
     *
     * @return array<string, mixed>|null
     */
    public function dashboard(HospitalitySector $study, string $revenueStreamType = 'Total Revenues', string $grossProfitType = 'Departments Gross Profit'): ?array
    {
        $key = $study->id.'|'.$revenueStreamType.'|'.$grossProfitType;
        if (array_key_exists($key, $this->dashboards)) {
            return $this->dashboards[$key];
        }
        $cash = $this->reports->cashInOut($study);
        if ($cash === null) {
            return $this->dashboards[$key] = null;
        }
        $c = $cash['c'];
        $start = $study->financialYearStartMonth();
        $dateIndexWithDate = $c['dateIndexWithDate'];
        $dateWithDateIndex = $c['dateWithDateIndex'];
        $workingCapitalInjection = $cash['working_capital_injection'];
        $dashboardItems = $cash['dashboardItems'];
        $yearsWithItsMonths = $study->getOperationDurationPerYear($c['datesAsStringAndIndex'], $c['datesIndexWithYearIndex'], $c['yearIndexWithYear'], $c['dateIndexWithDate'], $c['dateWithMonthNumber']);

        $reportItems = $cash['finalReportItems']['monthly'];
        $reportItemsAnnually = $cash['finalReportItems']['annually'];
        $annual = fn (array $values) => sumIntervalsIndexes($values, 'annually', $start, $dateIndexWithDate);

        $revenueStreamValue = Arr::get($reportItems, 'hotelRevenue.'.$revenueStreamType, []);
        $totalRevenuesStreamPerYear = [];
        foreach ($revenueStreamValue['subItems'] ?? [] as $name => $values) {
            if (isset($values['subItems'])) {
                unset($values['subItems']);
            }
            $totalRevenuesStreamPerYear[$name] = $annual($values);
        }
        $grossProfitValue = Arr::get($reportItems, 'DepartmentsGrossProfit.'.$grossProfitType, []);
        $revenueStreamChartData = $annual(removeKeyFromArray($revenueStreamValue, 'subItems'));
        $revenueStreamAccumulatedData = formatAccumulatedDataForChart($revenueStreamChartData);
        $revenueStreamChart = formatDataForChart($revenueStreamChartData);
        $hotelRevenuesBreakdownChart = formatStackedChart(Arr::get($reportItems, 'hotelRevenue.Total Revenues.subItems', []), $c['datesIndexWithYearIndex'], $c['yearIndexWithYear']);

        $adrChart = formatDataForChart($this->ratio($dashboardItems['totalRoomRevenueOfEachYear'] ?? [], $dashboardItems['totalRoomsSoldNightsPerYear'] ?? []), true, true, false, [], $yearsWithItsMonths);
        $revparChart = formatDataForChart($this->ratio($dashboardItems['totalRoomRevenueOfEachYear'] ?? [], $dashboardItems['totalMaxAvailableNightsPerYear'] ?? []), true, false, false, [], $yearsWithItsMonths);
        $occupancyChart = formatDataForChart($this->ratio($dashboardItems['totalRoomsSoldNightsPerYear'] ?? [], $dashboardItems['totalMaxPracticalAvailableNightsPerYear'] ?? []), true, false, false, [], $yearsWithItsMonths);

        $grossProfitDepartmentChartData = $annual(removeKeyFromArray($grossProfitValue, 'subItems'));
        $grossAccumulated = formatAccumulatedDataForChart($grossProfitDepartmentChartData);
        $grossProfitDepartmentChart = formatDataForChart($grossProfitDepartmentChartData, false, false, true, $revenueStreamChartData);

        $totalRevenueAnnually = $annual(removeKeyFromArray(Arr::get($reportItems, 'hotelRevenue.Total Revenues', []), 'subItems'));
        $ebitdaChartData = $annual($reportItemsAnnually['EBITDA']['Earnings Before Interest Taxes Depreciation & Amortization [ EBITDA ]'] ?? []);
        $ebitChartData = $annual($reportItemsAnnually['EBIT']['Earnings Before Interest Taxes [ EBIT ]'] ?? []);
        $ebtChartData = $annual($reportItemsAnnually['EBT']['Earnings Before Taxes [ EBT ]'] ?? []);
        $netProfitChartData = $annual($reportItemsAnnually['net_profit']['Net Profit'] ?? []);
        $ebitdaChart = formatDataForChart($ebitdaChartData, false, false, true, $totalRevenueAnnually);
        $ebitChart = formatDataForChart($ebitChartData, false, false, true, $totalRevenueAnnually);
        $ebtChart = formatDataForChart($ebtChartData, false, false, true, $totalRevenueAnnually);
        $netProfitChart = formatDataForChart($netProfitChartData, false, false, true, $totalRevenueAnnually);

        // the cash report is formatted again here, after the working capital is in (as the previous application did)
        $dashboardItems['CashInReport']['Equity Injection']['Working Capital'] = $workingCapitalInjection;
        $cashReport = $this->reports->formatCashInOut($dashboardItems, $study);
        $totalEquityInjectionWithoutWorkingCapital = $dashboardItems['CashInReport']['Equity Injection'] ?? [];
        $totalPreOperatingAndWorkingCapital = array_sum($totalEquityInjectionWithoutWorkingCapital['Working Capital'] ?? []);
        unset($totalEquityInjectionWithoutWorkingCapital['Working Capital']);
        $totalLoanWithdrawal = sumTwoDimArr($dashboardItems['CashInReport']['Loan Withdrawal']);
        $totalFixedAssetInvestment = $totalLoanWithdrawal + sumTwoDimArr($totalEquityInjectionWithoutWorkingCapital);
        $totalEquityInjection = sumTwoDimArr($dashboardItems['CashInReport']['Equity Injection'] ?? []);

        $propertyLoanAmount = $dashboardItems['propertyLoanAmount'];
        $landLoanAmount = $dashboardItems['landLoanAmount'];
        $hardLoanAmount = $dashboardItems['hardLoanAmount'];
        $ffeLoanAmount = $dashboardItems['ffeLoanAmount'];
        $propertyLoanPricing = $dashboardItems['propertyLoanPricing'] / 100;
        $landLoanPricing = $dashboardItems['landLoanPricing'] / 100;
        $hardLoanPricing = $dashboardItems['hardLoanPricing'] / 100;
        $ffeLoanPricing = $dashboardItems['ffeLoanPricing'] / 100;
        $totalLoans = $propertyLoanAmount + $landLoanAmount + $hardLoanAmount + $ffeLoanAmount;
        $totalEndBalanceAtStudyEndDate = $dashboardItems['propertyLoanEndBalanceAtStudyEndBalance'] + $dashboardItems['landLoanEndBalanceAtStudyEndDate'] + $dashboardItems['hardConstructionLoanEndBalanceAtStudyEndDate'] + $dashboardItems['ffeLoanEndBalanceAtStudyEndDate'];
        $totalRequiredInvestment = $totalEquityInjection + $totalLoans;

        $costOfEquity = $study->getInvestmentReturnRate();
        $corporateTaxesRate = $study->getCorporateTaxesRate() / 100;
        $costOfDebt = ($totalLoans ? ($propertyLoanAmount / $totalLoans * $propertyLoanPricing) + ($landLoanAmount / $totalLoans * $landLoanPricing) + ($hardLoanAmount / $totalLoans * $hardLoanPricing) + ($ffeLoanAmount / $totalLoans * $ffeLoanPricing) : 0) * (1 - $corporateTaxesRate);
        $wacc = $totalRequiredInvestment ? ($totalEquityInjection / $totalRequiredInvestment * ($costOfEquity / 100)) + ($totalLoans / $totalRequiredInvestment * $costOfDebt) : 0;

        // investment feasibility for the equity
        $freeCashFlowForEquity = $study->calculateFreeCashFlowForEquity($cashReport);
        $freeCashFlowForEquityAnnually = $annual($freeCashFlowForEquity);
        $freeCashFlowForEquityAnnually = $study->removeDatesAfterDate($freeCashFlowForEquityAnnually, $study->getStudyEndDateAsIndex());
        $terminalValue = Arr::last($freeCashFlowForEquityAnnually, null, 0);
        $perpetualGrowthRate = $study->getPerpetualGrowthRate() / 100;
        $costOfEquityPercentage = $costOfEquity / 100;
        $costOfEquityMinusPerpetual = ($costOfEquityPercentage - $perpetualGrowthRate) <= 0 ? 1 : ($costOfEquityPercentage - $perpetualGrowthRate);
        $terminalValue = ($terminalValue * (1 + $perpetualGrowthRate)) / $costOfEquityMinusPerpetual;
        $terminalValueMinusLoanBalance = $terminalValue - $totalEndBalanceAtStudyEndDate;
        $lastKey = array_key_last($freeCashFlowForEquityAnnually);
        $equityWithTerminal = $freeCashFlowForEquityAnnually;
        if ($lastKey) {
            $equityWithTerminal[$lastKey] = $equityWithTerminal[$lastKey] + $terminalValueMinusLoanBalance;
        }
        $irrService = new CalculateIrrService;
        $netPresentValueForEquity = $irrService->calculateNetPresentValue($equityWithTerminal, $costOfEquity);
        $irrForEquity = Finance::irr(array_values($equityWithTerminal));

        // investment feasibility for the project
        $freeCashFlowForFirm = $study->calculateFreeCashFlowForFirm($cashReport);
        $freeCashFlowForFirmAnnually = $annual($freeCashFlowForFirm);
        $freeCashFlowForFirmAnnually = $study->removeDatesAfterDate($freeCashFlowForFirmAnnually, $study->getStudyEndDateAsIndex());
        $terminalValueForFirm = Arr::last($freeCashFlowForFirmAnnually, null, 0);
        $waccMinusPerpetual = ($wacc - $perpetualGrowthRate) <= 0 ? 1 : ($wacc - $perpetualGrowthRate);
        $terminalValueForFirm = ($terminalValueForFirm * (1 + $perpetualGrowthRate)) / $waccMinusPerpetual;
        $lastKeyFirm = array_key_last($freeCashFlowForFirmAnnually);
        $firmWithTerminal = $freeCashFlowForFirmAnnually;
        if ($lastKeyFirm) {
            $firmWithTerminal[$lastKeyFirm] = $firmWithTerminal[$lastKeyFirm] + $terminalValueForFirm;
        }
        $waccPercentage = $wacc * 100;
        $netPresentValueForFirm = $irrService->calculateNetPresentValue($firmWithTerminal, $waccPercentage);
        $irrForFirm = Finance::irr(array_values($firmWithTerminal));

        $accumulated = HArr::accumulateArray($freeCashFlowForEquity);
        $accumulated = $study->convertArrayOfIndexKeysToDateStringAsIndexWithItsOriginalValue($accumulated, $dateIndexWithDate);
        $payback = (new CalculatePaybackPeriodService)->__calculate($study->duration_in_years, $accumulated, $study->getStudyStartDateFormatted(), $totalEquityInjection);
        $paybackDate = array_key_last($payback);
        $paybackValue = $payback[$paybackDate] ?? 0;
        $currency = $study->getMainFunctionalCurrencyFormatted();

        $cardItems = [
            ['title' => 'Total Fixed Asset Investment', 'value' => LegacyMath::numberFormat($totalFixedAssetInvestment, 0)],
            ['title' => 'Pre-operating & Working Capital', 'value' => LegacyMath::numberFormat($totalPreOperatingAndWorkingCapital, 0)],
            ['title' => 'Total Required Investment', 'value' => LegacyMath::numberFormat($totalRequiredInvestment, 0)],
            ['title' => 'Total Equity Injection', 'value' => LegacyMath::numberFormat($totalEquityInjection, 0)],
            ['title' => 'Total Loans', 'value' => LegacyMath::numberFormat($totalLoans, 0)],
            ['title' => 'WACC %', 'value' => LegacyMath::numberFormat($wacc * 100, 1).' %'],
            ['title' => 'Cost Of Equity %', 'value' => LegacyMath::numberFormat($costOfEquity, 1).' %'],
            ['title' => 'Cost Of Debt %', 'value' => LegacyMath::numberFormat($costOfDebt * 100, 1).' %'],
            ['title' => 'Net Present Value For Firm', 'value' => LegacyMath::numberFormat($netPresentValueForFirm).' [ '.$currency.' ]'],
            ['title' => 'Firm IRR %', 'value' => is_numeric($irrForFirm) ? LegacyMath::numberFormat($irrForFirm * 100, 1).' %' : $irrForFirm],
            ['title' => 'Payback Period', 'value' => $paybackValue.' ( '.$paybackDate.' )'],
        ];

        return $this->dashboards[$key] = [
            'card_items' => $cardItems,
            'total_revenues' => $revenueStreamChartData,
            'totalRevenuesStreamPerYear' => $totalRevenuesStreamPerYear,
            'grossProfitDepartmentChartData' => $grossProfitDepartmentChartData,
            'ebitda' => $ebitdaChartData,
            'ebit' => $ebitChartData,
            'ebt' => $ebtChartData,
            'net_profit' => $netProfitChartData,
            'occupancyChart' => $occupancyChart,
            'revenueStreamChart' => $revenueStreamChart,
            'hotelRevenuesBreakdownChart' => $hotelRevenuesBreakdownChart,
            'adrChart' => $adrChart,
            'revparChart' => $revparChart,
            'revenueStreamAccumulatedData' => $revenueStreamAccumulatedData,
            'grossProfitDepartmentChart' => $grossProfitDepartmentChart,
            'grossAccumulatedProfitDepartmentAccumulatedChart' => $grossAccumulated,
            'ebitdaChart' => $ebitdaChart,
            'ebitChart' => $ebitChart,
            'ebtChart' => $ebtChart,
            'netProfitChart' => $netProfitChart,
            'dates' => $this->operationYearDates($study, $c),
            'revenueStreamType' => $revenueStreamType,
            'grossProfitDepartmentType' => $grossProfitType,
            'irrForEquity' => $irrForEquity,
            'netPresentValueForEquity' => $netPresentValueForEquity,
        ];
    }

    /** the old controller's calculateADR / calculateREVPAR / calculateOccupancyRate: a total divided by another, year by year (0 when there is nothing to divide by) */
    private function ratio(array $numerators, array $denominators): array
    {
        $result = [];
        foreach ($numerators as $year => $value) {
            $denominator = $denominators[$year] ?? 1;
            $result[$year] = $denominator ? $value / $denominator : 0;
        }

        return $result;
    }

    private function operationYearDates(HospitalitySector $study, array $c): array
    {
        $perYear = $study->getOperationDurationPerYear($c['datesAsStringAndIndex'], $c['datesIndexWithYearIndex'], $c['yearIndexWithYear'], $c['dateIndexWithDate'], $c['dateWithMonthNumber']);

        return $study->getOnlyDatesOfActiveOperation($perYear, $c['dateIndexWithDate']);
    }
}
