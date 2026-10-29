<?php

namespace App\Support;

use App\Calculations\Date;
use App\Support\LegacyCarbon;
use App\Support\LegacyMath;
use Carbon\Carbon;

class HDate
{
    public static function formatDateFromDatePicker(?string $date): ?string
    {
        $originDate = $date;
        if (! $date) {
            return null;
        }
        $date = explode('/', $date);
        if (isset($date[1])) {
            return $date[2].'-'.$date[1].'-'.$date[0];
        }

        return $originDate;
    }

    public static function generateUniqueDateTimeForModel(string $fullClassName, string $columnName, string $dateTime, array $additionalConditions)
    {
        return self::searchForUnique($fullClassName, $columnName, $dateTime, $additionalConditions);
    }

    public static function searchForUnique(string $fullClassName, string $columnName, string $dateTime, array $additionalConditions)
    {
        $query = $fullClassName::where($columnName, $dateTime);
        foreach ($additionalConditions as $condition) {
            $additionalColumnName = $condition[0];
            $operation = $condition[1];
            $value = $condition[2];
            $query->where($additionalColumnName, $operation, $value);
        }
        $isExist = $query->exists();
        if ($isExist) {
            $dateTime = LegacyCarbon::make($dateTime)->addSecond()->format('Y-m-d H:i:s');

            return self::searchForUnique($fullClassName, $columnName, $dateTime, $additionalConditions);
        }

        return $dateTime;
    }

    public static function allDatesGreaterThanOrEqual(array $dates, ?string $checkDate = null)
    {
        if (is_null($checkDate)) {
            return false;
        }
        foreach ($dates as $date) {
            $currentDate = LegacyCarbon::make($date);
            if (is_null($currentDate)) {
                return false;
            }
            $lessThan = $currentDate->lessThan(LegacyCarbon::make($checkDate) ?? 'now');
            if ($lessThan) {
                return false;
            }
        }

        return true;
    }

    public static function generateStartDateAndEndDateBetween(string $startDate, string $endDate): array
    {

        $result = [];
        $dates = generateDatesBetweenTwoDates(LegacyCarbon::make($startDate), LegacyCarbon::make($endDate));
        $currentStartDate = null;
        foreach ($dates as $startDate) {
            $startDate = $currentStartDate ?: $startDate;
            $endDateOfCurrentMonth = LegacyCarbon::make($startDate)->endOfMonth()->format('Y-m-d');
            if (LegacyCarbon::make($endDateOfCurrentMonth)->greaterThan(LegacyCarbon::make($endDate) ?? 'now')) {
                $endDateOfCurrentMonth = $endDate;
            }
            $result[] = ['start_date' => $startDate, 'end_date' => $endDateOfCurrentMonth];
            $currentStartDate = LegacyCarbon::make($endDateOfCurrentMonth)->addDay()->format('Y-m-d');
        }

        return $result;

    }

    public static function generateEndOfMonthsDatesBetweenTwoDates(Carbon $startDate, Carbon $endDate)
    {
        $startDate = $startDate->endOfMonth();
        $endDate = $endDate->endOfMonth();
        $currentDate = $startDate;
        $intervalDates = [
            $currentDate->format('Y-m-d'),
        ];

        while ($endDate->greaterThan($currentDate)) {
            $intervalDates[] = $currentDate->addMonthWithNoOverflow()->endOfMonth()->format('Y-m-d');
        }

        return $intervalDates;

    }

    public static function generateDatesBetweenStartDateAndDuration(int $currentStartDateAsIndex, string $startDate, int $duration, string $intervalName, bool $isAtEnd = true)
    {
        $dateService = new Date;

        $intervalValue = 12;
        switch ($intervalName) {
            case 'monthly':
                $intervalValue = 1;
                break;
            case 'quartly':
                $intervalValue = 3;
                break;
            case 'semi annually':
                $intervalValue = 6;
                break;
        }
        $startDateDay = explode('-', $startDate)[0];
        if ($startDateDay > 31) {
            throw new \Exception('custom error .. invalid date '.$startDateDay);
        }
        $result = [];
        if ($isAtEnd) {
            for ($i = 0; $i <= ($duration / $intervalValue); $i++) {
                $result[$currentStartDateAsIndex] = $dateService->addMonths($startDateDay, $startDate, $i * $intervalValue);
                $currentStartDateAsIndex += $intervalValue;
            }
        } else {
            for ($i = 0; $i <= ($duration / $intervalValue); $i++) {
                $result[$currentStartDateAsIndex] = $dateService->addMonths($startDateDay, $startDate, $i * $intervalValue);
                $currentStartDateAsIndex += $intervalValue;
            }
        }

        return $result;

    }

    /**
     * How many MONTHS after the loan start the first installment falls: one installment interval (1, 3 or 6 months) plus the grace
     * period, rounded up to a whole number of intervals so that it lands on a date of the schedule.
     * Monthly, no grace: 1. Quarterly, no grace: 3. Semi-annual, no grace: 6. Monthly, 12 months of grace: 13.
     */
    public static function installmentStartShift(float $gracePeriodMonths, int $intervalMonths): int
    {
        $intervalMonths = max(1, $intervalMonths);

        return (int) (ceil(max(0, $gracePeriodMonths) / $intervalMonths) * $intervalMonths) + $intervalMonths;
    }

    public static function getDateAfterIndex(array $datesAsIndexString, array $datesAsStringIndex, string $date, int $numberOfShifts)
    {
        $index = $datesAsStringIndex[$date];

        return $datesAsIndexString[$index + $numberOfShifts] ?? null;
    }

    public static function calculateDaysCountAtEnd(array $items, int $intervalValue, ?int $currentDaysCount = null): array
    {
        $currentDayCount = 0;
        $dayCounts = [];
        $secondDate = null;
        $loopIndex = 0;
        foreach ($items as $currentDateIndex => $dateAsString) {

            if ($loopIndex == 0) {
                $dayCounts[$currentDateIndex] = $currentDayCount;
                $loopIndex++;
            } else {
                if (! is_null($currentDaysCount)) {
                    $dayCounts[$currentDateIndex] = $currentDaysCount;

                    continue;
                }
                $secondDate = $dateAsString;
                $firstDate = $items[$currentDateIndex - $intervalValue];
                $secondDateTime = strtotime($secondDate.' 00:00:00');
                $firstDateTime = strtotime($firstDate.' 00:00:00');
                $result = $secondDateTime - $firstDateTime;
                $result = LegacyMath::round(($result) / (60 * 60 * 24));
                $dayCounts[$currentDateIndex] = (int) $result;
            }
        }

        return $dayCounts;

    }

    public static function calculateDaysCountAtBeginning(array $items, ?int $currentDaysCount = null): array
    {
        $currentDayCount = 0;
        $dayCounts = [];
        $secondDate = null;
        $loopIndex = 0;
        foreach ($items as $currentDateIndex => $dateAsString) {

            if (! is_null($currentDaysCount)) {
                $dayCounts[$currentDateIndex] = $currentDaysCount;

                continue;
            }
            $secondDate = $items[$currentDateIndex + 1] ?? null;

            $firstDate = $dateAsString;
            if (! is_null($secondDate)) {
                $secondDateTime = strtotime($secondDate.' 00:00:00');
                $firstDateTime = strtotime($firstDate.' 00:00:00');
                $result = $secondDateTime - $firstDateTime;
                $result = LegacyMath::round(($result) / (60 * 60 * 24));
                $dayCounts[$currentDateIndex] = (int) $result;
            }

        }

        return $dayCounts;

    }

    public static function convertDateIndexArrayToDateString(array $payload, array $dateIndexWithDate)
    {
        $result = [];
        foreach ($payload as $dateIndex => $value) {
            $dateAsString = $dateIndexWithDate[$dateIndex];
            $result[$dateAsString] = $value;
        }

        return $result;
    }
}
