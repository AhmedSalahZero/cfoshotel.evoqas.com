<?php

namespace App\Services\Revenue;

use App\Models\HospitalitySector;
use App\Models\SalesChannel;
use App\Support\CollectionTerms;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — RevenueParts (the pieces every revenue screen shares)
//  Location: app/Services/Revenue/RevenueParts.php
//
//  · yearMap / monthMap   a saved list ("{"2":"75","3":"75"}") as one number per operating year / per month;
//  · channels / saveChannels   the reservation channels with their share of the revenue, discount and collection terms;
//  · generalTerms / saveGeneralTerms   the collection policy of one stream, kept in columns of the study.
//  Numbers are kept the way the previous application kept them (JSON in text columns) so the calculations read them unchanged.
// ══════════════════════════════════════════════════════════════════

class RevenueParts
{
    public const MONTHS = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];

    /** An array that always reaches the screen as an object ({"2": 75}), even when its keys are 0, 1, 2 ... */
    public static function map(array $values): object
    {
        return (object) $values;
    }

    /** One number per operating year, from a saved JSON list; missing years are 0. */
    public static function yearMap(?string $json, array $years, int|float $default = 0): object
    {
        $saved = (array) json_decode((string) $json, true);
        $out = [];
        foreach ($years as $year) {
            $out[$year['index']] = array_key_exists($year['index'], $saved) && $saved[$year['index']] !== null && $saved[$year['index']] !== ''
                ? CollectionTerms::number($saved[$year['index']])
                : $default;
        }

        return self::map($out);
    }

    /** One number per month ("01" ... "12") from a saved JSON list. */
    public static function monthMap(?string $json, int|float $default = 100): object
    {
        $saved = (array) json_decode((string) $json, true);
        $out = [];
        foreach (self::MONTHS as $month) {
            $out[$month] = array_key_exists($month, $saved) && $saved[$month] !== null && $saved[$month] !== ''
                ? CollectionTerms::number($saved[$month])
                : $default;
        }

        return self::map($out);
    }

    /** The JSON a map is kept as; only the keys wanted, in their order. */
    public static function json(array $values, array $keys): string
    {
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = (string) CollectionTerms::number($values[$key] ?? 0);
        }

        return json_encode($out);
    }

    // ── reservation channels ─────────────────────────────────────

    /** @return array<int, array{name: string, share: object, discount: object, terms: array}> */
    public static function channels(HospitalitySector $study, array $years): array
    {
        return $study->salesChannels()->orderBy('id')->get()->map(fn (SalesChannel $channel) => [
            'name' => (string) $channel->name,
            'share' => self::yearMap($channel->revenue_share_percentage, $years),
            'discount' => self::yearMap($channel->discount_or_commission, $years),
            'terms' => CollectionTerms::present($channel->collection_policy_type, $channel->collection_policy_interval, $channel->collection_policy_value),
        ])->values()->all();
    }

    public static function saveChannels(HospitalitySector $study, array $sent, array $years): void
    {
        $byName = collect($sent)->keyBy('name');
        $keys = array_column($years, 'index');

        foreach ($study->salesChannels()->get() as $channel) {
            $row = $byName->get($channel->name);
            if (! $row) {
                continue;
            }
            [$type, $interval, $value] = CollectionTerms::stored($row['terms']);

            $channel->update([
                'revenue_share_percentage' => self::json((array) $row['share'], $keys),
                'discount_or_commission' => self::json((array) $row['discount'], $keys),
                'collection_policy_type' => $type,
                'collection_policy_interval' => $interval,
                'collection_policy_value' => $value,
            ]);
        }
    }

    // ── the collection policy of a stream ────────────────────────

    /**
     * @param  string  $stream  rooms | foods | casinos | meetings | others
     * @param  bool  $needsChannels  the policy per channel needs reservation channels (rooms); the policy per facility (food) does not
     * @return array{mode: string, general: array}
     */
    public static function generalTerms(HospitalitySector $study, string $stream, string $modeColumn, bool $needsChannels = true, string $perName = 'per_channel'): array
    {
        $mode = $study->{$modeColumn};

        return [
            'mode' => $mode === CollectionTerms::MODE_PER_CHANNEL && (! $needsChannels || $study->salesChannels()->exists()) ? $perName : 'general',
            'general' => CollectionTerms::present(
                $study->{"{$stream}_general_collection_policy_type"},
                $study->{"{$stream}_general_collection_policy_interval"},
                $study->{"{$stream}_general_collection_policy_value"},
            ),
        ];
    }

    /** The columns of the study a stream's collection policy is saved in (kept as the old application kept them). */
    public static function termsColumns(string $stream, string $modeColumn, array $collection): array
    {
        [$type, $interval, $value] = CollectionTerms::stored($collection['general']);
        $isGeneral = $collection['mode'] === 'general';

        return [
            $modeColumn => $isGeneral ? CollectionTerms::MODE_GENERAL : CollectionTerms::MODE_PER_CHANNEL,
            "{$stream}_general_collection_policy_type" => $type,
            "{$stream}_general_collection_policy_interval" => $isGeneral ? $interval : null,
            "{$stream}_general_collection_policy_value" => $isGeneral ? $value : null,
        ];
    }

    /** "12.5" and 12.5 and "12.50" read the same. */
    public static function amount(mixed $value): string
    {
        $text = rtrim(rtrim(sprintf('%.8F', (float) $value), '0'), '.');

        return $text === '' ? '0' : $text;
    }
}
