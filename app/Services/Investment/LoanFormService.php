<?php

namespace App\Services\Investment;

use App\Models\HospitalitySector;
use App\Models\Loan;
use App\Support\CollectionTerms;
use App\Support\Investment;
use App\Services\Revenue\RevenueParts;
use Illuminate\Database\Eloquent\Model;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — LoanFormService (the loan of a purchase or a cost)
//  Location: app/Services/Investment/LoanFormService.php
//
//  Every investment section that is partly funded by debt has the same loan form (resources/views/loan-form.blade.php of the old application):
//  land, hard construction, property and FF&E. The loan is kept in `loans`, one row for each section (section_name), as the old application kept it:
//    loan_type, base_rate, margin_rate, pricing (= base + margin), period (the tenor in months), grace_period, installment_interval,
//    step_up_rate / step_up_interval, step_down_rate / step_down_interval, capitalization_type (worked out from the loan type).
//  What the type does (the same rules as the old loanForm.ts script):
//    · only a step-up type has a step-up rate and interval, only a step-down type has a step-down rate and interval;
//    · only a "grace" type has a grace period, and it must leave at least two months of the tenor;
//    · a type "with capitalization" / "without capitalization" says so; the other types say nothing.
//  The calculation reads these rows as they are saved (App\Calculations\CalculateFixedLoanAtEndService).
// ══════════════════════════════════════════════════════════════════

class LoanFormService
{
    /** What the form shows for a section: the saved loan, or the old application's blank loan. */
    public function present(?Loan $loan): array
    {
        $type = $loan?->loan_type ?: 'normal';

        return [
            'loan_type' => in_array($type, Investment::LOAN_TYPES, true) ? $type : 'normal',
            'base_rate' => CollectionTerms::number($loan?->base_rate ?: 0),
            'margin_rate' => CollectionTerms::number($loan?->margin_rate ?: 0),
            'period' => (int) ($loan?->period ?: 0),
            'grace_period' => (int) ($loan?->grace_period ?: 0),
            'installment_interval' => in_array($loan?->installment_interval, Investment::LOAN_INSTALLMENT, true) ? $loan->installment_interval : 'quartly',
            'step_up_rate' => CollectionTerms::number($loan?->step_up_rate ?: 0),
            'step_up_interval' => in_array($loan?->step_up_interval, Investment::LOAN_STEP, true) ? $loan->step_up_interval : 'annually',
            'step_down_rate' => CollectionTerms::number($loan?->step_down_rate ?: 0),
            'step_down_interval' => in_array($loan?->step_down_interval, Investment::LOAN_STEP, true) ? $loan->step_down_interval : 'annually',
        ];
    }

    /** The checks of one loan form. `$prefix` is where it sits in the request, like "loans.land". */
    public function rules(string $prefix, bool $needed): array
    {
        $must = $needed ? 'required' : 'nullable';

        return [
            $prefix => ['required', 'array'],
            "{$prefix}.loan_type" => ['required', 'string', 'in:'.implode(',', Investment::LOAN_TYPES)],
            "{$prefix}.base_rate" => ['nullable', 'numeric', 'min:0', 'max:100'],
            "{$prefix}.margin_rate" => ['nullable', 'numeric', 'min:0', 'max:100'],
            "{$prefix}.period" => [$must, 'integer', $needed ? 'min:1' : 'min:0', 'max:600'],
            "{$prefix}.grace_period" => ['nullable', 'integer', 'min:0', 'max:600'],
            "{$prefix}.installment_interval" => [$must, 'string', 'in:'.implode(',', Investment::LOAN_INSTALLMENT)],
            "{$prefix}.step_up_rate" => ['nullable', 'numeric', 'min:0', 'max:100'],
            "{$prefix}.step_up_interval" => ['nullable', 'string', 'in:'.implode(',', Investment::LOAN_STEP)],
            "{$prefix}.step_down_rate" => ['nullable', 'numeric', 'min:-100', 'max:0'],
            "{$prefix}.step_down_interval" => ['nullable', 'string', 'in:'.implode(',', Investment::LOAN_STEP)],
        ];
    }

    /** The grace period must leave two months of the tenor (the old script cut it back to the tenor minus two). Returns the error key, or null. */
    public function graceProblem(array $loan): ?string
    {
        $grace = (int) ($loan['grace_period'] ?? 0);
        $type = (string) ($loan['loan_type'] ?? 'normal');

        return Investment::hasGrace($type) && $grace > 0 && $grace > (int) ($loan['period'] ?? 0) - 2 ? 'grace_period' : null;
    }

    /** The columns of a loan as the form sent it, with the fields its type does not use set to nothing. */
    public function columns(array $loan): array
    {
        $type = (string) ($loan['loan_type'] ?? 'normal');
        $base = (float) ($loan['base_rate'] ?? 0);
        $margin = (float) ($loan['margin_rate'] ?? 0);

        return [
            'loan_type' => $type,
            'base_rate' => RevenueParts::amount($base),
            'margin_rate' => RevenueParts::amount($margin),
            'pricing' => RevenueParts::amount($base + $margin),
            'period' => (string) (int) ($loan['period'] ?? 0),
            'grace_period' => (string) (Investment::hasGrace($type) ? (int) ($loan['grace_period'] ?? 0) : 0),
            'installment_interval' => (string) ($loan['installment_interval'] ?? 'quartly'),
            'step_up_rate' => RevenueParts::amount(Investment::hasStepUp($type) ? ($loan['step_up_rate'] ?? 0) : 0),
            'step_up_interval' => (string) ($loan['step_up_interval'] ?? 'annually'),
            'step_down_rate' => RevenueParts::amount(Investment::hasStepDown($type) ? ($loan['step_down_rate'] ?? 0) : 0),
            'step_down_interval' => (string) ($loan['step_down_interval'] ?? 'annually'),
            'capitalization_type' => Loan::getCapitalizationType($type),
        ];
    }

    /**
     * Keep the loan of a section: the one of the owner (an Acquisition, PropertyAcquisition or FFE record, through its loans() relation) is
     * updated, or created when it has none.
     */
    public function save(Model $owner, string $section, array $loan, HospitalitySector $study): Loan
    {
        $columns = $this->columns($loan) + ['section_name' => $section, 'hospitality_sector_id' => $study->id, 'company_id' => $study->company_id];
        // the loan the calculation reads for this section is the one that is updated
        $owner->unsetRelation('loans');
        $existing = $owner->getLoanForSection($section);

        if ($existing) {
            $existing->update($columns);

            return $existing;
        }

        return $owner->loans()->create($columns);
    }
}
