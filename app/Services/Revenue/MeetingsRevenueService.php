<?php

namespace App\Services\Revenue;

use App\Models\Currency;
use App\Models\HospitalitySector;
use App\Models\Meeting;
use App\Support\CollectionTerms;
use App\Support\StudyYears;
use Illuminate\Support\Facades\DB;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — MeetingsRevenueService (the meeting spaces screen: what it shows and what it saves)
//  Location: app/Services/Revenue/MeetingsRevenueService.php
//
//  Ports HospitalitySectorController@viewMeetings / storeMeetings of the previous application.
//  Each meeting facility says HOW its sales are worked out, by one of three methods:
//    guest_count_charges_per_guest_occupancy_rate_method .. guests (the capacity) x charges per guest x occupancy %
//    facility_count_daily_rent_occupancy_rate_method ...... facilities x daily rent x occupancy %
//    percentage_from_f_b_revenue .......................... a percentage of the food and beverage revenue
//  The two occupancy methods each have a SEASONALITY plan: how the year's demand is spread over its months. The plan is general (one for
//  all facilities) or per facility, and flat (equal months), quarterly (four shares) or monthly (twelve shares). Shares of a quarterly or
//  monthly plan add up to 100 %.
//  Kept the way the old application kept them: the occupancy of the two methods in guest_capture_cover_percentage, the charges per guest in
//  charges_value_per_guest, the daily rent in cover_value, seasonality as month / quarter keyed JSON.
// ══════════════════════════════════════════════════════════════════

class MeetingsRevenueService
{
    public const MODE_COLUMN = 'meeting_collection_policy_type';

    public const PER_GUEST = 'guest_count_charges_per_guest_occupancy_rate_method';

    public const DAILY_RENT = 'facility_count_daily_rent_occupancy_rate_method';

    public const FB_PERCENT = 'percentage_from_f_b_revenue';

    public const METHODS = [self::PER_GUEST, self::DAILY_RENT, self::FB_PERCENT];

    public const QUARTERS = ['quarter-one', 'quarter-two', 'quarter-three', 'quarter-four'];

    public const PLAN_TYPES = ['general' => 'general-seasonality', 'per_facility' => 'per-meeting-type-seasonality'];

    public const PLAN_INTERVALS = ['flat' => 'flat-seasonality', 'quarterly' => 'quarterly-seasonality', 'monthly' => 'monthly-seasonality'];

    /** plan name => [method that uses it, study column prefix, facility column] */
    public const PLANS = [
        'guest' => [self::PER_GUEST, 'guest_meeting', 'guest_seasonality'],
        'rent' => [self::DAILY_RENT, 'rent_meeting', 'rent_seasonality'],
    ];

    public function present(HospitalitySector $study): array
    {
        $years = StudyYears::of($study);
        $meetings = $study->meetings()->orderBy('id')->get();
        $main = (int) $study->main_functional_currency;
        $isTotal = $study->isTotalMeeting();

        return [
            'years' => $years,
            'total' => $isTotal,
            'daysDifference' => (float) $study->getDiffBetweenOperationStartDateAndStudyStartDate(),
            'estimationDate' => (string) $study->getStudyStartDateFormattedForView(),
            'currencies' => $this->currencies($study),
            'methods' => self::METHODS,
            'form' => [
                'facilities' => $meetings->map(fn (Meeting $meeting) => $this->facility($meeting, $years, $main))->values()->all(),
                'guestPlan' => $this->plan($study, $meetings, 'guest', $isTotal),
                'rentPlan' => $this->plan($study, $meetings, 'rent', $isTotal),
                'collection' => RevenueParts::generalTerms($study, 'meetings', self::MODE_COLUMN, false, 'per_facility'),
            ],
        ];
    }

    private function facility(Meeting $meeting, array $years, int $mainCurrency): array
    {
        $method = in_array($meeting->getFAndBFacilities(), self::METHODS, true) ? $meeting->getFAndBFacilities() : self::PER_GUEST;
        $series = fn (string $column) => RevenueParts::yearMap($this->json($meeting->{$column}), $years);

        return [
            'key' => (int) $meeting->meeting_type_id,
            'name' => $meeting->getName(),
            'count' => (int) $meeting->meeting_count,
            'cover' => (int) $meeting->meeting_cover,
            'capacity' => (int) $meeting->getTotalGuestCapacityCount(),
            'method' => $method,
            'currency' => (int) ($meeting->chosen_meeting_currency ?: $mainCurrency),
            'value' => CollectionTerms::number(($method === self::PER_GUEST ? $meeting->charges_value_per_guest : $meeting->cover_value) ?: 0),
            'escalation' => CollectionTerms::number($meeting->charges_value_escalation_rate ?: 0),
            'annual' => CollectionTerms::number($meeting->charges_value_annual_escalation_rate ?: 0),
            'occupancy' => $method === self::FB_PERCENT ? RevenueParts::yearMap(null, $years) : $series('guest_capture_cover_percentage'),
            'fb' => $series('percentage_from_f_and_b_revenues'),
            'terms' => CollectionTerms::present($meeting->collection_policy_type, $meeting->collection_policy_interval, $meeting->collection_policy_value),
        ];
    }

    /** The seasonality plan of one method, as the screen shows it. */
    private function plan(HospitalitySector $study, $meetings, string $name, bool $isTotal): array
    {
        [, $prefix, $column] = self::PLANS[$name];
        $type = $study->{"{$prefix}_seasonality_type"};
        $interval = array_search($study->{"{$prefix}_seasonality_interval"}, self::PLAN_INTERVALS, true) ?: 'flat';
        $general = $this->json($study->{"{$prefix}_general_seasonality"});

        return [
            'type' => ($type === self::PLAN_TYPES['per_facility'] && ! $isTotal && $meetings->isNotEmpty()) ? 'per_facility' : 'general',
            'interval' => $interval,
            'months' => $this->months($general),
            'quarters' => $this->quarters($general),
            'perMonths' => RevenueParts::map($meetings->mapWithKeys(fn (Meeting $m) => [(int) $m->meeting_type_id => $this->months($this->json($m->{$column}))])->all()),
            'perQuarters' => RevenueParts::map($meetings->mapWithKeys(fn (Meeting $m) => [(int) $m->meeting_type_id => $this->quarters($this->json($m->{$column}))])->all()),
        ];
    }

    /** The twelve shares of the months; an empty plan is shown as equal months that add up to 100. */
    public static function months(?string $json): object
    {
        $saved = (array) json_decode((string) $json, true);
        $out = [];
        foreach (RevenueParts::MONTHS as $i => $month) {
            $has = array_key_exists($month, $saved) && $saved[$month] !== null && $saved[$month] !== '';
            $out[$month] = $has ? CollectionTerms::number($saved[$month]) : ($i < 8 ? 8.33 : 8.34);
        }

        return RevenueParts::map($out);
    }

    /** The four shares of the quarters; an empty plan is shown as four equal quarters. */
    public static function quarters(?string $json): object
    {
        $saved = (array) json_decode((string) $json, true);
        $out = [];
        foreach (self::QUARTERS as $quarter) {
            $out[$quarter] = array_key_exists($quarter, $saved) && $saved[$quarter] !== null && $saved[$quarter] !== '' ? CollectionTerms::number($saved[$quarter]) : 25;
        }

        return RevenueParts::map($out);
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

    // ── what is saved ─────────────────────────────────────────────

    public function save(HospitalitySector $study, array $data): void
    {
        DB::transaction(function () use ($study, $data) {
            $yearKeys = array_column(StudyYears::of($study), 'index');
            $power = (float) $study->getDiffBetweenOperationStartDateAndStudyStartDate();
            $facilities = collect($data['facilities'])->keyBy('key');

            $columns = RevenueParts::termsColumns('meetings', self::MODE_COLUMN, $data['collection']) + ['has_visit_meeting_section' => true];
            $plans = [];
            foreach (self::PLANS as $name => [$method, $prefix]) {
                $inUse = $facilities->contains(fn ($f) => $f['method'] === $method);
                $plan = $inUse ? ($data["{$name}Plan"] ?? null) : null;
                $plans[$name] = $plan;
                $general = $plan && $plan['type'] === 'general';
                $columns["{$prefix}_seasonality_type"] = $plan ? self::PLAN_TYPES[$plan['type']] : null;
                $columns["{$prefix}_seasonality_interval"] = $plan ? self::PLAN_INTERVALS[$plan['interval']] : null;
                $columns["{$prefix}_general_seasonality"] = $general ? $this->planJson($plan, $plan['months'] ?? [], $plan['quarters'] ?? []) : null;
            }
            $study->update($columns);

            foreach ($study->meetings()->get() as $meeting) {
                $row = $facilities->get((int) $meeting->meeting_type_id);
                $method = $row['method'];
                $hasValue = $method !== self::FB_PERCENT;
                $value = $hasValue ? (float) $row['value'] : 0.0;
                $escalation = $hasValue ? (float) $row['escalation'] : 0.0;
                [$type, $interval, $terms] = CollectionTerms::stored($row['terms']);

                $update = [
                    'f&b_facilities' => $method,
                    'cover_value' => RevenueParts::amount($method === self::DAILY_RENT ? $value : 0),
                    'charges_value_per_guest' => $method === self::PER_GUEST ? RevenueParts::amount($value) : null,
                    'chosen_meeting_currency' => (string) $row['currency'],
                    'charges_value_escalation_rate' => RevenueParts::amount($escalation),
                    'charges_value_at_operation_date' => $hasValue ? RevenueParts::amount(FoodsRevenueService::coverValueAtOperation($value, $escalation, $power)) : null,
                    'charges_value_annual_escalation_rate' => RevenueParts::amount($hasValue ? $row['annual'] : 0),
                    'guest_capture_cover_percentage' => $hasValue ? json_decode(RevenueParts::json((array) ($row['occupancy'] ?? []), $yearKeys), true) : [],
                    'percentage_from_f_and_b_revenues' => $method === self::FB_PERCENT ? json_decode(RevenueParts::json((array) ($row['fb'] ?? []), $yearKeys), true) : [],
                    'collection_policy_type' => $type,
                    'collection_policy_interval' => $interval,
                    'collection_policy_value' => $terms ?? '',
                ];

                foreach (self::PLANS as $name => [$planMethod, , $column]) {
                    $plan = $plans[$name];
                    $update[$column] = ($plan && $plan['type'] === 'per_facility' && $method === $planMethod)
                        ? json_decode($this->planJson($plan, (array) ($plan['perMonths'][$meeting->meeting_type_id] ?? []), (array) ($plan['perQuarters'][$meeting->meeting_type_id] ?? [])), true)
                        : null;
                }

                $meeting->update($update);
            }
        });
    }

    /** The JSON of a plan as the old application kept it: flat = equal months, quarterly = four quarters, monthly = the twelve months. */
    private function planJson(array $plan, array $months, array $quarters): string
    {
        if ($plan['interval'] === 'quarterly') {
            return json_encode(array_map(fn ($quarter) => (string) CollectionTerms::number($quarters[$quarter] ?? 0), array_combine(self::QUARTERS, self::QUARTERS)));
        }
        if ($plan['interval'] === 'flat') {
            return json_encode(array_fill_keys(RevenueParts::MONTHS, rtrim(rtrim(sprintf('%.10F', 100 / 12), '0'), '.')));
        }

        return RevenueParts::json($months, RevenueParts::MONTHS);
    }
}
