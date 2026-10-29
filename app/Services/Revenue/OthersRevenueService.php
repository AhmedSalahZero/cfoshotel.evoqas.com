<?php

namespace App\Services\Revenue;

use App\Models\Other;
use App\Models\Currency;
use App\Models\HospitalitySector;
use App\Support\CollectionTerms;
use App\Support\StudyYears;
use Illuminate\Support\Facades\DB;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — OthersRevenueService (the other revenue screen: what it shows and what it saves)
//  Location: app/Services/Revenue/OthersRevenueService.php
//
//  Ports HospitalitySectorController@viewOtherRevenues / storeOtherRevenues of the previous application.
//  Each other-revenue facility (spa, parking, shops...) says HOW its sales are worked out, by one of two methods:
//    guest_capture_x_charges_per_guest_method ... guest capture % (of the hotel guests) x charges per guest
//    percentage_from_rooms_revenue .............. a percentage of the rooms revenue
//  The method is kept in the column `f&b_facilities` (the old name), the charges in charges_per_guest with its escalation columns,
//  the currency in chosen_other_currency. The number of facilities comes from the study (Step 07) and is only shown here.
// ══════════════════════════════════════════════════════════════════

class OthersRevenueService
{
    public const MODE_COLUMN = 'other_collection_policy_type';

    public const GUEST_CAPTURE = 'guest_capture_x_charges_per_guest_method';

    public const ROOMS_PERCENT = 'percentage_from_rooms_revenue';

    public const METHODS = [self::GUEST_CAPTURE, self::ROOMS_PERCENT];

    /** method => [screen series name => column] */
    public const SERIES = [
        self::GUEST_CAPTURE => ['capture' => 'guest_capture_cover_percentage'],
        self::ROOMS_PERCENT => ['rooms' => 'percentage_from_rooms_revenues'],
    ];

    public function present(HospitalitySector $study): array
    {
        $years = StudyYears::of($study);
        $main = (int) $study->main_functional_currency;

        return [
            'years' => $years,
            'total' => $study->isTotalOther(),
            'daysDifference' => (float) $study->getDiffBetweenOperationStartDateAndStudyStartDate(),
            'estimationDate' => (string) $study->getStudyStartDateFormattedForView(),
            'currencies' => $this->currencies($study),
            'methods' => self::METHODS,
            'form' => [
                'facilities' => $study->others()->orderBy('id')->get()->map(fn (Other $other) => $this->facility($other, $years, $main))->values()->all(),
                'collection' => RevenueParts::generalTerms($study, 'others', self::MODE_COLUMN, false, 'per_facility'),
            ],
        ];
    }

    private function facility(Other $other, array $years, int $mainCurrency): array
    {
        $method = in_array($other->getFAndBFacilities(), self::METHODS, true) ? $other->getFAndBFacilities() : self::GUEST_CAPTURE;
        $series = fn (string $column) => RevenueParts::yearMap($this->json($other->{$column}), $years);
        $zero = RevenueParts::yearMap(null, $years);

        return [
            'key' => (int) $other->other_type_id,
            'name' => $other->getName(),
            'count' => (int) $other->other_count,
            'method' => $method,
            'currency' => (int) ($other->chosen_other_currency ?: $mainCurrency),
            'value' => CollectionTerms::number($other->charges_per_guest ?: 0),
            'escalation' => CollectionTerms::number($other->charges_per_guest_escalation_rate ?: 0),
            'annual' => CollectionTerms::number($other->charges_per_guest_annual_escalation_rate ?: 0),
            'capture' => $method === self::GUEST_CAPTURE ? $series('guest_capture_cover_percentage') : $zero,
            'rooms' => $series('percentage_from_rooms_revenues'),
            'terms' => CollectionTerms::present($other->collection_policy_type, $other->collection_policy_interval, $other->collection_policy_value),
        ];
    }

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

    public function save(HospitalitySector $study, array $data): void
    {
        DB::transaction(function () use ($study, $data) {
            $yearKeys = array_column(StudyYears::of($study), 'index');
            $power = (float) $study->getDiffBetweenOperationStartDateAndStudyStartDate();

            $study->update(RevenueParts::termsColumns('others', self::MODE_COLUMN, $data['collection']) + ['has_visit_other_section' => true]);

            $sent = collect($data['facilities'])->keyBy('key');
            foreach ($study->others()->get() as $other) {
                $row = $sent->get((int) $other->other_type_id);
                $method = $row['method'];
                $hasValue = $method !== self::ROOMS_PERCENT;
                $value = $hasValue ? (float) $row['value'] : 0.0;
                $escalation = $hasValue ? (float) $row['escalation'] : 0.0;
                [$type, $interval, $terms] = CollectionTerms::stored($row['terms']);

                $series = fn (string $name) => isset(self::SERIES[$method][$name]) ? json_decode(RevenueParts::json((array) ($row[$name] ?? []), $yearKeys), true) : [];

                $other->update([
                    'f&b_facilities' => $method,
                    'charges_per_guest' => RevenueParts::amount($value),
                    'chosen_other_currency' => (string) $row['currency'],
                    'charges_per_guest_escalation_rate' => RevenueParts::amount($escalation),
                    'charges_per_guest_at_operation_date' => RevenueParts::amount(FoodsRevenueService::coverValueAtOperation($value, $escalation, $power)),
                    'charges_per_guest_annual_escalation_rate' => RevenueParts::amount($hasValue ? $row['annual'] : 0),
                    'guest_capture_cover_percentage' => $series('capture'),
                    'percentage_from_rooms_revenues' => $series('rooms'),
                    'collection_policy_type' => $type,
                    'collection_policy_interval' => $interval,
                    'collection_policy_value' => $terms ?? '',
                ]);
            }
        });
    }
}
