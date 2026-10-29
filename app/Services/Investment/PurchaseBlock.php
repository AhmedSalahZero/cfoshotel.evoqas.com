<?php

namespace App\Services\Investment;

use App\Services\Revenue\RevenueParts;
use App\Support\CollectionTerms;
use App\Support\Investment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — PurchaseBlock (the purchase of the land, or of the property)
//  Location: app/Services/Investment/PurchaseBlock.php
//
//  The land (on the Land & Construction page) and the property (on the Property page) are bought the same way: a date, a cost with a contingency,
//  a payment method (cash, installment or customize) and the equity funding rate (the rest is funded by the debt of the loan form).
//  The two kinds only differ in the names of their columns (land_purchase_cost / property_purchase_cost ...), which is what `$kind` ("land" or "property") picks.
//
//  What the screen exchanges:
//    ['date' => 3, 'cost' => 1000, 'contingency' => 5, 'method' => 'installment', 'first' => 20, 'second' => 30, 'after' => 6, 'count' => 4,
//     'interval' => 'quarterly', 'terms' => ['rates' => [100,0,0,0], 'due' => [0,0,0,0]], 'equity' => 100]
//  The total cost, the balance rate, the equity and debt amounts are worked out from these (on the screen as the user types, and by the calculation).
// ══════════════════════════════════════════════════════════════════

class PurchaseBlock
{
    public static function present(?Model $record, string $kind, int $defaultDate): array
    {
        $number = fn (string $column, $fallback = 0) => CollectionTerms::number($record?->{$column} ?: $fallback);
        $saved = (array) json_decode((string) $record?->collection_policy_value, true);
        $four = fn ($list) => collect(range(0, 3))->map(fn ($i) => CollectionTerms::number(((array) $list)[$i] ?? 0))->all();
        $method = $record?->{"{$kind}_payment_method"};
        $interval = $record?->installment_interval;
        $equity = $record?->{"{$kind}_equity_funding_rate"};

        return [
            'date' => $record && $record->purchase_date !== null && $record->purchase_date !== '' ? (int) $record->purchase_date : $defaultDate,
            'cost' => $number("{$kind}_purchase_cost"),
            'contingency' => $number("{$kind}_contingency_rate"),
            'method' => in_array($method, Investment::PAYMENT_METHODS, true) ? $method : 'cash',
            'first' => $number("first_{$kind}_down_payment_percentage"),
            'second' => $number("second_{$kind}_down_payment_percentage"),
            'after' => (int) ($record?->{"{$kind}_after_month"} ?: 0),
            'count' => max(1, (int) ($record?->{"{$kind}_installment_count"} ?: 1)),
            'interval' => in_array($interval, Investment::INTERVALS, true) ? $interval : 'monthly',
            'terms' => ['rates' => $four($saved['rate'] ?? []), 'due' => $four($saved['due_in_days'] ?? [])],
            'equity' => $equity === null || $equity === '' ? 100 : CollectionTerms::number($equity),
        ];
    }

    /** The columns the old application kept for a purchase. Fields of another payment method are set to nothing, as it did. */
    public static function columns(array $block, string $kind): array
    {
        $installment = ($block['method'] ?? 'cash') === 'installment';

        return [
            'purchase_date' => (int) $block['date'],
            "{$kind}_purchase_cost" => RevenueParts::amount($block['cost'] ?? 0),
            "{$kind}_contingency_rate" => RevenueParts::amount($block['contingency'] ?? 0),
            "{$kind}_payment_method" => (string) $block['method'],
            "first_{$kind}_down_payment_percentage" => $installment ? RevenueParts::amount($block['first'] ?? 0) : '0',
            "second_{$kind}_down_payment_percentage" => $installment ? RevenueParts::amount($block['second'] ?? 0) : '0',
            "{$kind}_after_month" => $installment ? (string) (int) ($block['after'] ?? 0) : '0',
            'installment_interval' => $installment ? (string) $block['interval'] : '0',
            "{$kind}_installment_count" => (string) max(1, (int) ($block['count'] ?? 1)),
            "{$kind}_equity_funding_rate" => RevenueParts::amount($block['equity'] ?? 100),
            'collection_policy_type' => 'customize',
            'collection_policy_value' => json_encode([
                'rate' => array_map(fn ($n) => (string) $n, array_values((array) ($block['terms']['rates'] ?? [0, 0, 0, 0]))),
                'due_in_days' => array_map(fn ($n) => (string) $n, array_values((array) ($block['terms']['due'] ?? [0, 0, 0, 0]))),
            ]),
        ];
    }

    /** The checks of a purchase. `$prefix` is where the block sits in the request ("land" or "property"). */
    public static function rules(string $prefix, array $block, array $monthIndexes): array
    {
        $method = $block['method'] ?? null;
        $installment = $method === 'installment' ? 'required' : 'nullable';
        $rules = [
            "{$prefix}.date" => ['required', 'integer', Rule::in($monthIndexes)],
            "{$prefix}.cost" => ['required', 'numeric', 'min:0', 'max:1000000000000'],
            "{$prefix}.contingency" => ['required', 'numeric', 'min:0', 'max:100'],
            "{$prefix}.method" => ['required', Rule::in(Investment::PAYMENT_METHODS)],
            "{$prefix}.equity" => ['required', 'numeric', 'min:0', 'max:100'],
            "{$prefix}.first" => [$installment, 'numeric', 'min:0', 'max:100'],
            "{$prefix}.second" => [$installment, 'numeric', 'min:0', 'max:100'],
            "{$prefix}.after" => [$installment, 'integer', 'min:0', 'max:600'],
            "{$prefix}.count" => [$installment, 'integer', 'min:1', 'max:600'],
            "{$prefix}.interval" => [$installment, Rule::in(Investment::INTERVALS)],
        ];
        if ($method === 'customize') {
            $rules["{$prefix}.terms.rates"] = ['required', 'array', 'size:4'];
            $rules["{$prefix}.terms.rates.*"] = ['required', 'numeric', 'min:0', 'max:100'];
            $rules["{$prefix}.terms.due"] = ['required', 'array', 'size:4'];
            $rules["{$prefix}.terms.due.*"] = ['required', 'integer', Rule::in(Investment::COLLECTION_DUE)];
        }

        return $rules;
    }

    /** What cannot be said by a single field: the two down payments within 100 %, the customised rates adding up to 100 %. [attribute => message key] */
    public static function problems(string $prefix, array $block): array
    {
        $problems = [];
        if (($block['method'] ?? null) === 'installment' && (float) ($block['first'] ?? 0) + (float) ($block['second'] ?? 0) > 100.0001) {
            $problems["{$prefix}.second"] = 'investment.errors.down_payments';
        }
        if (($block['method'] ?? null) === 'customize' && abs(array_sum(array_map('floatval', (array) ($block['terms']['rates'] ?? []))) - 100) > 0.005) {
            $problems["{$prefix}.terms.rates"] = 'investment.errors.rates_total';
        }

        return $problems;
    }

    /** The debt share of a purchase, 0 to 100: a loan is asked for when it is above 0. */
    public static function debt(array $block): float
    {
        return 100 - (float) ($block['equity'] ?? 100);
    }
}
