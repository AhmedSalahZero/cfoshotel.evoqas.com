<?php

namespace App\Http\Controllers\Hospitality;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investment\LandAcquisitionRequest;
use App\Models\HospitalitySector;
use App\Services\Investment\LandAcquisitionService;
use App\Support\StudyFlow;
use App\Support\StudyMenu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

// CFO Hotels — the land and construction screen of a study ( /studies/{id}/land-acquisition-costs ).
// Location: app/Http/Controllers/Hospitality/LandAcquisitionController.php
// Replaces viewLandAcquisitionCosts / storeLandAcquisitionCosts of the old HospitalitySectorController.

class LandAcquisitionController extends Controller
{
    public function __construct(private readonly LandAcquisitionService $service) {}

    public function edit(Request $request, HospitalitySector $study): Response
    {
        Gate::authorize('view', $study);
        $study->bindDateTables();
        $user = $request->user();

        return Inertia::render('Studies/LandAcquisition', [
            'study' => ['id' => $study->id, 'name' => (string) $study->study_name],
            'menu' => StudyMenu::for($study),
            'access' => $study->accessFor($user),
            'readOnly' => (bool) $user->company?->isReadOnly(),
        ] + $this->service->present($study));
    }

    public function update(LandAcquisitionRequest $request, HospitalitySector $study): RedirectResponse
    {
        $study->bindDateTables();
        $this->service->save($study, $request->validated());

        return StudyFlow::after($request, $study, 'investment.land', redirect()->route('studies.investment.land.edit', $study->id))->with('success', __('investment.messages.land_saved'));
    }
}
