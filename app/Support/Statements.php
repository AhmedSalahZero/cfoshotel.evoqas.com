<?php

namespace App\Support;

use App\Models\HospitalitySector;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Statements (the list of statement screens of a study)
//  Location: app/Support/Statements.php
//
//  The previous application had 16 routes for them. Here they are 9 families, each with its types:
//    collection    receivables of the rooms, F&B, gaming, meetings, other revenue, and all together
//    inventory     disposable inventory of rooms, F&B, gaming, and all together
//    disposables   payments for the disposables of the same
//    prepaid       fixed expenses paid in advance: general, sales & marketing, property, energy, and all together
//    fees          the incentive management fee payable
//    taxes         property taxes, property insurance, corporate taxes
//    assets        fixed assets, fixed assets suppliers, construction payable
//    loans         the repayment schedule of the property, land, hard construction and FF&E loans
//  A type that belongs to a stream the study does not use (gaming, meetings, other revenue) does not exist for that study.
// ══════════════════════════════════════════════════════════════════

class Statements
{
    /** family => [type => column that says the stream is used (null = always)]. A family without types has one entry named '-'. */
    public const FAMILIES = [
        'collection' => ['rooms' => null, 'foods' => null, 'gaming' => 'has_casino_section', 'meetings' => 'has_meeting_section', 'others' => 'has_other_section', 'total' => null],
        'inventory' => ['rooms' => null, 'foods' => null, 'gaming' => 'has_casino_section', 'total' => null],
        'disposables' => ['rooms' => null, 'foods' => null, 'gaming' => 'has_casino_section', 'total' => null],
        'prepaid' => ['general' => null, 'marketing' => null, 'property' => null, 'energy' => null, 'total' => null],
        'fees' => ['-' => null],
        'taxes' => ['property' => null, 'insurance' => null, 'corporate' => null],
        'assets' => ['assets' => null, 'suppliers' => null, 'payable' => null],
        'loans' => ['property' => null, 'land' => null, 'hard-construction' => null, 'ffe' => null],
    ];

    /** the key the previous application used for the type of a statement of the collection / inventory / disposables families */
    public const STREAM_KEY = ['rooms' => 'rooms', 'foods' => 'foods', 'gaming' => 'gaming', 'meetings' => 'meetings', 'others' => 'others', 'total' => 'total'];

    /** the types a study has for a family, in order */
    public static function types(HospitalitySector $study, string $family): array
    {
        $types = [];
        foreach (self::FAMILIES[$family] ?? [] as $type => $usedColumn) {
            if ($usedColumn === null || $study->{$usedColumn}) {
                $types[] = $type;
            }
        }

        return $types;
    }

    /** Whether the study has this statement. */
    public static function exists(HospitalitySector $study, string $family, string $type): bool
    {
        return in_array($type, self::types($study, $family), true);
    }

    /** The first type of a family (where the menu leads to). */
    public static function first(HospitalitySector $study, string $family): string
    {
        return self::types($study, $family)[0] ?? '-';
    }
}
