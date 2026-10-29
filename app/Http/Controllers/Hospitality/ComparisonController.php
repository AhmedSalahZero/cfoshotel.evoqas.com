<?php

namespace App\Http\Controllers\Hospitality;

use App\Http\Controllers\Controller;
use App\Models\HospitalitySector;
use App\Services\Reports\ComparisonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Compare between two studies ( /studies/compare?first=&second= )
//  Location: app/Http/Controllers/Hospitality/ComparisonController.php
//  Read only. Both studies must be ones the person may view; the same study twice is refused (as the previous application did).
// ══════════════════════════════════════════════════════════════════

class ComparisonController extends Controller
{
    public function __construct(private readonly ComparisonService $comparison) {}

    public function show(Request $request): Response
    {
        $studies = HospitalitySector::query()->visibleTo($request->user())
            ->select(['id', 'study_name', 'property_name'])->orderByDesc('updated_at')->orderByDesc('id')->get();
        $ids = $studies->pluck('id')->all();

        $first = (int) $request->query('first');
        $second = (int) $request->query('second');
        $first = in_array($first, $ids, true) ? $first : null;
        $second = in_array($second, $ids, true) ? $second : null;

        $error = null;
        $result = null;
        if ($first && $second) {
            if ($first === $second) {
                $error = 'same';
            } else {
                $one = HospitalitySector::query()->findOrFail($first);
                $two = HospitalitySector::query()->findOrFail($second);
                Gate::authorize('view', $one);
                Gate::authorize('view', $two);
                $one->bindDateTables();
                $result = $this->comparison->compare($one, $two);
                $error = $result === null ? 'not-calculated' : null;
            }
        }

        return Inertia::render('Studies/Compare', [
            'studies' => $studies->map(fn (HospitalitySector $s) => ['id' => $s->id, 'name' => (string) $s->study_name, 'property' => (string) $s->property_name])->values(),
            'action' => route('studies.compare'),
            'studiesUrl' => route('studies.index'),
            'first' => $first,
            'second' => $second,
            'error' => $error,
            'result' => $result,
        ]);
    }
}
