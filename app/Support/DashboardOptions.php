<?php

namespace App\Support;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — the two lists of the study dashboard (which revenue stream and which gross profit its charts show)
//  Location: app/Support/DashboardOptions.php
//  The values are the paths the previous application used into its report data, so the same words are accepted.
// ══════════════════════════════════════════════════════════════════

class DashboardOptions
{
    /** value => key of the label (dashboard.streams.<key>) */
    public const REVENUE_STREAMS = [
        'Total Revenues' => 'total',
        'Total Revenues.subItems.Total Rooms Revenue' => 'rooms',
        'Total Revenues.subItems.Total F&B Revenues' => 'foods',
        'Total Revenues.subItems.Total Gaming Revenues' => 'gaming',
        'Total Revenues.subItems.Total Meeting Spaces Revenues' => 'meetings',
        'Total Revenues.subItems.Total Other Revenues' => 'others',
    ];

    public const GROSS_PROFITS = [
        'Departments Gross Profit' => 'total',
        'Departments Gross Profit.subItems.Rooms Gross Profit' => 'rooms',
        'Departments Gross Profit.subItems.F&B Gross Profit' => 'foods',
        'Departments Gross Profit.subItems.Gaming Gross Profit' => 'gaming',
        'Departments Gross Profit.subItems.Meeting Spaces Gross Profit' => 'meetings',
        'Departments Gross Profit.subItems.Other Revenues Gross Profit' => 'others',
    ];

    /** @param  array<string, string>  $list */
    public static function options(array $list): array
    {
        return array_map(fn (string $value, string $key) => ['value' => $value, 'key' => $key], array_keys($list), array_values($list));
    }
}
