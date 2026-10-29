<?php

namespace App\Http\Controllers;

use App\Http\Requests\Studies\StoreStudyRequest;
use App\Http\Requests\Studies\UpdateStudyRequest;
use App\Models\Country;
use App\Models\HospitalitySector;
use App\Services\StudyForm;
use App\Services\StudyService;
use App\Support\StudyFlow;
use App\Support\StudyMenu;
use App\Support\StudyCalendar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — StudyController (the list of studies, and creating / changing / copying / deleting one)
//  Location: app/Http/Controllers/StudyController.php
//
//  Who sees what: the owner and the people a study is shared with; the company administrator sees every study
//  of the company (App\Models\Concerns\HospitalitySectorAccess). Rules per action: App\Policies\HospitalitySectorPolicy.
//  The work itself is in App\Services\StudyService; the form data in App\Services\StudyForm.
// ══════════════════════════════════════════════════════════════════

class StudyController extends Controller
{
    public function __construct(private readonly StudyService $studies, private readonly StudyForm $forms) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $studies = HospitalitySector::query()->visibleTo($user)
            ->select(['id', 'company_id', 'creator_id', 'study_name', 'property_name', 'star_rating', 'property_status', 'country_id',
                'study_start_date', 'study_end_date', 'duration_in_years', 'development_start_month', 'development_duration',
                'operation_start_month', 'created_at', 'updated_at'])
            ->with('creator:id,name')
            ->orderByDesc('updated_at')->orderByDesc('id')
            ->get();

        $ids = $studies->pluck('id');
        $mine = DB::table('hospitality_sector_shares')->where('user_id', $user->id)->pluck('can_edit', 'hospitality_sector_id');
        $counts = DB::table('hospitality_sector_shares')->whereIn('hospitality_sector_id', $ids)
            ->selectRaw('hospitality_sector_id, count(*) as people')->groupBy('hospitality_sector_id')->pluck('people', 'hospitality_sector_id');
        $locale = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';
        $countries = Country::query()->whereIn('id', $studies->pluck('country_id')->filter()->unique())->pluck($locale, 'id');
        $readOnly = (bool) $user->company?->isReadOnly();

        return Inertia::render('Studies/Index', [
            'studies' => $studies->map(function (HospitalitySector $s) use ($user, $mine, $counts, $countries, $readOnly) {
                $shared = $mine->has($s->id) ? (bool) $mine[$s->id] : null;
                $access = $s->accessFor($user, $shared, true);

                return [
                    'id' => $s->id,
                    'name' => (string) $s->study_name,
                    'property' => (string) $s->property_name,
                    'stars' => $s->star_rating ? (int) $s->star_rating : null,
                    'status' => $s->property_status,
                    'country' => $s->country_id ? ($countries[$s->country_id] ?? null) : null,
                    'start' => StudyCalendar::fromStored($s->study_start_date),
                    'end' => StudyCalendar::fromStored($s->study_end_date),
                    'years' => (int) $s->duration_in_years,
                    'development_start' => (int) $s->development_start_month,
                    'development_duration' => (int) $s->development_duration,
                    'operation_start' => (int) $s->operation_start_month,
                    'owner' => $s->creator?->name,
                    'access' => $access,
                    'shared_with' => (int) ($counts[$s->id] ?? 0),
                    'updated' => $s->updated_at?->format('Y-m-d') ?? $s->created_at?->format('Y-m-d'),
                    'can' => [
                        'edit' => ! $readOnly && HospitalitySector::mayChange($access),
                        'share' => ! $readOnly && HospitalitySector::mayManage($access),
                        'delete' => ! $readOnly && HospitalitySector::mayManage($access),
                        'copy' => ! $readOnly,
                        'excel' => true,
                    ],
                ];
            })->values(),
            'isAdmin' => $user->isCompanyAdmin(),
            'readOnly' => $readOnly,
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', HospitalitySector::class);
        $user = $request->user();

        return Inertia::render('Studies/Form', [
            'study' => $this->forms->defaults($user),
            'options' => $this->forms->options($user, null),
            'access' => HospitalitySector::ACCESS_OWNER,
            'readOnly' => (bool) $user->company?->isReadOnly(),
        ]);
    }

    public function store(StoreStudyRequest $request): RedirectResponse
    {
        $study = $this->studies->create($request->user(), $request->validated());

        return $this->after($request, $study)->with('success', __('studies.messages.created', ['name' => $study->study_name]));
    }

    /** Where the person goes after saving: the button they pressed says so (the next page of the study, the same page, or the list). */
    private function after(Request $request, HospitalitySector $study): RedirectResponse
    {
        return match ($request->input('after')) {
            'next' => redirect()->to(StudyFlow::next($study, 'study') ?? route('studies.rooms.edit', $study->id)),
            'stay' => redirect()->route('studies.edit', $study->id),
            default => redirect()->route('studies.index'),
        };
    }

    /** Opens a study: the owner / editor changes it; a colleague with view-only access sees it without being able to change anything. */
    public function edit(Request $request, HospitalitySector $study): Response
    {
        Gate::authorize('view', $study);
        $user = $request->user();
        $form = $this->forms->fromStudy($study);

        return Inertia::render('Studies/Form', [
            'study' => $form,
            'menu' => StudyMenu::for($study),
            'options' => $this->forms->options($user, $form['country_id']),
            'access' => $study->accessFor($user),
            'readOnly' => (bool) $user->company?->isReadOnly(),
        ]);
    }

    public function update(UpdateStudyRequest $request, HospitalitySector $study): RedirectResponse
    {
        $study = $this->studies->update($study, $request->validated());

        return $this->after($request, $study)->with('success', __('studies.messages.saved', ['name' => $study->study_name]));
    }

    public function copy(Request $request, HospitalitySector $study): RedirectResponse
    {
        Gate::authorize('copy', $study);
        $data = $request->validate(['name' => ['required', 'string', 'max:190']], [], ['name' => __('studies.fields.copy_name')]);

        $copy = $this->studies->copy($study, $request->user(), trim($data['name']));

        return redirect()->route('studies.index')->with('success', __('studies.messages.copied', ['name' => $copy->study_name]));
    }

    public function destroy(HospitalitySector $study): RedirectResponse
    {
        Gate::authorize('delete', $study);
        $name = $study->study_name;
        $this->studies->delete($study);

        return redirect()->route('studies.index')->with('success', __('studies.messages.deleted', ['name' => $name]));
    }

    /** The states / governorates of a country (fills the second drop-down when the country changes). */
    public function states(Country $country): JsonResponse
    {
        return response()->json($this->forms->states($country->id));
    }
}
