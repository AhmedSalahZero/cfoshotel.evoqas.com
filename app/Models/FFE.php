<?php

namespace App\Models;

use App\Models\Concerns\CompanyScope;
use App\Support\LegacyCarbon;
use App\Models\Concerns\AllowsLazyLoading;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FFE extends Model implements AllowsLazyLoading
{
    use CompanyScope;

    protected $table = 'ffe';

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
        return app('dateIndexWithDate')[$this->getLandPurchaseDateAsIndex()];
    }

    public function getLandPurchaseDateFormatted(): ?string
    {
        return $this->getLandPurchaseDateAsString() ? LegacyCarbon::make($this->getLandPurchaseDateAsString())->format('d-m-Y') : null;
    }

    public function getDebtFundingPercentage()
    {
        $EquityFunding = $this->getEquityFunding();

        return 1 - $EquityFunding;
    }

    public function getDebtAmount()
    {
        $totalPurchaseCost = $this->getTotalItemsCost();
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

    public function loans()
    {
        return $this->hasMany(Loan::class, 'ffe_id', 'id');
    }

    /**
     * The loan of a section. The previous application looked the loan up by "loan id = FF&E id" (a wrong key that worked because it also
     * saved the loan under that id), so the loan with the FF&E's own id comes first; any other loan of this FF&E follows, the latest first.
     */
    public function getLoanForSection(string $currentSectionName)
    {
        $loans = $this->loans->where('section_name', $currentSectionName);

        return $loans->firstWhere('id', $this->id) ?? $loans->sortByDesc('id')->first();
    }

    public function getTotalItemsCost(): float
    {

        $total = 0;
        $this->ffeItems->each(function ($ffeItem) use (&$total) {
            $total += $ffeItem->getItemCost() * (1 + ($ffeItem->getContingencyRate() / 100));
        });

        return $total;
    }

    public function getStartDateAsIndex(HospitalitySector $hospitalitySector): int
    {
        return $this->start_date ?: $hospitalitySector->getDevelopmentStartDateAsIndex();
    }

    public function getStartDateAsString(HospitalitySector $hospitalitySector)
    {
        $startDateAsIndex = $this->getStartDateAsIndex($hospitalitySector);

        return App('dateIndexWithDate')[$startDateAsIndex];
    }

    public function getStartDateFormatted(HospitalitySector $hospitalitySector)
    {
        $startDate = $this->getStartDateAsString($hospitalitySector);
        if ($startDate) {
            return LegacyCarbon::make($startDate)->format('d-m-Y');
        }

        return $hospitalitySector->getDevelopmentStartDateFormatted();
    }

    public function getDuration()
    {
        return $this->duration;
    }

    public function getEndDate()
    {
        return $this->end_date;
    }

    public function getExecutionMethod()
    {
        return $this->execution_method;
    }

    public function getDownPaymentPercentage()
    {
        return $this->down_payment ?: 0;
    }

    public function getBalanceRateOne()
    {
        return $this->balance_rate_one ?: 0;
    }

    public function getDueOne()
    {
        return $this->due_one ?: 0;
    }

    public function getBalanceRateTwo()
    {
        return $this->balance_rate_two ?: 0;
    }

    public function getDueTwo()
    {
        return $this->due_two ?: 0;
    }

    public function getEquityFunding()
    {

        return $this->ffe_equity_funding ?: 0;
    }

    public function getEquityAmount()
    {
        $EquityFunding = $this->getEquityFunding() / 100;

        return $EquityFunding * $this->getTotalItemsCost();
    }

    public function getDebtFunding()
    {
        $EquityFundingPercentage = $this->getEquityFunding();

        return 100 - $EquityFundingPercentage;
    }

    // mutation
    public function storeLoans(int $hospitalitySectorId, array $loans, int $companyId, int $ffeId)
    {
        foreach ($loans as $sectionName => $arrayOfData) {
            $loan = $this->getLoanForSection($sectionName);
            $loanType = $arrayOfData['loan_type'] ?? null;
            $data = array_merge($arrayOfData, ['company_id' => $companyId, 'section_name' => $sectionName, 'ffe_id' => $ffeId, 'fixedLoanType' => 'fixed.loan.fixed.at.end',
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
        return (array) json_decode($this->collection_policy_value ?? '');
    }

    public function getCollectionPolicyValue(): array
    {
        return [
            'due_in_days' => [$this->getDueOne(), $this->getDueTwo()],
            'rate' => [$this->getBalanceRateOne(), $this->getBalanceRateTwo()],
        ];
    }

    public function getStartDateIndex(HospitalitySector $hospitalitySector): int
    {
        return $hospitalitySector->getStartDate();

    }

    public function ffeItems(): HasMany
    {
        return $this->hasMany(FFEItem::class, 'ffe_id', 'id');
    }

    public function calculateFFEAssetsForFFE(int $transferredDateForFFEAsIndex, float $transferredAmount, array $studyDates, int $studyEndDateAsIndex): array
    {

        $assets = [];
        $totalItemsCost = $this->getTotalItemsCost();
        $this->ffeItems->each(function (FFEItem $ffeItem) use ($totalItemsCost, $transferredDateForFFEAsIndex, $transferredAmount, &$assets, $studyDates, $studyEndDateAsIndex) {
            $depreciationDurationInMonthsForFFE = $ffeItem->getDepreciationDurationInMonths();
            $ffeReplacementCostRateForFFE = $ffeItem->getReplacementCostRate();
            $ffeReplacementIntervalInMonthsForFFE = $ffeItem->getReplacementIntervalInMonths();
            $totalCost = $ffeItem->getTotalCost();
            $ffeItemTransferredAmount = $totalItemsCost ? $transferredAmount * ($totalCost / $totalItemsCost) : 0;
            $projectUnderProgressForFFE = [
                'transferred_date_and_vales' => [
                    $transferredDateForFFEAsIndex => $ffeItemTransferredAmount,
                ],
            ];
            $assets[$ffeItem->getName()] = $this->calculateFFEAssets($depreciationDurationInMonthsForFFE, $ffeReplacementCostRateForFFE, $ffeReplacementIntervalInMonthsForFFE, $projectUnderProgressForFFE, $studyDates, $studyEndDateAsIndex);
        });

        return $assets;

    }

    public function calculateFFEAssets(int $propertyDepreciationDurationInMonths, float $propertyReplacementCostRate, int $propertyReplacementIntervalInMonths, array $projectUnderProgressForConstruction, array $studyDates, int $studyEndDateAsIndex, ?HospitalitySector $hospitalitySector = null): array
    {
        $buildingAssets = [];
        $hospitalitySector = $this->hospitalitySector ?: $hospitalitySector;
        $datesAsStringAndIndex = $hospitalitySector->getDatesAsStringAndIndex();
        $operationStartDateAsIndex = $hospitalitySector->getOperationStartDateAsIndex($datesAsStringAndIndex, $hospitalitySector->getOperationStartDateFormatted());

        $propertyReplacementCostRate = $propertyReplacementCostRate / 100;
        $constructionTransferredDateAndValue = $projectUnderProgressForConstruction['transferred_date_and_vales'] ?? [];
        $constructionTransferredDateAsIndex = array_key_last($constructionTransferredDateAndValue);
        $constructionTransferredValue = $constructionTransferredDateAndValue[$constructionTransferredDateAsIndex] ?? 0;

        $beginningBalance = 0;
        $totalMonthlyDepreciation = [];
        $accumulatedDepreciation = [];
        $replacementDates = calculateReplacementDates($studyDates, $operationStartDateAsIndex, $studyEndDateAsIndex, $propertyReplacementIntervalInMonths);
        $depreciation = [];
        $index = 0;
        $depreciationStartDateAsIndex = null;
        foreach ($studyDates as $dateAsIndex) {
            if ($constructionTransferredDateAsIndex < $operationStartDateAsIndex) {
                $depreciationStartDateAsIndex = $operationStartDateAsIndex;
            } else {
                $depreciationStartDateAsIndex = $dateAsIndex + 1;
            }
            $depreciationEndDateAsIndex = $depreciationStartDateAsIndex >= 0 ? $depreciationStartDateAsIndex + $propertyDepreciationDurationInMonths - 1 : null;

            $buildingAssets['beginning_balance'][$dateAsIndex] = $beginningBalance;
            $buildingAssets['additions'][$dateAsIndex] = $dateAsIndex == $constructionTransferredDateAsIndex ? $constructionTransferredValue : 0;
            $buildingAssets['initial_total_gross'][$dateAsIndex] = $buildingAssets['additions'][$dateAsIndex] + $beginningBalance;
            $currentInitialTotalGross = $buildingAssets['initial_total_gross'][$dateAsIndex] ?? 0;
            $replacementCost[$dateAsIndex] = in_array($dateAsIndex, $replacementDates) ? $this->calculateReplacementCost($currentInitialTotalGross, $propertyReplacementCostRate) : 0;
            /**
             * ! Issue Here
             */
            if (in_array($dateAsIndex, $replacementDates) && ($constructionTransferredDateAsIndex <= $operationStartDateAsIndex)) {
                $depreciationEndDateAsIndex = $dateAsIndex + 1 + $propertyDepreciationDurationInMonths - 1;
            }
            $replacementValueAtCurrentDate = $replacementCost[$dateAsIndex] ?? 0;
            $buildingAssets['replacement_cost'][$dateAsIndex] = $replacementCost[$dateAsIndex];
            $buildingAssets['final_total_gross'][$dateAsIndex] = $buildingAssets['initial_total_gross'][$dateAsIndex] + $replacementValueAtCurrentDate;
            $depreciation[$dateAsIndex] = $this->calculateMonthlyDepreciation($buildingAssets['additions'][$dateAsIndex], $replacementValueAtCurrentDate, $propertyDepreciationDurationInMonths, $depreciationStartDateAsIndex, $depreciationEndDateAsIndex, $totalMonthlyDepreciation, $accumulatedDepreciation, $studyDates);
            $accumulatedDepreciation = calculateAccumulatedDepreciation($totalMonthlyDepreciation, $studyDates);
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
                // $monthlyDepreciations[$dateAsString] = 0;
                // $totalMonthlyDepreciation[$dateAsString]  = 0 ;
                $accumulatedDepreciation[$dateAsIndex] = $accumulatedDepreciation[$previousDateAsIndex] ?? 0;
            }
        }

        return $monthlyDepreciations;
    }
}
