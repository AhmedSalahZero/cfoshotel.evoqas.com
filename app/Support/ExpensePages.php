<?php

namespace App\Support;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — ExpensePages (the nine expense screens of a study and what each one asks for)
//  Location: app/Support/ExpensePages.php
//
//  Direct expenses of the five departments (rooms, food and beverage, gaming, meetings, other revenue) and the four undistributed
//  expenses (general, marketing, property, energy) are all kept in one table, department_expenses, one row for each line the person
//  writes. A line belongs to a "model" (the screen: rooms, foods, gaming ...) and a "section" (the table on the screen).
//  The names of the model and of the sections are the ones of the previous application, because its calculation looks them up.
//
//  Kinds of table on these screens:
//    percent     an expense as a percentage of revenues, one box for each operating year
//    cost        an amount that grows with escalation: per night sold, per guest, or a fixed monthly amount
//    disposable  disposable expenses: opening stock, days of stock, cash and deferred payment, a percentage for each year
//    manpower    positions with a net salary and the head count of each month
//    property    property taxes and property insurance, as a percentage of the fixed assets
//
//  "style" of a cost table says which columns of the old table keep its figures (the old application is not consistent about it, and
//  its calculation reads exactly these columns):
//    night       expense_per_night_sold      · at operation: night_expense_at_operation_date · annual: night_annual_escalation_rate
//    guest       expense_per_guest_sold      · at operation: guest_expense_at_operation_date · annual: guest_annual_escalation_rate
//    guestNight  expense_per_night_sold      · at operation: guest_expense_at_operation_date · annual: guest_annual_escalation_rate  (gaming, meetings, other)
//    fixed       as guestNight, and the payment terms in opex_payment_terms
// ══════════════════════════════════════════════════════════════════

class ExpensePages
{
    public const PERCENT_ROOMS = 'variable_expenses_as_percentage_from_rooms_revenues';

    public const PERCENT_TOTAL = 'variable_expenses_as_percentage_from_total_revenues';

    public const PER_NIGHT = 'variable_expenses_as_cost_per_night_sold';

    public const PER_GUEST = 'variable_expenses_as_cost_per_guest';

    public const FIXED = 'fixed_monthly_expenses';

    public const DISPOSABLE = 'disposable_expenses_rate';

    public const MANPOWER = 'rooms_manpower_expense';

    public const PROPERTY = 'other_property_fixed_expenses';

    /** The days of stock and the days a deferred payment is due. */
    public const START_UP = 'start_up_cost';
    public const PRE_OPERATING = 'pre_operating_expense';
    public const DAYS = [0, 15, 30, 45, 60, 75, 90, 120, 150, 180];

    public const TERMS = ['monthly', 'quarterly', 'semi-annually', 'annually'];

    /** The English names the old calculation looks for. */
    public const PROPERTY_NAMES = ['Property Taxes', 'Property Insurance'];

    public const COST_COLUMNS = [
        'night' => ['currency' => 'chosen_night_expense_currency', 'amount' => 'expense_per_night_sold', 'escalation' => 'night_expense_escalation_rate', 'atOperation' => 'night_expense_at_operation_date', 'annual' => 'night_annual_escalation_rate'],
        'guest' => ['currency' => 'chosen_guest_expense_currency', 'amount' => 'expense_per_guest_sold', 'escalation' => 'guest_expense_escalation_rate', 'atOperation' => 'guest_expense_at_operation_date', 'annual' => 'guest_annual_escalation_rate'],
        'guestNight' => ['currency' => 'chosen_night_expense_currency', 'amount' => 'expense_per_night_sold', 'escalation' => 'night_expense_escalation_rate', 'atOperation' => 'guest_expense_at_operation_date', 'annual' => 'guest_annual_escalation_rate'],
        'fixed' => ['currency' => 'chosen_night_expense_currency', 'amount' => 'expense_per_night_sold', 'escalation' => 'night_expense_escalation_rate', 'atOperation' => 'guest_expense_at_operation_date', 'annual' => 'guest_annual_escalation_rate'],
    ];

    /**
     * page => model (as saved), revenue (which revenue the percentages are taken from), flag (the column of the study that says the manpower
     * table is wanted, null = this screen has none), sections (in the order they appear).
     */
    public const PAGES = [
        'rooms' => ['model' => 'rooms', 'revenue' => 'rooms', 'flag' => 'has_rooms_manpower', 'sections' => [
            ['kind' => 'percent', 'name' => self::PERCENT_ROOMS, 'rows' => 5],
            ['kind' => 'cost', 'name' => self::PER_NIGHT, 'style' => 'night', 'rows' => 5],
            ['kind' => 'cost', 'name' => self::PER_GUEST, 'style' => 'guest', 'rows' => 5],
            ['kind' => 'disposable', 'name' => self::DISPOSABLE, 'rows' => 5],
            ['kind' => 'manpower', 'name' => self::MANPOWER, 'rows' => 12],
        ]],
        'foods' => ['model' => 'foods', 'revenue' => 'foods', 'flag' => 'has_foods_manpower', 'sections' => [
            ['kind' => 'percent', 'name' => self::PERCENT_ROOMS, 'rows' => 5],
            ['kind' => 'cost', 'name' => self::PER_GUEST, 'style' => 'guest', 'rows' => 5],
            ['kind' => 'disposable', 'name' => self::DISPOSABLE, 'rows' => 5],
            ['kind' => 'manpower', 'name' => self::MANPOWER, 'rows' => 12],
        ]],
        'casinos' => ['model' => 'gaming', 'revenue' => 'casinos', 'flag' => 'has_casinos_manpower', 'sections' => [
            ['kind' => 'percent', 'name' => self::PERCENT_ROOMS, 'rows' => 5],
            ['kind' => 'cost', 'name' => self::PER_GUEST, 'style' => 'guestNight', 'rows' => 5],
            ['kind' => 'disposable', 'name' => self::DISPOSABLE, 'rows' => 5],
            ['kind' => 'manpower', 'name' => self::MANPOWER, 'rows' => 12],
        ]],
        'meetings' => ['model' => 'meetings', 'revenue' => 'meetings', 'flag' => null, 'sections' => [
            ['kind' => 'percent', 'name' => self::PERCENT_ROOMS, 'rows' => 5],
            ['kind' => 'cost', 'name' => self::PER_GUEST, 'style' => 'guestNight', 'rows' => 5],
        ]],
        'others' => ['model' => 'other', 'revenue' => 'others', 'flag' => 'has_other_revenue_manpower', 'sections' => [
            ['kind' => 'percent', 'name' => self::PERCENT_ROOMS, 'rows' => 5],
            ['kind' => 'cost', 'name' => self::PER_GUEST, 'style' => 'guestNight', 'rows' => 5],
            ['kind' => 'manpower', 'name' => self::MANPOWER, 'rows' => 12],
        ]],
        'general' => ['model' => 'general', 'revenue' => 'total', 'flag' => 'has_sales_and_general_manpower', 'sections' => [
            ['kind' => 'percent', 'name' => self::PERCENT_TOTAL, 'rows' => 5],
            ['kind' => 'cost', 'name' => self::FIXED, 'style' => 'fixed', 'rows' => 5],
            ['kind' => 'manpower', 'name' => self::MANPOWER, 'rows' => 12],
        ]],
        'marketing' => ['model' => 'marketing', 'revenue' => 'total', 'flag' => 'has_sales_and_general_manpower', 'sections' => [
            ['kind' => 'percent', 'name' => self::PERCENT_TOTAL, 'rows' => 5],
            ['kind' => 'cost', 'name' => self::FIXED, 'style' => 'fixed', 'rows' => 5],
            ['kind' => 'manpower', 'name' => self::MANPOWER, 'rows' => 12],
        ]],
        'property' => ['model' => 'property', 'revenue' => 'total', 'flag' => null, 'sections' => [
            ['kind' => 'percent', 'name' => self::PERCENT_ROOMS, 'rows' => 5],
            ['kind' => 'cost', 'name' => self::FIXED, 'style' => 'fixed', 'rows' => 5],
            ['kind' => 'property', 'name' => self::PROPERTY, 'rows' => 2],
        ]],
        'energy' => ['model' => 'energy', 'revenue' => 'total', 'flag' => null, 'sections' => [
            ['kind' => 'percent', 'name' => self::PERCENT_ROOMS, 'rows' => 3],
            ['kind' => 'cost', 'name' => self::FIXED, 'style' => 'fixed', 'rows' => 3],
        ]],
    ];

    public static function keys(): array
    {
        return array_keys(self::PAGES);
    }

    public static function find(string $page): array
    {
        abort_unless(isset(self::PAGES[$page]), 404);

        return ['key' => $page] + self::PAGES[$page];
    }

    /** The first row of every table of the page can be switched off by the study (gaming, meetings and other revenue). */
    public static function usedColumn(string $page): ?string
    {
        return ['casinos' => 'has_casino_section', 'meetings' => 'has_meeting_section', 'others' => 'has_other_section'][$page] ?? null;
    }
}
