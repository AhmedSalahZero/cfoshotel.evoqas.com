<?php

namespace App\Calculations;

use App\Support\LegacyCarbon;
use App\Support\LegacyMath;

class CollectionPolicyService
{
    public function applyCollectionPolicy($hasCollectionPolicy, ?string $collectionPolicyType, $collectionPolicyValue, array $dateValue, array $dateIndexWithDate, array $dateWithDateIndex, $hospitalitySector)
    {
        // remove $hospitalitySector parameter if you copied this function into another one
        $collections = [];
        if (! $hasCollectionPolicy) {
            // reset Collection Policy
            foreach ($dateValue as $date => $value) {
                $collections[$date] = 0;
            }
        } elseif ($collectionPolicyType == 'customize') {
            $ratesWithDueDays = $this->formatRatesWithDueDays($collectionPolicyValue);
            foreach ($dateValue as $currentDate => $target) {
                foreach ($ratesWithDueDays as $dueDay => $rate) {
                    $rate = $rate / 100;
                    $actualMonthsNumbers = $dueDay < 30 ? 0 : LegacyMath::round((($dueDay) / 30));
                    $dateAsString = $dateIndexWithDate[$currentDate];
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
        } elseif ($collectionPolicyType == 'system_default' && is_string($collectionPolicyValue)) {
            $collections = $this->systemDefault($dateValue, $collectionPolicyValue, $hospitalitySector);
        }

        return $collections;
    }

    protected function getPeriodsForStartMonths($interval)
    {
        if ($interval == 'monthly') {
            return [
                1 => [1],
                2 => [2],
                3 => [3],
                4 => [4],
                5 => [5],
                6 => [6],
                7 => [7],
                8 => [8],
                9 => [9],
                10 => [10],
                11 => [11],
                12 => [12],
            ];
        }

        if ($interval == 'quarterly') {
            return [
                1 => [1, 2, 3], 4 => [4, 5, 6], 7 => [7, 8, 9], 10 => [10, 11, 12],
            ];
        }
        if ($interval == 'semi-annually') {
            return [
                1 => [1, 2, 3, 4, 5, 6], 7 => [7, 8, 9, 10, 11, 12],
            ];
        }

        if ($interval == 'annually') {
            return [
                1 => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
            ];
        }
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

    protected function getSumMonth($month, $mapMonths)
    {
        foreach ($mapMonths as $sumMonth => $sumMonths) {
            if (in_array($month, $sumMonths)) {
                return $sumMonth;
            }
        }
    }

    public function systemDefault($collections_dues, $interval, $hospitalitySector = null)
    {
        $collection = [];
        $plan_collection_interval = $this->IntervalNumber($interval);
        $interval_key = $plan_collection_interval - 1;
        $count = 1;
        $total_interval = 0;
        $total_interval_num = @count(@$collections_dues);

        $key = 0;

        $dates = array_keys($collections_dues);
        foreach ($collections_dues as $date => $value) {
            if ($count === $total_interval_num) {
                if ($count % $plan_collection_interval != 0) {
                    for ($i = $count; $i > $count - $plan_collection_interval; $i--) {
                        if ($i % $plan_collection_interval == 0) {
                            $total_interval += $value;
                            $dateIndex = $dates[$i];
                            $collection[$dateIndex] = $total_interval;
                            $total_interval = 0;
                        }
                    }
                } else {
                    $total_interval += $value;
                    $dateIndex = $dates[$key - $interval_key];
                    $collection[$dateIndex] = $total_interval;
                    $total_interval = 0;
                }

                $total_interval = 0;
            } elseif ($count % $plan_collection_interval == 0) {
                $total_interval += $value;
                $dateIndex = $dates[$key - $interval_key];
                $collection[$dateIndex] = $total_interval;
                $total_interval = 0;
            } else {
                $total_interval += $value;
            }
            $count++;
            $key++;
        }

        return $collection;
    }

    protected function IntervalNumber($interval)
    {
        if ($interval == 'monthly') {
            $count = 1;
        } elseif ($interval == 'quartly' || $interval == 'quarterly') {
            $count = 3;
        } elseif ($interval == 'semi-annually' || $interval == 'semi annually') {
            $count = 6;
        } elseif ($interval == 'annually') {
            $count = 12;
        }

        return $count;
    }
}
