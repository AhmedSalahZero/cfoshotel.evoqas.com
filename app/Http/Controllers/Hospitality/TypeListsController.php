<?php

namespace App\Http\Controllers\Hospitality;

use App\Http\Controllers\Controller;
use App\Http\Requests\Revenue\TypeListRequest;
use App\Services\Revenue\TypeListService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — The company's lists of types ( /types ), for the company administrator
//  Location: app/Http/Controllers/Hospitality/TypeListsController.php
//  Replaces the five "create" pages of the old application (rooms/create, foods/create, gamings/create, meetings/create, others/create).
// ══════════════════════════════════════════════════════════════════

class TypeListsController extends Controller
{
    public function __construct(private readonly TypeListService $lists) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Company/Types', [
            'lists' => $this->lists->lists($request->user()->company),
            'readOnly' => (bool) $request->user()->company?->isReadOnly(),
        ]);
    }

    public function update(TypeListRequest $request, string $stream): RedirectResponse
    {
        $this->lists->save($request->user()->company, $stream, $request->validated()['rows']);

        return redirect()->route('types.index')->with('success', __('revenue.messages.types_saved', ['list' => __("revenue.lists.{$stream}")]));
    }
}
