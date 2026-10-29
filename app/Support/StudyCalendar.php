<?php

namespace App\Support;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — StudyCalendar (the dates of a study, worked out on the server)
//  Location: app/Support/StudyCalendar.php
//
//  A study is written with its first month ("2026-03") and its length in years. From these the server works out,
//  exactly as the previous CFO Hotels did in the browser:
//
//    last month         = first month + (years x 12 - 1) months         2026-03 + 5 years  -> 2031-02
//    month list         = every month from the first month to December of the 20th year (the application's
//                         planning window, MAX_YEARS_COUNT). The place of a month in this list is its "date number";
//                         every monthly figure of the study is saved against that number.
//    operation months   = the months from the operation start to the end of the list, keyed by their date number.
//    stored dates       = the text formats of the previous database:  "03/01/2026" (month/day/year, day always 01)
//                         in study_start_date, study_end_date, operation_start_date;  "01-03-2026" (day-month-year)
//                         in the lists study_dates and operation_dates.
// ══════════════════════════════════════════════════════════════════

final class StudyCalendar
{
    /** How many calendar years the list of months covers, counted from the year of the first month. */
    public const WINDOW_YEARS = 20;

    /** @return array{0:int,1:int}|null  [year, month] of "YYYY-MM", or null when it is not a valid month */
    public static function parse(?string $value): ?array
    {
        if (! is_string($value) || ! preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $value, $m)) {
            return null;
        }

        return [(int) $m[1], (int) $m[2]];
    }

    public static function format(int $year, int $month): string
    {
        return sprintf('%04d-%02d', $year, $month);
    }

    /** The month n months after (or before, when negative) "YYYY-MM". */
    public static function addMonths(string $value, int $months): string
    {
        [$year, $month] = self::parse($value) ?? throw new \InvalidArgumentException("Not a month: {$value}");
        $total = $year * 12 + ($month - 1) + $months;

        return self::format(intdiv($total, 12), $total % 12 + 1);
    }

    /** The last month of a study: first month + (years x 12 - 1) months. */
    public static function endMonth(string $start, int $years): string
    {
        return self::addMonths($start, $years * 12 - 1);
    }

    /** "2026-03" → "03/01/2026" (how the previous database keeps a month). */
    public static function toStored(string $value): string
    {
        [$year, $month] = self::parse($value) ?? throw new \InvalidArgumentException("Not a month: {$value}");

        return sprintf('%02d/01/%04d', $month, $year);
    }

    /** "03/01/2026" → "2026-03"; anything else → null. */
    public static function fromStored(?string $value): ?string
    {
        if (! is_string($value) || ! preg_match('#^(\d{2})/\d{2}/(\d{4})$#', $value, $m)) {
            return null;
        }

        return self::parse("{$m[2]}-{$m[1]}") ? "{$m[2]}-{$m[1]}" : null;
    }

    /** Every month of the planning window as "01-MM-YYYY", in order; the list position is the date number. */
    public static function monthList(string $start): array
    {
        [$year, $month] = self::parse($start) ?? throw new \InvalidArgumentException("Not a month: {$start}");
        $list = [];

        for ($y = $year; $y < $year + self::WINDOW_YEARS; $y++) {
            for ($m = ($y === $year ? $month : 1); $m <= 12; $m++) {
                $list[] = sprintf('01-%02d-%04d', $m, $y);
            }
        }

        return $list;
    }

    /** The operating months: from the date number $operationStartMonth (months after the first month) to the end, keyed by date number. */
    public static function operationMonths(array $monthList, int $operationStartMonth): array
    {
        $months = [];

        foreach ($monthList as $number => $date) {
            if ($number >= $operationStartMonth) {
                $months[$number] = $date;
            }
        }

        return $months;
    }

    /** The year of the study's last month. */
    public static function endYear(string $start, int $years): int
    {
        return self::parse(self::endMonth($start, $years))[0];
    }
}
