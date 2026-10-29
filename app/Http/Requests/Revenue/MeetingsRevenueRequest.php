<?php

namespace App\Http\Requests\Revenue;

use App\Models\HospitalitySector;
use App\Services\Revenue\MeetingsRevenueService;
use App\Services\Revenue\RevenueParts;
use App\Support\CollectionTerms;
use App\Support\StudyYears;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — MeetingsRevenueRequest (the checks of the meeting spaces screen)
//  Location: app/Http/Requests/Revenue/MeetingsRevenueRequest.php
//
//  What the screen sends:
//    facilities[{key, method, currency, value, escalation, annual, occupancy{year: %}, fb{year: %}, terms}],
//    guestPlan / rentPlan {type: general|per_facility, interval: flat|quarterly|monthly, months{01..12}, quarters{quarter-one..four},
//                          perMonths{facility: {01..12}}, perQuarters{facility: {quarter-one..four}}},
//    collection{mode: general|per_facility, general{...}}
//  Rules: every facility once; only the figures of the chosen method are required; occupancy 0 - 100 %; the plan of a method is only needed when a
//  facility uses that method; the shares of a quarterly or monthly plan add up to 100 %; a study with ONE total meeting row has general plans and
//  the general collection policy only; a customised collection policy adds up to 100 %.
// ══════════════════════════════════════════════════════════════════

class MeetingsRevenueRequest extends FormRequest
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
        $keys = $study->meetings()->pluck('meeting_type_id')->map(fn ($id) => (int) $id)->all();
        $currencyIds = array_values(array_filter([(int) $study->main_functional_currency, (int) $study->additional_currency]));
        $isTotal = $study->isTotalMeeting();
        $facilities = (array) $this->input('facilities', []);

        $rules = [
            'facilities' => ['required', 'array', 'size:'.count($keys)],
            'facilities.*.key' => ['required', 'integer', Rule::in($keys)],
            'facilities.*.method' => ['required', Rule::in(MeetingsRevenueService::METHODS)],
            'facilities.*.currency' => ['required', 'integer', Rule::in($currencyIds)],
            'collection.mode' => ['required', Rule::in($isTotal ? ['general'] : ['general', 'per_facility'])],
        ];

        foreach ($facilities as $i => $facility) {
            $method = $facility['method'] ?? '';
            $needsValue = $method !== MeetingsRevenueService::FB_PERCENT;
            $rules["facilities.{$i}.value"] = [$needsValue ? 'required' : 'nullable', 'numeric', 'min:0', 'max:1000000000'];
            $rules["facilities.{$i}.escalation"] = [$needsValue ? 'required' : 'nullable', 'numeric', 'min:0', 'max:100'];
            $rules["facilities.{$i}.annual"] = [$needsValue ? 'required' : 'nullable', 'numeric', 'min:0', 'max:100'];
            foreach ($years as $year) {
                $rules["facilities.{$i}.occupancy.{$year['index']}"] = $needsValue ? ['required', 'numeric', 'min:0', 'max:100'] : ['nullable'];
                $rules["facilities.{$i}.fb.{$year['index']}"] = $method === MeetingsRevenueService::FB_PERCENT ? ['required', 'numeric', 'min:0', 'max:1000'] : ['nullable'];
            }
            $rules += CollectionTerms::rules("facilities.{$i}.terms");
        }
        $rules += CollectionTerms::rules('collection.general');

        foreach (MeetingsRevenueService::PLANS as $name => [$method]) {
            $plan = "{$name}Plan";
            $inUse = collect($facilities)->contains(fn ($f) => ($f['method'] ?? null) === $method);
            $interval = $this->input("{$plan}.interval");
            $perFacility = $this->input("{$plan}.type") === 'per_facility';

            $rules["{$plan}.type"] = [$inUse ? 'required' : 'nullable', Rule::in($isTotal ? ['general'] : array_keys(MeetingsRevenueService::PLAN_TYPES))];
            $rules["{$plan}.interval"] = [$inUse ? 'required' : 'nullable', Rule::in(array_keys(MeetingsRevenueService::PLAN_INTERVALS))];
            foreach (RevenueParts::MONTHS as $month) {
                $rules["{$plan}.months.{$month}"] = [$inUse && $interval === 'monthly' && ! $perFacility ? 'required' : 'nullable', 'numeric', 'min:0', 'max:100'];
                foreach ($keys as $key) {
                    $rules["{$plan}.perMonths.{$key}.{$month}"] = [$inUse && $interval === 'monthly' && $perFacility ? 'required' : 'nullable', 'numeric', 'min:0', 'max:100'];
                }
            }
            foreach (MeetingsRevenueService::QUARTERS as $quarter) {
                $rules["{$plan}.quarters.{$quarter}"] = [$inUse && $interval === 'quarterly' && ! $perFacility ? 'required' : 'nullable', 'numeric', 'min:0', 'max:100'];
                foreach ($keys as $key) {
                    $rules["{$plan}.perQuarters.{$key}.{$quarter}"] = [$inUse && $interval === 'quarterly' && $perFacility ? 'required' : 'nullable', 'numeric', 'min:0', 'max:100'];
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

            $keys = collect($data['facilities'])->pluck('key')->map(fn ($k) => (int) $k);
            if ($keys->unique()->count() !== $keys->count()) {
                $validator->errors()->add('facilities', __('revenue.errors.facility_twice'));
            }

            // a quarterly or monthly plan spreads the year: its shares add up to 100 %
            foreach (MeetingsRevenueService::PLANS as $name => [$method]) {
                $plan = $data["{$name}Plan"] ?? null;
                $users = collect($data['facilities'])->filter(fn ($f) => $f['method'] === $method);
                if (! $plan || $users->isEmpty() || $plan['interval'] === 'flat') {
                    continue;
                }
                $field = $plan['interval'] === 'monthly' ? 'Months' : 'Quarters';
                if ($plan['type'] === 'general') {
                    $this->checkShares($validator, "{$name}Plan.".strtolower($field), (array) ($plan[strtolower($field)] ?? []));
                } else {
                    foreach ($users as $facility) {
                        $this->checkShares($validator, "{$name}Plan.per{$field}.{$facility['key']}", (array) ($plan["per{$field}"][$facility['key']] ?? []));
                    }
                }
            }

            CollectionTerms::checkTotals($validator, $data['collection']['general'], 'collection.general');
            if ($data['collection']['mode'] === 'per_facility') {
                foreach ($data['facilities'] as $i => $facility) {
                    CollectionTerms::checkTotals($validator, $facility['terms'], "facilities.{$i}.terms");
                }
            }
        }];
    }

    private function checkShares(Validator $validator, string $key, array $shares): void
    {
        if (abs(array_sum(array_map('floatval', $shares)) - 100) > 0.01) {
            $validator->errors()->add($key, __('revenue.errors.plan_total'));
        }
    }

    public function attributes(): array
    {
        $attributes = [
            'facilities.*.value' => __('revenue.fields.charges_value'),
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
            $attributes["facilities.*.occupancy.{$year['index']}"] = __('revenue.fields.meeting_occupancy_year', ['year' => $year['year']]);
            $attributes["facilities.*.fb.{$year['index']}"] = __('revenue.fields.fb_percent_year', ['year' => $year['year']]);
        }
        foreach (['guestPlan', 'rentPlan'] as $plan) {
            foreach (RevenueParts::MONTHS as $month) {
                $attributes["{$plan}.months.{$month}"] = __('revenue.fields.seasonality_share_month', ['month' => (int) $month]);
            }
        }

        return $attributes;
    }
}
