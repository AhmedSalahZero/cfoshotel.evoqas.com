<?php

namespace App\Http\Requests\Investment;

use App\Models\HospitalitySector;
use App\Services\Investment\InvestmentDates;
use App\Services\Investment\LoanFormService;
use App\Services\Investment\PurchaseBlock;
use App\Support\Investment;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — LandAcquisitionRequest (the checks of the Land & Construction page)
//  Location: app/Http/Requests/Investment/LandAcquisitionRequest.php
//  Land: a month between the study start and the opening, cost >= 0, contingency 0 - 100, payment method, and for an installment the two down
//  payments (together 100 % or less), months after, count and interval; for a customised collection four rates adding up to 100 %.
//  Hard and soft construction: cost, contingency, start month, duration, execution method, down payment and first balance (together 100 % or less),
//  due days; the end (start + duration) may not be after the opening month. The hard construction also has its equity funding.
//  A loan is asked for (tenor and installment interval) when part of the land or of the hard construction is funded by debt.
// ══════════════════════════════════════════════════════════════════

class LandAcquisitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('study'));
    }

    public function rules(): array
    {
        /** @var HospitalitySector $study */
        $study = $this->route('study');
        $dates = app(InvestmentDates::class);
        $months = array_column($dates->months($study), 'index');
        $loans = app(LoanFormService::class);
        $land = (array) $this->input('land', []);
        $hard = (array) $this->input('hard', []);

        $rules = PurchaseBlock::rules('land', $land, $months);
        foreach (['hard', 'soft'] as $kind) {
            $rules["{$kind}.cost"] = ['required', 'numeric', 'min:0', 'max:1000000000000'];
            $rules["{$kind}.contingency"] = ['required', 'numeric', 'min:0', 'max:100'];
            $rules["{$kind}.start"] = ['required', 'integer', Rule::in($months)];
            $rules["{$kind}.duration"] = ['required', 'integer', 'min:0', 'max:600'];
            $rules["{$kind}.method"] = ['required', Rule::in($kind === 'hard' ? Investment::HARD_EXECUTION : Investment::SOFT_EXECUTION)];
            $rules["{$kind}.down"] = ['required', 'numeric', 'min:0', 'max:100'];
            $rules["{$kind}.one"] = ['required', 'numeric', 'min:0', 'max:100'];
            $rules["{$kind}.dueOne"] = ['required', 'integer', Rule::in(Investment::DUE_DAYS)];
            $rules["{$kind}.dueTwo"] = ['required', 'integer', Rule::in(Investment::DUE_DAYS)];
        }
        $rules['hard.equity'] = ['required', 'numeric', 'min:0', 'max:100'];

        $rules['loans'] = ['required', 'array'];

        return $rules
            + $loans->rules('loans.'.LAND_LOAN, PurchaseBlock::debt($land) > 0)
            + $loans->rules('loans.'.HARD_COST_CONSTRUCTION, 100 - (float) ($hard['equity'] ?? 100) > 0);
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            /** @var HospitalitySector $study */
            $study = $this->route('study');
            $dates = app(InvestmentDates::class);
            $data = $validator->getData();

            foreach (PurchaseBlock::problems('land', (array) $data['land']) as $key => $message) {
                $validator->errors()->add($key, __($message));
            }
            foreach (['hard', 'soft'] as $kind) {
                $block = (array) $data[$kind];
                if ((float) $block['down'] + (float) $block['one'] > 100.0001) {
                    $validator->errors()->add("{$kind}.one", __('investment.errors.balances'));
                }
                if ($dates->end((int) $block['start'], (int) $block['duration']) > $dates->opening($study)) {
                    $validator->errors()->add("{$kind}.duration", __("investment.errors.{$kind}_end"));
                }
            }
            $loans = app(LoanFormService::class);
            foreach ([LAND_LOAN, HARD_COST_CONSTRUCTION] as $section) {
                if ($loans->graceProblem((array) $data['loans'][$section])) {
                    $validator->errors()->add("loans.{$section}.grace_period", __('investment.errors.grace'));
                }
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'land.date' => __('investment.fields.purchase_date'), 'land.cost' => __('investment.fields.purchase_cost'), 'land.contingency' => __('investment.fields.contingency'),
            'land.method' => __('investment.fields.land_method'), 'land.equity' => __('investment.fields.equity'),
            'land.first' => __('investment.fields.first_down'), 'land.second' => __('investment.fields.second_down'), 'land.after' => __('investment.fields.after_month'),
            'land.count' => __('investment.fields.installment_count'), 'land.interval' => __('investment.fields.installment_interval'),
            'land.terms.rates.*' => __('investment.fields.collection_rate'), 'land.terms.due.*' => __('investment.fields.due_in_days'),
            '*.cost' => __('investment.fields.construction_cost'), '*.start' => __('investment.fields.start_date'), '*.duration' => __('investment.fields.duration'),
            '*.method' => __('investment.fields.execution_method'), '*.down' => __('investment.fields.down_payment'), '*.one' => __('investment.fields.balance_one'),
            '*.dueOne' => __('investment.fields.due_one'), '*.dueTwo' => __('investment.fields.due_two'), 'hard.equity' => __('investment.fields.equity'),
        ] + $this->loanAttributes();
    }

    private function loanAttributes(): array
    {
        $out = [];
        foreach (['loan_type', 'base_rate', 'margin_rate', 'period', 'grace_period', 'installment_interval', 'step_up_rate', 'step_up_interval', 'step_down_rate', 'step_down_interval'] as $field) {
            $out["loans.*.{$field}"] = __("investment.loan.{$field}");
        }

        return $out;
    }
}
