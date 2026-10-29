<?php

namespace App\Http\Controllers\Hospitality;

use App\Http\Controllers\Controller;
use App\Http\Requests\Revenue\FoodsRevenueRequest;
use App\Models\HospitalitySector;
use App\Services\Revenue\FoodsRevenueService;
use App\Services\Revenue\RevenueFiguresService;
use App\Support\StudyFlow;
use App\Support\StudyMenu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Food and beverage revenue of a study ( /studies/{id}/foods )
//  Location: app/Http/Controllers/Hospitality/FoodsController.php
//  A person who may only view the study can open the page; saving needs the right to change the study.
//  Replaces FoodsController, HospitalitySectorController@viewFoods / storeFoods and the Foods screen-data builder of the old application.
// ══════════════════════════════════════════════════════════════════

class FoodsController extends Controller
{
    public function __construct(private readonly FoodsRevenueService $foods, private readonly RevenueFiguresService $figures) {}

    public function edit(Request $request, HospitalitySector $study): Response
    {
        Gate::authorize('view', $study);
        $study->bindDateTables();
        $user = $request->user();

        return Inertia::render('Studies/Foods', [
            'study' => ['id' => $study->id, 'name' => (string) $study->study_name],
            'menu' => StudyMenu::for($study),
            'access' => $study->accessFor($user),
            'readOnly' => (bool) $user->company?->isReadOnly(),
            'aids' => Inertia::defer(fn () => $this->figures->aids($study)),
        ] + $this->foods->present($study));
    }

    public function update(FoodsRevenueRequest $request, HospitalitySector $study): RedirectResponse
    {
        $study->bindDateTables();
        $this->foods->save($study, $request->validated());

        return StudyFlow::after($request, $study, 'foods', redirect()->route('studies.foods.edit', $study->id))->with('success', __('revenue.messages.foods_saved'));
    }
}
