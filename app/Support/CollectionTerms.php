<?php

namespace App\Support;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — CollectionTerms (when the hotel gets paid, shared by the five revenue screens)
//  Location: app/Support/CollectionTerms.php
//
//  Every revenue stream says when the money comes in. There is one GENERAL policy for the stream, and, when the study has
//  reservation channels, the choice of a policy PER CHANNEL. A policy is either
//    · the system default: collected every month / quarter / half year / year ("interval"), or
//    · customised: four collection rates that add up to 100 %, each with the days after the sale it is paid ("due in days").
//  The previous application keeps a customised policy as JSON {"rate":[40,30,20,10],"due_in_days":[0,30,60,90]}.
//
//  What the screens exchange, the same shape for the general policy and for each channel:
//    ['type' => 'system_default'|'customize', 'interval' => 'monthly', 'rates' => [0,0,0,0], 'due' => [0,0,0,0]]
// ══════════════════════════════════════════════════════════════════

class CollectionTerms
{
    public const TYPES = ['system_default', 'customize'];

    public const INTERVALS = ['monthly', 'quarterly', 'semi-annually', 'annually'];

    /** How many days after the sale a customised rate can be due. */
    public const DUE_DAYS = [0, 15, 30, 45, 60, 75, 90, 120, 150, 180];

    public const MODE_GENERAL = 'general_collection_terms';

    public const MODE_PER_CHANNEL = 'terms_per_sales_channel';

    public static function blank(): array
    {
        return ['type' => 'system_default', 'interval' => 'monthly', 'rates' => [0, 0, 0, 0], 'due' => [0, 0, 0, 0]];
    }

    /** A saved policy (its type, interval and JSON value) as the screen shows it. */
    public static function present(?string $type, ?string $interval, ?string $value): array
    {
        $saved = (array) json_decode((string) $value, true);
        $four = fn ($list) => collect(range(0, 3))->map(fn ($i) => self::number($list[$i] ?? 0))->all();

        return [
            'type' => $type === 'customize' ? 'customize' : 'system_default',
            'interval' => in_array($interval, self::INTERVALS, true) ? $interval : 'monthly',
            'rates' => $four((array) ($saved['rate'] ?? [])),
            'due' => $four((array) ($saved['due_in_days'] ?? [])),
        ];
    }

    /** What the database keeps for a policy the screen sent: [type, interval, JSON value]. */
    public static function stored(array $terms): array
    {
        if (($terms['type'] ?? 'system_default') === 'customize') {
            return ['customize', null, json_encode([
                'rate' => array_map(fn ($n) => (string) $n, array_values($terms['rates'])),
                'due_in_days' => array_map(fn ($n) => (string) $n, array_values($terms['due'])),
            ])];
        }

        return ['system_default', $terms['interval'] ?? 'monthly', null];
    }

    /** Whole numbers stay whole: 40.0 → 40. */
    public static function number(mixed $value): int|float
    {
        $float = (float) $value;

        return floor($float) === $float ? (int) $float : $float;
    }

    /**
     * The rules of one policy.
     *
     * @param  string  $prefix  where it sits in the form, like "collection.general" or "collection.items.agency"
     */
    public static function rules(string $prefix): array
    {
        return [
            "{$prefix}.type" => ['required', Rule::in(self::TYPES)],
            "{$prefix}.interval" => ['nullable', Rule::in(self::INTERVALS)],
            "{$prefix}.rates" => ['required', 'array', 'size:4'],
            "{$prefix}.rates.*" => ['required', 'numeric', 'min:0', 'max:100'],
            "{$prefix}.due" => ['required', 'array', 'size:4'],
            "{$prefix}.due.*" => ['required', 'integer', Rule::in(self::DUE_DAYS)],
        ];
    }

    /** A customised policy must collect everything: the four rates add up to 100. */
    public static function checkTotals(Validator $validator, array $terms, string $prefix): void
    {
        if (($terms['type'] ?? null) !== 'customize') {
            return;
        }

        $sum = array_sum(array_map('floatval', (array) ($terms['rates'] ?? [])));
        if (abs($sum - 100) > 0.01) {
            $validator->errors()->add("{$prefix}.rates", __('revenue.errors.collection_total'));
        }
    }
}
