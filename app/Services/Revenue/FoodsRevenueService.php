<?php

namespace App\Services\Revenue;

use App\Models\Currency;
use App\Models\Food;
use App\Models\HospitalitySector;
use App\Support\CollectionTerms;
use App\Support\StudyYears;
use Illuminate\Support\Facades\DB;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — FoodsRevenueService (the food and beverage screen: what it shows and what it saves)
//  Location: app/Services/Revenue/FoodsRevenueService.php
//
//  Ports HospitalitySectorController@viewFoods / storeFoods of the previous application.
//  Each F&B facility (restaurant, café, ...) says HOW its sales are worked out, by one of four methods:
//    guest_capture_x_cover_value_per_guest_method ..................... guests of the hotel who eat there (%) x value of a cover
//    guest_capture_..._x_meals_per_guest_x_cover_value_per_meal_method  the same, times meals per guest
//    cover_count_target_per_day_x_cover_value_method ................... covers served each day x value of a cover
//    percentage_from_rooms_revenue ..................................... a percentage of the rooms revenue
//  plus the value of a cover (currency, escalation, annual escalation) and when the money is collected.
//  Only the yearly figures that the chosen method uses are kept; the others are saved empty, as the old application did.
//  The number of facilities and the seats of each are set in the study itself (Step 07) and are shown here, not changed.
// ══════════════════════════════════════════════════════════════════

class FoodsRevenueService
{
    public const MODE_COLUMN = 'food_collection_policy_type';

    public const PER_GUEST = 'guest_capture_x_cover_value_per_guest_method';

    public const PER_MEAL = 'guest_capture_x_meals_per_guest_x_cover_value_per_meal_method';

    public const COVER_TARGET = 'cover_count_target_per_day_x_cover_value_method';

    public const ROOMS_PERCENT = 'percentage_from_rooms_revenue';

    public const METHODS = [self::PER_GUEST, self::PER_MEAL, self::COVER_TARGET, self::ROOMS_PERCENT];

    /** method => the yearly series it needs (screen name => column) */
    public const SERIES = [
        self::PER_GUEST => ['capture' => 'guest_capture_cover_percentage'],
        self::PER_MEAL => ['capture' => 'guest_capture_cover_percentage', 'meals' => 'meal_per_guest'],
        self::COVER_TARGET => ['target' => 'cover_per_day'],
        self::ROOMS_PERCENT => ['rooms' => 'percentage_from_rooms_revenues'],
    ];

    public const ALL_COLUMNS = ['capture' => 'guest_capture_cover_percentage', 'meals' => 'meal_per_guest', 'target' => 'cover_per_day', 'rooms' => 'percentage_from_rooms_revenues'];

    // ── what the screen is given ──────────────────────────────────

    public function present(HospitalitySector $study): array
    {
        $years = StudyYears::of($study);
        $foods = $study->foods()->orderBy('id')->get();
        $main = (int) $study->main_functional_currency;

        return [
            'years' => $years,
            'total' => $study->isTotalFood(),
            'daysDifference' => (float) $study->getDiffBetweenOperationStartDateAndStudyStartDate(),
            'estimationDate' => (string) $study->getStudyStartDateFormattedForView(),
            'currencies' => $this->currencies($study),
            'methods' => self::METHODS,
            'form' => [
                'facilities' => $foods->map(fn (Food $food) => $this->facility($food, $years, $main))->values()->all(),
                'collection' => RevenueParts::generalTerms($study, 'foods', self::MODE_COLUMN, false, 'per_facility'),
            ],
        ];
    }

    private function facility(Food $food, array $years, int $mainCurrency): array
    {
        $series = fn (string $column) => RevenueParts::yearMap($this->json($food->{$column}), $years);
        $method = in_array($food->getFAndBFacilities(), self::METHODS, true) ? $food->getFAndBFacilities() : self::PER_GUEST;

        return [
            'key' => (int) $food->food_type_id,
            'name' => $food->getName(),
            'count' => (int) $food->food_count,
            'cover' => (int) $food->food_cover,
            'capacity' => (int) $food->getTotalGuestCapacityCount(),
            'method' => $method,
            'currency' => (int) ($food->chosen_food_currency ?: $mainCurrency),
            'value' => CollectionTerms::number($food->cover_value ?: 0),
            'escalation' => CollectionTerms::number($food->cover_value_escalation_rate ?: 0),
            'annual' => CollectionTerms::number($food->cover_value_annual_escalation_rate ?: 0),
            'capture' => $series('guest_capture_cover_percentage'),
            'meals' => $series('meal_per_guest'),
            'target' => $series('cover_per_day'),
            'rooms' => $series('percentage_from_rooms_revenues'),
            'terms' => CollectionTerms::present($food->collection_policy_type, $food->collection_policy_interval, $food->collection_policy_value),
        ];
    }

    /** A cast column comes back as an array; the yearly helpers read JSON text. */
    private function json(mixed $value): ?string
    {
        return is_array($value) ? json_encode($value) : ($value === null ? null : (string) $value);
    }

    private function currencies(HospitalitySector $study): array
    {
        $ids = array_filter([(int) $study->main_functional_currency, (int) $study->additional_currency]);

        return Currency::query()->whereIn('id', $ids)->orderBy('id')->get(['id', 'name'])
            ->map(fn ($currency) => ['id' => (int) $currency->id, 'name' => (string) $currency->name])->values()->all();
    }

    // ── what is saved ─────────────────────────────────────────────

    public function save(HospitalitySector $study, array $data): void
    {
        DB::transaction(function () use ($study, $data) {
            $yearKeys = array_column(StudyYears::of($study), 'index');
            $power = (float) $study->getDiffBetweenOperationStartDateAndStudyStartDate();

            $study->update(RevenueParts::termsColumns('foods', self::MODE_COLUMN, $data['collection']) + ['has_visit_food_section' => true]);

            $sent = collect($data['facilities'])->keyBy('key');
            foreach ($study->foods()->get() as $food) {
                $row = $sent->get((int) $food->food_type_id);
                $method = $row['method'];
                $used = self::SERIES[$method];
                [$type, $interval, $terms] = CollectionTerms::stored($row['terms']);

                // a percentage of the rooms revenue has no value of a cover: those boxes are saved as zero, as the old application did
                $hasValue = $method !== self::ROOMS_PERCENT;
                $value = $hasValue ? (float) $row['value'] : 0.0;
                $columns = [
                    'f&b_facilities' => $method,
                    'cover_value' => RevenueParts::amount($value),
                    'chosen_food_currency' => (string) $row['currency'],
                    'cover_value_escalation_rate' => RevenueParts::amount($hasValue ? $row['escalation'] : 0),
                    'cover_value_at_operation_date' => RevenueParts::amount(self::coverValueAtOperation($value, $hasValue ? (float) $row['escalation'] : 0.0, $power)),
                    'cover_value_annual_escalation_rate' => RevenueParts::amount($hasValue ? $row['annual'] : 0),
                    'collection_policy_type' => $type,
                    'collection_policy_interval' => $interval,
                    'collection_policy_value' => $terms ?? '',
                ];
                foreach (self::ALL_COLUMNS as $name => $column) {
                    $columns[$column] = isset($used[$name]) ? json_decode(RevenueParts::json((array) ($row[$name] ?? []), $yearKeys), true) : [];
                }

                $food->update($columns);
            }
        });
    }

    /** The value of a cover at the opening date: the value grown by the escalation for the time from the study start to the opening. */
    public static function coverValueAtOperation(float $value, float $escalationPercent, float $years): float
    {
        return $value * pow(1 + $escalationPercent / 100, $years);
    }
}
