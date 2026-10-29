<?php

namespace App\Http\Controllers\Hospitality;

use App\Http\Controllers\Controller;
use App\Models\HospitalitySector;
use App\Services\Expenses\ExpenseResultsService;
use App\Support\StudyMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Expense results of a study ( /studies/{id}/expenses-results )
//  Location: app/Http/Controllers/Hospitality/ExpenseResultsController.php
//  Read only: the expenses of each year (direct, undistributed, management fees, start-up cost), worked out from the inputs of the other pages.
//  Anyone who may view the study can open it.
// ══════════════════════════════════════════════════════════════════

class ExpenseResultsController extends Controller
{
    public function __construct(private readonly ExpenseResultsService $results) {}

    public function show(Request $request, HospitalitySector $study): Response
    {
        Gate::authorize('view', $study);
        $study->bindDateTables();

        return Inertia::render('Studies/ExpenseResults', [
            'study' => ['id' => $study->id, 'name' => (string) $study->study_name],
            'menu' => StudyMenu::for($study),
            'access' => $study->accessFor($request->user()),
        ] + $this->results->present($study));
    }
}
