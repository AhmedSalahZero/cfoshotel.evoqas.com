<?php

namespace App\Http\Controllers;

use App\Excel\ModelBuilder;
use App\Excel\ModelDataBuilder;
use App\Models\HospitalitySector;
use App\Services\Revenue\RevenueFiguresService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — ExcelModelController (the "Excel Model" download of a study)
//  Location: app/Http/Controllers/ExcelModelController.php
//
//  download   GET /studies/{study}/excel-model
//  Builds a live Excel workbook of the study: one sheet per page of the application, from the inputs to the financial statements, the ratios and the
//  dashboard, all linked by formulas. The person can change any yellow input cell and everything recalculates (it gives the same figures as the
//  application for the same inputs: see docs/EXCEL_MODEL.md). The sheets are protected without a password (only the input cells are open). The text
//  follows the language of the application (English, or Arabic with right-to-left sheets). Anyone who may view the study may download it.
// ══════════════════════════════════════════════════════════════════

class ExcelModelController extends Controller
{
    public const MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public function __construct(private readonly RevenueFiguresService $figures) {}

    public function download(HospitalitySector $study): Response|RedirectResponse
    {
        Gate::authorize('view', $study);

        // a study that the application cannot calculate yet (its dates are missing, a page is half filled) has no model either
        try {
            $study->bindDateTables();
            $ready = $this->figures->result($study) !== null;
        } catch (Throwable) {
            $ready = false;
        }
        if (! $ready) {
            return redirect()->route('studies.index')->with('error', __('studies.messages.excel_not_ready'));
        }

        $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';
        try {
            $bytes = ModelBuilder::build(ModelDataBuilder::fromStudy($study), $lang)->wb->toString();
        } catch (Throwable $e) {
            Log::warning('The Excel model of study '.$study->id.' could not be built: '.$e->getMessage().' @ '.basename($e->getFile()).':'.$e->getLine());

            return redirect()->route('studies.index')->with('error', __('studies.messages.excel_failed'));
        }

        $slug = Str::slug((string) $study->study_name);
        $file = 'CFO-Hotels-Model-'.($slug !== '' ? $slug : 'study-'.$study->id).'-'.$lang.'.xlsx';

        return response($bytes, 200, [
            'Content-Type' => self::MIME,
            'Content-Disposition' => 'attachment; filename="'.$file.'"; filename*=UTF-8\'\''.rawurlencode($file),
            'Content-Length' => (string) strlen($bytes),
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
