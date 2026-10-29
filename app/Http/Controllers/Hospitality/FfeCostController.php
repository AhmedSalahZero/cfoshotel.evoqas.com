<?php

namespace App\Http\Controllers\Hospitality;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investment\FfeCostRequest;
use App\Models\HospitalitySector;
use App\Services\Investment\FfeCostService;
use App\Support\StudyFlow;
use App\Support\StudyMenu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

// CFO Hotels — the furniture, fixtures and equipment screen of a study ( /studies/{id}/ffe-cost ).
// Location: app/Http/Controllers/Hospitality/FfeCostController.php
// Replaces viewFFECost / storeFFECost of the old HospitalitySectorController.

class FfeCostController extends Controller
{
    public function __construct(private readonly FfeCostService $service) {}

    public function edit(Request $request, HospitalitySector $study): Response
    {
        Gate::authorize('view', $study);
        $study->bindDateTables();
        $user = $request->user();

        return Inertia::render('Studies/FfeCost', [
            'study' => ['id' => $study->id, 'name' => (string) $study->study_name],
            'menu' => StudyMenu::for($study),
            'access' => $study->accessFor($user),
            'readOnly' => (bool) $user->company?->isReadOnly(),
        ] + $this->service->present($study));
    }

    public function update(FfeCostRequest $request, HospitalitySector $study): RedirectResponse
    {
        $study->bindDateTables();
        $this->service->save($study, $request->validated());

        return StudyFlow::after($request, $study, 'investment.ffe', redirect()->route('studies.investment.ffe.edit', $study->id))->with('success', __('investment.messages.ffe_saved'));
    }
}
