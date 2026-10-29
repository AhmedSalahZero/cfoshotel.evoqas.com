<?php

namespace App\Services\Reports;

use App\Calculations\FixedAssetsPayableEndBalance;
use App\Calculations\PropertyInsurancePayableEndBalance;
use App\Calculations\PropertyTaxesPayableEndBalance;
use App\Models\HospitalitySector;
use App\Services\Statements\ReportItems;
use App\Support\HArr;
use App\Support\StudyCache;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — FinancialReports (the cash in / out report and the balance sheet, as the previous application worked them out)
//  Location: app/Services/Reports/FinancialReports.php
//
//  Carried over line for line from viewCashInOutReport, formatCashInOutReportItems and viewBalanceSheetReport of the previous application's
//  big controller. The lines of the income statement come from ReportItems (shared with the statements of step 11).
//  A report is null when the study cannot be calculated yet.
// ══════════════════════════════════════════════════════════════════

class FinancialReports
{
    /** @var array<int, array|null> */
    private array $cash = [];

    /** @var array<int, array|null> */
    private array $sheets = [];

    public function __construct(private readonly ReportItems $items) {}

    /**
     * The cash in / out report with everything the balance sheet, the ratios and the dashboard need.
     *
     * @return array{working_capital_injection: array, dashboardItems: array, reportItems: array, finalReportItems: array, reportItemsInterval: array, management_fees: array, monthlyPropertyTaxesAndExpensesAndPayments: array, monthlyPropertyInsuranceAndExpensesAndPayments: array, studyDates: array, c: array}|null
     */
    public function cashInOut(HospitalitySector $study): ?array
    {
        if (array_key_exists($study->id, $this->cash)) {
            return $this->cash[$study->id];
        }
        $c = $this->items->context($study);
        if ($c === null) {
            return $this->cash[$study->id] = null;
        }

        // the income statement, the cash in / out, the balance sheet, the ratios and the dashboard all start from this: work it out once and keep it
        // until study data is written (without the context, which holds objects and is kept on its own)
        $kept = StudyCache::remember($study->id, 'cash-in-out', fn () => $this->workOutCashInOut($study, $c));

        return $this->cash[$study->id] = $kept + ['c' => $c];
    }

    /** cashInOut() without the context: plain arrays only, so that it can be kept. */
    private function workOutCashInOut(HospitalitySector $study, array $c): array
    {
        $studyDates = $this->items->studyDates($c);
        $dashboardItems = $c['dashboard'];
        $propertyAcquisition = $study->propertyAcquisition;

        $finalReportItems = [];
        $reportItemsInterval = [];
        foreach (getIntervalOnlyMonthlyAndAnnuallyFormatted() as $intervalName => $label) {
            $reportItemsInterval = $this->items->forInterval($c, $intervalName);
            $finalReportItems[$intervalName] = $reportItemsInterval;
        }

        $replacementCost = [];
        $assetsBuilding = $c['monthly']['propertyAssetsForBuilding'];
        $assetsFfe = $c['monthly']['propertyAssetsForFFE'];
        if ($propertyAcquisition) {
            $replacementCost = HArr::sumAtDates([
                $assetsBuilding['replacement_cost'] ?? [],
                $assetsFfe['replacement_cost'] ?? [],
                $finalReportItems['monthly']['totalOfFFEItemForFFE']['replacement_cost'] ?? [],
            ], $studyDates);
            $replacementCost = $study->removeDatesAfterDate($replacementCost, $study->getStudyEndDateAsIndex());
        }
        $dashboardItems['CashOutReport']['Acquisition And Development Payment']['Replacement Cost'] = $replacementCost;

        $operationDatesAsIndexes = $this->items->operationDatesAsIndexes($c);
        $incentive = $reportItemsInterval['incentive_management_fees']['Incentive Management Fees'] ?? [];

        $managementFees = [];
        foreach (getIntervalOnlyMonthlyAndAnnuallyFormatted() as $intervalName => $label) {
            $managementFees = $study->calculateManagementFeesStatement($operationDatesAsIndexes, $incentive, $intervalName, $c['dateIndexWithDate'], $c['dateWithDateIndex']);
        }
        foreach (getIntervalOnlyMonthlyAndAnnuallyFormatted() as $intervalName => $label) {
            $finalReportItems[$intervalName]['taxes']['Corporate Taxes Payments'] = $study->calculateCorporateTaxesStatement($operationDatesAsIndexes, $finalReportItems['annually']['taxes']['Corporate Taxes'], $intervalName, $c['dateIndexWithDate'], $c['dateWithDateIndex']);
        }

        $dashboardItems['CashOutReport']['Management Fees']['Incentive Management Fees'] = $managementFees['monthly']['payments'] ?? [];
        $taxes = $study->calculatePropertyTaxes($assetsBuilding);
        $propertyTaxesPayments = $taxes['payments'] ?? [];
        $totalOfFfe = $this->items->totalOfFfeAssets($finalReportItems['monthly']['ffeAssetItems'] ?? [], $studyDates);
        $insurance = $study->calculatePropertyInsurance($studyDates, $assetsBuilding, $assetsFfe, $totalOfFfe);
        $propertyInsurancePayments = $insurance['payments'] ?? [];
        $dashboardItems['CashOutReport']['Taxes']['Property Taxes & Insurance'] = sumTwoArray($propertyTaxesPayments, $propertyInsurancePayments);
        $dashboardItems['CashOutReport']['Taxes']['Corporate Taxes'] = $finalReportItems['monthly']['taxes']['Corporate Taxes Payments']['monthly']['payments'] ?? [];

        $reportItems = $this->formatCashInOut($dashboardItems, $study);

        // must be last key added
        $workingCapital = $study->calculateWorkingCapitalInjection($reportItems['netCash']['Accumulated Net Cash'], $c['datesAsStringAndIndex'], $c['datesIndexWithYearIndex'], $c['yearIndexWithYear'], $c['dateIndexWithDate']);
        $dashboardItems['CashInReport']['Equity Injection']['Working Capital'] = $workingCapital;
        $reportItems = $this->formatCashInOut($dashboardItems, $study);

        return [
            'working_capital_injection' => $workingCapital,
            'dashboardItems' => $dashboardItems,
            'reportItems' => $reportItems,
            'finalReportItems' => $finalReportItems,
            'reportItemsInterval' => $reportItemsInterval,
            'management_fees' => $managementFees,
            'monthlyPropertyTaxesAndExpensesAndPayments' => $taxes,
            'monthlyPropertyInsuranceAndExpensesAndPayments' => $insurance,
            'studyDates' => $studyDates,
        ];
    }

    /** formatCashInOutReportItems */
    public function formatCashInOut(array $dashboardItems, HospitalitySector $study): array
    {
        $collection = [];
        $cashInReport = [];
        $totalCollectionSubItems = [];
        foreach ($dashboardItems['CashInReport'] as $reportName => $reportValue) {
            $cashInReport = array_merge($cashInReport, $this->items->formatReport($dashboardItems['CashInReport'], $reportName, $reportName, 'null', $study, false));
        }
        foreach ([
            'rooms' => ['Total Rooms Collection', 'Room'],
            'foods' => ['Total F&B Collection', 'Food'],
            'gaming' => ['Total Gaming Collection', 'Casino'],
            'meetings' => ['Total Meeting Spaces Collection', 'Meeting'],
            'others' => ['Total Other Collection', 'Other'],
        ] as $key => [$title, $modelName]) {
            $totalCollectionSubItems = array_merge($totalCollectionSubItems, $this->items->formatReport($dashboardItems['collectionPoliciesAndReceivableEndBalances']['collection_policy'], $key, $title, $modelName, $study, false));
        }

        $totalCollectionSubItems = array_merge($cashInReport, $totalCollectionSubItems);
        $totalOfCollectionSubItems = getTotalOfArraysOf2Depth($totalCollectionSubItems);
        ksort($totalOfCollectionSubItems);
        $collection['Total Cash In'] = $totalOfCollectionSubItems;
        $collection['Total Cash In']['subItems'] = $totalCollectionSubItems;
        $report['cashInReport'] = $collection;

        $cashOutReportData = [];
        $cashOutReport = [];
        foreach ($dashboardItems['CashOutReport'] as $reportName => $reportValue) {
            $cashOutReport = array_merge($cashOutReport, $this->items->formatReport($dashboardItems['CashOutReport'], $reportName, $reportName, 'null', $study, false));
        }
        $totalCashOutReport = getTotalOfArraysOf2Depth($cashOutReport);
        ksort($totalCashOutReport);
        $cashOutReportData['Total Cash Out Report'] = $totalCashOutReport;
        $cashOutReportData['Total Cash Out Report']['subItems'] = $cashOutReport;
        $report['cashOutReport'] = $cashOutReportData;

        $netCash = subtractTwoArray(removeKeyFromArray($collection['Total Cash In'], 'subItems'), removeKeyFromArray($cashOutReportData['Total Cash Out Report'], 'subItems'));
        $report['netCash']['Net Cash Report'] = $netCash;
        $report['netCash']['Accumulated Net Cash'] = HArr::accumulateArray($netCash);

        return $report;
    }

    /**
     * The balance sheet (viewBalanceSheetReport).
     *
     * @return array{balance_sheet_items: array, cashInOutReport: array, study_dates: array}|null
     */
    public function balanceSheet(HospitalitySector $study): ?array
    {
        if (array_key_exists($study->id, $this->sheets)) {
            return $this->sheets[$study->id];
        }
        $cashInOut = $this->cashInOut($study);
        if ($cashInOut === null) {
            return $this->sheets[$study->id] = null;
        }

        $c = $cashInOut['c'];
        $studyDates = $cashInOut['studyDates'];
        $dateIndexWithDate = $c['dateIndexWithDate'];
        $datesAsStringAndIndex = $c['datesAsStringAndIndex'];
        $reportItems = $cashInOut['reportItems'];
        $dashboardItems = $cashInOut['dashboardItems'];
        $finalReportItems = $cashInOut['finalReportItems'];
        $reportItemsInterval = $cashInOut['reportItemsInterval'];
        $monthlyPropertyTaxesAndExpensesAndPayments = $cashInOut['monthlyPropertyTaxesAndExpensesAndPayments'];
        $monthlyPropertyInsuranceAndExpensesAndPayments = $cashInOut['monthlyPropertyInsuranceAndExpensesAndPayments'];

        $managementFeesEndBalance = $cashInOut['management_fees']['monthly']['end_balance'] ?? [];

        // a loan: the balance at the end of each month, filled forward, plus the withdrawals that are not yet in a schedule
        $loanBalance = function (array $endBalance, array $withdrawal) use ($studyDates, $datesAsStringAndIndex, $study) {
            $endBalance = HArr::fillMissedKeysFromPreviousKeys($endBalance, $studyDates);
            $endBalance = $study->convertArrayOfIndexKeysToIndexAsDateStringWithItsOriginalValue($endBalance, $datesAsStringAndIndex);
            $scheduleStart = array_key_last($withdrawal);
            array_pop($withdrawal);
            // the months between two withdrawals keep the balance of the previous one until the schedule starts (withdrawals can be months apart)
            if (count($withdrawal)) {
                $previous = 0;
                foreach (range(array_key_first($withdrawal), $scheduleStart - 1) as $month) {
                    $previous = $withdrawal[$month] ?? $previous;
                    $withdrawal[$month] = $previous;
                }
            }

            return HArr::sumAtDates([$withdrawal, $endBalance], $studyDates);
        };
        $propertyLoanEndBalance = $loanBalance($dashboardItems['propertyLoanEndBalance'], $dashboardItems['propertyLoanWithdrawalEndBalance']);
        $hardConstructionLoanEndBalance = $loanBalance($dashboardItems['hardConstructionLoanEndBalance'], $dashboardItems['hardLoanWithdrawalEndBalance']);
        $landLoanEndBalance = $loanBalance($dashboardItems['landLoanEndBalance'], $dashboardItems['landLoanWithdrawalEndBalance']);
        $ffeLoanEndBalance = $loanBalance($dashboardItems['ffeLoanEndBalance'], $dashboardItems['ffeLoanWithdrawalEndBalance']);
        $propertyLandCapitalizedInterest = $dashboardItems['propertyLandCapitalizedInterest'];
        $landLoanCapitalizedInterest = $dashboardItems['landLoanCapitalizedInterest'];

        $monthly = $finalReportItems['monthly'];
        $buildingFixedAssets = $monthly['propertyAssetsForBuilding']['final_total_gross'] ?? [];
        $buildingEndBalance = $monthly['propertyAssetsForBuilding']['end_balance'] ?? [];
        $ffeAssetItems = $monthly['ffeAssetItems'] ?? [];
        $propertyAssetsForFFE = $monthly['propertyAssetsForFFE'] ?? [];
        $totalFfe = $this->items->totalOfFfeAssets(array_merge([$propertyAssetsForFFE], $ffeAssetItems), $studyDates);
        $totalFFEFixedAssets = $totalFfe['final_total_gross'] ?? [];
        $buildingAccumulatedDepreciation = $monthly['propertyAssetsForBuilding']['accumulated_depreciation'] ?? [];
        $projectUnderProgressConstructionEndBalance = $monthly['projectUnderProgressConstruction']['end_balance'] ?? [];
        $projectUnderProgressFFEEndBalance = ($monthly['projectUnderProgressFFE'] ?? [])['end_balance'] ?? [];
        $totalFFEAccumulatedDepreciation = $totalFfe['accumulated_depreciation'] ?? [];
        $totalFFEEndBalance = $totalFfe['end_balance'] ?? [];

        $currentPortionOfLongTermDebt = [];
        $totalCashAndBanks = $reportItems['netCash']['Accumulated Net Cash'] ?? [];

        $receivable = fn (string $stream) => $dashboardItems['collectionPoliciesAndReceivableEndBalances']['receivable_end_balance'][$stream]['total']['intervalsReport']['monthly']['end_balance'] ?? [];
        $inventory = fn (string $stream) => $dashboardItems['inventoryStatements'][$stream]['total']['monthly']['end_balance'] ?? [];
        $disposablePayable = fn (string $stream) => $dashboardItems['disposablePaymentStatements'][$stream]['total']['monthly']['end_balance'] ?? [];
        $prepaid = fn (string $key) => $dashboardItems[$key]['total']['monthly']['end_balance'] ?? [];

        $customerReceivablesForRooms = $receivable('rooms');
        $customerReceivablesForFoods = $receivable('foods');
        $customerReceivablesForGaming = $receivable('gaming');
        $customerReceivablesForMeetings = $receivable('meetings');
        $customerReceivablesForOthers = $receivable('others');
        $disposablesInventoryForRooms = $inventory('rooms');
        $disposablesInventoryForFoods = $inventory('foods');
        $disposablesInventoryForGaming = $inventory('gaming');
        $otherDebtors = [];

        $totalCurrentAssets = HArr::sumAtDates([$totalCashAndBanks, $customerReceivablesForRooms, $customerReceivablesForFoods, $customerReceivablesForGaming, $customerReceivablesForMeetings, $customerReceivablesForOthers, $disposablesInventoryForRooms, $disposablesInventoryForFoods, $disposablesInventoryForGaming, $otherDebtors], $studyDates);

        $disposablePayablesForRooms = $disposablePayable('rooms');
        $disposablePayablesForFoods = $disposablePayable('foods');
        $disposablePayablesForGaming = $disposablePayable('gaming');
        $generalExpense = $prepaid('prepaidExpenseStatementForGeneralForView');
        $marketExpense = $prepaid('prepaidExpenseStatementForMarketingForView');
        $propertyExpense = $prepaid('prepaidExpenseStatementForPropertyForView');
        $energyExpense = $prepaid('prepaidExpenseStatementForEnergyForView');

        $landFixedAssetsWithAccumulation = $study->calculateLandFixedAssets($studyDates, $propertyLandCapitalizedInterest, $landLoanCapitalizedInterest);
        $landFixedAssets = $landFixedAssetsWithAccumulation['AccumulatedLandFixed'];
        $startUp = $dashboardItems['startUpAndPreOperationExpensesTotals'] ?? [];
        $totalBeginningStartUpAndPreOperatingAssets = $startUp['final_total_gross'] ?? [];
        $totalAccumulatedDepreciationStartUpAndPreOperatingAssets = $startUp['accumulated_depreciation'] ?? [];
        $totalEndBalanceStartUpAndPreOperatingAssets = $startUp['end_balance'] ?? [];
        $otherPayableBalance = $startUp['payable_end_balance'] ?? [];

        $totalLongTermAssets = HArr::sumAtDates([$landFixedAssets, $buildingEndBalance, $totalFFEEndBalance, $projectUnderProgressConstructionEndBalance, $projectUnderProgressFFEEndBalance, $totalEndBalanceStartUpAndPreOperatingAssets], $studyDates);
        $totalAssets = HArr::sumAtDates([$totalCurrentAssets, $totalLongTermAssets], $studyDates);
        $accruedExpenses = HArr::sumAtDates([$generalExpense, $marketExpense, $propertyExpense, $energyExpense], $studyDates);

        $acquisition = $dashboardItems['CashOutReport']['Acquisition And Development Payment'] ?? [];
        $payable = fn (array $datesAndAmounts, array $payments, bool $flag = false) => ($flag
            ? (new FixedAssetsPayableEndBalance)->calculateEndBalance($datesAndAmounts, $payments, $dateIndexWithDate, $study, true)
            : (new FixedAssetsPayableEndBalance)->calculateEndBalance($datesAndAmounts, $payments, $dateIndexWithDate, $study))['monthly']['end_balance'] ?? [];
        $executionByDate = fn (array $execution) => HArr::sumAtDates([$study->convertArrayOfIndexKeysToIndexAsDateStringWithItsOriginalValue($execution, $datesAsStringAndIndex), []], $studyDates);

        // a purchase is only listed on its own date: give the payable every month, otherwise it is lost as soon as the payments are spread over several months
        $everyMonth = fn (array $purchaseByIndex) => HArr::sumAtDates([$purchaseByIndex, []], $studyDates);

        $propertyPayable = $payable($everyMonth($study->getPropertyAcquisitionDatesAndAmounts($studyDates)), $acquisition['Property Payments'] ?? []);

        $landPayable = [];
        if (count($landFixedAssetsWithAccumulation['landFixed'] ?? [])) {
            $landPayable = $payable($everyMonth($landFixedAssetsWithAccumulation['landFixedWithoutProperty'] ?? []), $acquisition['Land Payments'] ?? []);
        }
        $constructionDates = $executionByDate($dashboardItems['hardConstructionExecutionAndPayment'] ?? []);
        $constructionPayable = [];
        if (count($constructionDates)) {
            $constructionPayable = $payable($constructionDates, $acquisition['Hard Construction Payment'] ?? [], true);
        }
        $softDates = $executionByDate($dashboardItems['softConstructionExecutionAndPayment'] ?? []);
        $softPayable = [];
        if (count($softDates)) {
            $softPayable = $payable($softDates, $acquisition['Soft Construction Payment'] ?? []);
        }
        $ffeDates = $executionByDate($dashboardItems['ffeExecutionAndPayment'] ?? []);
        $ffePayable = [];
        if (count($ffeDates)) {
            $ffePayable = $payable($ffeDates, $acquisition['FFE Payment'] ?? []);
        }

        $equityInjection = $dashboardItems['CashInReport']['Equity Injection'] ?? [];
        $totalOfEquityInjection = HArr::sumAtDates(array_values($equityInjection), $studyDates);
        $paidUpCapital = HArr::accumulateArray($totalOfEquityInjection);
        $annuallyNetProfit = $reportItemsInterval['net_profit']['Net Profit'] ?? [];
        $retainedEarning = $study->calculateRetainedEarning($annuallyNetProfit, 'monthly');

        $taxesPayments = $monthlyPropertyTaxesAndExpensesAndPayments['payments'] ?? [];
        $propertyTaxesEndBalance = (new PropertyTaxesPayableEndBalance)->getPropertyTaxesPayableEndBalance($monthlyPropertyTaxesAndExpensesAndPayments['monthlyPropertyTaxesExpenses'] ?? [], $taxesPayments, $dateIndexWithDate, $study)['monthly']['end_balance'] ?? [];
        $propertyTaxesEndBalance = $study->convertArrayOfIndexKeysToIndexAsDateStringWithItsOriginalValue($propertyTaxesEndBalance, $datesAsStringAndIndex);

        $insurancePayments = $monthlyPropertyInsuranceAndExpensesAndPayments['payments'] ?? [];
        $propertyInsuranceEndBalance = (new PropertyInsurancePayableEndBalance)->getPropertyInsurancePayableEndBalance($monthlyPropertyInsuranceAndExpensesAndPayments['monthlyPropertyInsuranceExpenses'] ?? [], $insurancePayments, $dateIndexWithDate, $c['dateWithDateIndex'], $study)['monthly']['end_balance'] ?? [];
        $propertyInsuranceEndBalance = $study->convertArrayOfIndexKeysToIndexAsDateStringWithItsOriginalValue($propertyInsuranceEndBalance, $datesAsStringAndIndex);

        $corporateTaxesEndBalance = $finalReportItems['monthly']['taxes']['Corporate Taxes Payments']['monthly']['end_balance'];

        $totalCurrentLiabilities = HArr::sumAtDates([
            $disposablePayablesForRooms, $disposablePayablesForFoods, $disposablePayablesForGaming, $propertyTaxesEndBalance, $propertyInsuranceEndBalance, $corporateTaxesEndBalance,
            $accruedExpenses, $managementFeesEndBalance, $otherPayableBalance, $propertyPayable, $landPayable, $constructionPayable, $softPayable, $ffePayable,
        ], $studyDates);

        $workingCapital = HArr::subtractAtDates([$totalCurrentAssets, $totalCurrentLiabilities], $studyDates);
        $totalInvestment = HArr::subtractAtDates([$totalAssets, $totalCurrentLiabilities], $studyDates);
        $totalOwnersEquity = HArr::sumAtDates([$paidUpCapital, $retainedEarning, $annuallyNetProfit], $studyDates);
        $totalLongTermLoans = HArr::sumAtDates([$propertyLoanEndBalance, $landLoanEndBalance, $hardConstructionLoanEndBalance, $ffeLoanEndBalance], $studyDates);
        $checkError = HArr::subtractAtDates([$totalInvestment, $totalLongTermLoans, $totalOwnersEquity], $studyDates);
        $totalFixedAssets = HArr::sumAtDates([$landFixedAssets, $buildingFixedAssets, $totalFFEFixedAssets, $totalBeginningStartUpAndPreOperatingAssets], $studyDates);
        $totalAccumulatedDepreciation = HArr::sumAtDates([$buildingAccumulatedDepreciation, $totalFFEAccumulatedDepreciation, $totalAccumulatedDepreciationStartUpAndPreOperatingAssets], $studyDates);
        $totalNetFixedAssets = HArr::sumAtDates([$landFixedAssets, $buildingEndBalance, $totalFFEEndBalance, $totalEndBalanceStartUpAndPreOperatingAssets], $studyDates);
        $totalProjectsUnderProgress = HArr::sumAtDates([$projectUnderProgressConstructionEndBalance, $projectUnderProgressFFEEndBalance], $studyDates);
        $totalCustomersReceivables = HArr::sumAtDates([$customerReceivablesForRooms, $customerReceivablesForFoods, $customerReceivablesForGaming, $customerReceivablesForMeetings, $customerReceivablesForOthers], $studyDates);
        $totalDisposablesInventory = HArr::sumAtDates([$disposablesInventoryForRooms, $disposablesInventoryForFoods, $disposablesInventoryForGaming], $studyDates);
        $totalSuppliersPayables = HArr::sumAtDates([$disposablePayablesForRooms, $disposablePayablesForFoods, $disposablePayablesForGaming, $propertyInsuranceEndBalance, $otherPayableBalance], $studyDates);
        $totalFixedAssetsPayables = HArr::sumAtDates([$propertyPayable, $landPayable, $constructionPayable, $softPayable, $ffePayable], $studyDates);
        $totalTaxesPayables = HArr::sumAtDates([$propertyTaxesEndBalance, $corporateTaxesEndBalance], $studyDates);
        $totalOtherCreditors = HArr::sumAtDates([$accruedExpenses, $managementFeesEndBalance], $studyDates);

        $withSub = fn (array $total, array $subItems) => arrayMergeTwoDimArray($total, ['subItems' => $subItems]);

        $balanceSheetItems = [
            'Fixed Assets' => ['Fixed Assets' => $withSub($totalFixedAssets, ['Land' => $landFixedAssets, 'Building' => $buildingFixedAssets, 'FFE' => $totalFFEFixedAssets, 'Others' => $totalBeginningStartUpAndPreOperatingAssets])],
            'Accumulated Depreciation' => ['Accumulated Depreciation' => $withSub($totalAccumulatedDepreciation, ['Building Accumulated Depreciation' => $buildingAccumulatedDepreciation, 'FFE Accumulated Depreciation' => $totalFFEAccumulatedDepreciation, 'Others' => $totalAccumulatedDepreciationStartUpAndPreOperatingAssets])],
            'Net Fixed Assets' => ['Net Fixed Assets' => $withSub($totalNetFixedAssets, ['Land' => $landFixedAssets, 'Building' => $buildingEndBalance, 'FFE' => $totalFFEEndBalance, 'Others' => $totalEndBalanceStartUpAndPreOperatingAssets])],
            'Projects Under Progress' => ['Projects Under Progress' => $withSub($totalProjectsUnderProgress, ['Construction' => $projectUnderProgressConstructionEndBalance, 'FFE' => $projectUnderProgressFFEEndBalance])],
            'Total Long Term Assets' => ['Total Long Term Assets' => $totalLongTermAssets],
            'Total Cash & Banks' => ['Total Cash & Banks' => $totalCashAndBanks],
            'Customers\' Receivables' => ['Customers\' Receivables' => $withSub($totalCustomersReceivables, [
                'Rooms Receivables' => $customerReceivablesForRooms, 'F&B Receivables' => $customerReceivablesForFoods, 'Gaming Receivables' => $customerReceivablesForGaming,
                'Meeting Spaces Receivables' => $customerReceivablesForMeetings, 'Other Revenues Receivables' => $customerReceivablesForOthers,
            ])],
            'Disposables Inventory' => ['Disposables Inventory' => $withSub($totalDisposablesInventory, ['Rooms Disposables' => $disposablesInventoryForRooms, 'F&B Disposables' => $disposablesInventoryForFoods, 'Gaming Disposables' => $disposablesInventoryForGaming])],
            'Other Debtors' => ['Other Debtors' => $otherDebtors],
            'Total Current Assets' => ['Total Current Assets' => $totalCurrentAssets],
            'Total Assets' => ['Total Assets' => $totalAssets],
            'Suppliers\' Payables' => ['Suppliers\' Payables' => $withSub($totalSuppliersPayables, [
                'Rooms Disposable Payables' => $disposablePayablesForRooms, 'F&B Disposable Payables' => $disposablePayablesForFoods, 'Gaming Disposable Payables' => $disposablePayablesForGaming,
                'Property Insurance Payables' => $propertyInsuranceEndBalance, 'Others' => $otherPayableBalance,
            ])],
            'Fixed Assets\' Payables' => ['Fixed Assets\' Payables' => $withSub($totalFixedAssetsPayables, [
                'Property Payables' => $propertyPayable, 'Land Payables' => $landPayable, 'Construction Payables' => $constructionPayable, 'Soft Payables' => $softPayable, 'FFE Payables' => $ffePayable,
            ])],
            'Taxes Payables' => ['Taxes Payables' => $withSub($totalTaxesPayables, ['Property Taxes' => $propertyTaxesEndBalance, 'Corporate Taxes' => $corporateTaxesEndBalance])],
            'Current Portion Of Long Term Debt' => ['Current Portion Of Long Term Debt' => $currentPortionOfLongTermDebt],
            'Other Creditors' => ['Other Creditors' => $withSub($totalOtherCreditors, ['Accrued Expenses' => $accruedExpenses, 'Management Fees' => $managementFeesEndBalance])],
            'Total Current Liabilities' => ['Total Current Liabilities' => $totalCurrentLiabilities],
            'Working Capital' => ['Working Capital' => $workingCapital],
            'Total Investment' => ['Total Investment' => $totalInvestment],
            'Long Term Loans' => ['Long Term Loans' => $withSub($totalLongTermLoans, ['Property Loan' => $propertyLoanEndBalance, 'Land Loan' => $landLoanEndBalance, 'Construction Loan' => $hardConstructionLoanEndBalance, 'FFE Loan' => $ffeLoanEndBalance])],
            'Owners Equity' => ['Owners Equity' => $withSub($totalOwnersEquity, ['Paid Up Capital' => $paidUpCapital, 'Retained Earnings' => $retainedEarning, 'Net Profit' => $annuallyNetProfit])],
            'Check Error' => ['Check Error' => $checkError],
        ];

        return $this->sheets[$study->id] = [
            'balance_sheet_items' => $balanceSheetItems,
            'cashInOutReport' => $cashInOut,
            'study_dates' => $studyDates,
        ];
    }
}
