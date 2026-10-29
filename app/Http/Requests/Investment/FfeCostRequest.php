<?php

namespace App\Http\Requests\Investment;

use App\Models\HospitalitySector;
use App\Services\Investment\InvestmentDates;
use App\Services\Investment\LoanFormService;
use App\Support\Investment;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — FfeCostRequest (the checks of the FF&E page)
//  Location: app/Http/Requests/Investment/FfeCostRequest.php
//  Ten item lines. A line with no name is empty and nothing in it is checked; a line with a name needs a currency of the study, a cost >= 0, a contingency
//  0 - 100, depreciation years 3 - 25, a replacement rate 0 - 100 % and an interval 1 - 5 years. The start is a month between the study start and the
//  opening; start + duration may not be after the opening. Down payment and first balance together 100 % or less. A loan is asked for when part is funded by debt.
// ══════════════════════════════════════════════════════════════════

class FfeCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('study'));
    }

    public function rules(): array
    {
        /** @var HospitalitySector $study */
        $study = $this->route('study');
        $months = array_column(app(InvestmentDates::class)->months($study), 'index');
        $currencyIds = array_values(array_filter([(int) $study->main_functional_currency, (int) $study->additional_currency]));

        $rules = [
            'rows' => ['required', 'array', 'size:'.Investment::FFE_ROWS],
            'rows.*.id' => ['nullable', 'integer'],
            'rows.*.name' => ['nullable', 'string', 'max:120'],
            'dates.start' => ['required', 'integer', Rule::in($months)],
            'dates.duration' => ['required', 'integer', 'min:0', 'max:600'],
            'dates.method' => ['required', Rule::in(Investment::FFE_EXECUTION)],
            'terms.down' => ['required', 'numeric', 'min:0', 'max:100'],
            'terms.one' => ['required', 'numeric', 'min:0', 'max:100'],
            'terms.dueOne' => ['required', 'integer', Rule::in(Investment::DUE_DAYS)],
            'terms.dueTwo' => ['required', 'integer', Rule::in(Investment::DUE_DAYS)],
            'equity' => ['required', 'numeric', 'min:0', 'max:100'],
            'loans' => ['required', 'array'],
        ];
        foreach ((array) $this->input('rows', []) as $i => $row) {
            $need = trim((string) ($row['name'] ?? '')) !== '' ? 'required' : 'nullable';
            $rules["rows.{$i}.currency"] = [$need, 'integer', Rule::in($currencyIds)];
            $rules["rows.{$i}.cost"] = [$need, 'numeric', 'min:0', 'max:1000000000000'];
            $rules["rows.{$i}.contingency"] = [$need, 'numeric', 'min:0', 'max:100'];
            $rules["rows.{$i}.depreciation"] = [$need, 'integer', Rule::in(Investment::depreciationYears())];
            $rules["rows.{$i}.replacementRate"] = [$need, 'numeric', 'min:0', 'max:100'];
            $rules["rows.{$i}.replacementInterval"] = [$need, 'integer', Rule::in(Investment::replacementYears())];
        }

        return $rules + app(LoanFormService::class)->rules('loans.'.FFE_COST, 100 - (float) $this->input('equity', 100) > 0);
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            /** @var HospitalitySector $study */
            $study = $this->route('study');
            $data = $validator->getData();

            if ((float) $data['terms']['down'] + (float) $data['terms']['one'] > 100.0001) {
                $validator->errors()->add('terms.one', __('investment.errors.balances'));
            }
            if (app(InvestmentDates::class)->end((int) $data['dates']['start'], (int) $data['dates']['duration']) > app(InvestmentDates::class)->opening($study)) {
                $validator->errors()->add('dates.duration', __('investment.errors.ffe_end'));
            }
            $names = collect((array) $data['rows'])->pluck('name')->map(fn ($n) => mb_strtolower(trim((string) $n)))->filter();
            if ($names->unique()->count() !== $names->count()) {
                $validator->errors()->add('rows', __('investment.errors.name_twice'));
            }
            if (app(LoanFormService::class)->graceProblem((array) $data['loans'][FFE_COST])) {
                $validator->errors()->add('loans.'.FFE_COST.'.grace_period', __('investment.errors.grace'));
            }
        }];
    }

    public function attributes(): array
    {
        $out = [
            'rows.*.currency' => __('investment.fields.currency'), 'rows.*.cost' => __('investment.fields.item_cost'), 'rows.*.contingency' => __('investment.fields.contingency_short'),
            'rows.*.depreciation' => __('investment.fields.depreciation'), 'rows.*.replacementRate' => __('investment.fields.replacement_rate'), 'rows.*.replacementInterval' => __('investment.fields.replacement_interval'),
            'dates.start' => __('investment.fields.start_date'), 'dates.duration' => __('investment.fields.duration'), 'dates.method' => __('investment.fields.execution_method'),
            'terms.down' => __('investment.fields.down_payment'), 'terms.one' => __('investment.fields.balance_one'), 'terms.dueOne' => __('investment.fields.due_one'),
            'terms.dueTwo' => __('investment.fields.due_two'), 'equity' => __('investment.fields.equity'),
        ];
        foreach (['loan_type', 'base_rate', 'margin_rate', 'period', 'grace_period', 'installment_interval', 'step_up_rate', 'step_up_interval', 'step_down_rate', 'step_down_interval'] as $field) {
            $out["loans.*.{$field}"] = __("investment.loan.{$field}");
        }

        return $out;
    }
}
