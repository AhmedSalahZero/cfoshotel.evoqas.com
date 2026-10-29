<?php

namespace App\Http\Controllers;

use App\Models\HospitalitySector;
use App\Models\User;
use App\Services\StudyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — StudyShareController (who a study is shared with)
//  Location: app/Http/Controllers/StudyShareController.php
//  The owner (or the company administrator) gives each colleague of the company no access, view or edit.
//  Company administrators always see every study of the company, so they are shown but cannot be changed here.
// ══════════════════════════════════════════════════════════════════

class StudyShareController extends Controller
{
    public function __construct(private readonly StudyService $studies) {}

    /** The colleagues of the study's company and what each may do now (for the share window). */
    public function show(HospitalitySector $study): JsonResponse
    {
        Gate::authorize('share', $study);

        $levels = DB::table('hospitality_sector_shares')->where('hospitality_sector_id', $study->id)->pluck('can_edit', 'user_id');

        $people = User::query()
            ->where('company_id', $study->company_id)->where('is_active', true)->where('id', '!=', $study->creator_id)
            ->orderBy('name')->get(['id', 'name', 'email', 'job_title', 'role'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'job_title' => $u->job_title,
                'admin' => $u->role === User::ROLE_COMPANY_ADMIN,
                'access' => $levels->has($u->id) ? ($levels[$u->id] ? 'edit' : 'view') : 'none',
            ])->values();

        return response()->json(['name' => $study->study_name, 'people' => $people]);
    }

    public function update(Request $request, HospitalitySector $study): RedirectResponse
    {
        Gate::authorize('share', $study);

        $eligible = User::query()
            ->where('company_id', $study->company_id)->where('is_active', true)
            ->where('id', '!=', $study->creator_id)->where('role', '!=', User::ROLE_COMPANY_ADMIN)
            ->pluck('id')->all();

        $data = $request->validate([
            'access' => ['present', 'array'],
            'access.*' => ['string', Rule::in(['none', 'view', 'edit'])],
        ]);

        $access = [];
        foreach ($data['access'] as $userId => $level) {
            if (in_array((int) $userId, $eligible, true)) {
                $access[(int) $userId] = $level;
            }
        }
        $this->studies->share($study, $access);

        return redirect()->route('studies.index')->with('success', __('studies.messages.shared', ['name' => $study->study_name]));
    }
}
