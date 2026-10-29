<?php

namespace App\Services\Revenue;

use App\Models\HospitalitySector;
use App\Support\StudyCache;
use App\Support\StudyYears;
use Illuminate\Support\Facades\Log;
use Throwable;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — RevenueFiguresService (the calculated revenue of a study, as the screens show it)
//  Location: app/Services/Revenue/RevenueFiguresService.php
//
//  The figures come from HospitalitySector::calculateRoomRevenueAndGuestCount(), the calculation of the previous application,
//  carried over line for line (Step 05). It was run against the old application on the same studies and gave the same numbers
//  (see docs/REVENUE.md, "Checked against the old application"). This class only picks the figures a screen needs and puts every
//  one of them on the list of operating years, keyed by the year index (a year without a figure reads 0).
//  A study whose inputs are not complete yet cannot be calculated: the answer is null and the screens simply leave the figures out.
// ══════════════════════════════════════════════════════════════════

class RevenueFiguresService
{
    /** @var array<int, array|null> the figures of a study, kept for the rest of the request (a page asks twice) */
    private array $memory = [];

    /** @var array<int, array> the whole result of the calculation, for the pages that read more than the revenue (the expense results) */
    private array $results = [];

    /** Why the last study could not be calculated (shown by the tests, written to the log). */
    public ?string $error = null;

    /**
     * @return array{years: array, revenue: array<string, array<int, float>>, operating: array<string, array<int, float>>}|null
     */
    public function figures(HospitalitySector $study): ?array
    {
        if (array_key_exists($study->id, $this->memory)) {
            return $this->memory[$study->id];
        }

        try {
            $study->bindDateTables();
            // only the revenue part of the calculation (about a tenth of the work): the pages that need more ask for result()
            $result = $study->calculateRevenueOnly();
        } catch (Throwable $e) {
            $this->error = $e::class.': '.$e->getMessage().' @ '.basename($e->getFile()).':'.$e->getLine();
            Log::warning('Revenue figures of study '.$study->id.' could not be calculated: '.$e->getMessage().' @ '.basename($e->getFile()).':'.$e->getLine());

            return $this->memory[$study->id] = null;
        }

        $years = StudyYears::of($study);
        $byYear = function ($figures) use ($years) {
            $figures = is_array($figures) ? $figures : [];

            return collect($years)->mapWithKeys(fn (array $year) => [$year['index'] => (float) ($figures[$year['index']] ?? 0)])->all();
        };

        return $this->memory[$study->id] = [
            'years' => $years,
            'revenue' => [
                'rooms' => $byYear($result['totalRoomRevenueOfEachYear'] ?? []),
                'foods' => $byYear($result['fAndBFacilityRevenue']['totalOfEachYear'] ?? []),
                'casinos' => $byYear($result['casinoFacilityRevenue']['totalOfEachYear'] ?? []),
                'meetings' => $byYear($result['meetingFacilityRevenue']['totalOfEachYear'] ?? []),
                'others' => $byYear($result['otherRevenueFacilityRevenue']['totalOfEachYear'] ?? []),
                'total' => $byYear($result['annuallyTotalHotelRevenue'] ?? []),
            ],
            'operating' => [
                'nightsAvailable' => $byYear($result['totalMaxAvailableNightsPerYear'] ?? []),
                'nightsPractical' => $byYear($result['totalMaxPracticalAvailableNightsPerYear'] ?? []),
                'nightsSold' => $byYear($result['totalRoomsSoldNightsPerYear'] ?? []),
                'guests' => $byYear($result['annualGuestCountPerRoom'] ?? []),
                'days' => $byYear($result['daysCountPerYear']['totalOfEachYear'] ?? []),
            ],
        ];
    }

    /** The whole result of the calculation of a study (null when it cannot be calculated yet). */
    public function result(HospitalitySector $study): ?array
    {
        if (array_key_exists($study->id, $this->results)) {
            return $this->results[$study->id];
        }

        // the whole calculation takes seconds: it is kept until study data is written (StudyCache)
        return $this->results[$study->id] = StudyCache::remember($study->id, 'result', function () use ($study) {
            try {
                $study->bindDateTables();

                return $study->calculateRoomRevenueAndGuestCount();
            } catch (Throwable $e) {
                $this->error = $e::class.': '.$e->getMessage().' @ '.basename($e->getFile()).':'.$e->getLine();
                Log::warning('The whole calculation of study '.$study->id.' could not be calculated: '.$e->getMessage().' @ '.basename($e->getFile()).':'.$e->getLine());

                return null;
            }
        });
    }

    /**
     * The reading aids under the yearly boxes of the revenue pages: the guests of the hotel, the days it is open, and the revenue the
     * percentage methods are taken from. Null when the study cannot be calculated yet.
     *
     * @return array{guests: array, days: array, roomsRevenue: array, foodsRevenue: array, nightsAvailable: array}|null
     */
    public function aids(HospitalitySector $study): ?array
    {
        $figures = $this->figures($study);

        return $figures === null ? null : [
            'guests' => $figures['operating']['guests'],
            'days' => $figures['operating']['days'],
            'nightsAvailable' => $figures['operating']['nightsAvailable'],
            'roomsRevenue' => $figures['revenue']['rooms'],
            'foodsRevenue' => $figures['revenue']['foods'],
        ];
    }
}
