<?php

namespace App\Http\Controllers\Hospitality;

use App\Http\Controllers\Controller;
use App\Models\HospitalitySector;
use App\Services\Statements\StatementService;
use App\Support\Statements;
use App\Support\StudyMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — The statements of a study ( /studies/{id}/statements/{family}/{type} )
//  Location: app/Http/Controllers/Hospitality/StatementsController.php
//  Read only. One controller for the 16 statement screens of the previous application (see App\Support\Statements for the list).
//  Anyone who may view the study can open them. A statement of a stream the study does not use does not exist (404).
// ══════════════════════════════════════════════════════════════════

class StatementsController extends Controller
{
    public function __construct(private readonly StatementService $statements) {}

    public function show(Request $request, HospitalitySector $study, string $family, ?string $type = null): Response
    {
        Gate::authorize('view', $study);

        abort_unless(isset(Statements::FAMILIES[$family]), 404);
        $type ??= Statements::first($study, $family);
        abort_unless(Statements::exists($study, $family, $type), 404);

        $study->bindDateTables();

        return Inertia::render('Studies/Statement', [
            'study' => ['id' => $study->id, 'name' => (string) $study->study_name],
            'menu' => StudyMenu::for($study),
            'access' => $study->accessFor($request->user()),
            'links' => collect(Statements::types($study, $family))->mapWithKeys(fn (string $each) => [$each => route('studies.statements.show', array_filter([$study->id, $family, $each === '-' ? null : $each]))])->all(),
        ] + $this->statements->present($study, $family, $type));
    }
}
