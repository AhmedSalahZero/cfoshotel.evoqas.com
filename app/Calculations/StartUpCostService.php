<?php

namespace App\Calculations;

use App\Support\HArr;
use App\Models\FFE;
use App\Models\HospitalitySector;

class StartUpCostService
{
    public function calculateStartUpCost(float $dueDays, float $cashPayment, float $deferredPaymentPercentage, float $costAmount, int $dateAsIndex, array $studyDatesAsStringAndIndex, array $dateIndexWithDate, array $dateWithDateIndex, HospitalitySector $hospitalitySector, array &$startUpAndPreOperationExpensesTotals)
    {
        $startUpPayment = $this->calculatePayment($dueDays, $cashPayment, $deferredPaymentPercentage, $costAmount, $dateAsIndex, $dateIndexWithDate, $dateWithDateIndex, $hospitalitySector);

        $dateAsString = $dateIndexWithDate[$dateAsIndex];
        $dateIndexWithCostAmount = [];
        foreach ($studyDatesAsStringAndIndex as $dateString => $dateIndex) {
            $dateIndexWithCostAmount[$dateIndex] = $dateAsString == $dateString ? $costAmount : 0;
        }

        $startUpPayableBalance = $this->calculateEndBalance($dateIndexWithCostAmount, $startUpPayment, $dateIndexWithDate, $hospitalitySector);
        $projectUnderProgressForFFE = [
            'transferred_date_and_vales' => [
                $dateAsIndex => $costAmount,
            ],
        ];
        $startUpCostAssets = (new FFE)->calculateFFEAssets(12, 0, 0, $projectUnderProgressForFFE, $studyDatesAsStringAndIndex, $hospitalitySector->getStudyEndDateAsIndex(), $hospitalitySector);

        $startUpAndPreOperationExpensesTotals['payments'] = HArr::sumAtDates([$startUpAndPreOperationExpensesTotals['payments'] ?? [], $startUpPayment], $studyDatesAsStringAndIndex);
        $startUpAndPreOperationExpensesTotals['total_monthly_depreciation'] = HArr::sumAtDates([$startUpAndPreOperationExpensesTotals['total_monthly_depreciation'] ?? [], $startUpCostAssets['total_monthly_depreciation'] ?? []], $studyDatesAsStringAndIndex);
        $startUpAndPreOperationExpensesTotals['payable_end_balance'] = HArr::sumAtDates([$startUpAndPreOperationExpensesTotals['payable_end_balance'] ?? [], $startUpPayableBalance['monthly']['end_balance'] ?? []], $studyDatesAsStringAndIndex);
        //  $startUpAndPreOperationExpensesTotals['beginning_balance'] = HArr::sumAtDates([$startUpAndPreOperationExpensesTotals['beginning_balance'] ??[], $startUpCostAssets['beginning_balance']??[] ],$studyDatesAsStringAndIndex);
        $startUpAndPreOperationExpensesTotals['final_total_gross'] = HArr::sumAtDates([$startUpAndPreOperationExpensesTotals['final_total_gross'] ?? [], $startUpCostAssets['final_total_gross'] ?? []], $studyDatesAsStringAndIndex);
        $startUpAndPreOperationExpensesTotals['accumulated_depreciation'] = HArr::sumAtDates([$startUpAndPreOperationExpensesTotals['accumulated_depreciation'] ?? [], $startUpCostAssets['accumulated_depreciation'] ?? []], $studyDatesAsStringAndIndex);
        $startUpAndPreOperationExpensesTotals['end_balance'] = HArr::sumAtDates([$startUpAndPreOperationExpensesTotals['end_balance'] ?? [], $startUpCostAssets['end_balance'] ?? []], $studyDatesAsStringAndIndex);

        return [
            'payments' => $startUpPayment,
            'start_up_payable_statement' => $startUpPayableBalance,
            'start_up_cost_assets' => $startUpCostAssets,
        ];
    }

    public function calculatePreOperatingExpense(array $payload, array $studyDatesAsStringAndIndex, array $dateIndexWithDate, HospitalitySector $hospitalitySector, array &$startUpAndPreOperationExpensesTotals)
    {
        $payloadAsIndexes = $payload;
        // $payload = $hospitalitySector->convertArrayOfIndexKeysToDateStringAsIndexWithItsOriginalValue($payload , $dateIndexWithDate);
        $perOperatingAssets = $this->calculatePreOperatingAssets($payload, 12, $studyDatesAsStringAndIndex, $hospitalitySector);
        $startUpAndPreOperationExpensesTotals['payments'] = HArr::sumAtDates([$startUpAndPreOperationExpensesTotals['payments'] ?? [], $payloadAsIndexes], $studyDatesAsStringAndIndex);
        $startUpAndPreOperationExpensesTotals['total_monthly_depreciation'] = HArr::sumAtDates([$startUpAndPreOperationExpensesTotals['total_monthly_depreciation'] ?? [], $perOperatingAssets['total_monthly_depreciation'] ?? []], $studyDatesAsStringAndIndex);
        //  $startUpAndPreOperationExpensesTotals['beginning_balance'] = HArr::sumAtDates([$startUpAndPreOperationExpensesTotals['beginning_balance'] ??[], $perOperatingAssets['beginning_balance']??[] ],$studyDatesAsStringAndIndex);
        $startUpAndPreOperationExpensesTotals['final_total_gross'] = HArr::sumAtDates([$startUpAndPreOperationExpensesTotals['final_total_gross'] ?? [], $perOperatingAssets['final_total_gross'] ?? []], $studyDatesAsStringAndIndex);
        $startUpAndPreOperationExpensesTotals['accumulated_depreciation'] = HArr::sumAtDates([$startUpAndPreOperationExpensesTotals['accumulated_depreciation'] ?? [], $perOperatingAssets['accumulated_depreciation'] ?? []], $studyDatesAsStringAndIndex);
        $startUpAndPreOperationExpensesTotals['end_balance'] = HArr::sumAtDates([$startUpAndPreOperationExpensesTotals['end_balance'] ?? [], $perOperatingAssets['end_balance'] ?? []], $studyDatesAsStringAndIndex);

        return [
            'payments' => $payload,
            'per_operating_assets' => $perOperatingAssets,
        ];
    }

    protected function calculatePayment(float $dueDays, float $cashPayment, float $deferredPaymentPercentage, float $costAmount, int $dateAsIndex, array $dateIndexWithDate, array $dateWithDateIndex, HospitalitySector $hospitalitySector)
    {
        $collectionPolicyService = new CollectionPolicyService;
        $collectionPolicyValue = ['due_in_days' => [0, $dueDays], 'rate' => [$cashPayment, $deferredPaymentPercentage]];

        return $collectionPolicyService->applyCollectionPolicy(true, 'customize', $collectionPolicyValue, [$dateAsIndex => $costAmount], $dateIndexWithDate, $dateWithDateIndex, $hospitalitySector);
    }

    public function calculateEndBalance(array $purchase, array $collection, array $dateIndexWithDate, HospitalitySector $hospitalitySector)
    {
        $purchasesForIntervals = [
            'monthly' => $purchase,
            'quarterly' => sumIntervalsIndexes($purchase, 'quarterly', $hospitalitySector->financialYearStartMonth(), $dateIndexWithDate),
            'semi-annually' => sumIntervalsIndexes($purchase, 'semi-annually', $hospitalitySector->financialYearStartMonth(), $dateIndexWithDate),
            'annually' => sumIntervalsIndexes($purchase, 'annually', $hospitalitySector->financialYearStartMonth(), $dateIndexWithDate),
        ];
        $collectionForInterval = [
            'monthly' => $collection,
            'quarterly' => sumIntervalsIndexes($collection, 'quarterly', $hospitalitySector->financialYearStartMonth(), $dateIndexWithDate),
            'semi-annually' => sumIntervalsIndexes($collection, 'semi-annually', $hospitalitySector->financialYearStartMonth(), $dateIndexWithDate),
            'annually' => sumIntervalsIndexes($collection, 'annually', $hospitalitySector->financialYearStartMonth(), $dateIndexWithDate),
        ];

        $result = [];
        $beginning_balances = [];
        $dueAmounts = [];
        $end_balance = [];
        foreach (getIntervalFormatted() as $intervalName => $intervalNameFormatted) {
            $beginning_balance = 0;
            foreach ($purchasesForIntervals[$intervalName] as $dateAsIndex => $purchaseAtDate) {
                $result[$intervalName]['beginning_balance'][$dateAsIndex] = $beginning_balance;
                $beginning_balances[$dateAsIndex] = $beginning_balance;
                $result[$intervalName]['asset_purchases'][$dateAsIndex] = $purchaseAtDate ?? 0;
                $due_amount = ($purchaseAtDate ?? 0) + $beginning_balance;
                $result[$intervalName]['due_amount'][$dateAsIndex] = $due_amount;
                $currenPayment = getValueFromArrayStringAndIndex($collectionForInterval[$intervalName], $dateAsIndex, $dateAsIndex, 0);
                $dueAmounts[$dateAsIndex] = $due_amount;
                $end_balance[$dateAsIndex] = $due_amount - $currenPayment;
                $result[$intervalName]['payment'][$dateAsIndex] = $currenPayment;
                $result[$intervalName]['end_balance'][$dateAsIndex] = $end_balance[$dateAsIndex] ?? 0;
                $beginning_balance = $end_balance[$dateAsIndex];
            }

        }

        return $result;
    }

    public function calculatePreOperatingAssets(array $payload, int $propertyDepreciationDurationInMonths, array $studyDates, ?HospitalitySector $hospitalitySector = null): array
    {
        $preOperatingAssets = [];

        $operationStartDateAsIndex = $hospitalitySector->getOperationStartDateAsIndex();

        $beginningBalance = 0;
        $totalMonthlyDepreciation = [];
        $accumulatedDepreciation = [];
        $depreciation = [];
        $index = 0;
        $depreciationStartDateAsIndex = $operationStartDateAsIndex;
        $depreciationEndDateAsIndex = is_numeric($depreciationStartDateAsIndex) ? $depreciationStartDateAsIndex + 11 : null;
        foreach ($studyDates as $dateAsIndex) {
            $preOperatingAssets['beginning_balance'][$dateAsIndex] = $beginningBalance;
            $preOperatingAssets['additions'][$dateAsIndex] = $payload[$dateAsIndex] ?? 0;
            $preOperatingAssets['initial_total_gross'][$dateAsIndex] = $preOperatingAssets['additions'][$dateAsIndex] + $beginningBalance;
            $preOperatingAssets['final_total_gross'][$dateAsIndex] = $preOperatingAssets['initial_total_gross'][$dateAsIndex];
            $depreciation[$dateAsIndex] = $this->calculateMonthlyDepreciation($preOperatingAssets['additions'][$dateAsIndex], 0, $propertyDepreciationDurationInMonths, $depreciationStartDateAsIndex, $depreciationEndDateAsIndex, $totalMonthlyDepreciation, $accumulatedDepreciation, $studyDates);
            $accumulatedDepreciation = calculateAccumulatedDepreciation($totalMonthlyDepreciation, $studyDates);
            $preOperatingAssets['total_monthly_depreciation'] = $totalMonthlyDepreciation;
            $preOperatingAssets['accumulated_depreciation'] = $accumulatedDepreciation;
            $currentAccumulatedDepreciation = $preOperatingAssets['accumulated_depreciation'][$dateAsIndex] ?? 0;
            $preOperatingAssets['end_balance'][$dateAsIndex] = $preOperatingAssets['final_total_gross'][$dateAsIndex] - $currentAccumulatedDepreciation;
            $beginningBalance = $preOperatingAssets['final_total_gross'][$dateAsIndex];
            $index++;
        }

        return $preOperatingAssets;
    }

    protected function calculateMonthlyDepreciation(float $additions, float $replacementCost, int $propertyDepreciationDurationInMonths, ?int $depreciationStartDateAsIndex, ?int $depreciationEndDateAsIndex, &$totalMonthlyDepreciation, &$accumulatedDepreciation, array $studyDates)
    {
        if (is_null($depreciationStartDateAsIndex) || is_null($depreciationEndDateAsIndex)) {
            return [];
        }
        $monthlyDepreciations = [];
        $monthlyDepreciationAtCurrentDate = ($additions + $replacementCost) / $propertyDepreciationDurationInMonths;
        $depreciationDates = generateDatesBetweenTwoIndexedDates($depreciationStartDateAsIndex, $depreciationEndDateAsIndex);
        foreach ($studyDates as $dateAsIndex) {
            $previousDateAsIndex = $dateAsIndex - 1;
            if (in_array($dateAsIndex, $depreciationDates)) {
                $monthlyDepreciations[$dateAsIndex] = $monthlyDepreciationAtCurrentDate;
                $totalMonthlyDepreciation[$dateAsIndex] = isset($totalMonthlyDepreciation[$dateAsIndex]) ? $totalMonthlyDepreciation[$dateAsIndex] + $monthlyDepreciationAtCurrentDate : $monthlyDepreciationAtCurrentDate;
                $accumulatedDepreciation[$dateAsIndex] = $previousDateAsIndex >= 0 ? ($totalMonthlyDepreciation[$dateAsIndex] + $accumulatedDepreciation[$previousDateAsIndex]) : $totalMonthlyDepreciation[$dateAsIndex];
            } else {
                // $monthlyDepreciations[$dateAsString] = 0;
                // $totalMonthlyDepreciation[$dateAsString]  = 0 ;
                $accumulatedDepreciation[$dateAsIndex] = $accumulatedDepreciation[$previousDateAsIndex] ?? 0;
            }
        }

        return $monthlyDepreciations;
    }
}
