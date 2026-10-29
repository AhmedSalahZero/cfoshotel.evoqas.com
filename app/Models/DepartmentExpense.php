<?php

namespace App\Models;

use App\Support\LegacyCarbon;
use App\Models\Concerns\AllowsLazyLoading;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class DepartmentExpense extends Model implements AllowsLazyLoading
{


    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'manpower_payload' => 'array',
    ];

    public function hospitalitySector()
    {
        return $this->belongsTo(HospitalitySector::class, 'hospitality_sector_id', 'id');
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPayloadAtYear(int $year)
    {
        $payload = $this->payload;
        $payload = arrayToValueIndexes($payload);

        return isset($payload[$year]) ? $payload[$year] : 0;
    }

    public function getManpowerPayloadAtDate(string $date, $repeatingDates = null)
    {
        $payload = $this->manpower_payload;
        if ($repeatingDates && $payload) {
            $payload = sortDatesAsIndex($payload);
            $payload = repeatLastKeyForUnExistingKeysIn($payload, $repeatingDates);
        }
        $payload = arrayToValueIndexes($payload);

        return isset($payload[$date]) ? $payload[$date] : 0;
    }

    /**
     * The head count of every date in $dates, worked out once: the same figures as asking getManpowerPayloadAtDate() date by date
     * (that one sorts and fills the whole table again on every call, which is slow when it is asked for every month of a long study).
     *
     * @param  array<int, mixed>  $dates  the date numbers of the study, as the calculation passes them
     * @return array<int|string, mixed>  date => head count (0 when there is none)
     */
    public function getManpowerPayloadForDates(array $dates): array
    {
        $payload = $this->manpower_payload;
        if ($dates && $payload) {
            $payload = sortDatesAsIndex($payload);
            $payload = repeatLastKeyForUnExistingKeysIn($payload, $dates);
        }
        $payload = arrayToValueIndexes($payload);

        $heads = [];
        foreach ($dates as $date) {
            $heads[$date] = isset($payload[$date]) ? $payload[$date] : 0;
        }

        return $heads;
    }

    public function getExpensePerNightSold()
    {
        return $this->expense_per_night_sold ?: 0;
    }

    public function getExpensePerGuest()
    {
        return $this->expense_per_guest ?: 0;
    }

    public function getInventoryCoverageDays()
    {
        return $this->inventory_coverage_days ?: 0;
    }

    public function beginningInventoryBalanceValue()
    {
        return $this->beginning_inventory_balance_value ?: 0;
    }

    public function getCashPayment()
    {
        return $this->cash_payment_percentage ?: 0;
    }

    public function getDeferredPaymentPercentage()
    {
        return $this->deferred_payment_percentage ?: 0;
    }

    public function getDueDays()
    {
        return $this->due_days ?: 0;
    }

    public function getCurrentNetSalary()
    {
        return $this->current_net_salary ?: 0;
    }

    public function getChosenCurrency()
    {
        return $this->chosen_currency;
    }

    public function getEscalationRate()
    {
        return $this->escalation_rate ?: 0;
    }

    public function getNetSalaryAtOperationDate()
    {
        return $this->net_salary_at_operation_date ?: 0;
    }

    public function getAnnualEscalationRate()
    {
        return $this->annual_escalation_rate ?: 0;
    }

    public function getSalaryTaxes()
    {
        return $this->salary_taxes ?: 0;
    }

    public function getSocialInsurance()
    {
        return $this->social_insurance ?: 0;
    }

    public function getNightExpenseEscalationRate()
    {
        return $this->night_expense_escalation_rate ?: 0;
    }

    public function getNightAnnualEscalationRate()
    {
        return $this->night_annual_escalation_rate ?: 0;
    }

    public function getNightExpenseAtOperationDate()
    {
        return $this->night_expense_at_operation_date ?: 0;
    }

    public function getNightExpenseChosenCurrency()
    {
        return $this->chosen_night_expense_currency ?: 0;
    }

    public function getGuestAnnualEscalationRate()
    {
        return $this->guest_annual_escalation_rate ?: 0;
    }

    public function getPercentageFromFixedAssets()
    {
        return $this->percentage_from_fixed_assets ?: 0;
    }

    public function getPaymentMonth()
    {
        return $this->payment_month ?: 0;
    }

    public function getGuestExpenseAtOperationDate()
    {
        return $this->guest_expense_at_operation_date;
    }

    public function getGuestExpenseEscalationRate()
    {
        return $this->guest_expense_escalation_rate;
    }

    public function getExpensePerGuestSold()
    {
        return $this->expense_per_guest_sold ?: 0;
    }

    public function getGuestExpenseChosenCurrency()
    {
        return $this->chosen_guest_expense_currency;
    }

    public function getIdentifier()
    {
        return $this->id;
    }

    public static function getIdentifierName()
    {
        return 'id';
    }

    public function getAnnualEscalationPercentage($type)
    {
        if ($type == variable_expenses_as_cost_per_night_sold) {
            return $this->getNightAnnualEscalationRate();
        } elseif ($type == variable_expenses_as_cost_per_guest) {
            return $this->getGuestAnnualEscalationRate();
        } elseif ($type == rooms_manpower_expense) {
            return $this->getAnnualEscalationRate();
        } elseif ($type == fixed_monthly_expenses) {
            return $this->getGuestAnnualEscalationRate();
        }
    }

    public function getBaseValueBeforeEscalation($type)
    {
        if ($type == variable_expenses_as_cost_per_night_sold) {
            return $this->getNightExpenseAtOperationDate();
        } elseif ($type == variable_expenses_as_cost_per_guest) {
            return $this->getGuestExpenseAtOperationDate();
        } elseif ($type == rooms_manpower_expense) {
            $netSalaryAtOperationDate = $this->getNetSalaryAtOperationDate();
            $salaryTax = $this->getSalaryTaxes();
            $socialInsurance = $this->getSocialInsurance();
            $grossSalaryAtOperationDate = $netSalaryAtOperationDate / (1 - ($salaryTax / 100) - ($socialInsurance / 100));

            return $grossSalaryAtOperationDate;
        } elseif ($type == fixed_monthly_expenses) {
            return $this->getGuestExpenseAtOperationDate();
        }
    }

    public function getBeginningInventoryBalanceValue()
    {
        return $this->beginning_inventory_balance_value ?: 0;
    }

    public function getOpexPaymentTerm()
    {
        return $this->opex_payment_terms;
    }

    public function setPayloadAttribute($jsonValue)
    {
        $this->attributes['payload'] = repeatJson($jsonValue, true);
    }

    public static function getNameFromId($id, Collection $departmentExpenses, ?string $totalNaming = null): string
    {
        if ($id == 0 && $totalNaming) {
            return $totalNaming;
        }

        if (is_numeric($id)) {
            return $departmentExpenses->where('id', $id)->first()->getName();
        }

        return $id;
    }

    public function getStartUpCost()
    {
        return $this->start_up_cost ?: 0;
    }

    public function getDateAsString()
    {
        return app('dateIndexWithDate')[$this->getDateAsIndex()] ?? null;
    }

    public function getDateAsIndex()
    {
        return $this->date;
    }

    public function getDateFormatted()
    {
        return LegacyCarbon::make($this->getDateAsString())?->format('d-m-Y');
    }
}
