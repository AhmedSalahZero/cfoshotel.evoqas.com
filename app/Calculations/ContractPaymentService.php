<?php

namespace App\Calculations;

use App\Support\LegacyCarbon;
use App\Support\LegacyMath;

class ContractPaymentService
{
    public function __calculate(float $hardConstructionCost, float $hardContingencyRate, array $executionAmounts, int $startDateAsIndex, float $downPaymentRateOne, array $collectionPolicyValue, array $dateIndexWithDate, array $dateWithDateIndex)
    {
        $hardTotalConstructionCost = $hardConstructionCost * (1 + ($hardContingencyRate / 100));
        $ratesWithDueDays = $this->formatRatesWithDueDays($collectionPolicyValue);
        $downPaymentOneAmount = $hardTotalConstructionCost * ($downPaymentRateOne / 100);
        $collections[$startDateAsIndex] = $downPaymentOneAmount;
        $dateValue = $executionAmounts;
        foreach ($dateValue as $currentDate => $target) {
            foreach ($ratesWithDueDays as $dueDay => $rate) {
                $rate = $rate / 100;
                $actualMonthsNumbers = $dueDay < 30 ? 0 : LegacyMath::round((($dueDay) / 30));
                $dateAsString = is_numeric($currentDate) ? $dateIndexWithDate[$currentDate] : $currentDate;
                $currentDateObject = LegacyCarbon::make($dateAsString);
                $date = $currentDateObject->addMonths($actualMonthsNumbers);
                $month = $date->format('m');
                $year = $date->format('Y');
                $day = $date->format('d');
                $fullDate = $day.'-'.$month.'-'.$year;
                $dateIndex = $dateWithDateIndex[$fullDate];
                $collections[$dateIndex] = ($target * $rate) + ($collections[$dateIndex] ?? 0);
            }
        }

        return $collections;
    }

    protected function formatRatesWithDueDays(array $ratesAndDueDays): array
    {
        $result = [];
        foreach ($ratesAndDueDays['due_in_days'] ?? [] as $index => $dueDay) {
            $rate = $ratesAndDueDays['rate'][$index] ?? 0;
            if ($rate) {
                if (isset($result[$dueDay])) {
                    $result[$dueDay] += $rate;
                } else {
                    $result[$dueDay] = $rate;
                }
            }
        }

        return $result;
    }
}
