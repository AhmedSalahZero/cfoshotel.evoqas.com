<?php

namespace App\Support;

use App\Models\DepartmentExpense;
use App\Models\Acquisition;
use App\Models\FFE;
use App\Models\HospitalitySector;
use App\Models\PropertyAcquisition;
use App\Services\Reports\ReportService;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — StudyMenu (the pages of one study, shown as tabs at the top of each of them)
//  Location: app/Support/StudyMenu.php
//
//  Study (the form of the study itself) is always there. Each revenue stream has its own page, and appears only when
//  the study uses that stream (casino, meetings and other revenue can be switched off in the study form).
//  A page is listed here by the step that builds it; "done" means the person has opened and saved that page once.
// ══════════════════════════════════════════════════════════════════

class StudyMenu
{
    /** key => [route name, column that says the stream is used (null = always), column that says the page was saved]. Pages are added by the step that builds them. */
    private const PAGES = [
        'rooms' => ['studies.rooms.edit', null, 'has_visit_room_section'],
        'foods' => ['studies.foods.edit', null, 'has_visit_food_section'],
        'casinos' => ['studies.casinos.edit', 'has_casino_section', 'has_visit_casino_section'],
        'meetings' => ['studies.meetings.edit', 'has_meeting_section', 'has_visit_meeting_section'],
        'others' => ['studies.others.edit', 'has_other_section', 'has_visit_other_section'],
        'revenue' => ['studies.revenue.show', null, null],
    ];

    /** The expense pages: the page, the column that says the stream is used (null = always). */
    private const EXPENSES = [
        'rooms' => null, 'foods' => null, 'casinos' => 'has_casino_section', 'meetings' => 'has_meeting_section', 'others' => 'has_other_section',
        'general' => null, 'marketing' => null, 'property' => null, 'energy' => null,
    ];

    /** @return array<int, array{key: string, href: string, done: bool}> */
    public static function for(HospitalitySector $study): array
    {
        $menu = [['key' => 'study', 'href' => route('studies.edit', $study->id), 'done' => true]];

        foreach (self::PAGES as $key => [$route, $usedColumn, $doneColumn]) {
            if ($usedColumn !== null && ! $study->{$usedColumn}) {
                continue;
            }
            $menu[] = ['key' => $key, 'href' => route($route, $study->id), 'done' => $doneColumn !== null && (bool) $study->{$doneColumn}];
        }

        // the investment: the property bought as a whole, the land and its construction, the furniture, fixtures and equipment (done once saved)
        $menu[] = ['key' => 'investment.property', 'href' => route('studies.investment.property.edit', $study->id), 'done' => PropertyAcquisition::query()->where('hospitality_sector_id', $study->id)->exists()];
        $menu[] = ['key' => 'investment.land', 'href' => route('studies.investment.land.edit', $study->id), 'done' => Acquisition::query()->where('hospitality_sector_id', $study->id)->exists()];
        $menu[] = ['key' => 'investment.ffe', 'href' => route('studies.investment.ffe.edit', $study->id), 'done' => FFE::query()->where('hospitality_sector_id', $study->id)->exists()];

        // an expense page is "done" once it holds at least one line
        $written = DepartmentExpense::query()->where('hospitality_sector_id', $study->id)->distinct()->pluck('model_name')->all();
        foreach (self::EXPENSES as $page => $usedColumn) {
            if ($usedColumn !== null && ! $study->{$usedColumn}) {
                continue;
            }
            $menu[] = ['key' => 'expenses.'.$page, 'href' => route('studies.expenses.edit', [$study->id, $page]), 'done' => in_array(ExpensePages::PAGES[$page]['model'], $written, true)];
        }

        // management fees close the expenses
        $menu[] = ['key' => 'expenses.management', 'href' => route('studies.management.edit', $study->id), 'done' => $study->managementFees()->exists()];

        $menu[] = ['key' => 'expenses.startup', 'href' => route('studies.startup.edit', $study->id), 'done' => in_array('start', $written, true)];

        // the results of the expenses sit with the other results
        $menu[] = ['key' => 'expenses.results', 'href' => route('studies.expenses.results', $study->id), 'done' => false];

        // the statements: one tab for each family (collection, inventory, ...); its first type is where the tab leads, the others are chosen on the page
        foreach (array_keys(Statements::FAMILIES) as $family) {
            $type = Statements::first($study, $family);
            $menu[] = ['key' => 'statements.'.$family, 'href' => route('studies.statements.show', array_filter([$study->id, $family, $type === '-' ? null : $type])), 'done' => false];
        }

        // the financial statements (income statement, cash in / out, balance sheet, ratios) and the dashboard close the menu
        foreach (ReportService::REPORTS as $report) {
            $menu[] = ['key' => 'financial.'.$report, 'href' => route('studies.reports.show', [$study->id, $report]), 'done' => false];
        }
        $menu[] = ['key' => 'dashboard', 'href' => route('studies.dashboard.show', $study->id), 'done' => false];

        return $menu;
    }
}
