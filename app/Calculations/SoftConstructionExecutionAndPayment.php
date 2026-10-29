<?php

namespace App\Calculations;

use App\Models\HospitalitySector;

class SoftConstructionExecutionAndPayment
{
    public function __calculate(float $softConstructionCost, float $softContingencyRate, int $softConstructionStartDateAsIndex, int $duration, string $softExecutionMethod, array $dateWithDateIndex, HospitalitySector $hospitalitySector): array
    {
        $softTotalConstructionCost = $softConstructionCost * (1 + ($softContingencyRate / 100));

        return $this->calculateConstructionExecution($softTotalConstructionCost, $softExecutionMethod, $softConstructionStartDateAsIndex, $duration, $dateWithDateIndex, $hospitalitySector);
    }

    protected function calculateConstructionExecution(float $softTotalConstructionCost, string $softExecutionMethod, int $softConstructionStartDateAsIndex, int $duration, array $dateWithDateIndex, ?HospitalitySector $hospitalitySector = null): array
    {
        switch ($softExecutionMethod) {
            case 'straight-line':
                // $softConstructionStartDateAsString = $dateIndexWithDate[$softConstructionStartDateAsIndex];
                $straightMethodService = new StraightMethodService;

                return $straightMethodService->calculateStraightAmount($softTotalConstructionCost, $softConstructionStartDateAsIndex, $duration);
            case 'steady-growth':
                $steadyGrowthMethod = new SteadyGrowthMethod;
                $startDateAsIndex = $hospitalitySector->getSoftConstructionStartDateAsIndex($dateWithDateIndex);

                return $steadyGrowthMethod->calculateSteadyGrowthAmount($softTotalConstructionCost, $startDateAsIndex, $duration);
            case 'steady-decline':
                $steadyDeclineMethod = new SteadyDeclineMethod;
                $startDateAsIndex = $hospitalitySector->getSoftConstructionStartDateAsIndex($dateWithDateIndex);

                return $steadyDeclineMethod->calculateSteadyDeclineAmount($softTotalConstructionCost, $startDateAsIndex, $duration);
            default:
                return [];
        }
    }

    protected function calculateEquityFundingAmount(float $totalSoftConstructionCost, float $softEquityFundingRate)
    {
        return $totalSoftConstructionCost * ($softEquityFundingRate / 100);
    }

    protected function calculateTotalSoftConstructionCost(float $softConstructionCost, float $softContingencyRate)
    {
        return $softConstructionCost * (1 + ($softContingencyRate / 100));
    }

    public function calculateSoftConstructionEquityPayment(array $softConstructionPayments, float $softConstructionCost, float $softContingencyRate, float $softEquityFundingRate)
    {
        $totalSoftConstructionCost = $this->calculateTotalSoftConstructionCost($softConstructionCost, $softContingencyRate);
        $equityFundingAmount = $this->calculateEquityFundingAmount($totalSoftConstructionCost, $softEquityFundingRate);
        $softConstructionEquityPayment = [];
        $remainingEquityFunding = [];
        $firstLoop = true;
        foreach ($softConstructionPayments as $dateIndex => $softConstructionPaymentValue) {
            $nextDateIndex = getNextDate($softConstructionPayments, $dateIndex);
            if ($firstLoop) {
                $remainingEquityFunding[$dateIndex] = $equityFundingAmount;
                $firstLoop = false;
            }
            if ($remainingEquityFunding[$dateIndex] >= $softConstructionPaymentValue) {
                $softConstructionEquityPayment[$dateIndex] = $softConstructionPaymentValue;
                if ($nextDateIndex) {
                    $remainingEquityFunding[$nextDateIndex] = $remainingEquityFunding[$dateIndex] - $softConstructionEquityPayment[$dateIndex];
                }
            } else {
                $softConstructionEquityPayment[$dateIndex] = $remainingEquityFunding[$dateIndex];
                if ($nextDateIndex) {
                    $remainingEquityFunding[$nextDateIndex] = $remainingEquityFunding[$dateIndex] - $softConstructionEquityPayment[$dateIndex];
                }
            }
        }

        return $softConstructionEquityPayment;
    }

    public function calculateSoftConstructionLoanWithdrawal(array $softConstructionPayments, float $softConstructionCost, float $softContingencyRate, float $equityFundingRate)
    {
        $totalSoftConstructionCost = $this->calculateTotalSoftConstructionCost($softConstructionCost, $softContingencyRate);

        $equityFundingAmount = $this->calculateEquityFundingAmount($totalSoftConstructionCost, $equityFundingRate);
        $softConstructionLoanWithdrawal = [];
        $isFirstNestedIf = true;

        foreach ($softConstructionPayments as $index => $landPayment) {
            $previousIndex = getPreviousDate($softConstructionPayments, $index);
            $equityPaymentBalance[$index] = $equityFundingAmount - $landPayment;
            $equityFundingAmount = $equityPaymentBalance[$index];
            if ($equityPaymentBalance[$index] < 0) {
                if ($isFirstNestedIf) {
                    $softConstructionLoanWithdrawal[$index] = $equityPaymentBalance[$index] * -1;
                } else {
                    $softConstructionLoanWithdrawal[$index] = ($equityPaymentBalance[$index] - $equityPaymentBalance[$previousIndex]) * -1;
                }
                $isFirstNestedIf = false;
            }
        }
        if (array_sum($softConstructionLoanWithdrawal) > -1 && array_sum($softConstructionLoanWithdrawal) < 1) {
            return [];
        }

        return $softConstructionLoanWithdrawal;
    }
}
