<?php

namespace App\Http\Requests\Expenses;

use App\Models\HospitalitySector;
use App\Services\Expenses\StartUpCostService;
use App\Support\ExpensePages;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — StartUpCostRequest (the checks of the start-up cost screen)
//  Location: app/Http/Requests/Expenses/StartUpCostRequest.php
//  Three lines in each table. A line with no name is empty and nothing in it is checked; a line with a name must be complete.
//  Start-up cost: currency of the study, amount >= 0, a month between the study start and the opening, cash payment 0 - 100, due days from the list.
//  Pre-operating expense: currency of the study and an amount >= 0 for every month before the opening.
// ══════════════════════════════════════════════════════════════════

class StartUpCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('study'));
    }

    public function rules(): array
    {
        /** @var HospitalitySector $study */
        $study = $this->route('study');
        $service = app(StartUpCostService::class);
        $currencyIds = array_values(array_filter([(int) $study->main_functional_currency, (int) $study->additional_currency]));
        $months = array_column($service->openingMonths($study), 'index');
        $dates = array_column($service->preOperatingDates($study), 'index');
        $rules = [];

        foreach ([ExpensePages::START_UP, ExpensePages::PRE_OPERATING] as $section) {
            $base = "sections.{$section}";
            $rules[$base] = ['required', 'array', 'size:'.StartUpCostService::LINES];
            $rules["{$base}.*.id"] = ['nullable', 'integer'];
            $rules["{$base}.*.name"] = ['nullable', 'string', 'max:120'];

            foreach ((array) $this->input($base, []) as $i => $row) {
                $need = trim((string) ($row['name'] ?? '')) !== '' ? 'required' : 'nullable';
                $at = "{$base}.{$i}";
                $rules["{$at}.currency"] = [$need, 'integer', Rule::in($currencyIds)];
                if ($section === ExpensePages::START_UP) {
                    $rules["{$at}.cost"] = [$need, 'numeric', 'min:0', 'max:1000000000000'];
                    $rules["{$at}.date"] = [$need, 'integer', Rule::in($months)];
                    $rules["{$at}.cash"] = [$need, 'numeric', 'min:0', 'max:100'];
                    $rules["{$at}.dueDays"] = [$need, 'integer', Rule::in(ExpensePages::DAYS)];
                } else {
                    foreach ($dates as $key) {
                        $rules["{$at}.amounts.{$key}"] = ['nullable', 'numeric', 'min:0', 'max:1000000000000'];
                    }
                }
            }
        }

        return $rules;
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            foreach ([ExpensePages::START_UP, ExpensePages::PRE_OPERATING] as $section) {
                $names = collect((array) ($validator->getData()['sections'][$section] ?? []))->pluck('name')->map(fn ($n) => mb_strtolower(trim((string) $n)))->filter();
                if ($names->unique()->count() !== $names->count()) {
                    $validator->errors()->add("sections.{$section}", __('expenses.errors.name_twice'));
                }
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'sections.*.*.cost' => __('expenses.fields.cost'), 'sections.*.*.date' => __('expenses.fields.date'), 'sections.*.*.cash' => __('expenses.fields.cash'),
            'sections.*.*.dueDays' => __('expenses.fields.due_days'), 'sections.*.*.currency' => __('expenses.fields.currency'),
        ];
    }
}
