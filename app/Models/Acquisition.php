<?php

namespace App\Models;

use App\Models\Concerns\CompanyScope;
use App\Support\LegacyCarbon;
use App\Models\Concerns\AllowsLazyLoading;
use Illuminate\Database\Eloquent\Model;

class Acquisition extends Model implements AllowsLazyLoading
{
    protected $table = 'acquisitions';

    use CompanyScope;

    protected $guarded = [
        'id',
    ];

    protected $casts = [
    ];

    public function getLandPurchaseDateAsIndex()
    {
        return $this->purchase_date;
    }

    public function getLandPurchaseDateAsString()
    {
        return app('dateIndexWithDate')[$this->getLandPurchaseDateAsIndex()] ?? null;
    }

    public function getLandPurchaseDateFormatted(): ?string
    {
        return $this->getLandPurchaseDateAsString() ? LegacyCarbon::make($this->getLandPurchaseDateAsString())->format('d-m-Y') : null;
    }

    public function getLandPurchaseCost()
    {
        return $this->land_purchase_cost ?: 0;
    }

    public function getLandContingencyRate()
    {
        return $this->land_contingency_rate ?: 0;
    }

    public function getTotalPurchaseCost()
    {
        $contingencyRate = $this->getLandContingencyRate() / 100;
        $landPurchaseCost = $this->getLandPurchaseCost();

        return $landPurchaseCost * (1 + $contingencyRate);
    }

    public function getLandPaymentMethod()
    {
        return $this->land_payment_method;
    }

    public function getFirstLandDownPaymentPercentage()
    {
        return $this->first_land_down_payment_percentage ?: 0;
    }

    public function getSecondLandDownPaymentPercentage()
    {
        return $this->second_land_down_payment_percentage ?: 0;
    }

    public function getLandAfterMonthDays()
    {
        return $this->land_after_month ?: 0;
    }

    public function getLandBalanceRate()
    {
        return 100 - $this->getFirstLandDownPaymentPercentage() - $this->getSecondLandDownPaymentPercentage();
    }

    public function getLandInstallmentCount()
    {
        return $this->land_installment_count ?: 1;
    }

    public function getLandInstallmentInterval()
    {
        return $this->installment_interval;
    }

    public function getLandEquityFundingRate()
    {
        return ! is_null($this->land_equity_funding_rate) ? $this->land_equity_funding_rate : 100;
    }

    public function getHardDebtFundingPercentage()
    {
        $hardEquityFunding = $this->getHardEquityFunding();

        return 1 - $hardEquityFunding;
    }

    public function getDebtFundingPercentage()
    {
        $equityFundingPercentage = $this->getLandEquityFundingRate();

        return 100 - $equityFundingPercentage;
    }

    public function getEquityAmount()
    {
        return $this->equity_amount ?: 0;
    }

    public function getDebtAmount()
    {
        $totalPurchaseCost = $this->getTotalPurchaseCost();
        $debtFundingPercentage = $this->getDebtFundingPercentage() / 100;

        return $totalPurchaseCost * $debtFundingPercentage;
    }

    public function hospitalitySector()
    {
        return $this->belongsTo(HospitalitySector::class, 'hospitality_sector_id', 'id');
    }

    public function getCollectionPolicyType()
    {
        return $this->collection_policy_type;
    }

    public function collectionPolicyInterval()
    {
        return $this->collection_policy_interval;
    }

    public function isSystemDefaultCollectionPolicy()
    {
        return $this->getCollectionPolicyType() == 'system_default';
    }

    public function isCustomizeCollectionPolicy()
    {
        return $this->getCollectionPolicyType() == 'customize';
    }

    public function getSalesChannelRateAndDueInDays(int $index, $type)
    {
        if (! $this->isCustomizeCollectionPolicy()) {
            return [
                'rate' => 0,
                'due_in_days' => 0,
            ][$type];
        }

        return [
            'rate' => ((array) json_decode($this->collection_policy_value))['rate'][$index] ?? 0,
            'due_in_days' => ((array) json_decode($this->collection_policy_value))['due_in_days'][$index] ?? 0,
        ][$type];
    }

    public function getCollectionPolicyValue()
    {
        $collectionPolicyValue = convertJsonToArray($this->collection_policy_value);

        return $collectionPolicyValue;
    }

    public function loans()
    {
        return $this->hasMany(Loan::class, 'acquisition_id', 'id');
    }

    public function getLoanForSection(string $currentSectionName)
    {
        return $this->loans->where('section_name', $currentSectionName)->first();
    }

    public function getHardConstructionCost()
    {
        return $this->hard_construction_cost ?: 0;
    }

    public function getSoftConstructionCost()
    {
        return $this->soft_construction_cost ?: 0;
    }

    public function getHardConstructionContingencyRate()
    {
        return $this->hard_construction_contingency_rate ?: 0;
    }

    public function getSoftConstructionContingencyRate()
    {
        return $this->soft_construction_contingency_rate ?: 0;
    }

    public function getTotalHardConstructionCost()
    {
        $hardConstructionContingencyRate = $this->getHardConstructionContingencyRate() / 100;

        return $this->getHardConstructionCost() * (1 + $hardConstructionContingencyRate);
    }

    public function getTotalSoftConstructionCost()
    {
        $softConstructionContingencyRate = $this->getSoftConstructionContingencyRate() / 100;

        return $this->getSoftConstructionCost() * (1 + $softConstructionContingencyRate);
    }

    public function getHardConstructionStartDateAsIndex(HospitalitySector $hospitalitySector)
    {
        return is_null($this->hard_construction_start_date) ? $hospitalitySector->getDevelopmentStartDateAsIndex() : $this->hard_construction_start_date;
    }

    public function getHardConstructionStartDateAsString(HospitalitySector $hospitalitySector)
    {
        return app('dateIndexWithDate')[$this->getHardConstructionStartDateAsIndex($hospitalitySector)];
    }

    public function getHardConstructionStartDateFormatted(HospitalitySector $hospitalitySector)
    {
        $hardConstructionStartDate = $this->getHardConstructionStartDateAsString($hospitalitySector);
        if ($hardConstructionStartDate) {
            return LegacyCarbon::make($hardConstructionStartDate)->format('d-m-Y');
        }

        return $hospitalitySector->getDevelopmentStartDateFormatted();
    }

    public function getSoftConstructionStartDateAsIndex(HospitalitySector $hospitalitySector)
    {
        return $this->soft_construction_start_date ?: $hospitalitySector->getDevelopmentStartDateAsIndex();
    }

    public function getSoftConstructionStartDateAsString(HospitalitySector $hospitalitySector)
    {
        $date = app('dateIndexWithDate')[$this->getSoftConstructionStartDateAsIndex($hospitalitySector)] ?? null;

        return $date ?: $hospitalitySector->getDevelopmentStartDateAsString();
    }

    public function getSoftConstructionStartDateFormatted(HospitalitySector $hospitalitySector)
    {
        $softConstructionStartDate = $this->getSoftConstructionStartDateAsString($hospitalitySector);
        if ($softConstructionStartDate) {
            return LegacyCarbon::make($softConstructionStartDate)->format('d-m-Y');
        }

        return $hospitalitySector->getDevelopmentStartDateAsString();
    }

    public function getHardConstructionDuration()
    {
        return $this->hard_construction_duration;
    }

    public function getSoftConstructionDuration()
    {
        return $this->soft_construction_duration;
    }

    public function getConstructionEndDate()
    {
        return $this->hard_construction_end_date;
    }

    public function getHardExecutionMethod()
    {
        return $this->hard_execution_method;
    }

    public function getSoftExecutionMethod()
    {
        return $this->soft_execution_method;
    }

    public function getHardDownPaymentPercentage()
    {
        return $this->hard_down_payment ?: 0;
    }

    public function getSoftDownPaymentPercentage()
    {
        return $this->soft_down_payment ?: 0;
    }

    public function getHardBalanceRateOne()
    {
        return $this->hard_balance_rate_one ?: 0;
    }

    public function getSoftBalanceRateOne()
    {
        return $this->soft_balance_rate_one ?: 0;
    }

    public function getHardDueOne()
    {
        return $this->hard_due_one ?: 0;
    }

    public function getSoftDueOne()
    {
        return $this->soft_due_one ?: 0;
    }

    public function getHardBalanceRateTwo()
    {
        return $this->hard_balance_rate_two ?: 0;
    }

    public function getSoftBalanceRateTwo()
    {
        return $this->soft_balance_rate_two ?: 0;
    }

    public function getHardDueTwo()
    {
        return $this->hard_due_two ?: 0;
    }

    public function getSoftDueTwo()
    {
        return $this->soft_due_two ?: 0;
    }

    public function getHardEquityFunding()
    {

        return $this->hard_equity_funding ?: 0;
    }

    public function getHardEquityAmount()
    {
        $hardEquityFunding = $this->getHardEquityFunding() / 100;

        return $hardEquityFunding * $this->getTotalPurchaseCost();
    }

    public function getHardDebtFunding()
    {
        $hardEquityFundingPercentage = $this->getHardEquityFunding();

        return 100 - $hardEquityFundingPercentage;
    }

    public function getHardDebtAmount()
    {
        $hardDebtFunding = $this->getHardDebtFunding() / 100;

        return $hardDebtFunding * $this->getTotalHardConstructionCost();

    }

    // mutation
    public function storeLoans(int $hospitalitySectorId, array $loans, int $companyId)
    {
        foreach ($loans as $sectionName => $arrayOfData) {
            $loan = $this->getLoanForSection($sectionName);
            // current_section_name
            $loanType = $arrayOfData['loan_type'] ?? null;

            $data = array_merge($arrayOfData, ['company_id' => $companyId, 'section_name' => $sectionName,
                'fixedLoanType' => 'fixed.loan.fixed.at.end',
                'capitalization_type' => Loan::getCapitalizationType($loanType),
                'hospitality_sector_id' => $hospitalitySectorId,

            ]);
            if ($loan) {
                $loan->update($data);
            } else {
                $this->loans()->create($data);
            }
        }

        return $this;
    }

    public function getLandCustomCollectionPolicyValue()
    {
        return (array) json_decode($this->collection_policy_value);
    }

    public function getHardCollectionPolicyValue(): array
    {
        return [
            'due_in_days' => [$this->getHardDueOne(), $this->getHardDueTwo()],
            'rate' => [$this->getHardBalanceRateOne(), $this->getHardBalanceRateTwo()],
        ];
    }

    public function getHardConstructionStartDateIndex(HospitalitySector $hospitalitySector): int
    {
        return $hospitalitySector->getHardConstructionStartDate();

    }

    public function getSoftCollectionPolicyValue(): array
    {
        return [
            'due_in_days' => [$this->getSoftDueOne(), $this->getSoftDueTwo()],
            'rate' => [$this->getSoftBalanceRateOne(), $this->getSoftBalanceRateTwo()],
        ];
    }

    public function hasSoftConstructionSection()
    {
        return $this->has_soft_construction_cost_section;
    }

    public function getSoftEquityFundingRate()
    {
        return 100;
    }
}
