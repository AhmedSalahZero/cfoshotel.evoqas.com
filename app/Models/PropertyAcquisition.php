<?php

namespace App\Models;

use App\Models\Concerns\CompanyScope;
use App\Calculations\ProjectsUnderProgress;
use App\Support\LegacyCarbon;
use App\Models\Concerns\AllowsLazyLoading;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PropertyAcquisition extends Model implements AllowsLazyLoading
{
    use CompanyScope;

    // const BUILDING_DEPRECIATION_DURATION=300; // months

    protected $guarded = [
        'id',
    ];

    protected $casts = [
    ];

    public function getPropertyPurchaseDateAsIndex()
    {

        return $this->purchase_date;
    }

    public function getPropertyPurchaseDateAsString()
    {
        $purchaseDateAsIndex = $this->getPropertyPurchaseDateAsIndex();

        return app('dateIndexWithDate')[$purchaseDateAsIndex] ?? null;
    }

    public function getPropertyPurchaseDateFormatted(): ?string
    {
        return $this->getPropertyPurchaseDateAsString() ? LegacyCarbon::make($this->getPropertyPurchaseDateAsString())->format('d-m-Y') : null;
    }

    public function getPropertyPurchaseCost()
    {
        return $this->property_purchase_cost ?: 0;
    }

    public function getPropertyContingencyRate()
    {
        return $this->property_contingency_rate ?: 0;
    }

    public function getTotalPurchaseCost()
    {
        $contingencyRate = $this->getPropertyContingencyRate() / 100;
        $propertyPurchaseCost = $this->getPropertyPurchaseCost();

        return $propertyPurchaseCost * (1 + $contingencyRate);
    }

    public function getPropertyPaymentMethod()
    {
        return $this->property_payment_method;
    }

    public function getFirstPropertyDownPaymentPercentage()
    {
        return $this->first_property_down_payment_percentage ?: 0;
    }

    public function getSecondPropertyDownPaymentPercentage()
    {
        return $this->second_property_down_payment_percentage ?: 0;
    }

    public function getPropertyAfterMonthDays()
    {
        return $this->property_after_month ?: 0;
    }

    public function getPropertyBalanceRate()
    {
        return 100 - $this->getFirstPropertyDownPaymentPercentage() - $this->getSecondPropertyDownPaymentPercentage();
    }

    public function getPropertyInstallmentCount()
    {
        return $this->property_installment_count ?: 1;
    }

    public function getPropertyInstallmentInterval()
    {
        return $this->installment_interval;
    }

    public function getPropertyEquityFundingRate()
    {
        return is_null($this->property_equity_funding_rate) ? 100 : $this->property_equity_funding_rate;
    }

    public function getDebtFundingPercentage()
    {
        $equityFundingPercentage = $this->getPropertyEquityFundingRate();

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

    // mutation
    public function storeLoans(int $hospitalitySectorId, array $loans, int $companyId)
    {
        foreach ($loans as $sectionName => $arrayOfData) {
            $loan = $this->getLoanForSection($sectionName);
            // current_section_name
            $loanType = $arrayOfData['loan_type'] ?? null;
            $data = array_merge($arrayOfData, ['company_id' => $companyId, 'section_name' => $sectionName, 'fixedLoanType' => 'fixed.loan.fixed.at.end',
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

    public function getPropertyCustomCollectionPolicyValue()
    {
        return (array) json_decode($this->collection_policy_value);
    }

    public function getReplacementCostRateForBuilding()
    {
        return $this->replacement_cost_rate ?: 0;
    }

    public function getReplacementCostRateForFFE()
    {
        return $this->ffe_replacement_cost_rate ?: 0;
    }

    public function getReplacementIntervalForBuilding()
    {
        return $this->replacement_interval ?: 0;
    }

    public function getReplacementIntervalInMonthsForBuilding()
    {
        return $this->getReplacementIntervalForBuilding() * 12;
    }

    public function getReplacementIntervalForFFE()
    {
        return $this->ffe_replacement_interval ?: 0;
    }

    public function getReplacementIntervalInMonthsForFFE()
    {
        return $this->getReplacementIntervalForFFE() * 12;
    }

    public function getFFEReplacementCostRate()
    {
        return $this->ffe_replacement_cost_rate ?: 0;
    }

    public function getFFEReplacementInterval()
    {
        return $this->ffe_replacement_interval ?: 0;
    }

    public function propertyBreakdowns(): Collection
    {
        return $this->hospitalitySector->getPropertyCostBreakdownForSection('property', PROPERTY_ACQUISITION, '-1');
    }

    public function getBuildingProperty()
    {
        return $this->propertyBreakdowns()->whereIn('name', ['Building Cost', __('Building Cost')])
            ->first();
    }

    public function getLandProperty()
    {
        return $this->propertyBreakdowns()->whereIn('name', ['Land Cost', __('Land Cost')])
            ->first();
    }

    public function getBuildingPropertyAmount(): float
    {
        return $this->getBuildingProperty() ? $this->getBuildingProperty()->item_amount : 0;
    }

    public function getLandPropertyAmount(): float
    {
        return $this->getLandProperty()->item_amount ?: 0;
    }

    public function getBuildingPropertyPercentage(): float
    {
        return $this->getBuildingProperty() ? $this->getBuildingProperty()->property_cost_percentage : 0;
    }

    public function getFFEProperty()
    {
        return $this->propertyBreakdowns()->whereIn('name', ['Furniture, Fixture & Equipment Cost', __('Furniture, Fixture & Equipment Cost')])
            ->first();
    }

    public function getFFEPropertyAmount(): float
    {
        return $this->getFFEProperty() ? $this->getFFEProperty()->item_amount : 0;
    }

    public function getFFEPropertyAmountPercentage(): float
    {
        return $this->getFFEProperty() ? $this->getFFEProperty()->property_cost_percentage : 0;
    }

    public function getLandPropertyAmountPercentage(): float
    {
        return $this->getLandProperty()->property_cost_percentage ?: 0;
    }

    public function getBuildingPropertyDepreciationDuration(): float
    {
        return $this->getBuildingProperty() ? $this->getBuildingProperty()->depreciation_duration : 1;
    }

    public function getBuildingPropertyDepreciationDurationInMonths(): float
    {
        return $this->getBuildingPropertyDepreciationDuration() * 12;
    }

    public function getFFEPropertyDepreciationDuration(): float
    {
        return $this->getFFEProperty() ? $this->getFFEProperty()->depreciation_duration : 1;
    }

    public function getFFEPropertyDepreciationDurationInMonths(): float
    {
        return $this->getFFEPropertyDepreciationDuration() * 12;
    }

    public function getProjectUnderProgressForConstructionForBuilding(array $hardConstructionExecution, array $softConstructionExecution, array $loanInterestOfHardConstruction, array $withdrawalInterestOfHardConstruction, HospitalitySector $hospitalitySector, int $operationStartDateAsIndex, array $datesAsStringAndIndex, array $datesIndexWithYearIndex, array $yearIndexWithYear, array $dateIndexWithDate)
    {
        return (new ProjectsUnderProgress)->calculateForConstruction($hardConstructionExecution, $softConstructionExecution, $loanInterestOfHardConstruction, $withdrawalInterestOfHardConstruction, $hospitalitySector, $operationStartDateAsIndex, $datesAsStringAndIndex, $datesIndexWithYearIndex, $yearIndexWithYear, $dateIndexWithDate);
    }

    public function getProjectUnderProgressForConstructionForFFE()
    {
        return [];
    }

    // $propertyAmount is $this->getBuildingPropertyAmount() in case of building
    // $propertyDepreciationDurationInMonths is $this->getBuildingPropertyDepreciationDurationInMonths()  in case of building
    // $propertyReplacementCostRate is $this->getReplacementCostRate() in case of building
    // $propertyReplacementIntervalInMonths is $this->getReplacementIntervalInMonths() in case of building
    public function calculatePropertyAssetsForBuilding(array $hardConstructionExecution, array $softConstructionExecution, array $loanInterestOfHardConstruction, array $withdrawalInterestOfHardConstruction, int $operationStartDateAsIndex, array $datesAsStringAndIndex, array $datesIndexWithYearIndex, array $yearIndexWithYear, array $dateIndexWithDate, array $propertyBuildingCapitalizedInterest): array
    {
        $hospitalitySector = $this->hospitalitySector;
        $propertyAcquisitionAmountForBuilding = $this->getBuildingPropertyAmount();
        $propertyDepreciationDurationInMonthsForBuilding = $this->getBuildingPropertyDepreciationDurationInMonths();
        $propertyReplacementCostRateForBuilding = $this->getReplacementCostRateForBuilding();
        $propertyReplacementIntervalInMonthsForBuilding = $this->getReplacementIntervalInMonthsForBuilding();
        $projectUnderProgressForConstruction = $this->getProjectUnderProgressForConstructionForBuilding($hardConstructionExecution, $softConstructionExecution, $loanInterestOfHardConstruction, $withdrawalInterestOfHardConstruction, $hospitalitySector, $operationStartDateAsIndex, $datesAsStringAndIndex, $datesIndexWithYearIndex, $yearIndexWithYear, $dateIndexWithDate);

        return $this->calculatePropertyAssets($propertyAcquisitionAmountForBuilding, $propertyDepreciationDurationInMonthsForBuilding, $propertyReplacementCostRateForBuilding, $propertyReplacementIntervalInMonthsForBuilding, $projectUnderProgressForConstruction, $datesAsStringAndIndex, $datesIndexWithYearIndex, $yearIndexWithYear, $dateIndexWithDate, $propertyBuildingCapitalizedInterest);

    }

    public function calculatePropertyAssetsForFFE(array $datesAsStringAndIndex, array $datesIndexWithYearIndex, array $yearIndexWithYear, array $dateIndexWithDate, array $propertyFFECapitalizedInterest): array
    {
        $propertyAcquisitionAmountForFFE = $this->getFFEPropertyAmount();
        $propertyDepreciationDurationInMonthsForFFE = $this->getFFEPropertyDepreciationDurationInMonths();
        $propertyReplacementCostRateForFFE = $this->getReplacementCostRateForFFE();
        $propertyReplacementIntervalInMonthsForFFE = $this->getReplacementIntervalInMonthsForFFE();
        $projectUnderProgressForFFE = $this->getProjectUnderProgressForConstructionForFFE();

        return $this->calculatePropertyAssets($propertyAcquisitionAmountForFFE, $propertyDepreciationDurationInMonthsForFFE, $propertyReplacementCostRateForFFE, $propertyReplacementIntervalInMonthsForFFE, $projectUnderProgressForFFE, $datesAsStringAndIndex, $datesIndexWithYearIndex, $yearIndexWithYear, $dateIndexWithDate, $propertyFFECapitalizedInterest);

    }

    public function calculatePropertyAssets(float $propertyAmount, int $propertyDepreciationDurationInMonths, float $propertyReplacementCostRate, int $propertyReplacementIntervalInMonths, array $projectUnderProgressForConstruction, array $datesAsStringAndIndex, array $datesIndexWithYearIndex, array $yearIndexWithYear, array $dateIndexWithDate, array $propertyCapitalizedInterest): array
    {
        $buildingAssets = [];
        $hospitalitySector = $this->hospitalitySector;
        $operationStartDateAsIndex = $hospitalitySector->getOperationStartDateAsIndex($datesAsStringAndIndex, $hospitalitySector->getOperationStartDateFormatted());
        $purchaseDateAsIndex = $this->getPropertyPurchaseDateAsIndex();
        $purchaseCost = $this->getPropertyPurchaseCost();
        $propertyReplacementCostRate = $propertyReplacementCostRate / 100;
        $constructionTransferredDateAndValue = $projectUnderProgressForConstruction['transferred_date_and_vales'] ?? [];
        $constructionTransferredDateAsIndex = array_key_last($constructionTransferredDateAndValue);
        $constructionTransferredValue = $constructionTransferredDateAndValue[$constructionTransferredDateAsIndex] ?? 0;
        $studyDates = $hospitalitySector->getStudyDateFormatted($datesAsStringAndIndex, $datesIndexWithYearIndex, $yearIndexWithYear, $dateIndexWithDate);
        $studyEndDateAsIndex = $hospitalitySector->getStudyEndDateAsIndex();

        $beginningBalance = 0;
        $totalMonthlyDepreciation = [];
        $accumulatedDepreciation = [];
        $replacementDates = calculateReplacementDates($studyDates, $operationStartDateAsIndex, $studyEndDateAsIndex, $propertyReplacementIntervalInMonths);
        $depreciation = [];
        $index = 0;
        $depreciationStartDateAsIndex = null;
        foreach ($studyDates as $dateAsIndex) {

            if ($purchaseDateAsIndex < $operationStartDateAsIndex && $purchaseCost > 0) {
                $depreciationStartDateAsIndex = $operationStartDateAsIndex;
            } elseif ($constructionTransferredDateAsIndex < $operationStartDateAsIndex) {
                $depreciationStartDateAsIndex = $operationStartDateAsIndex;
            } elseif ($constructionTransferredDateAsIndex >= $operationStartDateAsIndex) {
                $depreciationStartDateAsIndex = $constructionTransferredDateAsIndex + 1;
            } else {
                // $depreciationStartDateAsIndex = $dateAsIndex+1;
            }
            $depreciationEndDateAsIndex = ! is_null($depreciationStartDateAsIndex) ? $depreciationStartDateAsIndex + $propertyDepreciationDurationInMonths - 1 : null;
            $buildingAssets['beginning_balance'][$dateAsIndex] = $beginningBalance;
            $buildingAssets['additions'][$dateAsIndex] = $dateAsIndex == $purchaseDateAsIndex ? $propertyAmount : ($dateAsIndex == $constructionTransferredDateAsIndex ? $constructionTransferredValue : 0);

            $buildingAssets['additions'][$dateAsIndex] += $propertyCapitalizedInterest[$dateAsIndex] ?? 0;

            $buildingAssets['initial_total_gross'][$dateAsIndex] = $buildingAssets['additions'][$dateAsIndex] + $beginningBalance;
            $currentInitialTotalGross = $buildingAssets['initial_total_gross'][$dateAsIndex] ?? 0;
            $replacementCost[$dateAsIndex] = in_array($dateAsIndex, $replacementDates) ? $this->calculateReplacementCost($currentInitialTotalGross, $propertyReplacementCostRate) : 0;
            /**
             * ! Issue Here
             */
            if (in_array($dateAsIndex, $replacementDates) && ($purchaseDateAsIndex <= $operationStartDateAsIndex || $constructionTransferredDateAsIndex <= $operationStartDateAsIndex)) {
                $depreciationStartDateAsIndex = $dateAsIndex + 1;
                $depreciationEndDateAsIndex = ! is_null($depreciationStartDateAsIndex) ? $depreciationStartDateAsIndex + $propertyDepreciationDurationInMonths - 1 : null;
            }
            $replacementValueAtCurrentDate = $replacementCost[$dateAsIndex] ?? 0;
            $buildingAssets['replacement_cost'][$dateAsIndex] = $replacementCost[$dateAsIndex];
            $buildingAssets['final_total_gross'][$dateAsIndex] = $buildingAssets['initial_total_gross'][$dateAsIndex] + $replacementValueAtCurrentDate;
            $depreciation[$dateAsIndex] = $this->calculateMonthlyDepreciation($buildingAssets['additions'][$dateAsIndex], $replacementValueAtCurrentDate, $propertyDepreciationDurationInMonths, $depreciationStartDateAsIndex, $depreciationEndDateAsIndex, $totalMonthlyDepreciation, $accumulatedDepreciation, $studyDates);
            $accumulatedDepreciation = calculateAccumulatedDepreciation($totalMonthlyDepreciation, $studyDates, $dateAsIndex);
            $buildingAssets['total_monthly_depreciation'] = $totalMonthlyDepreciation;
            $buildingAssets['accumulated_depreciation'] = $accumulatedDepreciation;
            $currentAccumulatedDepreciation = $buildingAssets['accumulated_depreciation'][$dateAsIndex] ?? 0;

            $buildingAssets['end_balance'][$dateAsIndex] = $buildingAssets['final_total_gross'][$dateAsIndex] - $currentAccumulatedDepreciation;
            $beginningBalance = $buildingAssets['final_total_gross'][$dateAsIndex];
            $index++;
        }

        return $buildingAssets;
    }

    protected function calculateReplacementCost(float $totalGross, float $propertyReplacementCostRate)
    {
        return $totalGross * $propertyReplacementCostRate;
    }

    protected function calculateMonthlyDepreciation(float $additions, float $replacementCost, int $propertyDepreciationDurationInMonths, ?int $depreciationStartDateAsIndex, ?int $depreciationEndDateAsIndex, &$totalMonthlyDepreciation, &$accumulatedDepreciation, array $studyDates)
    {
        if (is_null($depreciationStartDateAsIndex) || is_null($depreciationEndDateAsIndex)) {
            return [];
        }
        $monthlyDepreciations = [];
        $monthlyDepreciationAtCurrentDate = ($additions + $replacementCost) / $propertyDepreciationDurationInMonths;
        $depreciationDates = generateDatesBetweenTwoIndexedDates($depreciationStartDateAsIndex, $depreciationEndDateAsIndex);

        foreach ($studyDates as $dateAsIndex) {
            $previousDateAsIndex = $dateAsIndex - 1;
            if (in_array($dateAsIndex, $depreciationDates)) {
                $monthlyDepreciations[$dateAsIndex] = $monthlyDepreciationAtCurrentDate;
                $totalMonthlyDepreciation[$dateAsIndex] = isset($totalMonthlyDepreciation[$dateAsIndex]) ? $totalMonthlyDepreciation[$dateAsIndex] + $monthlyDepreciationAtCurrentDate : $monthlyDepreciationAtCurrentDate;
                $accumulatedDepreciation[$dateAsIndex] = $previousDateAsIndex >= 0 ? ($totalMonthlyDepreciation[$dateAsIndex] + $accumulatedDepreciation[$previousDateAsIndex]) : $totalMonthlyDepreciation[$dateAsIndex];
            } else {
                $accumulatedDepreciation[$dateAsIndex] = $accumulatedDepreciation[$previousDateAsIndex] ?? 0;
            }
        }

        return $monthlyDepreciations;
    }
}
