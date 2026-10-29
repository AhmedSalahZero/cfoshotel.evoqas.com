<?php

namespace App\Services\Revenue;

use App\Models\Currency;
use App\Models\HospitalitySector;
use App\Models\Room;
use App\Support\CollectionTerms;
use App\Support\StudyYears;
use Illuminate\Support\Facades\DB;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — RoomsRevenueService (the rooms revenue screen: what it shows and what it saves)
//  Location: app/Services/Revenue/RoomsRevenueService.php
//
//  Ports HospitalitySectorController@viewRooms / storeRooms of the previous application:
//    · the rooms: average daily rate (ADR), its currency, escalation and the ADR at the operation date;
//    · the exchange rate for each operating year (only when the study has an additional currency);
//    · the targeted occupancy for each operating year: general, or per room type;
//    · the maximum monthly occupancy ("seasonality"): general or per room type, flat 100 % or month by month;
//    · the reservation channels' share of revenue and discount for each year, and when the money is collected.
//  The ADR at the operation date is worked out HERE (ADR x (1 + escalation) ^ years from the study start to the operation start),
//  never taken from the browser. The screen shows the same number while the person types, from the same formula.
// ══════════════════════════════════════════════════════════════════

class RoomsRevenueService
{
    public const MODE_COLUMN = 'room_collection_policy_type';

    public const SEASONALITY_TYPES = ['general' => 'general-seasonality', 'per_room' => 'per-room-type-seasonality'];

    public const SEASONALITY_INTERVALS = ['flat' => 'flat-seasonality', 'monthly' => 'monthly-seasonality'];

    public const OCCUPANCY_TYPES = ['general' => 'general_occupancy_rate', 'per_room' => 'occupancy_rate_per_room'];

    // ── what the screen is given ──────────────────────────────────

    public function present(HospitalitySector $study): array
    {
        $years = StudyYears::of($study);
        $rooms = $study->rooms()->orderBy('id')->get();
        $isTotal = $study->isTotalRooms();
        $currencies = $this->currencies($study);
        $first = $rooms->first();

        $general = $study->seasonality_type !== self::SEASONALITY_TYPES['per_room'] || $isTotal;
        $interval = array_search($study->seasonality_interval, self::SEASONALITY_INTERVALS, true) ?: 'flat';
        $perRoomOccupancy = ! $isTotal && $first && $first->occupancy_rate_type === self::OCCUPANCY_TYPES['per_room'];

        return [
            'years' => $years,
            'total' => $isTotal,
            'daysDifference' => (float) $study->getDiffBetweenOperationStartDateAndStudyStartDate(),
            'estimationDate' => (string) $study->getStudyStartDateFormattedForView(),
            'currencies' => $currencies,
            'additionalCurrency' => $study->additional_currency !== null && $study->additional_currency !== '' ? $this->currencyName((int) $study->additional_currency) : null,
            'mainCurrency' => $this->currencyName((int) $study->main_functional_currency),
            'form' => [
                'currency' => (int) ($first?->chosen_room_currency ?: $study->main_functional_currency),
                'rooms' => $rooms->map(fn (Room $room) => [
                    'key' => (int) $room->room_type_id,
                    'name' => $room->getName(),
                    'count' => (int) $room->room_count,
                    'nights' => (int) $room->room_count * 365,
                    'guests' => $room->guest_per_room,
                    'adr' => CollectionTerms::number($room->average_daily_rate ?: 0),
                    'escalation' => CollectionTerms::number($room->average_daily_rate_escalation_rate ?: 0),
                    'annual' => CollectionTerms::number($room->average_daily_rate_annual_escalation_rate ?: 0),
                ])->values()->all(),
                'exchange' => RevenueParts::yearMap($study->exchange_rates, $years, 1),
                'occupancy' => [
                    'mode' => $perRoomOccupancy ? 'per_room' : 'general',
                    'general' => RevenueParts::yearMap($study->general_occupancy_rate, $years),
                    'perRoom' => RevenueParts::map($rooms->mapWithKeys(fn (Room $room) => [(int) $room->room_type_id => RevenueParts::yearMap($room->occupancy_rate_per_room, $years)])->all()),
                ],
                'seasonality' => [
                    'type' => $general ? 'general' : 'per_room',
                    'interval' => $interval,
                    'general' => RevenueParts::monthMap($study->general_seasonality),
                    'perRoom' => RevenueParts::map($rooms->mapWithKeys(fn (Room $room) => [(int) $room->room_type_id => RevenueParts::monthMap($room->seasonality)])->all()),
                ],
                'channels' => RevenueParts::channels($study, $years),
                'collection' => RevenueParts::generalTerms($study, 'rooms', self::MODE_COLUMN),
            ],
        ];
    }

    /** The main currency and, when there is one, the additional currency. */
    private function currencies(HospitalitySector $study): array
    {
        $ids = array_filter([(int) $study->main_functional_currency, (int) $study->additional_currency]);

        return Currency::query()->whereIn('id', $ids)->orderBy('id')->get(['id', 'name'])
            ->map(fn ($currency) => ['id' => (int) $currency->id, 'name' => (string) $currency->name])->values()->all();
    }

    private function currencyName(int $id): string
    {
        return (string) Currency::query()->whereKey($id)->value('name');
    }

    // ── what is saved ─────────────────────────────────────────────

    /** The ADR at the operation date: the rate grown by the escalation for the time between the study start and the operation start. */
    public static function adrAtOperation(float $adr, float $escalationPercent, float $years): float
    {
        return $adr * pow(1 + $escalationPercent / 100, $years);
    }

    public function save(HospitalitySector $study, array $data): void
    {
        DB::transaction(function () use ($study, $data) {
            $years = StudyYears::of($study);
            $yearKeys = array_column($years, 'index');
            $power = (float) $study->getDiffBetweenOperationStartDateAndStudyStartDate();
            $hasAdditional = $study->additional_currency !== null && $study->additional_currency !== '';
            $occupancy = $data['occupancy'];
            $seasonality = $data['seasonality'];
            $perRoomOccupancy = $occupancy['mode'] === 'per_room';
            $perRoomSeasonality = $seasonality['type'] === 'per_room';
            $channels = $study->salesChannels()->count();
            $flat = $seasonality['interval'] === 'flat';

            $columns = [
                'general_occupancy_rate' => $perRoomOccupancy ? null : RevenueParts::json((array) $occupancy['general'], $yearKeys),
                'seasonality_type' => self::SEASONALITY_TYPES[$seasonality['type']],
                'seasonality_interval' => self::SEASONALITY_INTERVALS[$seasonality['interval']],
                'general_seasonality' => $perRoomSeasonality ? null : $this->months((array) $seasonality['general'], $flat),
                'add_sales_channels_share_discount' => $channels,
                'has_visit_room_section' => true,
            ] + RevenueParts::termsColumns('rooms', self::MODE_COLUMN, $data['collection']);

            if ($hasAdditional) {
                $columns['exchange_rates'] = RevenueParts::json((array) $data['exchange'], $yearKeys);
            }

            $study->update($columns);

            $sent = collect($data['rooms'])->keyBy('key');
            foreach ($study->rooms()->get() as $room) {
                $row = $sent->get((int) $room->room_type_id);
                $adr = (float) $row['adr'];

                $room->update([
                    'average_daily_rate' => RevenueParts::amount($adr),
                    'chosen_room_currency' => (string) $data['currency'],
                    'average_daily_rate_escalation_rate' => RevenueParts::amount($row['escalation']),
                    'average_daily_rate_at_operation_date' => RevenueParts::amount(self::adrAtOperation($adr, (float) $row['escalation'], $power)),
                    'average_daily_rate_annual_escalation_rate' => RevenueParts::amount($row['annual']),
                    'occupancy_rate_type' => self::OCCUPANCY_TYPES[$occupancy['mode']],
                    'occupancy_rate_per_room' => $perRoomOccupancy ? RevenueParts::json((array) ($occupancy['perRoom'][$room->room_type_id] ?? []), $yearKeys) : json_encode([]),
                    'seasonality' => $perRoomSeasonality ? $this->months((array) ($seasonality['perRoom'][$room->room_type_id] ?? []), $flat) : null,
                ]);
            }

            if ($channels) {
                RevenueParts::saveChannels($study, $data['channels'] ?? [], $years);
            }
        });
    }

    /** The twelve months as saved; "flat" means 100 % in every month whatever was typed. */
    private function months(array $values, bool $flat): string
    {
        return RevenueParts::json($flat ? array_fill_keys(RevenueParts::MONTHS, 100) : $values, RevenueParts::MONTHS);
    }
}
