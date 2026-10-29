<?php

namespace App\Support;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Investment (the lists and the small rules of the investment pages)
//  Location: app/Support/Investment.php
//
//  The investment pages are: Property Acquisition Cost, Land & Construction Acquisition Cost and Furniture, Fixtures, and Equipment (FF&E).
//  The values below are exactly the ones the old application stored (they are read back by the calculation), so they must not be renamed.
// ══════════════════════════════════════════════════════════════════

final class Investment
{
    public const PAYMENT_METHODS = ['cash', 'installment', 'customize'];

    /** The installment interval of a land or property purchase. */
    public const INTERVALS = ['monthly', 'quarterly', 'semi-annually', 'annually'];

    /** Due in days of the customised collection of a purchase (0, then 1 to 12 months). */
    public const COLLECTION_DUE = [0, 30, 60, 90, 120, 150, 180, 270, 360];

    /** Due in days of the payment terms of a construction or FF&E cost. */
    public const DUE_DAYS = [0, 15, 30, 45, 60, 75, 90, 120, 150, 180];

    public const HARD_EXECUTION = ['straight-line', 's-curve'];

    public const SOFT_EXECUTION = ['straight-line', 'steady-growth', 'steady-decline'];

    public const FFE_EXECUTION = ['straight-line', 's-curve', 'steady-growth', 'steady-decline'];

    public const LOAN_TYPES = [
        'normal', 'step-up', 'step-down', 'grace_period_with_capitalization', 'grace_period_without_capitalization', 'grace_step-up_with_capitalization',
        'grace_step-up_without_capitalization', 'grace_step-down_with_capitalization', 'grace_step-down_without_capitalization',
    ];

    /** Spelt as the old application spelt them ("quartly"): the calculation reads these words. */
    public const LOAN_INSTALLMENT = ['monthly', 'quartly', 'semi annually'];

    public const LOAN_STEP = ['quartly', 'semi annually', 'annually'];

    /** The three lines of the property cost breakdown: the name the old application stored and whether the line is depreciated. */
    public const BREAKDOWN = [
        ['name' => 'Land Cost', 'depreciated' => false],
        ['name' => 'Building Cost', 'depreciated' => true],
        ['name' => 'Furniture, Fixture & Equipment Cost', 'depreciated' => true],
    ];

    public const BREAKDOWN_MODEL = 'property';

    public const FFE_MODEL = 'ffe';

    public const FFE_ROWS = 10;

    public const REPLACEMENT_BUILDING_NAME = 'Refurbishment & Renovation';

    public const REPLACEMENT_FFE_NAME = 'Furniture, Fixture & Equipment';

    /** Depreciation duration in years: 3 to 25 (and 50 for the building of a property). */
    public static function depreciationYears(bool $fifty = false): array
    {
        return array_merge(range(3, 25), $fifty ? [50] : []);
    }

    /** Replacement interval in years. */
    public static function replacementYears(): array
    {
        return range(1, 5);
    }

    public static function hasGrace(string $loanType): bool
    {
        return ! in_array($loanType, ['normal', 'step-up', 'step-down'], true);
    }

    public static function hasStepUp(string $loanType): bool
    {
        return in_array($loanType, ['step-up', 'grace_step-up_with_capitalization', 'grace_step-up_without_capitalization'], true);
    }

    public static function hasStepDown(string $loanType): bool
    {
        return in_array($loanType, ['step-down', 'grace_step-down_with_capitalization', 'grace_step-down_without_capitalization'], true);
    }
}
