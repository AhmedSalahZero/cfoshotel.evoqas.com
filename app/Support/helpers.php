<?php

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Global helper functions of the hotel module
//  Location: app/Support/helpers.php   (loaded by composer.json "autoload.files")
//
//  Small functions for dates, arrays and totals, copied from the previous CFO Hotels
//  (app/Helpers/helpers.php). Only the ones the hotel models and calculations call are
//  here; the others are added with the step that needs them.
//  Every function is wrapped in function_exists() so a second copy can never clash.
// ══════════════════════════════════════════════════════════════════

use App\Calculations\IntervalSummationOperations;
use App\Support\LegacyCarbon;
use App\Support\LegacyMath;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// Constants of the hotel module (global, as in the previous application).
if (! defined('LAND_LOAN')) {
    define('LAND_LOAN', 'land');
}
if (! defined('FFE_COST')) {
    define('FFE_COST', 'ffe_cost');
}
if (! defined('HARD_COST_CONSTRUCTION')) {
    define('HARD_COST_CONSTRUCTION', 'hard_cost_construction');
}
if (! defined('PROPERTY_LOAN')) {
    define('PROPERTY_LOAN', 'property_loan');
}
if (! defined('BASE_MANAGEMENT_FEES_AS_PERCENTAGE_FROM_REVENUES')) {
    define('BASE_MANAGEMENT_FEES_AS_PERCENTAGE_FROM_REVENUES', 'base_management_fees_as_percentage_from_revenues');
}
if (! defined('INCENTIVE_MANAGEMENT_FEES_AS_PERCENTAGE_FROM_REVENUES')) {
    define('INCENTIVE_MANAGEMENT_FEES_AS_PERCENTAGE_FROM_REVENUES', 'incentive_management_fees_as_percentage_from_revenues');
}
if (! defined('guest_capture_x_cover_value_per_guest_method')) {
    define('guest_capture_x_cover_value_per_guest_method', 'guest_capture_x_cover_value_per_guest_method');
}
if (! defined('guest_capture_x_meals_per_guest_x_cover_value_per_meal_method')) {
    define('guest_capture_x_meals_per_guest_x_cover_value_per_meal_method', 'guest_capture_x_meals_per_guest_x_cover_value_per_meal_method');
}
if (! defined('cover_count_target_per_day_x_cover_value_method')) {
    define('cover_count_target_per_day_x_cover_value_method', 'cover_count_target_per_day_x_cover_value_method');
}
if (! defined('percentage_from_rooms_revenue')) {
    define('percentage_from_rooms_revenue', 'percentage_from_rooms_revenue');
}
if (! defined('guest_capture_casino_charges_per_guest_method')) {
    define('guest_capture_casino_charges_per_guest_method', 'guest_capture_casino_charges_per_guest_method');
}
if (! defined('guest_count_target_per_day_charges_per_guest_method')) {
    define('guest_count_target_per_day_charges_per_guest_method', 'guest_count_target_per_day_charges_per_guest_method');
}
if (! defined('guest_count_charges_per_guest_occupancy_rate_method')) {
    define('guest_count_charges_per_guest_occupancy_rate_method', 'guest_count_charges_per_guest_occupancy_rate_method');
}
if (! defined('facility_count_daily_rent_occupancy_rate_method')) {
    define('facility_count_daily_rent_occupancy_rate_method', 'facility_count_daily_rent_occupancy_rate_method');
}
if (! defined('percentage_from_f_b_revenue')) {
    define('percentage_from_f_b_revenue', 'percentage_from_f_b_revenue');
}
if (! defined('guest_capture_x_charges_per_guest_method')) {
    define('guest_capture_x_charges_per_guest_method', 'guest_capture_x_charges_per_guest_method');
}
if (! defined('variable_expenses_as_percentage_from_rooms_revenues')) {
    define('variable_expenses_as_percentage_from_rooms_revenues', 'variable_expenses_as_percentage_from_rooms_revenues');
}
if (! defined('variable_expenses_as_cost_per_night_sold')) {
    define('variable_expenses_as_cost_per_night_sold', 'variable_expenses_as_cost_per_night_sold');
}
if (! defined('variable_expenses_as_cost_per_guest')) {
    define('variable_expenses_as_cost_per_guest', 'variable_expenses_as_cost_per_guest');
}
if (! defined('disposable_expenses_rate')) {
    define('disposable_expenses_rate', 'disposable_expenses_rate');
}
if (! defined('rooms_manpower_expense')) {
    define('rooms_manpower_expense', 'rooms_manpower_expense');
}
if (! defined('gaming')) {
    define('gaming', 'gaming');
}
if (! defined('other_property_fixed_expenses')) {
    define('other_property_fixed_expenses', 'other_property_fixed_expenses');
}
if (! defined('fixed_monthly_expenses')) {
    define('fixed_monthly_expenses', 'fixed_monthly_expenses');
}
if (! defined('variable_expenses_as_percentage_from_total_revenues')) {
    define('variable_expenses_as_percentage_from_total_revenues', 'variable_expenses_as_percentage_from_total_revenues');
}
if (! defined('start_up_cost')) {
    define('start_up_cost', 'start_up_cost');
}
if (! defined('pre_operating_expense')) {
    define('pre_operating_expense', 'pre_operating_expense');
}
if (! defined('MAX_YEARS_COUNT')) {
    define('MAX_YEARS_COUNT', 20);
}
if (! defined('PROPERTY_ACQUISITION')) {
    define('PROPERTY_ACQUISITION', 'property_acquisition');
}

if (! function_exists('dateFormating')) {
function dateFormating($date, $formate = 'd-m-Y')
    {
        return date($formate, strtotime($date));
    }
}

if (! function_exists('formatOptionsForSelect')) {
function formatOptionsForSelect(Collection $items, $idFun = 'getId', $valueFun = 'getName'): array
{
    $ids = [];
    $formattedData = [];
    foreach ($items as $item) {
        $id = $item->$idFun();
        if (! in_array($id, $ids)) {
            $ids[] = $id;
            $formattedData[] = [
                'value' => $id,
                'title' => $item->$valueFun(),
            ];
        }

    }

    return $formattedData;
}
}

if (! function_exists('generateDatesBetweenTwoDates')) {
function generateDatesBetweenTwoDates(Carbon $start_date, Carbon $end_date, $method = 'addMonth', $format = 'Y-m-d', $indexedArray = true, $indexFormat = 'Y-m-d')
{
    $dates = [];

    for ($date = $start_date->copy(); $date->lte($end_date); $date->{$method}()) {
        if ($indexedArray) {
            $dates[] = $date->format($format);
        } else {
            $dates[$date->format($indexFormat)] = $date->format($format);
        }
    }

    return $dates;
}
}

if (! function_exists('convertJsonToArray')) {
function convertJsonToArray($jsonString)
{
    if (! $jsonString) {
        return [];
    }
    if (is_array($jsonString)) {
        return $jsonString;
    }

    return (array) json_decode($jsonString);
}
}

if (! function_exists('getAmount')) {
function getAmount($str)
{
    return preg_replace('/([^0-9\\.])/i', '', $str);
}
}

if (! function_exists('getYearFromDate')) {
function getYearFromDate(string $date)
{
    return explode('-', $date)[2];
}
}

if (! function_exists('getDayFromDate')) {
function getDayFromDate(string $date)
{
    return explode('-', $date)[0];
}
}

if (! function_exists('getMonthFromDate')) {
function getMonthFromDate(string $date)
{
    return explode('-', $date)[1];
}
}

if (! function_exists('sumAllOfDates')) {
function sumAllOfDates(array $items)
{
    $result = [];
    foreach ($items as $id => $datesAndValues) {
        foreach ($datesAndValues as $date => $value) {
            $result[$date] = isset($result[$date]) ? $result[$date] + $value : $value;
        }
    }

    return $result;
}
}

if (! function_exists('applyInflationRate')) {
function applyInflationRate(float $base, float $percentage, float $power)
{

    return $base * pow(1 + ($percentage / 100), $power);
}
}

if (! function_exists('getValueOfFirstKey')) {
function getValueOfFirstKey(array $items, $makeArrayKeysAsValues = true): array
{
    $firstKeyAtArray = array_keys($items)[0] ?? null;
    if (is_null($firstKeyAtArray)) {
        return [];
    }
    if ($makeArrayKeysAsValues) {
        return array_keys($items[$firstKeyAtArray]);
    }

    return $items[$firstKeyAtArray];

}
}

if (! function_exists('sumForEachYear')) {
function sumForEachYear(array $items, array $datesIndexWithYearIndex)
{
    // pass -1 to $hospitalitySector if non

    $result = [];

    foreach ($items as $date => $value) {
        if ($date != 'subItems' && $date != 'total') {
            $year = $datesIndexWithYearIndex[$date];
            $result[$year] = isset($result[$year]) ? $result[$year] + $value : $value;
        }
    }

    return $result;
}
}

if (! function_exists('getYearsFromDates')) {
function getYearsFromDates(array $dates)
{

    $result = [];
    foreach ($dates as $date) {
        $year = getYearFromDate($date);
        $result[$year] = $year;
    }
    $result = array_values($result);

    // $message = microtime(true)-$x;
    // Log::info($message);
    return $result;
}
}

if (! function_exists('getTotalOfArraysOf2Depth')) {
function getTotalOfArraysOf2Depth(array $items, bool $debug = false)
{
    $totalAtDates = [];
    foreach ($items as $identifier => $dateAndValues) {

        ksort($dateAndValues);

        foreach ($dateAndValues as $date => $value) {
            if (is_numeric($value)) {
                $totalAtDates[$date] = isset($totalAtDates[$date]) ? $totalAtDates[$date] + $value : $value;
            }
        }
    }

    return $totalAtDates;
}
}

if (! function_exists('sortDatesAsIndex')) {
function sortDatesAsIndex(array $payload)
{
    uksort($payload, 'date_compare');

    return $payload;
}
}

if (! function_exists('date_compare')) {
function date_compare($a, $b)
{
    $t1 = strtotime($a);
    $t2 = strtotime($b);

    return $t1 - $t2;
}
}

if (! function_exists('repeatLastKeyForUnExistingKeysIn')) {
function repeatLastKeyForUnExistingKeysIn(array $payload, array $dates)
{

    if (! count($payload)) {
        return [];
    }
    $repeatedArray = [];
    $lastIndex = array_key_last($payload);
    $lastValue = $payload[$lastIndex];
    $dates = makeArrayKeysEqualToIndexes($dates);
    foreach ($dates as $index => $dateAsIndex) {
        if (isset($payload[$index])) {
            $repeatedArray[$index] = $payload[$index];
        } elseif ($index > $lastIndex) {
            $repeatedArray[$dateAsIndex] = $lastValue;
        }

    }

    return $repeatedArray;
}
}

if (! function_exists('totalOf5Arr')) {
function totalOf5Arr(array $totalRoomsRevenue, array $totalFAndBFacilityRevenue, array $totalCasinoFacilityRevenue, array $totalMeetingFacilityRevenue, array $totalOtherFacilityRevenue, array $dates): array
{
    $result = [];
    foreach ($dates as $date) {
        $totalOfRoomAtDate = $totalRoomsRevenue[$date] ?? 0;
        $totalOfFoodsAtDate = $totalFAndBFacilityRevenue[$date] ?? 0;
        $totalOfGamingsAtDate = $totalCasinoFacilityRevenue[$date] ?? 0;
        $totalOfMeetingsAtDate = $totalMeetingFacilityRevenue[$date] ?? 0;
        $totalOfOtherRevenueAtDate = $totalOtherFacilityRevenue[$date] ?? 0;
        $result[$date] = $totalOfRoomAtDate + $totalOfFoodsAtDate + $totalOfGamingsAtDate + $totalOfMeetingsAtDate + $totalOfOtherRevenueAtDate;

    }

    return $result;
}
}

if (! function_exists('arrayToValueIndexes')) {
function arrayToValueIndexes(?array $items): array
{

    if ($items && count($items)) {
        return $items;
    }

    return [];
}
}

if (! function_exists('repeatJson')) {
function repeatJson($jsonItems)
{

    $itemsArray = convertJsonToArray($jsonItems);
    if (! count($itemsArray)) {
        return null;
    }
    $lastKey = array_key_last($itemsArray);
    if (! is_numeric($lastKey)) {
        return json_encode($itemsArray);
    }
    $loopingKey = $lastKey + 1;
    for ($loopingKey; $loopingKey < MAX_YEARS_COUNT; $loopingKey++) {
        $itemsArray[$loopingKey] = $itemsArray[$lastKey];
    }

    return json_encode($itemsArray);
}
}

if (! function_exists('getTotalOfTwoDimArr')) {
function getTotalOfTwoDimArr(array $items)
{
    $totals = [];
    foreach ($items as $key => $datesAndValues) {
        foreach ($datesAndValues as $date => $value) {
            $totals[$date] = isset($totals[$date]) ? $totals[$date] + $value : $value;
        }
    }

    return $totals;
}
}

if (! function_exists('sumSecondKeyInThreeDimArr')) {
function sumSecondKeyInThreeDimArr(array $items)
{

    $result = [];
    foreach ($items as $identifier => $intervalWithItems) {
        foreach ($intervalWithItems as $intervalName => $reportTypeWithDatesAndValues) {
            foreach ($reportTypeWithDatesAndValues as $reportType => $datesAndValues) {
                foreach ($datesAndValues as $date => $value) {
                    $result[$intervalName][$reportType][$date] = isset($result[$intervalName][$reportType][$date]) ? $result[$intervalName][$reportType][$date] + $value : $value;
                }
            }
        }
    }

    return $result;
}
}

if (! function_exists('sumSecondKeyInFourDimArr')) {
function sumSecondKeyInFourDimArr(array $arrayOfItems)
{
    $result = [];

    foreach ($arrayOfItems as $name => $items) {

        foreach ($items as $itemKeyName => $reportNameWithItsDatesAndValues) {
            if ($itemKeyName == 'intervalsReport') {
                foreach ($reportNameWithItsDatesAndValues as $quarterName => $reportItems) {
                    foreach ($reportItems as $reportName => $values) {
                        foreach ($values as $date => $value) {

                            $result['intervalsReport'][$quarterName][$reportName][$date] = isset($result['intervalsReport'][$quarterName][$reportName][$date]) ? $result['intervalsReport'][$quarterName][$reportName][$date] + $value : $value;
                        }
                    }
                }

            }

        }
    }

    return $result;
}
}

if (! function_exists('getPreviousDate')) {
function getPreviousDate(?array $array, ?string $date, $datesExistsAsKeys = true)
{

    $searched = array_search($date, $datesExistsAsKeys ? array_keys($array) : $array);
    $arrayPlusOne = $datesExistsAsKeys ? @array_keys($array)[$searched - 1] : @($array)[$searched - 1];
    if ($searched !== null && isset($arrayPlusOne)) {
        return $datesExistsAsKeys ? array_keys($array)[$searched - 1] : ($array)[$searched - 1];
    }

    return null;
}
}

if (! function_exists('getNextDate')) {
function getNextDate(?array $array, ?string $date, $datesExistsAsKeys = true)
{

    $searched = array_search($date, $datesExistsAsKeys ? array_keys($array) : $array);
    $arrayPlusOne = $datesExistsAsKeys ? @array_keys($array)[$searched + 1] : @($array)[$searched + 1];
    if ($searched !== null && isset($arrayPlusOne)) {
        return $datesExistsAsKeys ? array_keys($array)[$searched + 1] : ($array)[$searched + 1];
    }

    return null;
}
}

if (! function_exists('getDifferenceBetweenTwoDatesInDays')) {
function getDifferenceBetweenTwoDatesInDays(Carbon $firstDate, Carbon $secondDate)
{
    return LegacyCarbon::diffInDays($secondDate, $firstDate);
}
}

if (! function_exists('arrayMergeTwoDimArray')) {
function arrayMergeTwoDimArray(...$args)
{
    $mergedArray = [];
    foreach ($args as $index => $array) {
        foreach ($array as $key => $values) {

            $mergedArray[$key] = $values;
        }
    }

    return $mergedArray;
}
}

if (! function_exists('getMonthNumberFromMonthFullName')) {
function getMonthNumberFromMonthFullName($fullMonthName): ?string
{
    $months = [

        'january' => [
            'value' => '01',
            'title' => __('Jan'),
        ], 'february' => [
            'value' => '02',
            'title' => __('Feb'),
        ], 'march' => [
            'value' => '03',
            'title' => __('Mar'),
        ], 'april' => [
            'title' => __('Apr'),
            'value' => '04',
        ], 'may' => [
            'title' => __('May'),
            'value' => '05',
        ], 'june' => [
            'title' => __('Jun'),
            'value' => '06',
        ], 'July' => [
            'title' => __('Jul'),
            'value' => '07',
        ],
        'august' => [
            'title' => __('Aug'),
            'value' => '08',
        ], 'september' => [
            'title' => __('Sep'),
            'value' => '09',
        ], 'october' => [
            'title' => __('Oct'),
            'value' => '10',
        ], 'november' => [
            'title' => __('Nov'),
            'value' => '11',
        ], 'december' => [
            'title' => __('Dec'),
            'value' => '12',
        ],
    ];

    return $months[$fullMonthName]['value'] ?? null;

}
}

if (! function_exists('sort_date')) {
function sort_date($a, $b)
{
    return DateTime::createFromFormat('m-d-Y', $a) <=> DateTime::createFromFormat('m-d-Y', $b);
}
}

if (! function_exists('isValidDateFormat')) {
function isValidDateFormat($date)
{
    if (is_null($date)) {
        return false;
    }

    return isset(explode('-', $date)[2]) && ! isset(explode('-', $date)[3]);

}
}

if (! function_exists('getDateFromTwoArrays')) {
function getDateFromTwoArrays(array $first, array $second)
{
    return array_values(array_unique(array_merge(array_keys($first), array_keys($second))));
}
}

if (! function_exists('subtractTwoArray')) {
function subtractTwoArray(array $first, array $second)
{
    $result = [];
    $dates = getDateFromTwoArrays($first, $second);
    //  array_values(array_unique(array_merge(array_keys($first),array_keys($second))));
    if (! count($dates)) {
        return [];
    }
    $firstIndex = Arr::first($dates);
    if (is_numeric($firstIndex)) {
        asort($dates);
    } elseif (isValidDateFormat($firstIndex)) {
        usort($dates, 'sort_date');
    }
    foreach ($dates as $date) {
        if (is_numeric($date)) {
            $secondVal = $second[$date] ?? 0;
            $value = $first[$date] ?? 0;
            $result[$date] = $value - $secondVal;
        }
    }

    return $result;
}
}

if (! function_exists('sumTwoArray')) {
function sumTwoArray(array $first, array $second)
{
    $result = [];
    $dates = array_values(array_unique(array_merge(array_keys($first), array_keys($second))));
    foreach ($dates as $date) {
        $secondVal = $second[$date] ?? 0;
        $value = $first[$date] ?? 0;
        $result[$date] = $value + $secondVal;
    }

    return $result;
}
}

if (! function_exists('getIntervalFormatted')) {
function getIntervalFormatted(): array
{
    return ['monthly' => __('Monthly'), 'quarterly' => __('Quarterly'), 'semi-annually' => __('Semi-annually'), 'annually' => __('Annually')];
}
}

if (! function_exists('getValueFromArrayStringAndIndex')) {
function getValueFromArrayStringAndIndex(array $items, $dateAsString, $dateAsIndex, $defaultValue = 0)
{
    if (isset($items[$dateAsString])) {
        return $items[$dateAsString];
    }
    if (isset($items[$dateAsIndex])) {
        return $items[$dateAsIndex];
    }

    return $defaultValue;
}
}

if (! function_exists('sumIntervalsIndexes')) {
function sumIntervalsIndexes(array $dateValues, string $intervalName, string $financialYearStartMonth, array $dateIndexWithDate)
{
    return (new IntervalSummationOperations)->sumForInterval($dateValues, $intervalName, $financialYearStartMonth, $dateIndexWithDate, true);
}
}

if (! function_exists('convertValuesToZero')) {
function convertValuesToZero(array $items): array
{
    $newItems = [];
    foreach ($items as $date => $value) {
        $newItems[$date] = 0;
    }

    return $newItems;
}
}

if (! function_exists('getMinAtEveryIndex')) {
function getMinAtEveryIndex(array $keyAndValues)
{
    $result = [];
    foreach ($keyAndValues as $index => $values) {
        $result[$index] = min($values);
    }

    return $result;
}
}

if (! function_exists('eachIndexMinusPreviousIfNegative')) {
function eachIndexMinusPreviousIfNegative(array $items, $debug = false)
{
    $result = [];
    foreach ($items as $dateAsIndex => $value) {
        $previousIndex = $dateAsIndex - 1;
        $previousValue = $items[$previousIndex] ?? 0;
        $newValue = 0;
        if ($value < 0) {
            $newValue = ($value - $previousValue) * -1;
            if ($newValue < 0) {
                $newValue = 0;
            }
        }
        $result[$dateAsIndex] = $newValue;
    }

    return $result;
}
}

if (! function_exists('makeArrayKeysEqualToIndexes')) {
function makeArrayKeysEqualToIndexes($arrItems)
{
    $result = [];
    foreach ($arrItems as $key => $item) {
        $result[$item] = $item;
    }

    return $result;
}
}

if (! function_exists('generateDatesBetweenTwoIndexedDates')) {
function generateDatesBetweenTwoIndexedDates(int $startDateAsIndex, int $endDateAsIndex): array
{
    $result = [];
    for ($i = $startDateAsIndex; $i <= $endDateAsIndex; $i++) {
        $result[] = $i;
    }

    return $result;
}
}

if (! function_exists('calculateReplacementDates')) {
function calculateReplacementDates(array $studyDates, int $operationStartDateAsIndex, int $studyEndDateAsIndex, int $propertyReplacementIntervalInMonths)
{
    $replacementDates = [];
    foreach ($studyDates as $studyDateAsString => $studyDateAsIndex) {
        if ($operationStartDateAsIndex > $studyEndDateAsIndex) {
            break;
        }
        $replacementDates[$studyDateAsIndex] = $operationStartDateAsIndex + $propertyReplacementIntervalInMonths;
        $operationStartDateAsIndex = $replacementDates[$studyDateAsIndex];
    }

    return $replacementDates;
}
}

if (! function_exists('calculateAccumulatedDepreciation')) {
function calculateAccumulatedDepreciation(array $totalMonthlyDepreciation, array $studyDates)
{
    $result = [];
    foreach ($studyDates as $dateIndex) {
        $value = $totalMonthlyDepreciation[$dateIndex] ?? 0;
        $previousDateAsIndex = $dateIndex - 1;
        $result[$dateIndex] = $previousDateAsIndex >= 0 ? $result[$previousDateAsIndex] + $value : $value;
    }

    return $result;
}
}

if (! function_exists('removeDateFrom')) {
function removeDateFrom(array $dateIndexWithDate)
{
    $result = [];
    foreach ($dateIndexWithDate as $dateAsIndex => $dateAsString) {
        $dateExploded = explode('-', $dateAsString);
        $month = $dateExploded[1];
        $year = $dateExploded[2];
        $dateMonthAndYear = $month.'-'.$year;
        $result[$dateMonthAndYear] = $dateAsIndex;
    }

    return $result;
}
}

if (! function_exists('getIdFromRowsWithTotal')) {
function getIdFromRowsWithTotal($rows)
{
    foreach ($rows as $row) {
        if (Str::contains($row->title, 'Total ')) {
            return $row->id;
        }
    }

    // Not found by its English title (the list was created in another language): the Total row is always the first one.
    return $rows->first()->id ?? 0;
}
}

if (! function_exists('getDiffKeysFrom')) {
function getDiffKeysFrom($fromArr, $toArr)
{
    $result = [];
    foreach ($fromArr as $index => $value) {
        if (isset($toArr[$index])) {
            $result[$index] = $fromArr[$index];
        }
    }

    return $result;
}
}

// ── Step 11: the helpers the statements need (copied from the previous application, same results) ──

if (! function_exists('formatDateForView')) {
function formatDateForView(string $date)
{
    return LegacyCarbon::make($date)->format('M\'Y');
}
}

if (! function_exists('convertZeroToTotalString')) {
function convertZeroToTotalString($str)
{
    return $str == 0 ? __('Total') : $str;
}
}

if (! function_exists('removeTotalTableIfFirstIsZero')) {
function removeTotalTableIfFirstIsZero(array $items)
{
    $newItems = [];
    if (count($items) && $items[0] == 0) {
        $newItems[0] = 0;
    } else {
        $newItems = $items;
    }

    return $newItems;
}
}

if (! function_exists('removeKeyFromArray')) {
function removeKeyFromArray(array $items, string $keyName): array
{
    $newItems = [];
    foreach ($items as $key => $value) {
        if ($key != $keyName) {
            $newItems[$key] = $value;
        }
    }

    return $newItems;
}
}

if (! function_exists('sumFourArray')) {
function sumFourArray(array $first, array $second, array $third, array $fourth)
{
    $result = [];
    $dates = array_values(array_unique(array_merge(array_keys($first), array_keys($second), array_keys($third), array_keys($fourth))));
    foreach ($dates as $date) {
        $secondVal = $second[$date] ?? 0;
        $thirdVal = $third[$date] ?? 0;
        $fourthVal = $fourth[$date] ?? 0;
        $value = $first[$date] ?? 0;
        $result[$date] = $value + $secondVal + $thirdVal + $fourthVal;
    }

    return $result;
}
}

if (! function_exists('sumFiveArrays')) {
function sumFiveArrays(array $first, array $second, array $third, array $fourth, array $fifth)
{
    $result = [];
    $dates = array_values(array_unique(array_merge(array_keys($first), array_keys($second), array_keys($third), array_keys($fourth), array_keys($fifth))));
    foreach ($dates as $date) {
        $secondVal = $second[$date] ?? 0;
        $thirdVal = $third[$date] ?? 0;
        $fourthVal = $fourth[$date] ?? 0;
        $fifthVal = $fifth[$date] ?? 0;
        $value = $first[$date] ?? 0;
        $result[$date] = $value + $secondVal + $thirdVal + $fourthVal + $fifthVal;
    }

    return $result;
}
}

if (! function_exists('zeroValueForIndexesBeforeIndex')) {
function zeroValueForIndexesBeforeIndex(array $items, int $date)
{
    $result = [];
    foreach ($items as $dateAsIndex => $value) {
        if ($dateAsIndex <= $date) {
            $result[$dateAsIndex] = 0;
        } else {
            $result[$dateAsIndex] = $value;
        }
    }

    return $result;
}
}

// ── step 12: the helpers of the financial statements, the ratios and the dashboard ──

if (! function_exists('getDateFromThreeArrays')) {
function getDateFromThreeArrays(array $first, array $second, array $third)
{
    return array_values(array_unique(array_merge(array_keys($first), array_keys($second), array_keys($third))));
}
}

if (! function_exists('getIntervalOnlyMonthlyAndAnnuallyFormatted')) {
function getIntervalOnlyMonthlyAndAnnuallyFormatted(): array
{
    return ['monthly' => 'Monthly', 'annually' => 'Annually'];
}
}

if (! function_exists('sumIntervalsAndPreserveOriginalDay')) {
/** like sumIntervals, but each result keeps the day of the date it came from (not the last day of the month) */
function sumIntervalsAndPreserveOriginalDay(array $dateValues, string $intervalName, string $financialYearStartMonth, array $dateIndexWithDate)
{
    return (new IntervalSummationOperations)->sumForInterval($dateValues, $intervalName, $financialYearStartMonth, $dateIndexWithDate, false, true);
}
}

if (! function_exists('intervalsEndBalance')) {
/** the balance at the end of each interval (monthly, quarterly, semi-annually, annually), keyed by date index */
function intervalsEndBalance(array $dateValues, string $intervalName, string $financialYearStartMonth, array $dateIndexWithDate)
{
    return (new \App\Calculations\IntervalEndBalancesService)->__calculate($dateValues, $financialYearStartMonth, $intervalName, $dateIndexWithDate);
}
}

if (! function_exists('getPreviousValue')) {
function getPreviousValue(array $data, int $index)
{
    $keys = array_keys($data);
    $position = array_search($index, $keys);
    if ($position !== false && $position > 0) {
        return $data[$keys[$position - 1]];
    }

    return null;
}
}

if (! function_exists('sumTwoDimArr')) {
function sumTwoDimArr($items)
{
    $sum = 0;
    foreach ($items as $item) {
        foreach ($item as $index => $value) {
            $sum += $value;
        }
    }

    return $sum;
}
}

if (! function_exists('formatDataForChart')) {
function formatDataForChart(array $reportDateAndValueArray, bool $indexIsOnlyYear = false, bool $includeGrowthRate = true, bool $includePercentageFromRevenue = false, array $totalHotelRevenue = [], array $yearsWithItsMonths = [], $debug = false): array
{
    $dateIndexWithDate = app('dateIndexWithDate');
    $formattedReport = [];
    foreach ($reportDateAndValueArray as $dateAsIndex => $value) {
        $dateAsString = $dateIndexWithDate[$dateAsIndex];
        $previousMonthSales = getPreviousValue($reportDateAndValueArray, $dateAsIndex);
        if ($indexIsOnlyYear) {
            $dateAsIndex = array_key_first($yearsWithItsMonths[$dateAsIndex]);
            $dateAsString = $dateIndexWithDate[$dateAsIndex];
        }
        $growthRate = [];
        $percentagesFromRevenue = [];
        $valueAndDate = [
            'Value' => $value ?? 0,
            'date' => $dateAsString = LegacyCarbon::make($dateAsString)->format('d-M-Y'),
        ];
        if ($includeGrowthRate) {
            $growthRate = [
                'Growth %' => $previousMonthSales ? LegacyMath::numberFormat(($value - $previousMonthSales) / ($previousMonthSales ? $previousMonthSales : 1) * 100, 2) : 0,
            ];
        }
        if ($includePercentageFromRevenue) {
            $totalHotelRevenueAtDate = $totalHotelRevenue[$dateAsIndex] ?? 0;
            $percentage = ($totalHotelRevenueAtDate ? $value / $totalHotelRevenueAtDate : 0) * 100;
            $percentagesFromRevenue = [
                '% From Revenue' => LegacyMath::numberFormat($percentage, 2),
            ];
        }
        $formattedReport[] = array_merge($valueAndDate, $growthRate, $percentagesFromRevenue);
    }

    return $formattedReport;
}
}

if (! function_exists('formatAccumulatedDataForChart')) {
function formatAccumulatedDataForChart(array $items)
{
    $result = [];
    $index = 0;
    $dateIndexWithDate = app('dateIndexWithDate');
    foreach ($items as $dateAsIndex => $value) {
        $previousValue = $result[$index - 1]['Value'] ?? 0;
        $dateAsString = $dateIndexWithDate[$dateAsIndex];
        $result[$index] = [
            'Value' => $previousValue + $value,
            'date' => LegacyCarbon::make($dateAsString)->format('Y-m-d'),
        ];
        $index++;
    }

    return $result;
}
}

if (! function_exists('formatStackedChart')) {
function formatStackedChart(array $items, array $datesIndexWithYearIndex, array $yearIndexWithYear)
{
    $chartData = [];
    $finalResult = [];
    foreach ($items as $reportName => $reportDataAndValues) {
        $chartData[$reportName] = sumForEachYear($reportDataAndValues, $datesIndexWithYearIndex);
    }
    $years = array_keys(Arr::first($chartData, null, []));
    foreach ($years as $yearAsIndex) {
        $values = [];
        $index = 1;
        foreach ($chartData as $reportName => $yearWithValue) {
            $values['value'.$index] = ($yearWithValue[$yearAsIndex] ?? 0) / 1000;
            $index++;
        }
        $finalResult[] = array_merge(['year' => $yearIndexWithYear[$yearAsIndex]], $values);
    }

    return $finalResult;
}
}

if (! function_exists('number_unformat')) {
    /** "1,234.5 %" => 1234.5 (the leading number of a formatted text; an int when it has no decimal point) */
    function number_unformat($number, $force_number = true, $dec_point = '.', $thousands_sep = ',')
    {
        $number = (string) $number;
        $isNegativeNumber = str_starts_with($number, '-');
        if ($force_number) {
            $number = preg_replace('/^[^\d]+/', '', $number);
        } elseif (preg_match('/^[^\d]+/', $number)) {
            return false;
        }
        $type = (strpos($number, $dec_point) === false) ? 'int' : 'float';
        $number = str_replace([$dec_point, $thousands_sep], ['.', ''], $number);
        settype($number, $type);
        if ($isNegativeNumber) {
            $number = $number * -1;
        }

        return $number;
    }
}
