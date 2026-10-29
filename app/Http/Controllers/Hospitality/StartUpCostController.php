<?php

namespace App\Http\Controllers\Hospitality;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expenses\StartUpCostRequest;
use App\Models\HospitalitySector;
use App\Services\Expenses\StartUpCostService;
use App\Support\StudyFlow;
use App\Support\StudyMenu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

// CFO Hotels — the start-up cost screen of a study ( /studies/{id}/start-up-cost ).
// Location: app/Http/Controllers/Hospitality/StartUpCostController.php
// Replaces viewStartUpCost / storeStartUpCost of the old HospitalitySectorController.

class StartUpCostController extends Controller
{
    public function __construct(private readonly StartUpCostService $startUp) {}

    public function edit(Request $request, HospitalitySector $study): Response
    {
        Gate::authorize('view', $study);
        $study->bindDateTables();
        $user = $request->user();

        return Inertia::render('Studies/StartUpCost', [
            'study' => ['id' => $study->id, 'name' => (string) $study->study_name],
            'menu' => StudyMenu::for($study),
            'access' => $study->accessFor($user),
            'readOnly' => (bool) $user->company?->isReadOnly(),
        ] + $this->startUp->present($study));
    }

    public function update(StartUpCostRequest $request, HospitalitySector $study): RedirectResponse
    {
        $study->bindDateTables();
        $this->startUp->save($study, $request->validated());

        return StudyFlow::after($request, $study, 'expenses.startup', redirect()->route('studies.startup.edit', $study->id))->with('success', __('expenses.messages.startup_saved'));
    }
}
