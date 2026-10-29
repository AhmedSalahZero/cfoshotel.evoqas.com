<?php

namespace App\Http\Controllers\Hospitality;

use App\Http\Controllers\Controller;
use App\Models\HospitalitySector;
use App\Services\Reports\ReportService;
use App\Support\StudyMenu;
use App\Support\StudyNotices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — The financial statements of a study ( /studies/{id}/reports/{report} )
//  Location: app/Http/Controllers/Hospitality/ReportsController.php
//  Read only: the income statement, the cash in / out flow, the balance sheet and the ratio analysis.
//  Anyone who may view the study can open them. A study that cannot be calculated yet shows what is missing instead of figures.
// ══════════════════════════════════════════════════════════════════

class ReportsController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function show(Request $request, HospitalitySector $study, string $report): Response
    {
        Gate::authorize('view', $study);
        abort_unless(in_array($report, ReportService::REPORTS, true), 404);

        $study->bindDateTables();

        return Inertia::render($report === 'ratios' ? 'Studies/Ratios' : 'Studies/Report', [
            'study' => ['id' => $study->id, 'name' => (string) $study->study_name],
            'menu' => StudyMenu::for($study),
            'access' => $study->accessFor($request->user()),
            'links' => collect(ReportService::REPORTS)->mapWithKeys(fn (string $each) => [$each => route('studies.reports.show', [$study->id, $each])])->all(),
            'currency' => $study->getMainFunctionalCurrencyFormatted(),
            'notices' => StudyNotices::for($study),
        ] + $this->reports->present($study, $report));
    }
}
