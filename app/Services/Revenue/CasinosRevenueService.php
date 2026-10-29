<?php

namespace App\Services\Revenue;

use App\Models\Casino;
use App\Models\Currency;
use App\Models\HospitalitySector;
use App\Support\CollectionTerms;
use App\Support\StudyYears;
use Illuminate\Support\Facades\DB;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — CasinosRevenueService (the gaming screen: what it shows and what it saves)
//  Location: app/Services/Revenue/CasinosRevenueService.php
//
//  Ports HospitalitySectorController@viewCasinos / storeCasinos of the previous application.
//  Each gaming facility says HOW its sales are worked out, by one of three methods:
//    guest_capture_casino_charges_per_guest_method ........ guest capture % (of the hotel guests) x charges per guest
//    guest_count_target_per_day_charges_per_guest_method .. guests per day x charges per guest
//    percentage_from_rooms_revenue ........................ a percentage of the rooms revenue
//  The two guest methods keep their yearly figure in the same column (guest_capture_cover_percentage), as the old application did;
//  what the figure means depends on the method. The charges per guest are kept in cover_value, with the escalation columns.
//  The number of facilities and their capacity are set in the study (Step 07) and only shown here.
// ══════════════════════════════════════════════════════════════════

class CasinosRevenueService
{
    public const MODE_COLUMN = 'casino_collection_policy_type';

    public const GUEST_CAPTURE = 'guest_capture_casino_charges_per_guest_method';

    public const GUEST_TARGET = 'guest_count_target_per_day_charges_per_guest_method';

    public const ROOMS_PERCENT = 'percentage_from_rooms_revenue';

    public const METHODS = [self::GUEST_CAPTURE, self::GUEST_TARGET, self::ROOMS_PERCENT];

    /** method => [screen series name => column] */
    public const SERIES = [
        self::GUEST_CAPTURE => ['capture' => 'guest_capture_cover_percentage'],
        self::GUEST_TARGET => ['target' => 'guest_capture_cover_percentage'],
        self::ROOMS_PERCENT => ['rooms' => 'percentage_from_rooms_revenues'],
    ];

    public function present(HospitalitySector $study): array
    {
        $years = StudyYears::of($study);
        $main = (int) $study->main_functional_currency;

        return [
            'years' => $years,
            'total' => $study->isTotalCasino(),
            'daysDifference' => (float) $study->getDiffBetweenOperationStartDateAndStudyStartDate(),
            'estimationDate' => (string) $study->getStudyStartDateFormattedForView(),
            'currencies' => $this->currencies($study),
            'methods' => self::METHODS,
            'form' => [
                'facilities' => $study->casinos()->orderBy('id')->get()->map(fn (Casino $casino) => $this->facility($casino, $years, $main))->values()->all(),
                'collection' => RevenueParts::generalTerms($study, 'casinos', self::MODE_COLUMN, false, 'per_facility'),
            ],
        ];
    }

    private function facility(Casino $casino, array $years, int $mainCurrency): array
    {
        $method = in_array($casino->getFAndBFacilities(), self::METHODS, true) ? $casino->getFAndBFacilities() : self::GUEST_CAPTURE;
        $series = fn (string $column) => RevenueParts::yearMap($this->json($casino->{$column}), $years);
        $zero = RevenueParts::yearMap(null, $years);

        return [
            'key' => (int) $casino->casino_type_id,
            'name' => $casino->getName(),
            'count' => (int) $casino->casino_count,
            'cover' => (int) $casino->casino_cover,
            'capacity' => (int) $casino->getTotalGuestCapacityCount(),
            'method' => $method,
            'currency' => (int) ($casino->chosen_casino_currency ?: $mainCurrency),
            'value' => CollectionTerms::number($casino->cover_value ?: 0),
            'escalation' => CollectionTerms::number($casino->charges_value_escalation_rate ?: 0),
            'annual' => CollectionTerms::number($casino->charges_value_annual_escalation_rate ?: 0),
            'capture' => $method === self::GUEST_CAPTURE ? $series('guest_capture_cover_percentage') : $zero,
            'target' => $method === self::GUEST_TARGET ? $series('guest_capture_cover_percentage') : $zero,
            'rooms' => $series('percentage_from_rooms_revenues'),
            'terms' => CollectionTerms::present($casino->collection_policy_type, $casino->collection_policy_interval, $casino->collection_policy_value),
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

            $study->update(RevenueParts::termsColumns('casinos', self::MODE_COLUMN, $data['collection']) + ['has_visit_casino_section' => true]);

            $sent = collect($data['facilities'])->keyBy('key');
            foreach ($study->casinos()->get() as $casino) {
                $row = $sent->get((int) $casino->casino_type_id);
                $method = $row['method'];
                $hasValue = $method !== self::ROOMS_PERCENT;
                $value = $hasValue ? (float) $row['value'] : 0.0;
                $escalation = $hasValue ? (float) $row['escalation'] : 0.0;
                [$type, $interval, $terms] = CollectionTerms::stored($row['terms']);

                $series = fn (string $name) => isset(self::SERIES[$method][$name]) ? json_decode(RevenueParts::json((array) ($row[$name] ?? []), $yearKeys), true) : [];

                $casino->update([
                    'f&b_facilities' => $method,
                    'cover_value' => RevenueParts::amount($value),
                    'chosen_casino_currency' => (string) $row['currency'],
                    'charges_value_escalation_rate' => RevenueParts::amount($escalation),
                    'charges_value_at_operation_date' => RevenueParts::amount(FoodsRevenueService::coverValueAtOperation($value, $escalation, $power)),
                    'charges_value_annual_escalation_rate' => RevenueParts::amount($hasValue ? $row['annual'] : 0),
                    'guest_capture_cover_percentage' => $method === self::GUEST_CAPTURE ? $series('capture') : ($method === self::GUEST_TARGET ? $series('target') : []),
                    'cover_per_day' => [],
                    'percentage_from_rooms_revenues' => $series('rooms'),
                    'collection_policy_type' => $type,
                    'collection_policy_interval' => $interval,
                    'collection_policy_value' => $terms ?? '',
                ]);
            }
        });
    }
}
