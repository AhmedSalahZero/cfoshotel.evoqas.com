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
//  CFO Hotels — PropertyAcquisitionRequest (the checks of the Property Acquisition Cost page)
//  Location: app/Http/Requests/Investment/PropertyAcquisitionRequest.php
//  The purchase as on the land page. The breakdown has three lines (land, building, furniture & equipment): a share 0 - 100 % each, adding up to
//  100 %, and the depreciation years of the building (3 - 25 or 50) and of the furniture & equipment (3 - 25). The replacement of the building and of
//  the furniture & equipment: a rate 0 - 100 % and an interval of 1 - 5 years. A loan is asked for when part of the purchase is funded by debt.
// ══════════════════════════════════════════════════════════════════

class PropertyAcquisitionRequest extends FormRequest
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
        $property = (array) $this->input('property', []);

        $rules = PurchaseBlock::rules('property', $property, $months);
        $rules['breakdown'] = ['required', 'array', 'size:'.count(Investment::BREAKDOWN)];
        $rules['breakdown.*.percentage'] = ['required', 'numeric', 'min:0', 'max:100'];
        $rules['breakdown.0.depreciation'] = ['nullable'];
        $rules['breakdown.1.depreciation'] = ['required', 'integer', Rule::in(Investment::depreciationYears(true))];
        $rules['breakdown.2.depreciation'] = ['required', 'integer', Rule::in(Investment::depreciationYears())];
        $rules['replacement.rate'] = ['required', 'numeric', 'min:0', 'max:100'];
        $rules['replacement.interval'] = ['required', 'integer', Rule::in(Investment::replacementYears())];
        $rules['replacement.ffeRate'] = ['required', 'numeric', 'min:0', 'max:100'];
        $rules['replacement.ffeInterval'] = ['required', 'integer', Rule::in(Investment::replacementYears())];
        $rules['loans'] = ['required', 'array'];

        return $rules + app(LoanFormService::class)->rules('loans.'.PROPERTY_LOAN, PurchaseBlock::debt($property) > 0);
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $data = $validator->getData();
            foreach (PurchaseBlock::problems('property', (array) $data['property']) as $key => $message) {
                $validator->errors()->add($key, __($message));
            }
            $total = array_sum(array_map(fn ($line) => (float) $line['percentage'], (array) $data['breakdown']));
            if (abs($total - 100) > 0.005) {
                $validator->errors()->add('breakdown', __('investment.errors.breakdown_total'));
            }
            if (app(LoanFormService::class)->graceProblem((array) $data['loans'][PROPERTY_LOAN])) {
                $validator->errors()->add('loans.'.PROPERTY_LOAN.'.grace_period', __('investment.errors.grace'));
            }
        }];
    }

    public function attributes(): array
    {
        $out = [
            'property.date' => __('investment.fields.purchase_date'), 'property.cost' => __('investment.fields.purchase_cost'), 'property.contingency' => __('investment.fields.contingency'),
            'property.method' => __('investment.fields.property_method'), 'property.equity' => __('investment.fields.equity'),
            'property.first' => __('investment.fields.first_down'), 'property.second' => __('investment.fields.second_down'), 'property.after' => __('investment.fields.after_month'),
            'property.count' => __('investment.fields.installment_count'), 'property.interval' => __('investment.fields.installment_interval'),
            'property.terms.rates.*' => __('investment.fields.collection_rate'), 'property.terms.due.*' => __('investment.fields.due_in_days'),
            'breakdown.*.percentage' => __('investment.fields.as_percentage'), 'breakdown.*.depreciation' => __('investment.fields.depreciation'),
            'replacement.rate' => __('investment.fields.replacement_rate'), 'replacement.ffeRate' => __('investment.fields.replacement_rate'),
            'replacement.interval' => __('investment.fields.replacement_interval'), 'replacement.ffeInterval' => __('investment.fields.replacement_interval'),
        ];
        foreach (['loan_type', 'base_rate', 'margin_rate', 'period', 'grace_period', 'installment_interval', 'step_up_rate', 'step_up_interval', 'step_down_rate', 'step_down_interval'] as $field) {
            $out["loans.*.{$field}"] = __("investment.loan.{$field}");
        }

        return $out;
    }
}
