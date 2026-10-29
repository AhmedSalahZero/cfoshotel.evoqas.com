<?php

namespace App\Calculations;

use App\Models\HospitalitySector;

class ProjectsUnderProgress
{
    public function calculateForConstruction(array $hardConstructionExecution, array $softConstructionExecution, array $loanInterestOfHardConstruction, array $withdrawalInterestOfHardConstruction, HospitalitySector $hospitalitySector, int $operationStartDateAsIndex, array $datesAsStringAndIndex, array $datesIndexWithYearIndex, array $yearIndexWithYear, array $dateIndexWithDate): array
    {
        $studyDates = $hospitalitySector->getStudyDateFormatted($datesAsStringAndIndex, $datesIndexWithYearIndex, $yearIndexWithYear, $dateIndexWithDate);

        $result = [];
        $beginningBalance = 0;
        $additions = sumTwoArray($hardConstructionExecution, $softConstructionExecution);
        $finalHardExecutionDate = array_key_last($hardConstructionExecution);
        $finalHardExecutionDate = is_numeric($finalHardExecutionDate) ? $finalHardExecutionDate : ($finalHardExecutionDate ? $datesAsStringAndIndex[$finalHardExecutionDate] : null);
        if (is_null($finalHardExecutionDate)) {
            return [];
        }
        $dateBeforeOperation = $operationStartDateAsIndex == 0 ? $operationStartDateAsIndex : $operationStartDateAsIndex - 1;
        $dateBeforeOperation = $dateBeforeOperation < 0 ? 0 : $dateBeforeOperation;
        $finalCapitalizedInterestDateAsIndex = $dateBeforeOperation >= $finalHardExecutionDate ? $dateBeforeOperation : $finalHardExecutionDate;
        $softEndDate = array_key_last($softConstructionExecution);
        $softEndDate = is_numeric($softEndDate) ? $softEndDate : $datesAsStringAndIndex[$softEndDate];
        /**
         * ! Largest
         */
        $transferredToFixedAssetDateAsIndex = $softEndDate >= $finalCapitalizedInterestDateAsIndex ? $softEndDate : $finalCapitalizedInterestDateAsIndex;
        $transferredToFixedAssetDateAsIndex = is_null($transferredToFixedAssetDateAsIndex) ? $operationStartDateAsIndex : $transferredToFixedAssetDateAsIndex;

        $capitalizedInterest = $hospitalitySector->sumTwoArrayUntilIndex($withdrawalInterestOfHardConstruction, $loanInterestOfHardConstruction, $finalCapitalizedInterestDateAsIndex);
        foreach ($studyDates as $dateAsIndex) {
            $result['beginning_balance'][$dateAsIndex] = $beginningBalance;
            $additionsAtDate = $additions[$dateAsIndex] ?? 0;
            $result['additions'][$dateAsIndex] = $additionsAtDate;
            $capitalizedInterestAtDate = $capitalizedInterest[$dateAsIndex] ?? 0;
            $capitalizedInterestAtDate = isset($withdrawalInterestOfHardConstruction[$dateAsIndex]) ? $withdrawalInterestOfHardConstruction[$dateAsIndex] : $capitalizedInterestAtDate;
            $result['capitalized_interest'][$dateAsIndex] = $capitalizedInterestAtDate;
            $total = $beginningBalance + $additionsAtDate + $capitalizedInterestAtDate;
            $result['total'][$dateAsIndex] = $total;
            $beginningBalance = $total;
            if ($dateAsIndex == ($transferredToFixedAssetDateAsIndex + 1)) {
                $result['transferred_date_and_vales'][$dateAsIndex] = $total;
                $result['end_balance'][$dateAsIndex] = $total - $result['transferred_date_and_vales'][$dateAsIndex];
                break;
            } else {
                $result['transferred_date_and_vales'][$dateAsIndex] = 0;
                $result['end_balance'][$dateAsIndex] = $total;
            }
        }

        return $result;
    }

    public function calculateForFFE(array $ffeExecutionAndPayment, array $ffeLoanInterestAmount, array $ffeLoanWithdrawalInterestAmounts, HospitalitySector $hospitalitySector, int $operationStartDateAsIndex, array $datesAsStringAndIndex, array $datesIndexWithYearIndex, array $yearIndexWithYear, array $dateIndexWithDate, array $dateWithMonthNumber): array
    {

        $studyDurationPerYear = $hospitalitySector->getStudyDurationPerYear($datesAsStringAndIndex, $datesIndexWithYearIndex, $yearIndexWithYear, $dateIndexWithDate, $dateWithMonthNumber, true, true, false);
        $studyDates = $hospitalitySector->getOnlyDatesOfActiveStudy($studyDurationPerYear, $dateIndexWithDate);

        $result = [];
        $beginningBalance = 0;
        $additions = sumTwoArray($ffeExecutionAndPayment, []);

        $finalFFEExecutionDateAsIndex = array_key_last($ffeExecutionAndPayment);
        if (is_null($finalFFEExecutionDateAsIndex)) {
            return [];
        }
        // $finalFFEExecutionDateAsIndex = $hospitalitySector->convertDateStringToDateIndex($finalFFEExecutionDateAsIndex);
        $dateBeforeOperation = $operationStartDateAsIndex == 0 ? $operationStartDateAsIndex : $operationStartDateAsIndex - 1;
        $dateBeforeOperation = $dateBeforeOperation < 0 ? 0 : $dateBeforeOperation;
        $finalCapitalizedInterestDateAsIndex = $dateBeforeOperation >= $finalFFEExecutionDateAsIndex ? $dateBeforeOperation : $finalFFEExecutionDateAsIndex;
        $transferredToFixedAssetDateAsIndex = $finalCapitalizedInterestDateAsIndex;

        $capitalizedInterest = $hospitalitySector->sumTwoArrayUntilIndex($ffeLoanWithdrawalInterestAmounts, $ffeLoanInterestAmount, $finalCapitalizedInterestDateAsIndex);
        foreach ($studyDates as $dateAsIndex) {
            $result['beginning_balance'][$dateAsIndex] = $beginningBalance;
            $additionsAtDate = $additions[$dateAsIndex] ?? 0;
            $result['additions'][$dateAsIndex] = $additionsAtDate;
            $capitalizedInterestAtDate = $capitalizedInterest[$dateAsIndex] ?? 0;
            $result['capitalized_interest'][$dateAsIndex] = $capitalizedInterestAtDate;
            $total = $beginningBalance + $additionsAtDate + $capitalizedInterestAtDate;
            $result['total'][$dateAsIndex] = $total;
            $beginningBalance = $total;
            if ($dateAsIndex == ($transferredToFixedAssetDateAsIndex + 1)) {
                $result['transferred_date_and_vales'][$dateAsIndex] = $total;
                $result['end_balance'][$dateAsIndex] = $total - $result['transferred_date_and_vales'][$dateAsIndex];
                break;
            } else {
                $result['transferred_date_and_vales'][$dateAsIndex] = 0;
                $result['end_balance'][$dateAsIndex] = $total;
            }
        }

        return $result;
    }
}
