<?php

namespace App\Http\Controllers\Hospitality;

use App\Http\Controllers\Controller;
use App\Http\Requests\Revenue\CasinosRevenueRequest;
use App\Models\HospitalitySector;
use App\Services\Revenue\CasinosRevenueService;
use App\Services\Revenue\RevenueFiguresService;
use App\Support\StudyFlow;
use App\Support\StudyMenu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Gaming revenue of a study ( /studies/{id}/casinos )
//  Location: app/Http/Controllers/Hospitality/CasinosController.php
//  A person who may only view the study can open the page; saving needs the right to change the study.
//  Replaces CasinosController, HospitalitySectorController@viewCasinos / storeCasinos and the Casinos screen-data builder of the old application.
// ══════════════════════════════════════════════════════════════════

class CasinosController extends Controller
{
    public function __construct(private readonly CasinosRevenueService $service, private readonly RevenueFiguresService $figures) {}

    public function edit(Request $request, HospitalitySector $study): Response
    {
        Gate::authorize('view', $study);
        $study->bindDateTables();
        $user = $request->user();

        return Inertia::render('Studies/Casinos', [
            'study' => ['id' => $study->id, 'name' => (string) $study->study_name],
            'menu' => StudyMenu::for($study),
            'access' => $study->accessFor($user),
            'readOnly' => (bool) $user->company?->isReadOnly(),
            'aids' => Inertia::defer(fn () => $this->figures->aids($study)),
        ] + $this->service->present($study));
    }

    public function update(CasinosRevenueRequest $request, HospitalitySector $study): RedirectResponse
    {
        $study->bindDateTables();
        $this->service->save($study, $request->validated());

        return StudyFlow::after($request, $study, 'casinos', redirect()->route('studies.casinos.edit', $study->id))->with('success', __('revenue.messages.casinos_saved'));
    }
}
