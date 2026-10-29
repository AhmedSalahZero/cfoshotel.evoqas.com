<?php

namespace App\Http\Controllers\Hospitality;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expenses\ExpensesRequest;
use App\Models\HospitalitySector;
use App\Services\Expenses\ExpensesService;
use App\Support\ExpensePages;
use App\Support\StudyFlow;
use App\Support\StudyMenu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Expense screens of a study ( /studies/{id}/expenses/{page} )
//  Location: app/Http/Controllers/Hospitality/ExpensesController.php
//  One controller for nine screens: rooms, foods, casinos, meetings, others (direct expenses) and general, marketing, property, energy.
//  A person who may only view the study can open a page; saving needs the right to change the study.
//  Replaces the view/store pairs of HospitalitySectorController for these screens and the screen-data builders of the old application.
// ══════════════════════════════════════════════════════════════════

class ExpensesController extends Controller
{
    public function __construct(private readonly ExpensesService $expenses) {}

    public function edit(Request $request, HospitalitySector $study, string $page): Response
    {
        Gate::authorize('view', $study);
        $definition = ExpensePages::find($page);
        $used = ExpensePages::usedColumn($page);
        abort_if($used !== null && ! $study->{$used}, 404);
        $study->bindDateTables();
        $user = $request->user();

        return Inertia::render('Studies/Expenses', [
            'study' => ['id' => $study->id, 'name' => (string) $study->study_name],
            'menu' => StudyMenu::for($study),
            'access' => $study->accessFor($user),
            'readOnly' => (bool) $user->company?->isReadOnly(),
            'revenue' => Inertia::defer(fn () => $this->expenses->revenue($study, $definition)),
        ] + $this->expenses->present($study, $definition));
    }

    public function update(ExpensesRequest $request, HospitalitySector $study, string $page): RedirectResponse
    {
        $study->bindDateTables();
        $this->expenses->save($study, $request->page(), $request->validated());

        return StudyFlow::after($request, $study, 'expenses.'.$page, redirect()->route('studies.expenses.edit', [$study->id, $page]))->with('success', __('expenses.messages.saved'));
    }
}
