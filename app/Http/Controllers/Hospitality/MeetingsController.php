<?php

namespace App\Http\Controllers\Hospitality;

use App\Http\Controllers\Controller;
use App\Http\Requests\Revenue\MeetingsRevenueRequest;
use App\Models\HospitalitySector;
use App\Services\Revenue\MeetingsRevenueService;
use App\Services\Revenue\RevenueFiguresService;
use App\Support\StudyFlow;
use App\Support\StudyMenu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Meeting spaces revenue of a study ( /studies/{id}/meetings )
//  Location: app/Http/Controllers/Hospitality/MeetingsController.php
//  A person who may only view the study can open the page; saving needs the right to change the study.
//  Replaces MeetingsController, HospitalitySectorController@viewMeetings / storeMeetings and the Meetings screen-data builder of the old application.
// ══════════════════════════════════════════════════════════════════

class MeetingsController extends Controller
{
    public function __construct(private readonly MeetingsRevenueService $service, private readonly RevenueFiguresService $figures) {}

    public function edit(Request $request, HospitalitySector $study): Response
    {
        Gate::authorize('view', $study);
        $study->bindDateTables();
        $user = $request->user();

        return Inertia::render('Studies/Meetings', [
            'study' => ['id' => $study->id, 'name' => (string) $study->study_name],
            'menu' => StudyMenu::for($study),
            'access' => $study->accessFor($user),
            'readOnly' => (bool) $user->company?->isReadOnly(),
            'aids' => Inertia::defer(fn () => $this->figures->aids($study)),
        ] + $this->service->present($study));
    }

    public function update(MeetingsRevenueRequest $request, HospitalitySector $study): RedirectResponse
    {
        $study->bindDateTables();
        $this->service->save($study, $request->validated());

        return StudyFlow::after($request, $study, 'meetings', redirect()->route('studies.meetings.edit', $study->id))->with('success', __('revenue.messages.meetings_saved'));
    }
}
