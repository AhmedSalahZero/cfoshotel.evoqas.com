<?php

namespace App\Http\Controllers\Hospitality;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investment\PropertyAcquisitionRequest;
use App\Models\HospitalitySector;
use App\Services\Investment\PropertyAcquisitionService;
use App\Support\StudyFlow;
use App\Support\StudyMenu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

// CFO Hotels — the property acquisition screen of a study ( /studies/{id}/property-acquisition-costs ).
// Location: app/Http/Controllers/Hospitality/PropertyAcquisitionController.php
// Replaces viewPropertyAcquisitionCosts / storePropertyAcquisitionCosts of the old HospitalitySectorController.

class PropertyAcquisitionController extends Controller
{
    public function __construct(private readonly PropertyAcquisitionService $service) {}

    public function edit(Request $request, HospitalitySector $study): Response
    {
        Gate::authorize('view', $study);
        $study->bindDateTables();
        $user = $request->user();

        return Inertia::render('Studies/PropertyAcquisition', [
            'study' => ['id' => $study->id, 'name' => (string) $study->study_name],
            'menu' => StudyMenu::for($study),
            'access' => $study->accessFor($user),
            'readOnly' => (bool) $user->company?->isReadOnly(),
        ] + $this->service->present($study));
    }

    public function update(PropertyAcquisitionRequest $request, HospitalitySector $study): RedirectResponse
    {
        $study->bindDateTables();
        $this->service->save($study, $request->validated());

        return StudyFlow::after($request, $study, 'investment.property', redirect()->route('studies.investment.property.edit', $study->id))->with('success', __('investment.messages.property_saved'));
    }
}
