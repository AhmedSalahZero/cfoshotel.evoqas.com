<?php

namespace App\Http\Controllers;

use App\Models\HospitalitySector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

// ==================================================================
//  CFO Hotels - Home ( /home )
//  Location: app/Http/Controllers/HomeController.php
//
//  The first screen after signing in: quick actions and the studies the person worked on last.
//  The super admin has no studies and goes straight to the companies.
// ==================================================================

class HomeController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return redirect()->route('admin.companies.index');
        }

        $visible = HospitalitySector::query()->visibleTo($user);
        $count = (clone $visible)->count();

        $recent = $visible
            ->select(['id', 'study_name', 'property_name', 'star_rating', 'created_at', 'updated_at'])
            ->orderByDesc('updated_at')->orderByDesc('id')
            ->limit(5)->get()
            ->map(fn (HospitalitySector $s) => [
                'id' => $s->id,
                'name' => (string) $s->study_name,
                'property' => (string) $s->property_name,
                'stars' => $s->star_rating ? (int) $s->star_rating : null,
                'updated' => $s->updated_at?->format('Y-m-d') ?? $s->created_at?->format('Y-m-d'),
            ])->values();

        return Inertia::render('Home', [
            'studyCount' => $count,
            'recent' => $recent,
            'canCreate' => Gate::allows('create', HospitalitySector::class) && ! $user->company?->isReadOnly(),
        ]);
    }
}
