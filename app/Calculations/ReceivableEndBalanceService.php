<?php

namespace App\Calculations;

use App\Models\HospitalitySector;

class ReceivableEndBalanceService
{
    public function __calculate(array $dateAndValesSales, array $collection, array $dateIndexWithDate, HospitalitySector $hospitalitySector, float $beginningBalance = 0)
    {
        $salesForIntervals = [
            'monthly' => $dateAndValesSales,
            'quarterly' => sumIntervalsIndexes($dateAndValesSales, 'quarterly', $hospitalitySector->financialYearStartMonth(), $dateIndexWithDate),
            'semi-annually' => sumIntervalsIndexes($dateAndValesSales, 'semi-annually', $hospitalitySector->financialYearStartMonth(), $dateIndexWithDate),
            'annually' => sumIntervalsIndexes($dateAndValesSales, 'annually', $hospitalitySector->financialYearStartMonth(), $dateIndexWithDate),
        ];
        $collectionForInterval = [
            'monthly' => $collection,
            'quarterly' => sumIntervalsIndexes($collection, 'quarterly', $hospitalitySector->financialYearStartMonth(), $dateIndexWithDate),
            'semi-annually' => sumIntervalsIndexes($collection, 'semi-annually', $hospitalitySector->financialYearStartMonth(), $dateIndexWithDate),
            'annually' => sumIntervalsIndexes($collection, 'annually', $hospitalitySector->financialYearStartMonth(), $dateIndexWithDate),
        ];

        $result = [];
        foreach (getIntervalFormatted() as $intervalName => $intervalNameFormatted) {
            $beginningBalance = 0;
            foreach ($salesForIntervals[$intervalName] as $dateIndex => $value) {

                $date = $dateIndex;
                $result[$intervalName]['beginning_balance'][$date] = $beginningBalance;
                $totalDue[$date] = $value + $beginningBalance;
                $collectionAtDate = $collectionForInterval[$intervalName][$date] ?? 0;
                $endBalance[$date] = $totalDue[$date] - $collectionAtDate;
                $beginningBalance = $endBalance[$date];
                $result[$intervalName]['revenues'][$date] = $value;
                $result[$intervalName]['total_due'][$date] = $totalDue[$date];
                $result[$intervalName]['collection'][$date] = $collectionAtDate;
                $result[$intervalName]['end_balance'][$date] = $endBalance[$date];
            }
        }

        return [
            'monthlyReport' => $result['monthly'] ?? [],
            'intervalsReport' => $result,
        ];

    }
}
