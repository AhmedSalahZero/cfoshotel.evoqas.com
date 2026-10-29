<?php

namespace App\Http\Controllers\Hospitality;

use App\Http\Controllers\Controller;
use App\Http\Requests\Revenue\OthersRevenueRequest;
use App\Models\HospitalitySector;
use App\Services\Revenue\OthersRevenueService;
use App\Services\Revenue\RevenueFiguresService;
use App\Support\StudyFlow;
use App\Support\StudyMenu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Other revenue of a study ( /studies/{id}/others )
//  Location: app/Http/Controllers/Hospitality/OthersController.php
//  A person who may only view the study can open the page; saving needs the right to change the study.
//  Replaces OthersController, HospitalitySectorController@viewOtherRevenues / storeOtherRevenues and the OthersRevenues screen-data builder of the old application.
// ══════════════════════════════════════════════════════════════════

class OthersController extends Controller
{
    public function __construct(private readonly OthersRevenueService $service, private readonly RevenueFiguresService $figures) {}

    public function edit(Request $request, HospitalitySector $study): Response
    {
        Gate::authorize('view', $study);
        $study->bindDateTables();
        $user = $request->user();

        return Inertia::render('Studies/Others', [
            'study' => ['id' => $study->id, 'name' => (string) $study->study_name],
            'menu' => StudyMenu::for($study),
            'access' => $study->accessFor($user),
            'readOnly' => (bool) $user->company?->isReadOnly(),
            'aids' => Inertia::defer(fn () => $this->figures->aids($study)),
        ] + $this->service->present($study));
    }

    public function update(OthersRevenueRequest $request, HospitalitySector $study): RedirectResponse
    {
        $study->bindDateTables();
        $this->service->save($study, $request->validated());

        return StudyFlow::after($request, $study, 'others', redirect()->route('studies.others.edit', $study->id))->with('success', __('revenue.messages.others_saved'));
    }
}
