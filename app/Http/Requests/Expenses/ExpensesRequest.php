<?php

namespace App\Http\Requests\Expenses;

use App\Models\HospitalitySector;
use App\Services\Expenses\ExpensesService;
use App\Support\ExpensePages;
use App\Support\StudyYears;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — ExpensesRequest (the checks of the nine expense screens)
//  Location: app/Http/Requests/Expenses/ExpensesRequest.php
//
//  What the screen sends:  flag (the manpower table is wanted), sections{ <section name>: [ row, row, ... ] }.
//  A row with no name is an empty line: it is ignored and nothing in it is checked. A row with a name must be complete.
//  Rules of the business: percentages are 0 - 100 and the percentages of one year add up to at most 100; a cash share is 0 - 100 (the rest is
//  deferred); taxes and social insurance together stay under 100 % of the gross salary; currencies are the ones of the study.
// ══════════════════════════════════════════════════════════════════

class ExpensesRequest extends FormRequest
{
    private ?HospitalitySector $studyCache = null;

    public function study(): HospitalitySector
    {
        return $this->studyCache ??= $this->route('study');
    }

    public function page(): array
    {
        $page = ExpensePages::find((string) $this->route('page'));
        $used = ExpensePages::usedColumn($page['key']);
        abort_if($used !== null && ! $this->study()->{$used}, 404);

        return $page;
    }

    public function authorize(): bool
    {
        return Gate::allows('update', $this->study());
    }

    public function rules(): array
    {
        $page = $this->page();
        $years = StudyYears::of($this->study());
        $dates = app(ExpensesService::class)->operatingDates($this->study());
        $currencyIds = array_values(array_filter([(int) $this->study()->main_functional_currency, (int) $this->study()->additional_currency]));

        $rules = ['flag' => ['nullable', 'boolean']];

        foreach ($page['sections'] as $section) {
            $name = $section['name'];
            $base = "sections.{$name}";
            $rules[$base] = ['required', 'array', 'size:'.$section['rows']];
            $rules["{$base}.*.id"] = ['nullable', 'integer'];

            foreach ((array) $this->input($base, []) as $i => $row) {
                $on = $section['kind'] === 'property' || trim((string) ($row['name'] ?? '')) !== '';
                $need = $on ? 'required' : 'nullable';
                $at = "{$base}.{$i}";
                $rules["{$at}.name"] = ['nullable', 'string', 'max:120'];

                switch ($section['kind']) {
                    case 'percent':
                        foreach ($years as $year) {
                            $rules["{$at}.values.{$year['index']}"] = [$need, 'numeric', 'min:0', 'max:100'];
                        }
                        break;

                    case 'cost':
                        $rules["{$at}.currency"] = [$need, 'integer', Rule::in($currencyIds)];
                        $rules["{$at}.amount"] = [$need, 'numeric', 'min:0', 'max:1000000000'];
                        $rules["{$at}.escalation"] = [$need, 'numeric', 'min:0', 'max:100'];
                        $rules["{$at}.annual"] = [$need, 'numeric', 'min:0', 'max:100'];
                        if ($section['style'] === 'fixed') {
                            $rules["{$at}.terms"] = [$need, Rule::in(ExpensePages::TERMS)];
                        }
                        break;

                    case 'disposable':
                        $rules["{$at}.beginning"] = [$need, 'numeric', 'min:0', 'max:1000000000'];
                        $rules["{$at}.coverage"] = [$need, 'integer', Rule::in(ExpensePages::DAYS)];
                        $rules["{$at}.cash"] = [$need, 'numeric', 'min:0', 'max:100'];
                        $rules["{$at}.dueDays"] = [$need, 'integer', Rule::in(ExpensePages::DAYS)];
                        foreach ($years as $year) {
                            $rules["{$at}.values.{$year['index']}"] = [$need, 'numeric', 'min:0', 'max:100'];
                        }
                        break;

                    case 'manpower':
                        if (! $this->boolean('flag')) {
                            break;
                        }
                        $rules["{$at}.salary"] = [$need, 'numeric', 'min:0', 'max:1000000000'];
                        $rules["{$at}.currency"] = [$need, 'integer', Rule::in($currencyIds)];
                        $rules["{$at}.escalation"] = [$need, 'numeric', 'min:0', 'max:100'];
                        $rules["{$at}.annual"] = [$need, 'numeric', 'min:0', 'max:100'];
                        $rules["{$at}.taxes"] = [$need, 'numeric', 'min:0', 'max:99.99'];
                        $rules["{$at}.insurance"] = [$need, 'numeric', 'min:0', 'max:99.99'];
                        foreach ($dates as $date) {
                            $rules["{$at}.heads.{$date['index']}"] = ['nullable', 'numeric', 'min:0', 'max:100000'];
                        }
                        break;

                    case 'property':
                        $rules["{$at}.currency"] = ['required', 'integer', Rule::in($currencyIds)];
                        $rules["{$at}.percentage"] = ['required', 'numeric', 'min:0', 'max:100'];
                        $rules["{$at}.annual"] = ['required', 'numeric', 'min:0', 'max:100'];
                        $rules["{$at}.month"] = [$i === 0 ? 'required' : 'nullable', Rule::in(['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'])];
                        break;
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
            $data = $validator->getData();
            $page = $this->page();

            foreach ($page['sections'] as $section) {
                $rows = array_values((array) ($data['sections'][$section['name']] ?? []));

                if ($section['kind'] === 'percent') {
                    foreach (StudyYears::of($this->study()) as $year) {
                        $sum = 0.0;
                        foreach ($rows as $row) {
                            if (trim((string) ($row['name'] ?? '')) !== '') {
                                $sum += (float) ($row['values'][$year['index']] ?? 0);
                            }
                        }
                        if ($sum > 100.0001) {
                            $validator->errors()->add("sections.{$section['name']}.total.{$year['index']}", __('expenses.errors.percent_total', ['year' => $year['year']]));
                        }
                    }
                }

                if ($section['kind'] === 'manpower' && ! empty($data['flag'])) {
                    foreach ($rows as $i => $row) {
                        if (trim((string) ($row['name'] ?? '')) !== '' && (float) $row['taxes'] + (float) $row['insurance'] >= 100) {
                            $validator->errors()->add("sections.{$section['name']}.{$i}.taxes", __('expenses.errors.taxes_total'));
                        }
                    }
                }

                // the same name twice in one table would be two lines the person cannot tell apart
                $names = collect($rows)->pluck('name')->map(fn ($n) => mb_strtolower(trim((string) $n)))->filter();
                if ($section['kind'] !== 'property' && $names->unique()->count() !== $names->count()) {
                    $validator->errors()->add("sections.{$section['name']}", __('expenses.errors.name_twice'));
                }
            }
        }];
    }

    public function attributes(): array
    {
        $attributes = [];
        foreach (StudyYears::of($this->study()) as $year) {
            $attributes["sections.*.*.values.{$year['index']}"] = __('expenses.fields.year', ['year' => $year['year']]);
        }

        return $attributes + [
            'sections.*.*.amount' => __('expenses.fields.amount'), 'sections.*.*.escalation' => __('expenses.fields.escalation'),
            'sections.*.*.annual' => __('expenses.fields.annual'), 'sections.*.*.currency' => __('expenses.fields.currency'),
            'sections.*.*.beginning' => __('expenses.fields.beginning'), 'sections.*.*.coverage' => __('expenses.fields.coverage'),
            'sections.*.*.cash' => __('expenses.fields.cash'), 'sections.*.*.dueDays' => __('expenses.fields.due_days'),
            'sections.*.*.salary' => __('expenses.fields.salary'), 'sections.*.*.taxes' => __('expenses.fields.taxes'),
            'sections.*.*.insurance' => __('expenses.fields.insurance'), 'sections.*.*.terms' => __('expenses.fields.terms'),
            'sections.*.*.percentage' => __('expenses.fields.percentage'), 'sections.*.*.month' => __('expenses.fields.month'),
        ];
    }
}
