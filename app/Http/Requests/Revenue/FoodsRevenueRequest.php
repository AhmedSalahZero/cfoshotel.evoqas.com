<?php

namespace App\Http\Requests\Revenue;

use App\Models\HospitalitySector;
use App\Services\Revenue\FoodsRevenueService;
use App\Support\CollectionTerms;
use App\Support\StudyYears;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — FoodsRevenueRequest (the checks of the food and beverage screen)
//  Location: app/Http/Requests/Revenue/FoodsRevenueRequest.php
//
//  What the screen sends:
//    facilities[{key, method, currency, value, escalation, annual, capture{year: %}, meals{year: n}, target{year: n}, rooms{year: %}, terms}],
//    collection{mode: general|per_facility, general{type, interval, rates[4], due[4]}}
//  Rules of the business: every facility of the study once; only the yearly figures of the chosen method are required and checked;
//  guest capture is 0 - 100 %; a customised collection policy adds up to 100 %; a study with ONE total F&B row has the general policy only.
// ══════════════════════════════════════════════════════════════════

class FoodsRevenueRequest extends FormRequest
{
    private ?HospitalitySector $studyCache = null;

    public function study(): HospitalitySector
    {
        return $this->studyCache ??= $this->route('study');
    }

    public function authorize(): bool
    {
        return Gate::allows('update', $this->study());
    }

    public function rules(): array
    {
        $study = $this->study();
        $years = StudyYears::of($study);
        $keys = $study->foods()->pluck('food_type_id')->map(fn ($id) => (int) $id)->all();
        $currencyIds = array_values(array_filter([(int) $study->main_functional_currency, (int) $study->additional_currency]));

        $rules = [
            'facilities' => ['required', 'array', 'size:'.count($keys)],
            'facilities.*.key' => ['required', 'integer', Rule::in($keys)],
            'facilities.*.method' => ['required', Rule::in(FoodsRevenueService::METHODS)],
            'facilities.*.currency' => ['required', 'integer', Rule::in($currencyIds)],
            'collection.mode' => ['required', Rule::in($study->isTotalFood() ? ['general'] : ['general', 'per_facility'])],
        ];

        $limits = ['capture' => 100, 'meals' => 100, 'target' => 1000000, 'rooms' => 1000];
        foreach ((array) $this->input('facilities', []) as $i => $facility) {
            $used = FoodsRevenueService::SERIES[$facility['method'] ?? ''] ?? [];
            // the value of a cover is not needed when the sales are a percentage of the rooms revenue
            $needsValue = ($facility['method'] ?? '') !== FoodsRevenueService::ROOMS_PERCENT;
            $rules["facilities.{$i}.value"] = [$needsValue ? 'required' : 'nullable', 'numeric', 'min:0', 'max:1000000000'];
            $rules["facilities.{$i}.escalation"] = [$needsValue ? 'required' : 'nullable', 'numeric', 'min:0', 'max:100'];
            $rules["facilities.{$i}.annual"] = [$needsValue ? 'required' : 'nullable', 'numeric', 'min:0', 'max:100'];
            foreach ($limits as $name => $max) {
                foreach ($years as $year) {
                    $rules["facilities.{$i}.{$name}.{$year['index']}"] = isset($used[$name])
                        ? ['required', 'numeric', 'min:0', 'max:'.$max]
                        : ['nullable'];
                }
            }
            $rules += CollectionTerms::rules("facilities.{$i}.terms");
        }
        $rules += CollectionTerms::rules('collection.general');

        return $rules;
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $data = $validator->getData();

            $keys = collect($data['facilities'])->pluck('key')->map(fn ($k) => (int) $k);
            if ($keys->unique()->count() !== $keys->count()) {
                $validator->errors()->add('facilities', __('revenue.errors.facility_twice'));
            }

            // the policy of a facility only counts when the policy is set per facility
            CollectionTerms::checkTotals($validator, $data['collection']['general'], 'collection.general');
            if ($data['collection']['mode'] === 'per_facility') {
                foreach ($data['facilities'] as $i => $facility) {
                    CollectionTerms::checkTotals($validator, $facility['terms'], "facilities.{$i}.terms");
                }
            }
        }];
    }

    public function attributes(): array
    {
        $attributes = [
            'facilities.*.value' => __('revenue.fields.cover_value'),
            'facilities.*.escalation' => __('revenue.fields.escalation'),
            'facilities.*.annual' => __('revenue.fields.annual'),
            'facilities.*.method' => __('revenue.fields.method'),
            'facilities.*.currency' => __('revenue.fields.currency'),
            'collection.general.rates.*' => __('revenue.fields.collection_rate'),
            'collection.general.due.*' => __('revenue.fields.due_days'),
            'facilities.*.terms.rates.*' => __('revenue.fields.collection_rate'),
            'facilities.*.terms.due.*' => __('revenue.fields.due_days'),
        ];

        foreach (StudyYears::of($this->study()) as $year) {
            $attributes["facilities.*.capture.{$year['index']}"] = __('revenue.fields.capture_year', ['year' => $year['year']]);
            $attributes["facilities.*.meals.{$year['index']}"] = __('revenue.fields.meals_year', ['year' => $year['year']]);
            $attributes["facilities.*.target.{$year['index']}"] = __('revenue.fields.target_year', ['year' => $year['year']]);
            $attributes["facilities.*.rooms.{$year['index']}"] = __('revenue.fields.rooms_percent_year', ['year' => $year['year']]);
        }

        return $attributes;
    }
}
