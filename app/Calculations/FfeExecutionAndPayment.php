<?php

namespace App\Calculations;

use App\Models\HospitalitySector;

class FfeExecutionAndPayment
{
    public function __calculate($totalCost, int $ffeStartDateAsIndex, int $duration, string $softExecutionMethod, array $dateWithDateIndex, HospitalitySector $hospitalitySector): array
    {
        return $this->calculateConstructionExecution($totalCost, $softExecutionMethod, $ffeStartDateAsIndex, $duration, $dateWithDateIndex, $hospitalitySector);
    }

    protected function calculateConstructionExecution(float $totalCost, string $softExecutionMethod, int $ffeStartDateAsIndex, int $duration, array $dateWithDateIndex, ?HospitalitySector $hospitalitySector = null): array
    {
        switch ($softExecutionMethod) {
            case 'straight-line':
                $straightMethodService = new StraightMethodService;

                return $straightMethodService->calculateStraightAmount($totalCost, $ffeStartDateAsIndex, $duration);
            case 's-curve':
                $sCurveService = new SCurveService;
                $startDateAsIndex = $hospitalitySector->getFFEStartDateAsIndex($dateWithDateIndex);

                return $sCurveService->__calculate($totalCost, $duration, $startDateAsIndex);

            case 'steady-growth':
                $steadyGrowthMethod = new SteadyGrowthMethod;
                $startDateAsIndex = $hospitalitySector->getFfeStartDateAsIndex($dateWithDateIndex);

                return $steadyGrowthMethod->calculateSteadyGrowthAmount($totalCost, $startDateAsIndex, $duration);
            case 'steady-decline':
                $steadyDeclineMethod = new SteadyDeclineMethod;
                $startDateAsIndex = $hospitalitySector->getFfeStartDateAsIndex($dateWithDateIndex);

                return $steadyDeclineMethod->calculateSteadyDeclineAmount($totalCost, $startDateAsIndex, $duration);
            default:
                return [];
        }
    }

    protected function calculateEquityFundingAmount(float $totalFfeCost, float $softEquityFundingRate)
    {
        return $totalFfeCost * ($softEquityFundingRate / 100);
    }

    protected function calculateTotalFfeCost(float $ffeCost, float $softContingencyRate)
    {
        return $ffeCost * (1 + ($softContingencyRate / 100));
    }

    public function calculateFfeEquityPayment(array $ffePayments, float $ffeCost, float $softContingencyRate, float $softEquityFundingRate)
    {
        $totalFfeCost = $this->calculateTotalFfeCost($ffeCost, $softContingencyRate);
        $equityFundingAmount = $this->calculateEquityFundingAmount($totalFfeCost, $softEquityFundingRate);
        $ffeEquityPayment = [];
        $remainingEquityFunding = [];
        $firstLoop = true;
        foreach ($ffePayments as $dateIndex => $ffePaymentValue) {
            $nextDateIndex = getNextDate($ffePayments, $dateIndex);
            if ($firstLoop) {
                $remainingEquityFunding[$dateIndex] = $equityFundingAmount;
                $firstLoop = false;
            }
            if ($remainingEquityFunding[$dateIndex] >= $ffePaymentValue) {
                $ffeEquityPayment[$dateIndex] = $ffePaymentValue;
                if ($nextDateIndex) {
                    $remainingEquityFunding[$nextDateIndex] = $remainingEquityFunding[$dateIndex] - $ffeEquityPayment[$dateIndex];
                }
            } else {
                $ffeEquityPayment[$dateIndex] = $remainingEquityFunding[$dateIndex];
                if ($nextDateIndex) {
                    $remainingEquityFunding[$nextDateIndex] = $remainingEquityFunding[$dateIndex] - $ffeEquityPayment[$dateIndex];
                }
            }
        }

        return $ffeEquityPayment;
    }

    public function calculateFfeLoanWithdrawal(array $ffePayments, float $ffeCost, float $softContingencyRate, float $equityFundingRate)
    {
        $totalFfeCost = $this->calculateTotalFfeCost($ffeCost, $softContingencyRate);

        $equityFundingAmount = $this->calculateEquityFundingAmount($totalFfeCost, $equityFundingRate);
        $ffeLoanWithdrawal = [];
        $isFirstNestedIf = true;
        $previousBalance = 0;
        foreach ($ffePayments as $dateAsIndex => $landPayment) {
            $equityPaymentBalance[$dateAsIndex] = $equityFundingAmount - $landPayment;
            $equityFundingAmount = $equityPaymentBalance[$dateAsIndex];
            if ($equityPaymentBalance[$dateAsIndex] < 0) {
                if ($isFirstNestedIf) {
                    $ffeLoanWithdrawal[$dateAsIndex] = $equityPaymentBalance[$dateAsIndex] * -1;
                } else {
                    $ffeLoanWithdrawal[$dateAsIndex] = ($equityPaymentBalance[$dateAsIndex] - $previousBalance) * -1;
                }
                $isFirstNestedIf = false;
            }
            // the equity balance after the PREVIOUS PAYMENT (payments are not always in month order, nor in consecutive months)
            $previousBalance = $equityPaymentBalance[$dateAsIndex];
        }
        if (array_sum($ffeLoanWithdrawal) > -1 && array_sum($ffeLoanWithdrawal) < 1) {
            return [];
        }

        return $ffeLoanWithdrawal;
    }
}
