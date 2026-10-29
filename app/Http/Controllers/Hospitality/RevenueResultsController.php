<?php

namespace App\Http\Controllers\Hospitality;

use App\Http\Controllers\Controller;
use App\Models\HospitalitySector;
use App\Services\Revenue\RevenueFiguresService;
use App\Support\StudyMenu;
use App\Support\StudyYears;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Revenue results of a study ( /studies/{id}/revenue )
//  Location: app/Http/Controllers/Hospitality/RevenueResultsController.php
//  Read only: the revenue of each year, by stream, worked out from the inputs of the other pages. Anyone who may view the study can open it.
// ══════════════════════════════════════════════════════════════════

class RevenueResultsController extends Controller
{
    public function __construct(private readonly RevenueFiguresService $figures) {}

    public function show(Request $request, HospitalitySector $study): Response
    {
        Gate::authorize('view', $study);
        $study->bindDateTables();
        $figures = $this->figures->figures($study);

        return Inertia::render('Studies/Revenue', [
            'study' => ['id' => $study->id, 'name' => (string) $study->study_name],
            'menu' => StudyMenu::for($study),
            'access' => $study->accessFor($request->user()),
            'years' => $figures['years'] ?? StudyYears::of($study),
            'calculated' => $figures !== null,
            'revenue' => $figures['revenue'] ?? null,
            'operating' => $figures['operating'] ?? null,
            'streams' => array_values(array_filter(['rooms', 'foods', $study->has_casino_section ? 'casinos' : null, $study->has_meeting_section ? 'meetings' : null, $study->has_other_section ? 'others' : null])),
        ]);
    }
}
