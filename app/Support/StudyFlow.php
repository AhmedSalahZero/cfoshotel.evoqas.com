<?php

namespace App\Support;

use App\Models\HospitalitySector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — StudyFlow (the page that follows when a page of a study is saved with "Save & next")
//  Location: app/Support/StudyFlow.php
//
//  The order is the one of the previous application (getRedirectUrlName): the revenue pages, then the expense pages (the rooms, food,
//  gaming, meetings and other revenue ones, then energy, general, marketing and property), management fees, start-up cost, the three
//  investment pages, then the income statement and the cash in / out. A page the study does not use (casino, meetings, other revenue switched
//  off) is skipped: the pages come from StudyMenu, which already leaves them out. After the last one the person goes back to the list of studies.
// ══════════════════════════════════════════════════════════════════

final class StudyFlow
{
    /** The keys are the ones of StudyMenu. */
    private const ORDER = [
        'study', 'rooms', 'foods', 'casinos', 'meetings', 'others',
        'expenses.rooms', 'expenses.foods', 'expenses.casinos', 'expenses.meetings', 'expenses.others',
        'expenses.energy', 'expenses.general', 'expenses.marketing', 'expenses.property', 'expenses.management', 'expenses.startup',
        'investment.property', 'investment.land', 'investment.ffe',
        'financial.income', 'financial.cash',
    ];

    /** The address of the page after $current, or null when it was the last one. */
    public static function next(HospitalitySector $study, string $current): ?string
    {
        $position = array_search($current, self::ORDER, true);
        if ($position === false) {
            return null;
        }

        $hrefs = array_column(StudyMenu::for($study), 'href', 'key');
        foreach (array_slice(self::ORDER, $position + 1) as $key) {
            if (isset($hrefs[$key])) {
                return $hrefs[$key];
            }
        }

        return null;
    }

    /** "Save & next" sends after=next: go to the next page of the study. Anything else stays on the page that was saved ($stay). */
    public static function after(Request $request, HospitalitySector $study, string $current, RedirectResponse $stay): RedirectResponse
    {
        if ($request->input('after') !== 'next') {
            return $stay;
        }

        $next = self::next($study, $current);

        return $next === null ? redirect()->route('studies.index') : redirect()->to($next);
    }
}
