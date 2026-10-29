<?php

namespace App\Services\Investment;

use App\Models\HospitalitySector;
use App\Services\Expenses\StartUpCostService;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — InvestmentDates (the months the investment pages choose from)
//  Location: app/Services/Investment/InvestmentDates.php
//
//  Dates are kept as the index of the month in the study's list of months (0 = the first month of the study), as the old application kept them.
//  A purchase, or the start of a construction or of the FF&E, can be any month from the study start to the opening month. A construction (or FF&E)
//  ends `duration` months after it starts and that month must not be after the opening month (the old application's "must be less than or
//  equal operation start date" check).
// ══════════════════════════════════════════════════════════════════

class InvestmentDates
{
    public function __construct(private readonly StartUpCostService $months) {}

    /** @return array<int, array{index: int, label: string}> */
    public function months(HospitalitySector $study): array
    {
        return $this->months->openingMonths($study);
    }

    /** The index of the opening month (the last month a purchase or construction can reach). */
    public function opening(HospitalitySector $study): int
    {
        return (int) ($study->getOperationStartDateAsIndex() ?? 0);
    }

    /** The month a chosen month falls back to when nothing is saved: the study start. */
    public function studyStart(HospitalitySector $study): int
    {
        return (int) $study->getStudyStartDateAsIndex();
    }

    /** The month the development starts (the default start of a construction or of the FF&E), kept inside the months that can be chosen. */
    public function developmentStart(HospitalitySector $study): int
    {
        $start = $study->getDevelopmentStartDateAsIndex();

        return $start === null ? $this->studyStart($study) : (int) $start;
    }

    public function developmentDuration(HospitalitySector $study): int
    {
        return (int) ($study->development_duration ?: 0);
    }

    /** The month a construction or FF&E ends: the start plus its duration. */
    public function end(int $start, int $duration): int
    {
        return $start + max(0, $duration);
    }

    /** The label of a month ("Mar 2027"), or an empty text when the study has no such month. */
    public function label(HospitalitySector $study, int $index): string
    {
        $date = $study->getStudyDates()[$index] ?? null;
        if (! $date) {
            return '';
        }
        $parts = explode('-', (string) $date);
        $names = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        return count($parts) === 3 ? ($names[((int) $parts[1]) - 1] ?? $parts[1]).' '.$parts[2] : (string) $date;
    }
}
