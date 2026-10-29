<?php

namespace App\Calculations;

use App\Models\HospitalitySector;

class ConstructionExecutionAndPayment
{
    public function __calculate(float $hardConstructionCost, float $hardContingencyRate, int $hardConstructionStartDateAsIndex, int $duration, string $hardExecutionMethod, array $dateWithDateIndex, HospitalitySector $hospitalitySector): array
    {
        $hardTotalConstructionCost = $hardConstructionCost * (1 + ($hardContingencyRate / 100));

        return $this->calculateConstructionExecution($hardTotalConstructionCost, $hardExecutionMethod, $hardConstructionStartDateAsIndex, $duration, $dateWithDateIndex, $hospitalitySector);
    }

    protected function calculateConstructionExecution(float $hardTotalConstructionCost, string $hardExecutionMethod, int $hardConstructionStartDateAsIndex, int $duration, array $dateWithDateIndex, ?HospitalitySector $hospitalitySector = null): array
    {
        switch ($hardExecutionMethod) {
            case 'straight-line':
                // $hardConstructionStartDateAsString = $dateIndexWithDate[$hardConstructionStartDateAsIndex];
                $straightMethodService = new StraightMethodService;

                return $straightMethodService->calculateStraightAmount($hardTotalConstructionCost, $hardConstructionStartDateAsIndex, $duration);
            case 's-curve':
                $sCurveService = new SCurveService;
                $startDateAsIndex = $hospitalitySector->getHardConstructionStartDateAsIndex($dateWithDateIndex);

                return $sCurveService->__calculate($hardTotalConstructionCost, $duration, $startDateAsIndex);
            default:
                return [];
        }
    }

    protected function calculateEquityFundingAmount(float $totalHardConstructionCost, float $hardEquityFundingRate)
    {
        return $totalHardConstructionCost * ($hardEquityFundingRate / 100);
    }

    protected function calculateTotalHardConstructionCost(float $hardConstructionCost, float $hardContingencyRate)
    {
        return $hardConstructionCost * (1 + ($hardContingencyRate / 100));
    }

    public function calculateHardConstructionEquityPayment(array $hardConstructionPayments, float $hardConstructionCost, float $hardContingencyRate, float $hardEquityFundingRate)
    {
        $totalHardConstructionCost = $this->calculateTotalHardConstructionCost($hardConstructionCost, $hardContingencyRate);
        $equityFundingAmount = $this->calculateEquityFundingAmount($totalHardConstructionCost, $hardEquityFundingRate);
        $hardConstructionEquityPayment = [];
        $remainingEquityFunding = [];
        $firstLoop = true;
        foreach ($hardConstructionPayments as $dateIndex => $hardConstructionPaymentValue) {
            $nextDateIndex = getNextDate($hardConstructionPayments, $dateIndex);
            if ($firstLoop) {
                $remainingEquityFunding[$dateIndex] = $equityFundingAmount;
                $firstLoop = false;
            }
            if ($remainingEquityFunding[$dateIndex] >= $hardConstructionPaymentValue) {
                $hardConstructionEquityPayment[$dateIndex] = $hardConstructionPaymentValue;
                if ($nextDateIndex) {
                    $remainingEquityFunding[$nextDateIndex] = $remainingEquityFunding[$dateIndex] - $hardConstructionEquityPayment[$dateIndex];
                }
            } else {
                $hardConstructionEquityPayment[$dateIndex] = $remainingEquityFunding[$dateIndex];
                if ($nextDateIndex) {
                    $remainingEquityFunding[$nextDateIndex] = $remainingEquityFunding[$dateIndex] - $hardConstructionEquityPayment[$dateIndex];
                }
            }
        }

        return $hardConstructionEquityPayment;
    }

    public function calculateHardConstructionLoanWithdrawal(array $hardConstructionPayments, float $hardConstructionCost, float $hardContingencyRate, float $equityFundingRate)
    {
        $totalHardConstructionCost = $this->calculateTotalHardConstructionCost($hardConstructionCost, $hardContingencyRate);

        $equityFundingAmount = $this->calculateEquityFundingAmount($totalHardConstructionCost, $equityFundingRate);
        $hardConstructionLoanWithdrawal = [];
        $isFirstNestedIf = true;
        $previousBalance = 0;
        foreach ($hardConstructionPayments as $dateAsIndex => $landPayment) {
            $equityPaymentBalance[$dateAsIndex] = $equityFundingAmount - $landPayment;
            $equityFundingAmount = $equityPaymentBalance[$dateAsIndex];
            if ($equityPaymentBalance[$dateAsIndex] < 0) {
                if ($isFirstNestedIf) {
                    $hardConstructionLoanWithdrawal[$dateAsIndex] = $equityPaymentBalance[$dateAsIndex] * -1;
                } else {
                    $hardConstructionLoanWithdrawal[$dateAsIndex] = ($equityPaymentBalance[$dateAsIndex] - $previousBalance) * -1;
                }
                $isFirstNestedIf = false;
            }
            // the equity balance after the PREVIOUS PAYMENT (payments are not always in month order, nor in consecutive months)
            $previousBalance = $equityPaymentBalance[$dateAsIndex];
        }
        if (array_sum($hardConstructionLoanWithdrawal) > -1 && array_sum($hardConstructionLoanWithdrawal) < 1) {
            return [];
        }

        return $hardConstructionLoanWithdrawal;
    }
}
