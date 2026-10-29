<?php

namespace App\Http\Requests\Studies;

use App\Support\StudyCalendar;
use App\Support\StudyStreams;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — StudyRequest (what a study form may contain)
//  Location: app/Http/Requests/Studies/StudyRequest.php
//
//  The rules for creating and for changing a study are the same; the two small classes StoreStudyRequest and
//  UpdateStudyRequest only decide who may send it. The previous CFO Hotels checked nothing on the server (the
//  browser did); here the server is the authority.
//
//  Shape of the form (what Studies/Form.vue sends):
//    study_name, property_name, property_status, star_rating, country_id, state_id, region
//    study_start ("2026-03"), duration_in_years, development_start_month, development_duration, operation_start_month
//    financial_year_start_month, main_functional_currency, additional_currency, exchange_rate,
//    corporate_taxes_rate, investment_return_rate, perpetual_growth_rate
//    streams.rooms|foods|casinos|meetings|others = { on, mode: 'total'|'types', rows: [{ type_id, count, value }] }
//    channels = { on, names: [...] }
// ══════════════════════════════════════════════════════════════════

abstract class StudyRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $input = $this->all();

        foreach (['property_name', 'region', 'study_name'] as $key) {
            if (isset($input[$key]) && is_string($input[$key])) {
                $input[$key] = trim($input[$key]);
            }
        }
        foreach (['star_rating', 'country_id', 'state_id', 'additional_currency'] as $key) {
            if (($input[$key] ?? null) === '') {
                $input[$key] = null;
            }
        }
        // Without a second currency there is nothing to convert: the rate is 1.
        if (empty($input['additional_currency'])) {
            $input['exchange_rate'] = 1;
        }

        foreach (StudyStreams::ALL as $name => $config) {
            $stream = $input['streams'][$name] ?? null;
            if (! is_array($stream)) {
                continue;
            }
            $stream['on'] = $config['on'] === null ? true : filter_var($stream['on'] ?? false, FILTER_VALIDATE_BOOL);
            $rows = array_values(array_filter((array) ($stream['rows'] ?? []), 'is_array'));
            // One row for the whole stream: only the first row counts.
            if (($stream['mode'] ?? null) === 'total') {
                $rows = array_slice($rows, 0, 1);
                if ($rows) {
                    $rows[0]['type_id'] = null;
                }
            }
            $stream['rows'] = $rows;
            $input['streams'][$name] = $stream;
        }

        if (isset($input['channels']) && is_array($input['channels'])) {
            $input['channels']['on'] = filter_var($input['channels']['on'] ?? false, FILTER_VALIDATE_BOOL);
            $input['channels']['names'] = array_values((array) ($input['channels']['names'] ?? []));
        }

        $this->replace($input);
    }

    public function rules(): array
    {
        $years = (int) $this->input('duration_in_years');
        $lastMonth = max(0, $years * 12 - 1);

        $rules = [
            'study_name' => ['required', 'string', 'max:190'],
            'property_name' => ['nullable', 'string', 'max:190'],
            'region' => ['nullable', 'string', 'max:190'],
            'property_status' => ['required', Rule::in(StudyStreams::PROPERTY_STATUSES)],
            'star_rating' => ['nullable', Rule::in(StudyStreams::STAR_RATINGS)],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'state_id' => ['nullable', 'integer', Rule::exists('states', 'id')->where('country_id', $this->input('country_id'))],
            'study_start' => ['required', 'string', function ($attribute, $value, $fail) {
                $parts = StudyCalendar::parse($value);
                if (! $parts || $parts[0] < 2000 || $parts[0] > 2100) {
                    $fail(__('studies.errors.start_month'));
                }
            }],
            'duration_in_years' => ['required', 'integer', Rule::in(StudyStreams::DURATIONS)],
            'development_start_month' => ['required', 'integer', 'min:0', "max:{$lastMonth}"],
            'development_duration' => ['required', 'integer', 'min:0', 'max:600'],
            'operation_start_month' => ['required', 'integer', 'min:0', "max:{$lastMonth}"],
            'financial_year_start_month' => ['required', Rule::in(StudyStreams::FINANCIAL_YEAR_STARTS)],
            'main_functional_currency' => ['required', 'integer', 'exists:currencies,id'],
            'additional_currency' => ['nullable', 'integer', 'exists:currencies,id', 'different:main_functional_currency'],
            'exchange_rate' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'corporate_taxes_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'investment_return_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'perpetual_growth_rate' => ['required', 'numeric', 'min:0', 'max:100'],

            'streams' => ['required', 'array'],
            'channels' => ['required', 'array'],
            'channels.on' => ['boolean'],
            'channels.names' => ['array', 'max:'.count(StudyStreams::CHANNELS)],
            'channels.names.*' => ['string', Rule::in(array_keys(StudyStreams::CHANNELS))],
        ];

        foreach (StudyStreams::ALL as $name => $config) {
            $on = $config['on'] === null ? true : $this->boolean("streams.{$name}.on");
            $rules["streams.{$name}"] = ['required', 'array'];
            $rules["streams.{$name}.mode"] = ['required', Rule::in(['total', 'types'])];
            $rules["streams.{$name}.on"] = ['boolean'];
            $rules["streams.{$name}.rows"] = $on ? ['required', 'array', 'min:1', 'max:60'] : ['array'];

            if ($on) {
                $rules["streams.{$name}.rows.*.type_id"] = ['nullable', 'integer'];
                $rules["streams.{$name}.rows.*.count"] = ['required', 'integer', 'min:1', 'max:100000'];
                if ($config['value'] !== null) {
                    // guests per room may be 1.5; seats of a restaurant, hall ... are whole numbers
                    $rules["streams.{$name}.rows.*.value"] = $name === 'rooms'
                        ? ['required', 'numeric', 'min:1', 'max:20']
                        : ['required', 'integer', 'min:1', 'max:100000'];
                }
            }
        }

        return $rules;
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $this->checkTypes($validator);
            $this->checkChannels($validator);
        }];
    }

    /** By-type rows: each type belongs to the company's list (not the "Total ..." row) and is used once. */
    private function checkTypes(Validator $validator): void
    {
        $company = $this->user()->company;

        foreach (StudyStreams::ALL as $name => $config) {
            $stream = $this->input("streams.{$name}");
            if (! is_array($stream) || ! ($stream['on'] ?? false) || ($stream['mode'] ?? null) !== 'types') {
                continue;
            }
            $allowed = StudyStreams::typeIds($company, $name);
            $seen = [];

            foreach ($stream['rows'] ?? [] as $i => $row) {
                $id = isset($row['type_id']) && is_numeric($row['type_id']) ? (int) $row['type_id'] : 0;
                $key = "streams.{$name}.rows.{$i}.type_id";

                if ($id === 0 || ! in_array($id, $allowed, true)) {
                    $validator->errors()->add($key, __('studies.errors.type_required'));
                } elseif (isset($seen[$id])) {
                    $validator->errors()->add($key, __('studies.errors.type_twice'));
                }
                $seen[$id] = true;
            }
        }
    }

    private function checkChannels(Validator $validator): void
    {
        $channels = $this->input('channels', []);

        if (! ($channels['on'] ?? false)) {
            return;
        }
        $names = $channels['names'] ?? [];

        if (count($names) < 1) {
            $validator->errors()->add('channels.names', __('studies.errors.channel_required'));
        } elseif (count($names) !== count(array_unique($names))) {
            $validator->errors()->add('channels.names', __('studies.errors.channel_twice'));
        }
    }

    public function attributes(): array
    {
        $attributes = trans('studies.fields');
        $attributes = is_array($attributes) ? $attributes : [];

        foreach (StudyStreams::ALL as $name => $config) {
            $attributes["streams.{$name}.rows.*.count"] = __("studies.fields.count_{$name}");
            $attributes["streams.{$name}.rows.*.value"] = __("studies.fields.value_{$name}");
            $attributes["streams.{$name}.rows.*.type_id"] = __("studies.fields.type_{$name}");
            $attributes["streams.{$name}.rows"] = __("studies.fields.rows_{$name}");
        }
        $attributes['channels.names'] = __('studies.fields.channels');

        return $attributes;
    }
}
