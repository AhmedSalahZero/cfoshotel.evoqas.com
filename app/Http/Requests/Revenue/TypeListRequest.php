<?php

namespace App\Http\Requests\Revenue;

use App\Support\StudyStreams;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — TypeListRequest (the checks of one list of types)
//  Location: app/Http/Requests/Revenue/TypeListRequest.php
//  Titles are required and each appears once in a list (spelling of capitals does not matter).
//  A row with an id must be one of the company's own types, and never the "Total ..." row.
//  Only the company administrator reaches this (route middleware).
// ══════════════════════════════════════════════════════════════════

class TypeListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isCompanyAdmin();
    }

    public function stream(): string
    {
        return (string) $this->route('stream');
    }

    public function rules(): array
    {
        return [
            'rows' => ['present', 'array', 'max:200'],
            'rows.*.id' => ['nullable', 'integer'],
            'rows.*.title' => ['required', 'string', 'max:190'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $company = $this->user()->company;
            $types = StudyStreams::types($company, $this->stream());
            $own = $types->where('total', false)->pluck('id')->all();
            $total = $types->firstWhere('total', true);
            $totalTitle = mb_strtolower((string) ($total['title'] ?? ''));
            $seen = [];

            foreach ($this->input('rows', []) as $i => $row) {
                if (! empty($row['id']) && ! in_array((int) $row['id'], $own, true)) {
                    $validator->errors()->add("rows.{$i}.id", __('revenue.errors.type_unknown'));
                }
                $title = mb_strtolower(trim((string) $row['title']));
                if ($title === $totalTitle || isset($seen[$title])) {
                    $validator->errors()->add("rows.{$i}.title", __('revenue.errors.type_twice'));
                }
                $seen[$title] = true;
            }
        }];
    }

    public function attributes(): array
    {
        return ['rows.*.title' => __('revenue.fields.type_title')];
    }
}
