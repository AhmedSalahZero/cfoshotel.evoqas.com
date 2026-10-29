<?php

namespace App\Http\Controllers\Hospitality;

use App\Http\Controllers\Controller;
use App\Models\HospitalitySector;
use App\Services\Reports\StudyAnalysis;
use App\Support\DashboardOptions;
use App\Support\StudyMenu;
use App\Support\StudyNotices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — The dashboard of a study ( /studies/{id}/dashboard )
//  Location: app/Http/Controllers/Hospitality/DashboardController.php
//  Read only: the charts (revenue streams, department breakdown, ADR / RevPAR / occupancy, gross profit, profitability) and the
//  investment feasibility cards. ?revenue_stream_type= and ?gross_profit_type= choose the stream and the gross profit shown.
// ══════════════════════════════════════════════════════════════════

class DashboardController extends Controller
{
    public function __construct(private readonly StudyAnalysis $analysis) {}

    public function show(Request $request, HospitalitySector $study): Response
    {
        Gate::authorize('view', $study);
        $study->bindDateTables();

        $stream = (string) $request->query('revenue_stream_type', 'Total Revenues');
        $profit = (string) $request->query('gross_profit_type', 'Departments Gross Profit');
        abort_unless(isset(DashboardOptions::REVENUE_STREAMS[$stream]) && isset(DashboardOptions::GROSS_PROFITS[$profit]), 404);

        $data = $this->analysis->dashboard($study, $stream, $profit);

        return Inertia::render('Studies/Dashboard', [
            'study' => ['id' => $study->id, 'name' => (string) $study->study_name],
            'menu' => StudyMenu::for($study),
            'access' => $study->accessFor($request->user()),
            'calculated' => $data !== null,
            'notices' => StudyNotices::for($study),
            'currency' => $study->getMainFunctionalCurrencyFormatted(),
            'streams' => DashboardOptions::options(DashboardOptions::REVENUE_STREAMS),
            'profits' => DashboardOptions::options(DashboardOptions::GROSS_PROFITS),
            'selected' => ['revenue_stream_type' => $stream, 'gross_profit_type' => $profit],
            'compareUrl' => route('studies.compare', ['first' => $study->id]),
            'dashboard' => $data === null ? null : [
                'cards' => $data['card_items'],
                'revenue' => $data['revenueStreamChart'],
                'revenueAccumulated' => $data['revenueStreamAccumulatedData'],
                'breakdown' => $data['hotelRevenuesBreakdownChart'],
                'adr' => $data['adrChart'],
                'revpar' => $data['revparChart'],
                'occupancy' => $data['occupancyChart'],
                'grossProfit' => $data['grossProfitDepartmentChart'],
                'grossProfitAccumulated' => $data['grossAccumulatedProfitDepartmentAccumulatedChart'],
                'ebitda' => $data['ebitdaChart'],
                'ebit' => $data['ebitChart'],
                'ebt' => $data['ebtChart'],
                'netProfit' => $data['netProfitChart'],
            ],
        ]);
    }
}
