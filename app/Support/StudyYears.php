<?php

namespace App\Support;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — StudyYears (the years a hotel is open, for the figures that change year by year)
//  Location: app/Support/StudyYears.php
//
//  The revenue screens ask for one number per OPERATING year (occupancy, exchange rate, share of a channel ...).
//  The previous application keeps such numbers in a list keyed by the "year index": 0 = the calendar year the study starts in,
//  1 = the next calendar year, and so on. A year is operating from the month operation starts until the last month of the study.
//
//    StudyYears::list('03/01/2026', 12, 5)
//      → [ ['index' => 1, 'year' => 2027, 'months' => 10], ['index' => 2, 'year' => 2028, 'months' => 12], ... ['index' => 5, 'year' => 2031, 'months' => 2] ]
//
//  Checked against the five real studies of the old application (tests/Unit/StudyYearsTest.php).
// ══════════════════════════════════════════════════════════════════

class StudyYears
{
    /**
     * @param  string  $studyStart  as the database keeps it: "MM/01/YYYY"
     * @param  int  $operationStartMonth  months after the first month of the study
     * @return array<int, array{index: int, year: int, months: int}>
     */
    public static function list(string $studyStart, int $operationStartMonth, int $durationInYears): array
    {
        $start = StudyCalendar::fromStored($studyStart);
        if ($start === null || $durationInYears < 1) {
            return [];
        }

        [$startYear] = StudyCalendar::parse($start);
        $first = StudyCalendar::addMonths($start, max(0, $operationStartMonth));
        $last = StudyCalendar::endMonth($start, $durationInYears);

        $years = [];
        for ($month = $first; strcmp($month, $last) <= 0; $month = StudyCalendar::addMonths($month, 1)) {
            [$year] = StudyCalendar::parse($month);
            $index = $year - $startYear;
            $years[$index] ??= ['index' => $index, 'year' => $year, 'months' => 0];
            $years[$index]['months']++;
        }

        return array_values($years);
    }

    /** The same, for a study. */
    public static function of(\App\Models\HospitalitySector $study): array
    {
        return self::list((string) $study->study_start_date, (int) $study->operation_start_month, (int) $study->duration_in_years);
    }
}
