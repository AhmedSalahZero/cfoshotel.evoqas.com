<?php

namespace App\Http\Controllers\Hospitality;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expenses\ManagementFeesRequest;
use App\Models\HospitalitySector;
use App\Services\Expenses\ManagementFeesService;
use App\Support\StudyFlow;
use App\Support\StudyMenu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

// CFO Hotels — the management fees screen of a study ( /studies/{id}/management-fees ).
// Location: app/Http/Controllers/Hospitality/ManagementFeesController.php
// Replaces viewManagementFees / storeManagementFees of the old HospitalitySectorController.

class ManagementFeesController extends Controller
{
    public function __construct(private readonly ManagementFeesService $fees) {}

    public function edit(Request $request, HospitalitySector $study): Response
    {
        Gate::authorize('view', $study);
        $study->bindDateTables();
        $user = $request->user();

        return Inertia::render('Studies/ManagementFees', [
            'study' => ['id' => $study->id, 'name' => (string) $study->study_name],
            'menu' => StudyMenu::for($study),
            'access' => $study->accessFor($user),
            'readOnly' => (bool) $user->company?->isReadOnly(),
            'revenue' => Inertia::defer(fn () => $this->fees->revenue($study)),
        ] + $this->fees->present($study));
    }

    public function update(ManagementFeesRequest $request, HospitalitySector $study): RedirectResponse
    {
        $study->bindDateTables();
        $this->fees->save($study, $request->validated());

        return StudyFlow::after($request, $study, 'expenses.management', redirect()->route('studies.management.edit', $study->id))->with('success', __('expenses.messages.management_saved'));
    }
}
