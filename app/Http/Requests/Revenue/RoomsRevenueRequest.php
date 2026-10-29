<?php

namespace App\Http\Requests\Revenue;

use App\Models\HospitalitySector;
use App\Services\Revenue\RevenueParts;
use App\Support\CollectionTerms;
use App\Support\StudyYears;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — RoomsRevenueRequest (the checks of the rooms revenue screen)
//  Location: app/Http/Requests/Revenue/RoomsRevenueRequest.php
//
//  The form is
//    currency, rooms[{key, adr, escalation, annual}], exchange{year: rate},
//    occupancy{mode, general{year: %}, perRoom{room: {year: %}}},
//    seasonality{type, interval, general{month: %}, perRoom{room: {month: %}}},
//    channels[{name, share{year: %}, discount{year: %}, terms{...}}], collection{mode, general{...}}.
//  A "year" is the year index of App\Support\StudyYears; a "month" is "01" ... "12".
//  Rules of the business: every room of the study once; percentages 0 - 100; the channels' shares add up to 100 % in each year;
//  a customised collection policy adds up to 100 %; a study with ONE total row has no per-room occupancy or seasonality.
// ══════════════════════════════════════════════════════════════════

class RoomsRevenueRequest extends FormRequest
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

    protected function prepareForValidation(): void
    {
        // flat seasonality is 100 % in every month: nothing for the person to type
        $seasonality = (array) $this->input('seasonality', []);
        if (($seasonality['interval'] ?? null) === 'flat') {
            $months = array_fill_keys(RevenueParts::MONTHS, 100);
            $seasonality['general'] = $months;
            foreach ($this->study()->rooms()->pluck('room_type_id') as $key) {
                $seasonality['perRoom'][(int) $key] = $months;
            }
            $this->merge(['seasonality' => $seasonality]);
        }

        if (! $this->hasAdditionalCurrency()) {
            $this->merge(['exchange' => null]);
        }
    }

    private function hasAdditionalCurrency(): bool
    {
        $extra = $this->study()->additional_currency;

        return $extra !== null && $extra !== '';
    }

    public function rules(): array
    {
        $study = $this->study();
        $years = StudyYears::of($study);
        $roomKeys = $study->rooms()->pluck('room_type_id')->map(fn ($id) => (int) $id)->all();
        $isTotal = $study->isTotalRooms();
        $channelNames = $study->salesChannels()->pluck('name')->all();
        $currencyIds = array_values(array_filter([(int) $study->main_functional_currency, (int) $study->additional_currency]));

        $rules = [
            'currency' => ['required', 'integer', Rule::in($currencyIds)],
            'rooms' => ['required', 'array', 'size:'.count($roomKeys)],
            'rooms.*.key' => ['required', 'integer', Rule::in($roomKeys)],
            'rooms.*.adr' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'rooms.*.escalation' => ['required', 'numeric', 'min:0', 'max:100'],
            'rooms.*.annual' => ['required', 'numeric', 'min:0', 'max:100'],
            'occupancy.mode' => ['required', Rule::in($isTotal ? ['general'] : ['general', 'per_room'])],
            'seasonality.type' => ['required', Rule::in($isTotal ? ['general'] : ['general', 'per_room'])],
            'seasonality.interval' => ['required', Rule::in(['flat', 'monthly'])],
            'collection.mode' => ['required', Rule::in($channelNames ? ['general', 'per_channel'] : ['general'])],
        ];

        $perRoomOccupancy = $this->input('occupancy.mode') === 'per_room';
        foreach ($years as $year) {
            $i = $year['index'];
            if ($this->hasAdditionalCurrency()) {
                $rules["exchange.{$i}"] = ['required', 'numeric', 'min:0.0001', 'max:100000000'];
            }
            $rules["occupancy.general.{$i}"] = [$perRoomOccupancy ? 'nullable' : 'required', 'numeric', 'min:0', 'max:100'];
            foreach ($roomKeys as $key) {
                $rules["occupancy.perRoom.{$key}.{$i}"] = [$perRoomOccupancy ? 'required' : 'nullable', 'numeric', 'min:0', 'max:100'];
            }
        }

        $perRoomSeasonality = $this->input('seasonality.type') === 'per_room';
        foreach (RevenueParts::MONTHS as $month) {
            $rules["seasonality.general.{$month}"] = [$perRoomSeasonality ? 'nullable' : 'required', 'numeric', 'min:0', 'max:100'];
            foreach ($roomKeys as $key) {
                $rules["seasonality.perRoom.{$key}.{$month}"] = [$perRoomSeasonality ? 'required' : 'nullable', 'numeric', 'min:0', 'max:100'];
            }
        }

        $rules += CollectionTerms::rules('collection.general');

        if ($channelNames) {
            $rules['channels'] = ['required', 'array', 'size:'.count($channelNames)];
            $rules['channels.*.name'] = ['required', Rule::in($channelNames)];
            foreach ($years as $year) {
                $rules["channels.*.share.{$year['index']}"] = ['required', 'numeric', 'min:0', 'max:100'];
                $rules["channels.*.discount.{$year['index']}"] = ['required', 'numeric', 'min:0', 'max:100'];
            }
            $rules += CollectionTerms::rules('channels.*.terms');
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
            $study = $this->study();

            // every room of the study once
            $keys = collect($data['rooms'])->pluck('key')->map(fn ($k) => (int) $k);
            if ($keys->unique()->count() !== $keys->count()) {
                $validator->errors()->add('rooms', __('revenue.errors.room_twice'));
            }

            // the reservation channels share 100 % of the revenue in every operating year
            foreach (StudyYears::of($study) as $year) {
                $total = collect($data['channels'] ?? [])->sum(fn ($channel) => (float) ($channel['share'][$year['index']] ?? 0));
                if (! empty($data['channels']) && abs($total - 100) > 0.01) {
                    $validator->errors()->add("channels.share.{$year['index']}", __('revenue.errors.share_total', ['year' => $year['year']]));
                }
            }

            CollectionTerms::checkTotals($validator, $data['collection']['general'], 'collection.general');
            foreach (($data['channels'] ?? []) as $i => $channel) {
                CollectionTerms::checkTotals($validator, $channel['terms'], "channels.{$i}.terms");
            }
        }];
    }

    public function attributes(): array
    {
        $attributes = [
            'currency' => __('revenue.fields.currency'),
            'rooms.*.adr' => __('revenue.fields.adr'),
            'rooms.*.escalation' => __('revenue.fields.escalation'),
            'rooms.*.annual' => __('revenue.fields.annual'),
            'collection.general.rates.*' => __('revenue.fields.collection_rate'),
            'collection.general.due.*' => __('revenue.fields.due_days'),
            'channels.*.terms.rates.*' => __('revenue.fields.collection_rate'),
            'channels.*.terms.due.*' => __('revenue.fields.due_days'),
        ];

        foreach (StudyYears::of($this->study()) as $year) {
            $attributes["exchange.{$year['index']}"] = __('revenue.fields.exchange_year', ['year' => $year['year']]);
            $attributes["occupancy.general.{$year['index']}"] = __('revenue.fields.occupancy_year', ['year' => $year['year']]);
            $attributes["channels.*.share.{$year['index']}"] = __('revenue.fields.share_year', ['year' => $year['year']]);
            $attributes["channels.*.discount.{$year['index']}"] = __('revenue.fields.discount_year', ['year' => $year['year']]);
            foreach ($this->study()->rooms()->pluck('room_type_id') as $key) {
                $attributes["occupancy.perRoom.{$key}.{$year['index']}"] = __('revenue.fields.occupancy_year', ['year' => $year['year']]);
            }
        }
        foreach (RevenueParts::MONTHS as $month) {
            $attributes["seasonality.general.{$month}"] = __('revenue.fields.seasonality_month', ['month' => (int) $month]);
            foreach ($this->study()->rooms()->pluck('room_type_id') as $key) {
                $attributes["seasonality.perRoom.{$key}.{$month}"] = __('revenue.fields.seasonality_month', ['month' => (int) $month]);
            }
        }

        return $attributes;
    }
}
