<?php

use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\CspReportController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Hospitality\CasinosController;
use App\Http\Controllers\Hospitality\FoodsController;
use App\Http\Controllers\Hospitality\MeetingsController;
use App\Http\Controllers\Hospitality\OthersController;
use App\Http\Controllers\Hospitality\ExpenseResultsController;
use App\Http\Controllers\Hospitality\ExpensesController;
use App\Http\Controllers\Hospitality\FfeCostController;
use App\Http\Controllers\Hospitality\LandAcquisitionController;
use App\Http\Controllers\Hospitality\PropertyAcquisitionController;
use App\Http\Controllers\Hospitality\ManagementFeesController;
use App\Http\Controllers\Hospitality\StartUpCostController;
use App\Http\Controllers\Hospitality\ComparisonController;
use App\Http\Controllers\Hospitality\DashboardController;
use App\Http\Controllers\Hospitality\ReportsController;
use App\Http\Controllers\Hospitality\StatementsController;
use App\Http\Controllers\Hospitality\RevenueResultsController;
use App\Support\ExpensePages;
use App\Support\Statements;
use App\Services\Reports\ReportService;
use App\Http\Controllers\Hospitality\RoomsController;
use App\Http\Controllers\Hospitality\TypeListsController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\ExcelModelController;
use App\Http\Controllers\StudyController;
use App\Http\Controllers\StudyShareController;
use App\Http\Controllers\UserController;
use App\Models\HospitalitySector;
use Illuminate\Support\Facades\Route;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Web routes
//  Location: routes/web.php
//
//  Sign-in routes are in routes/auth.php. Each planning module adds its own
//  routes inside the signed-in group below, in the step that builds it.
// ══════════════════════════════════════════════════════════════════

// The address of the site itself: signed in → home, otherwise → sign-in page.
Route::get('/', fn () => auth()->check() ? redirect()->route('home') : redirect()->route('login'));

// The offline helper and the app manifest (see App\Http\Controllers\PwaController).
Route::get('/sw.js', [PwaController::class, 'serviceWorker'])->name('pwa.sw');
Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');

// Browser security reports (see App\Http\Middleware\SecurityHeaders).
Route::post('/csp-report', CspReportController::class)->name('csp.report');

// Language and theme: also used by guests on the sign-in page (kept in the session).
Route::post('/preferences', [PreferenceController::class, 'update'])->name('preferences.update');

// A study in an address ( /studies/12/edit ) is only found inside the signed-in person's own company; anywhere else it does not exist.
// Whether the person may see or change it is decided by App\Policies\HospitalitySectorPolicy.
// The date tables of the study (its months) are loaded here, before anything else runs: the checks of a Save (start-up cost, expenses,
// investment) already read the months, and a browser sends every Save as a new request that has not opened the page first.
Route::bind('study', function (string $value) {
    $study = HospitalitySector::query()
        ->where('company_id', auth()->user()?->company_id ?? 0)
        ->findOrFail($value);

    try {
        $study->bindDateTables();
    } catch (\Throwable) {
        // a study whose dates cannot be built is still found; the screens that need the months say so themselves
    }

    return $study;
});

// ── Signed-in area ────────────────────────────────────────────────
// A company whose subscription ended is read-only: 'read-only' refuses every change (looking is always allowed).
Route::middleware(['auth', 'active', 'no-duplicate', 'read-only'])->group(function () {
    Route::get('/home', HomeController::class)->name('home');

    // Platform (super admin only): the companies and their administrators.
    Route::middleware('super')->prefix('admin')->group(function () {
        Route::get('/companies', [CompanyController::class, 'index'])->name('admin.companies.index');
        Route::get('/companies/create', [CompanyController::class, 'create'])->name('admin.companies.create');
        Route::post('/companies', [CompanyController::class, 'store'])->name('admin.companies.store');
        Route::get('/companies/{company}/edit', [CompanyController::class, 'edit'])->name('admin.companies.edit');
        Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('admin.companies.update');
        Route::put('/companies/{company}/users/{user}/password', [CompanyController::class, 'password'])->name('admin.companies.password');
    });

    // People of a company.
    Route::middleware('company')->group(function () {
        // Financial planning: the studies (hotel business plans) of my company.
        // Studies the person may see: their own, those shared with them, and (company administrator) all of the company.
        Route::get('/studies', [StudyController::class, 'index'])->name('studies.index');
        Route::get('/studies/create', [StudyController::class, 'create'])->name('studies.create');
        Route::post('/studies', [StudyController::class, 'store'])->name('studies.store');
        Route::get('/studies/countries/{country}/states', [StudyController::class, 'states'])->whereNumber('country')->name('studies.states');
        Route::get('/studies/{study}/edit', [StudyController::class, 'edit'])->whereNumber('study')->name('studies.edit');
        Route::put('/studies/{study}', [StudyController::class, 'update'])->whereNumber('study')->name('studies.update');
        Route::post('/studies/{study}/copy', [StudyController::class, 'copy'])->whereNumber('study')->name('studies.copy');
        Route::get('/studies/{study}/excel-model', [ExcelModelController::class, 'download'])->whereNumber('study')->name('studies.excel');
        Route::delete('/studies/{study}', [StudyController::class, 'destroy'])->whereNumber('study')->name('studies.destroy');
        Route::get('/studies/{study}/share', [StudyShareController::class, 'show'])->whereNumber('study')->name('studies.share');
        Route::put('/studies/{study}/share', [StudyShareController::class, 'update'])->whereNumber('study')->name('studies.share.update');
        // The revenue pages of a study (rooms now; food and beverage, gaming, meetings and other revenue in the next parts of step 08).
        Route::get('/studies/{study}/rooms', [RoomsController::class, 'edit'])->whereNumber('study')->name('studies.rooms.edit');
        Route::put('/studies/{study}/rooms', [RoomsController::class, 'update'])->whereNumber('study')->name('studies.rooms.update');
        Route::get('/studies/{study}/foods', [FoodsController::class, 'edit'])->whereNumber('study')->name('studies.foods.edit');
        Route::put('/studies/{study}/foods', [FoodsController::class, 'update'])->whereNumber('study')->name('studies.foods.update');
        Route::get('/studies/{study}/casinos', [CasinosController::class, 'edit'])->whereNumber('study')->name('studies.casinos.edit');
        Route::put('/studies/{study}/casinos', [CasinosController::class, 'update'])->whereNumber('study')->name('studies.casinos.update');
        Route::get('/studies/{study}/others', [OthersController::class, 'edit'])->whereNumber('study')->name('studies.others.edit');
        Route::put('/studies/{study}/others', [OthersController::class, 'update'])->whereNumber('study')->name('studies.others.update');
        Route::get('/studies/{study}/revenue', [RevenueResultsController::class, 'show'])->whereNumber('study')->name('studies.revenue.show');
        Route::get('/studies/{study}/meetings', [MeetingsController::class, 'edit'])->whereNumber('study')->name('studies.meetings.edit');
        Route::put('/studies/{study}/meetings', [MeetingsController::class, 'update'])->whereNumber('study')->name('studies.meetings.update');
        // The expense pages of a study: direct expenses of the departments, then general, marketing, property and energy (step 09).
        Route::get('/studies/{study}/expenses/{page}', [ExpensesController::class, 'edit'])->whereNumber('study')->whereIn('page', ExpensePages::keys())->name('studies.expenses.edit');
        Route::put('/studies/{study}/expenses/{page}', [ExpensesController::class, 'update'])->whereNumber('study')->whereIn('page', ExpensePages::keys())->name('studies.expenses.update');
        Route::get('/studies/{study}/management-fees', [ManagementFeesController::class, 'edit'])->whereNumber('study')->name('studies.management.edit');
        Route::put('/studies/{study}/management-fees', [ManagementFeesController::class, 'update'])->whereNumber('study')->name('studies.management.update');
        Route::get('/studies/{study}/start-up-cost', [StartUpCostController::class, 'edit'])->whereNumber('study')->name('studies.startup.edit');
        Route::put('/studies/{study}/start-up-cost', [StartUpCostController::class, 'update'])->whereNumber('study')->name('studies.startup.update');
        Route::get('/studies/{study}/expenses-results', [ExpenseResultsController::class, 'show'])->whereNumber('study')->name('studies.expenses.results');
        // The investment pages of a study: the property bought as a whole, the land and its construction, and the furniture, fixtures and equipment (step 10).
        Route::get('/studies/{study}/property-acquisition-costs', [PropertyAcquisitionController::class, 'edit'])->whereNumber('study')->name('studies.investment.property.edit');
        Route::put('/studies/{study}/property-acquisition-costs', [PropertyAcquisitionController::class, 'update'])->whereNumber('study')->name('studies.investment.property.update');
        Route::get('/studies/{study}/land-acquisition-costs', [LandAcquisitionController::class, 'edit'])->whereNumber('study')->name('studies.investment.land.edit');
        Route::put('/studies/{study}/land-acquisition-costs', [LandAcquisitionController::class, 'update'])->whereNumber('study')->name('studies.investment.land.update');
        Route::get('/studies/{study}/ffe-cost', [FfeCostController::class, 'edit'])->whereNumber('study')->name('studies.investment.ffe.edit');
        Route::put('/studies/{study}/ffe-cost', [FfeCostController::class, 'update'])->whereNumber('study')->name('studies.investment.ffe.update');
        // The statements of a study: receivables, inventory, payments, taxes, fixed assets and the loan schedules (step 11). Read only.
        Route::get('/studies/{study}/statements/{family}/{type?}', [StatementsController::class, 'show'])->whereNumber('study')->whereIn('family', array_keys(Statements::FAMILIES))->name('studies.statements.show');
        // The financial statements of a study (income statement, cash in / out, balance sheet, ratios), its dashboard, and the comparison of two studies (step 12). Read only.
        Route::get('/studies/{study}/reports/{report}', [ReportsController::class, 'show'])->whereNumber('study')->whereIn('report', ReportService::REPORTS)->name('studies.reports.show');
        Route::get('/studies/{study}/dashboard', [DashboardController::class, 'show'])->whereNumber('study')->name('studies.dashboard.show');
        Route::get('/studies/compare', [ComparisonController::class, 'show'])->name('studies.compare');

        // The people and the lists of types of my company (company administrator only).
        Route::middleware('company-admin')->group(function () {
            Route::get('/types', [TypeListsController::class, 'index'])->name('types.index');
            Route::put('/types/{stream}', [TypeListsController::class, 'update'])->whereIn('stream', ['rooms', 'foods', 'casinos', 'meetings', 'others'])->name('types.update');
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        });
    });
});

require __DIR__.'/auth.php';
