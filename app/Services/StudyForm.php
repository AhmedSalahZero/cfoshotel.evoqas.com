<?php

namespace App\Services;

use App\Models\Country;
use App\Models\Currency;
use App\Models\HospitalitySector;
use App\Models\State;
use App\Models\User;
use App\Support\StudyCalendar;
use App\Support\StudyStreams;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — StudyForm (what the study form screen is given)
//  Location: app/Services/StudyForm.php
//
//  defaults()      the empty form of a new study
//  fromStudy()     the form filled with a saved study (same shape, so one screen serves both)
//  options()       the lists for the drop-downs: countries, currencies, the company's own room / food / ... types
//  The shape is the one App\Http\Requests\Studies\StudyRequest accepts back.
// ══════════════════════════════════════════════════════════════════

class StudyForm
{
    public function defaults(User $user): array
    {
        $firstCurrency = Currency::query()->orderBy('id')->value('id');

        $emptyRow = ['type_id' => null, 'count' => null, 'value' => null];
        $stream = fn (bool $on) => ['on' => $on, 'mode' => 'types', 'rows' => [$emptyRow]];

        return [
            'id' => null,
            'study_name' => '',
            'property_name' => '',
            'property_status' => 'existing',
            'star_rating' => null,
            'country_id' => null,
            'state_id' => null,
            'region' => '',
            'study_start' => now()->format('Y-m'),
            'duration_in_years' => 5,
            'development_start_month' => 0,
            'development_duration' => 0,
            'operation_start_month' => 0,
            'financial_year_start_month' => 'january',
            'main_functional_currency' => $firstCurrency ? (int) $firstCurrency : null,
            'additional_currency' => null,
            'exchange_rate' => 1,
            'corporate_taxes_rate' => 0,
            'investment_return_rate' => 10,
            'perpetual_growth_rate' => 0,
            'streams' => [
                'rooms' => $stream(true),
                'foods' => $stream(true),
                'casinos' => $stream(false),
                'meetings' => $stream(false),
                'others' => $stream(false),
            ],
            'channels' => ['on' => false, 'names' => []],
        ];
    }

    public function fromStudy(HospitalitySector $study): array
    {
        $streams = [];

        foreach (StudyStreams::ALL as $name => $config) {
            $rows = $study->{$config['relation']}()->orderBy('id')->get()->map(fn ($row) => [
                'type_id' => $row->{$config['type']} ? (int) $row->{$config['type']} : null,
                'count' => $row->{$config['count']} !== null ? (int) $row->{$config['count']} : null,
                'value' => $config['value'] === null ? null : ($row->{$config['value']} !== null ? $this->number($row->{$config['value']}) : null),
            ])->values()->all();

            $total = (bool) $study->{$config['isTotal']};
            $streams[$name] = [
                'on' => $config['on'] === null ? true : (bool) $study->{$config['on']},
                'mode' => $total ? 'total' : 'types',
                'rows' => $total ? [$rows[0] ?? ['type_id' => null, 'count' => null, 'value' => null]] : ($rows ?: [['type_id' => null, 'count' => null, 'value' => null]]),
            ];
        }

        $additional = (string) $study->additional_currency;

        return [
            'id' => $study->id,
            'study_name' => (string) $study->study_name,
            'property_name' => (string) $study->property_name,
            'property_status' => $study->property_status ?: 'existing',
            'star_rating' => $study->star_rating ? (int) $study->star_rating : null,
            'country_id' => $study->country_id ? (int) $study->country_id : null,
            'state_id' => $study->state_id ? (int) $study->state_id : null,
            'region' => (string) $study->region,
            'study_start' => StudyCalendar::fromStored($study->study_start_date) ?? now()->format('Y-m'),
            'duration_in_years' => (int) $study->duration_in_years,
            'development_start_month' => (int) $study->development_start_month,
            'development_duration' => (int) $study->development_duration,
            'operation_start_month' => (int) $study->operation_start_month,
            'financial_year_start_month' => $study->financial_year_start_month ?: 'january',
            'main_functional_currency' => $study->main_functional_currency !== null && $study->main_functional_currency !== '' ? (int) $study->main_functional_currency : null,
            'additional_currency' => $additional !== '' ? (int) $additional : null,
            'exchange_rate' => $this->number($study->exchange_rate),
            'corporate_taxes_rate' => $this->number($study->corporate_taxes_rate),
            'investment_return_rate' => $this->number($study->investment_return_rate),
            'perpetual_growth_rate' => $this->number($study->perpetual_growth_rate),
            'streams' => $streams,
            'channels' => [
                'on' => (bool) $study->has_sales_channels,
                'names' => $study->salesChannels()->orderBy('id')->pluck('name')->all(),
            ],
        ];
    }

    /** "1.00" → 1, "2.50" → 2.5 (what a person expects to see in a box). */
    private function number(mixed $value): int|float
    {
        $float = (float) $value;

        return floor($float) === $float ? (int) $float : $float;
    }

    public function options(User $user, ?int $countryId): array
    {
        $locale = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';
        $company = $user->company;

        $types = [];
        foreach (array_keys(StudyStreams::ALL) as $name) {
            $types[$name] = StudyStreams::types($company, $name)->where('total', false)->map(fn ($t) => ['id' => $t['id'], 'title' => $t['title']])->values()->all();
        }

        return [
            'countries' => Country::query()->orderBy('name_en')->get(['id', 'name_en', 'name_ar'])
                ->map(fn ($c) => ['id' => (int) $c->id, 'name' => $c->{$locale} ?: $c->name_en])->values()->all(),
            'states' => $countryId ? $this->states($countryId) : [],
            'currencies' => Currency::query()->orderBy('id')->get(['id', 'name'])->map(fn ($c) => ['id' => (int) $c->id, 'name' => $c->name])->values()->all(),
            'statuses' => StudyStreams::PROPERTY_STATUSES,
            'star_ratings' => StudyStreams::STAR_RATINGS,
            'durations' => StudyStreams::DURATIONS,
            'financial_year_starts' => StudyStreams::FINANCIAL_YEAR_STARTS,
            'channels' => array_keys(StudyStreams::CHANNELS),
            'types' => $types,
        ];
    }

    /** The states / governorates of a country, in the person's language. */
    public function states(int $countryId): array
    {
        $locale = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';

        return State::query()->where('country_id', $countryId)->orderBy('name_en')->get(['id', 'name_en', 'name_ar'])
            ->map(fn ($s) => ['id' => (int) $s->id, 'name' => $s->{$locale} ?: $s->name_en])->values()->all();
    }
}
