<?php

namespace App\Calculations;

use App\Support\HArr;
use App\Support\HDate;
use App\Models\HospitalitySector;
use App\Models\Loan;

class CalculateFixedLoanAtEndService
{
    private HospitalitySector $hospitalitySector;

    public function __construct(HospitalitySector $hospitalitySector)
    {
        $this->hospitalitySector = $hospitalitySector;
    }

    public function __calculate(string $loanType, string $startDate, float $loanAmount, $baseRate, float $marginRate, float $tenor, string $installmentPaymentIntervalName, float $stepUpRate = 0, ?string $stepUpIntervalName = null, float $stepDownRate = 0, ?string $stepDownIntervalName = null, float $gracePeriod = 0, $currentStartDateAsIndex = 0, ?int $currentDaysCount = null, ?array $pricingPerMonths = null)
    {
        $currentStartDateAsIndex = $this->hospitalitySector->convertDateStringToDateIndex($startDate);

        // $startDate = Carbon::make($startDate)->format('Y-m-d');
        $previousResult = [];
        $indexOfLoop = -1;
        if ($loanAmount <= 0) {
            return [];
        }
        $loanFactors = [];
        $installmentFactors = [];

        $datesAsIndexString = HDate::generateDatesBetweenStartDateAndDuration($currentStartDateAsIndex, $startDate, $tenor, $installmentPaymentIntervalName);
        $installmentPaymentIntervalValue = $this->getInstallmentPaymentIntervalValue($installmentPaymentIntervalName);
        $datesIndexAndDaysCount = HDate::calculateDaysCountAtEnd($datesAsIndexString, $installmentPaymentIntervalValue, $currentDaysCount);
        $datesAsStringIndex = array_flip($datesAsIndexString);
        $currentPricing = ($baseRate + $marginRate) / 100;
        $stepRate = Loan::getStepRate($loanType, $stepUpRate, $stepDownRate);
        $stepRate = $stepRate / 100;
        $isWithCapitalization = Loan::isWithCapitalization($loanType);
        $appliedStepName = Loan::getAppliedStepIntervalName($loanType, $stepUpIntervalName, $stepDownIntervalName);
        $appliedStepValue = $this->getAppliedStepIntervalValue($appliedStepName);

        // The first installment falls one installment interval after the loan start, plus the grace period (rounded up to whole intervals).
        // The dates are keyed by month number, so the shift is counted in MONTHS. (It used to be counted in intervals, which only gave
        // an existing date for monthly loans: a quarterly or semi-annual loan stopped here with "Undefined array key".)
        $installmentStartDate = HDate::getDateAfterIndex($datesAsIndexString, $datesAsStringIndex, $startDate, HDate::installmentStartShift($gracePeriod, $installmentPaymentIntervalValue));
        $installmentStartDateAsIndex = $datesAsStringIndex[$installmentStartDate ?? $datesAsIndexString[array_key_last($datesAsIndexString)]];

        $endDateAsIndex = array_key_last($datesAsIndexString);

        $stepFactors = [];
        $currentStepFactorCounterValue = 0;
        $currentAppliedStepCounter = 0;
        $currentLoanFactor = $loanAmount;
        $currentInstallmentFactor = 0;
        $previousPricing = 0;
        foreach ($datesIndexAndDaysCount as $currentDateAsIndex => $currentDaysCount) {

            $currentPricing = is_null($pricingPerMonths) ? $currentPricing : ($pricingPerMonths[$currentDateAsIndex] ?? $previousPricing);
            $previousPricing = $currentPricing;
            /**
             * * calculate Interest Loan Factor
             */
            $interestFactors[$currentDateAsIndex] = ($currentPricing / 360) * $currentDaysCount;
            $currentInterestFactor = $interestFactors[$currentDateAsIndex];
            /**
             * * Calculate Loan Factors
             */
            if (! $isWithCapitalization && $currentDateAsIndex < $installmentStartDateAsIndex) {
                $currentLoanFactor = $loanAmount;
            } else {
                $currentLoanFactor = $currentLoanFactor + ($currentLoanFactor * $currentInterestFactor);
            }

            $loanFactors[$currentDateAsIndex] = $currentLoanFactor;

            /**
             * * Calculate Step Factor
             */
            if ($currentDateAsIndex < $installmentStartDateAsIndex) {
                $stepFactors[$currentDateAsIndex] = 0;
            } else {
                $currentAppliedStepCounter++;
                $stepFactors[$currentDateAsIndex] = $currentStepFactorCounterValue;
                if ($currentAppliedStepCounter == $appliedStepValue / $installmentPaymentIntervalValue) {
                    $currentAppliedStepCounter = 0;
                    $currentStepFactorCounterValue++;
                }
            }

            if ($currentDateAsIndex < $installmentStartDateAsIndex) {
                $currentInstallmentFactor = 0;
            } elseif ($currentDateAsIndex == $installmentStartDateAsIndex) {
                $currentInstallmentFactor = -1;
            } else {
                $v = $stepFactors[$currentDateAsIndex];
                $currentInstallmentFactor = ($currentInstallmentFactor + ($currentInstallmentFactor * $currentInterestFactor) - (1 * pow((1 + $stepRate), ($v))));
            }
            $installmentFactors[$currentDateAsIndex] = $currentInstallmentFactor;
            /**
             * * Calculate Installment Factor
             */
        }

        $installmentAmounts = $this->calculateInstallmentAmount($installmentPaymentIntervalValue, $loanFactors, $installmentFactors, $stepRate, $installmentStartDateAsIndex, $endDateAsIndex, $tenor, $installmentPaymentIntervalValue, $appliedStepValue, $pricingPerMonths);
        $loanScheduleResult = $this->calculateLoanScheduleResult($installmentPaymentIntervalValue, $datesIndexAndDaysCount, $loanType, $loanAmount, $interestFactors, $installmentAmounts, $currentStartDateAsIndex);

        return $loanScheduleResult;

    }

    public function getInstallmentPaymentIntervalValue($installmentPayment): int
    {
        switch ($installmentPayment) {
            case 'monthly':
                return 1;
            case 'quartly':
                return 3;
            case 'semi annually':
                return 6;
        }
    }

    protected function getAppliedStepIntervalValue($appliedStepIntervalName): int
    {

        switch ($appliedStepIntervalName) {
            case 'quartly':
                return 3;
            case 'semi annually':
                return 6;
            case 'annually':
                return 12;
            default:
                return 12;
        }
    }

    protected function defaultDateFormat(): string
    {
        return 'd-m-Y';
    }

    protected function calculateInstallmentAmount(int $intervalValue, array $loanFactors, array $installmentFactory, float $stepRate, int $installmentStartDateAsIndex, int $endDateAsIndex, float $tenor, int $installmentPaymentIntervalValue, int $appliedStepValue)
    {

        $installmentsAmounts = [];

        $loanFactoryAtEndDate = $loanFactors[$endDateAsIndex];

        $installmentFactorAtEndDate = $installmentFactory[$endDateAsIndex];

        $installmentAmount = $loanFactoryAtEndDate / ($installmentFactorAtEndDate * -1);

        $installmentsAmounts[$installmentStartDateAsIndex] = $installmentAmount;

        for ($i = 1; $i <= ($tenor / $installmentPaymentIntervalValue); $i++) {
            $loopDateAsIndex = $installmentStartDateAsIndex;
            $stepVal = ($appliedStepValue / $installmentPaymentIntervalValue);
            if ($i != 1 && ($i % $stepVal) == 1) {
                $installmentAmount = $installmentAmount * ((pow((1 + $stepRate), 1)));
            } else {
                $installmentAmount = $installmentAmount;
            }
            $installmentsAmounts[$loopDateAsIndex] = $installmentAmount;
            $installmentStartDateAsIndex = $loopDateAsIndex + $intervalValue;
        }

        return $installmentsAmounts;
    }

    protected function calculateLoanScheduleResult(int $intervalValue, array $datesIndexAndDaysCount, string $loanType, float $loanAmount, array $interestFactor, array $installmentAmount)
    {
        $loanScheduleResult = [];
        //	$finalLoanScheduleResult = [];
        $loanScheduleResult['totals']['totalSchedulePayment'] = 0;
        $loanScheduleResult['totals']['totalPrincipleAmount'] = 0;
        $loanScheduleResult['totals']['totalInterestAmount'] = 0;
        $isWithoutCapitalization = Loan::isWithoutCapitalization($loanType);
        $firstLoop = true;

        foreach ($datesIndexAndDaysCount as $dateAsIndex => $currentDaysCount) {
            $previousDate = $dateAsIndex - $intervalValue;
            $i = $dateAsIndex;
            $loanScheduleResult['beginning'][$i] = $firstLoop ? $loanAmount : $loanScheduleResult['endBalance'][$previousDate] ?? 0;
            $loanScheduleResult['interestAmount'][$i] = $loanScheduleResult['beginning'][$i] * $interestFactor[$i];
            $loanScheduleResult['totals']['totalInterestAmount'] += $loanScheduleResult['interestAmount'][$i];
            $installmentAmountAtIndex = $installmentAmount[$i] ?? 0;
            $loanScheduleResult['schedulePayment'][$i] = $isWithoutCapitalization && $installmentAmountAtIndex == 0 ? $loanScheduleResult['interestAmount'][$i] : $installmentAmountAtIndex;
            $loanScheduleResult['totals']['totalSchedulePayment'] = $loanScheduleResult['totals']['totalSchedulePayment'] + $loanScheduleResult['schedulePayment'][$i];
            $loanScheduleResult['principleAmount'][$i] = $loanScheduleResult['schedulePayment'][$i] - $loanScheduleResult['interestAmount'][$i];
            $loanScheduleResult['totals']['totalPrincipleAmount'] += $loanScheduleResult['principleAmount'][$i];
            $loanScheduleResult['endBalance'][$i] = $loanScheduleResult['beginning'][$i] + $loanScheduleResult['interestAmount'][$i] - $loanScheduleResult['schedulePayment'][$i];
            $loanScheduleResult['endBalance'][$i] = $loanScheduleResult['endBalance'][$i] < 1 && $loanScheduleResult['endBalance'][$i] > -1 ? 0 : $loanScheduleResult['endBalance'][$i];
            $firstLoop = false;
        }

        return $loanScheduleResult;

    }

    /**
     * The balance of a loan at a month: the end balance of its last schedule date up to that month (the schedule has dates every installment interval, and
     * the end of the study is not always one of them; a loan that is paid off by then has none left). The balances are keyed by month number.
     */
    private function balanceAtOrBefore(array $endBalance, int $month): float
    {
        $found = 0.0;
        foreach ($endBalance as $index => $balance) {
            if ($index <= $month) {
                $found = (float) $balance;
            }
        }

        return $found;
    }

    public function calculateFixedAssetsLoans(HospitalitySector $hospitalitySector, array $datesAsStringAndIndex, array $dateIndexWithDate, array $dateWithDateIndex)
    {
        $fixedLoanAtEndService = new CalculateFixedLoanAtEndService($hospitalitySector);
        $ffeExecutionAndPaymentService = new FfeExecutionAndPayment;
        $constructionExecutionAndPaymentService = new ConstructionExecutionAndPayment;
        $softConstructionExecutionAndPaymentService = new SoftConstructionExecutionAndPayment;
        $contractPaymentService = new ContractPaymentService;
        $landAcquisitionCostAndPaymentService = (new LandAcquisitionCostAndPayment);
        $loanWithdrawalService = new CalculateLoanWithdrawal;
        $operationStartDateAsIndex = $hospitalitySector->getOperationStartDateAsIndex();

        // 1- Land Equity Payment

        $landPayments = [];
        $landEquityPayment = [];
        $landLoanWithdrawal = [];
        $contractPayments = [];
        $landLoanInstallment = [];
        $landLoanStartDate = null;

        $landLoanAmount = 0;
        $landLoanEndBalanceAtStudyEndDate = 0;
        $landLoanEndBalance = [];
        $landLoanPricing = 0;

        $hardConstructionEquityPayment = [];
        $hardConstructionLoanInstallment = [];
        $hardConstructionLoanWithdrawal = [];
        $hardLoanStartDate = null;
        $hardLoanAmount = 0;
        $hardLoanPricing = 0;
        $hardConstructionLoanEndBalanceAtStudyEndDate = 0;
        $hardConstructionLoanEndBalance = [];
        $ffeEquityPayment = [];
        $ffeLoanInstallment = [];
        $ffeLoanWithdrawal = [];
        $ffeLoanStartDate = null;
        $ffeLoanAmount = 0;
        $ffeLoanPricing = 0;
        $ffeLoanEndBalanceAtStudyEndDate = 0;

        $landLoanInterestAmounts = [];
        $hardConstructionLoanInterestAmounts = [];
        $ffeLoanInterestAmounts = [];
        $ffeLoanEndBalance = [];
        $propertyLoanStartDate = null;
        $propertyLoanAmount = 0;
        $propertyLoanEndBalanceAtStudyEndBalance = 0;
        $propertyLoanPricing = 0;
        $propertyEquityPayment = [];
        $propertyLoanWithdrawal = [];
        $propertyPayments = [];
        $propertyLoanInstallment = [];
        $propertyLoanInterestAmounts = [];
        $propertyLoanEndBalance = [];

        $hardLoanWithdrawalEndBalance = [];
        $ffeLoanWithdrawalEndBalance = [];
        $propertyLoanWithdrawalEndBalance = [];
        $landLoanWithdrawalEndBalance = [];
        $hardLoanWithdrawalAmounts = [];
        $ffeLoanWithdrawalAmounts = [];
        $propertyLoanWithdrawalAmounts = [];
        $landLoanWithdrawalAmounts = [];

        $propertyLandCapitalizedInterest = [];
        $propertyBuildingCapitalizedInterest = [];
        $propertyFFECapitalizedInterest = [];

        $landLoanCapitalizedInterest = [];

        $acquisition = $hospitalitySector->getAcquisition();
        $propertyAcquisition = $hospitalitySector->getPropertyAcquisition();

        if ($propertyAcquisition) {
            // property Acquisition Equity Payment
            $purchaseDate = $propertyAcquisition->getPropertyPurchaseDateFormatted();
            $purchaseDateAsIndex = $dateWithDateIndex[$purchaseDate];
            $totalPropertyPurchaseCost = $propertyAcquisition->getTotalPurchaseCost();
            $paymentMethodType = $propertyAcquisition->getPropertyPaymentMethod();
            $downPaymentOneRate = $propertyAcquisition->getFirstPropertyDownPaymentPercentage();
            $balanceRate = $propertyAcquisition->getPropertyBalanceRate();
            $installmentCount = $propertyAcquisition->getPropertyInstallmentCount();
            $installmentInterval = $propertyAcquisition->getPropertyInstallmentInterval();
            $downPaymentTwoRate = $propertyAcquisition->getSecondPropertyDownPaymentPercentage();
            $propertyAfterMonths = $propertyAcquisition->getPropertyAfterMonthDays();
            $equityFundingRate = $propertyAcquisition->getPropertyEquityFundingRate();
            $customCollectionPolicyValue = $propertyAcquisition->getPropertyCustomCollectionPolicyValue();

            $propertyBuildingCostPercentage = $propertyAcquisition->getBuildingPropertyPercentage();
            $propertyLandCostPercentage = $propertyAcquisition->getLandPropertyAmountPercentage();
            $propertyFfeCostPercentage = $propertyAcquisition->getFFEPropertyAmountPercentage();

            $propertyPayments['Property Payments'] = $landAcquisitionCostAndPaymentService->calculateLandPayments($purchaseDateAsIndex, $totalPropertyPurchaseCost, $paymentMethodType, $downPaymentOneRate, $balanceRate, $installmentCount, $installmentInterval, $downPaymentTwoRate, $propertyAfterMonths, $customCollectionPolicyValue, $datesAsStringAndIndex, $dateIndexWithDate, $dateWithDateIndex, $hospitalitySector);
            $propertyEquityPayment['Property Equity Injection'] = $landAcquisitionCostAndPaymentService->calculateLandEquityPayment($propertyPayments['Property Payments'], $totalPropertyPurchaseCost, $equityFundingRate);
            $propertyLoanWithdrawal['Property Loan Withdrawal'] = $landAcquisitionCostAndPaymentService->calculateLandLoanWithdrawal($propertyPayments['Property Payments'], $totalPropertyPurchaseCost, $equityFundingRate);

            $loanForProperty = $propertyAcquisition->getLoanForSection(PROPERTY_LOAN);
            if ($loanForProperty) {
                $propertyLoanType = $loanForProperty->getLoanType();
                $propertyBaseRate = $loanForProperty->getBaseRate();
                $propertyMarginRate = $loanForProperty->getMarginRate();
                $propertyTenor = $loanForProperty->getTenor();
                $propertyInstallmentIntervalName = $loanForProperty->getInstallmentInterval();
                $propertyStepUpRate = $loanForProperty->getStepUpRate();
                $propertyStepUpIntervalName = $loanForProperty->getStepUpIntervalName();
                $propertyStepDownRate = $loanForProperty->getStepDownRate();
                $propertyStepDownIntervalName = $loanForProperty->getStepDownIntervalName();
                $propertyGracePeriod = $loanForProperty->getGracePeriod();
                $propertyLoanPricing = $loanForProperty->getPricing();
                $propertyWithdrawal = $loanWithdrawalService->__calculate($hospitalitySector->replaceIndexWithItsStringDate($propertyLoanWithdrawal['Property Loan Withdrawal'], $dateIndexWithDate), $propertyBaseRate, $propertyMarginRate, $dateWithDateIndex);

                $propertyLoanStartDate = array_key_last($propertyWithdrawal);
                $propertyLoanWithdrawalInterestAmounts = $propertyWithdrawal['withdrawal_interest_amounts'] ?? [];
                $propertyLoanWithdrawalEndBalance = $propertyWithdrawal['withdrawalEndBalance'] ?? [];
                $propertyLoanWithdrawalAmounts = $propertyWithdrawal['loanWithdrawal'] ?? [];

                $propertyLoanAmount = $propertyWithdrawal[$propertyLoanStartDate];

                if ($propertyLoanStartDate) {
                    $propertyLoanCalculations = $fixedLoanAtEndService->__calculate($propertyLoanType, $propertyLoanStartDate, $propertyLoanAmount, $propertyBaseRate, $propertyMarginRate, $propertyTenor, $propertyInstallmentIntervalName, $propertyStepUpRate, $propertyStepUpIntervalName, $propertyStepDownRate, $propertyStepDownIntervalName, $propertyGracePeriod);

                    $propertyLoanInterestAmounts = $propertyLoanCalculations['interestAmount'] ?? [];

                    $propertyLoanCapitalizedInterest = HArr::getIndexesBeforeDateOrNumericIndex($propertyLoanInterestAmounts, $operationStartDateAsIndex);
                    // the interest that built up while the loan was being drawn (several withdrawals, months apart) is part of the cost of the property too
                    foreach (HArr::getIndexesBeforeDateOrNumericIndex($propertyLoanWithdrawalInterestAmounts, $operationStartDateAsIndex) as $index => $interest) {
                        if ($interest) {
                            $propertyLoanCapitalizedInterest[$index] = ($propertyLoanCapitalizedInterest[$index] ?? 0) + $interest;
                        }
                    }
                    $propertyLandCapitalizedInterest = HArr::MultiplyWithNumber($propertyLoanCapitalizedInterest, $propertyLandCostPercentage / 100);
                    $propertyBuildingCapitalizedInterest = HArr::MultiplyWithNumber($propertyLoanCapitalizedInterest, $propertyBuildingCostPercentage / 100);

                    $propertyFFECapitalizedInterest = HArr::MultiplyWithNumber($propertyLoanCapitalizedInterest, $propertyFfeCostPercentage / 100);

                    $propertyLoanEndBalanceAtStudyEndBalance = $this->balanceAtOrBefore($propertyLoanCalculations['endBalance'] ?? [], (int) $hospitalitySector->getStudyEndDateAsIndex());
                    $propertyLoanEndBalance = $propertyLoanCalculations['endBalance'];

                    $propertyLoanInstallment['Property Loan Installment'] = $propertyLoanCalculations['schedulePayment'] ?? [];
                }
            }
        }

        if ($acquisition) {
            // land loan Equity Payment
            $purchaseDate = $acquisition->getLandPurchaseDateFormatted();
            $purchaseDateAsIndex = $dateWithDateIndex[$purchaseDate];
            $totalLandPurchaseCost = $acquisition->getTotalPurchaseCost();
            $paymentMethodType = $acquisition->getLandPaymentMethod();
            $downPaymentOneRate = $acquisition->getFirstLandDownPaymentPercentage();
            $balanceRate = $acquisition->getLandBalanceRate();
            $installmentCount = $acquisition->getLandInstallmentCount();
            $installmentInterval = $acquisition->getLandInstallmentInterval();
            $downPaymentTwoRate = $acquisition->getSecondLandDownPaymentPercentage();
            $landAfterMonths = $acquisition->getLandAfterMonthDays();
            $equityFundingRate = $acquisition->getLandEquityFundingRate();
            $hardEquityFundingRate = $acquisition->getHardEquityFunding();
            $customCollectionPolicyValue = $acquisition->getLandCustomCollectionPolicyValue();
            $landPayments['Land Payments'] = $landAcquisitionCostAndPaymentService->calculateLandPayments($purchaseDateAsIndex, $totalLandPurchaseCost, $paymentMethodType, $downPaymentOneRate, $balanceRate, $installmentCount, $installmentInterval, $downPaymentTwoRate, $landAfterMonths, $customCollectionPolicyValue, $datesAsStringAndIndex, $dateIndexWithDate, $dateWithDateIndex, $hospitalitySector);
            $landEquityPayment['Land Equity Injection'] = $landAcquisitionCostAndPaymentService->calculateLandEquityPayment($landPayments['Land Payments'], $totalLandPurchaseCost, $equityFundingRate);
            $landLoanWithdrawal['Land Loan Withdrawal'] = $landAcquisitionCostAndPaymentService->calculateLandLoanWithdrawal($landPayments['Land Payments'], $totalLandPurchaseCost, $equityFundingRate);

            $loanForLand = $acquisition->getLoanForSection(LAND_LOAN);
            if ($loanForLand) {
                $landLoanType = $loanForLand->getLoanType();
                $landBaseRate = $loanForLand->getBaseRate();
                $landMarginRate = $loanForLand->getMarginRate();
                $landTenor = $loanForLand->getTenor();
                $landInstallmentIntervalName = $loanForLand->getInstallmentInterval();
                $landStepUpRate = $loanForLand->getStepUpRate();
                $landStepUpIntervalName = $loanForLand->getStepUpIntervalName();
                $landStepDownRate = $loanForLand->getStepDownRate();
                $landStepDownIntervalName = $loanForLand->getStepDownIntervalName();
                $landGracePeriod = $loanForLand->getGracePeriod();

                $landLoanPricing = $loanForLand->getPricing();

                $landWithdrawal = $loanWithdrawalService->__calculate($hospitalitySector->replaceIndexWithItsStringDate($landLoanWithdrawal['Land Loan Withdrawal'], $dateIndexWithDate), $landBaseRate, $landMarginRate, $dateWithDateIndex);
                $landLoanStartDate = array_key_last($landWithdrawal);
                $landLoanWithdrawalEndBalance = $landWithdrawal['withdrawalEndBalance'] ?? [];
                $landLoanWithdrawalAmounts = $landWithdrawal['loanWithdrawal'] ?? [];

                $landLoanAmount = $landWithdrawal[$landLoanStartDate];
                if ($landLoanStartDate) {
                    $landLoanCalculations = $fixedLoanAtEndService->__calculate($landLoanType, $landLoanStartDate, $landLoanAmount, $landBaseRate, $landMarginRate, $landTenor, $landInstallmentIntervalName, $landStepUpRate, $landStepUpIntervalName, $landStepDownRate, $landStepDownIntervalName, $landGracePeriod);
                    $landLoanInterestAmounts = $landLoanCalculations['interestAmount'] ?? [];
                    $landLoanCapitalizedInterest = HArr::getIndexesBeforeDateOrNumericIndex($landLoanInterestAmounts, $operationStartDateAsIndex);
                    // the interest that built up while the loan was being drawn (several withdrawals, months apart) is part of the cost of the land too
                    foreach (HArr::getIndexesBeforeDateOrNumericIndex($landWithdrawal['withdrawal_interest_amounts'] ?? [], $operationStartDateAsIndex) as $index => $interest) {
                        if ($interest) {
                            $landLoanCapitalizedInterest[$index] = ($landLoanCapitalizedInterest[$index] ?? 0) + $interest;
                        }
                    }
                    $landLoanEndBalanceAtStudyEndDate = $this->balanceAtOrBefore($landLoanCalculations['endBalance'] ?? [], (int) $hospitalitySector->getStudyEndDateAsIndex());
                    $landLoanEndBalance = $landLoanCalculations['endBalance'] ?? [];
                    $landLoanInstallment['Land Loan Installment'] = $landLoanCalculations['schedulePayment'] ?? [];
                }
            }

            // hard Construction Equity Payment

            $hardConstructionCost = $acquisition->getHardConstructionCost();
            $hardContingencyRate = $acquisition->getHardConstructionContingencyRate();
            $hardConstructionStartDateAsString = $acquisition->getHardConstructionStartDateFormatted($hospitalitySector);
            $hardConstructionStartDateAsIndex = $dateWithDateIndex[$hardConstructionStartDateAsString];
            $duration = $acquisition->getHardConstructionDuration();
            $hardExecutionMethod = $acquisition->getHardExecutionMethod();
            $downPaymentRateOne = $acquisition->getHardDownPaymentPercentage();
            $hardConstructionCollectionPolicyValue = $acquisition->getHardCollectionPolicyValue();
            $downPaymentRateOne = $acquisition->getHardDownPaymentPercentage();
            $constructionExecutionAndPayment = $constructionExecutionAndPaymentService->__calculate($hardConstructionCost, $hardContingencyRate, $hardConstructionStartDateAsIndex, $duration, $hardExecutionMethod, $dateWithDateIndex, $hospitalitySector);

            $contractPayments['Hard Construction Payment'] = $contractPaymentService->__calculate($hardConstructionCost, $hardContingencyRate, $constructionExecutionAndPayment, $hardConstructionStartDateAsIndex, $downPaymentRateOne, $hardConstructionCollectionPolicyValue, $dateIndexWithDate, $dateWithDateIndex);
            $hardConstructionEquityPayment['Hard Construction Equity Injection'] = $constructionExecutionAndPaymentService->calculateHardConstructionEquityPayment($contractPayments['Hard Construction Payment'], $hardConstructionCost, $hardContingencyRate, $hardEquityFundingRate);
            $hardConstructionLoanWithdrawal['Hard Construction Loan Withdrawal'] = $constructionExecutionAndPaymentService->calculateHardConstructionLoanWithdrawal($contractPayments['Hard Construction Payment'], $hardConstructionCost, $hardContingencyRate, $hardEquityFundingRate);

            $loanForHardConstructionCost = $acquisition->getLoanForSection(HARD_COST_CONSTRUCTION);
            if ($loanForHardConstructionCost) {
                $hardLoanType = $loanForHardConstructionCost->getLoanType();
                $hardBaseRate = $loanForHardConstructionCost->getBaseRate();
                $hardMarginRate = $loanForHardConstructionCost->getMarginRate();
                $hardTenor = $loanForHardConstructionCost->getTenor();
                $hardInstallmentIntervalName = $loanForHardConstructionCost->getInstallmentInterval();
                $hardStepUpRate = $loanForHardConstructionCost->getStepUpRate();
                $hardStepUpIntervalName = $loanForHardConstructionCost->getStepUpIntervalName();
                $hardStepDownRate = $loanForHardConstructionCost->getStepDownRate();
                $hardStepDownIntervalName = $loanForHardConstructionCost->getStepDownIntervalName();
                $hardGracePeriod = $loanForHardConstructionCost->getGracePeriod();
                $hardLoanPricing = $loanForHardConstructionCost->getPricing();
                $loanWithdrawalService = new CalculateLoanWithdrawal;
                $hardLoanWithdrawal = $loanWithdrawalService->__calculate($hospitalitySector->replaceIndexWithItsStringDate($hardConstructionLoanWithdrawal['Hard Construction Loan Withdrawal'], $dateIndexWithDate), $hardBaseRate, $hardMarginRate, $dateWithDateIndex);
                $hardLoanWithdrawalEndBalance = $hardLoanWithdrawal['withdrawalEndBalance'];
                $hardLoanWithdrawalAmounts = $hardLoanWithdrawal['loanWithdrawal'];
                $hardWithdrawalInterestAmounts = $hardLoanWithdrawal['withdrawal_interest_amounts'];
                $hardLoanStartDate = array_key_last($hardLoanWithdrawal);
                $hardLoanAmount = $hardLoanWithdrawal[$hardLoanStartDate];
                if ($hardLoanStartDate) {
                    $hardConstructionLoanCalculations = $fixedLoanAtEndService->__calculate($hardLoanType, $hardLoanStartDate, $hardLoanAmount, $hardBaseRate, $hardMarginRate, $hardTenor, $hardInstallmentIntervalName, $hardStepUpRate, $hardStepUpIntervalName, $hardStepDownRate, $hardStepDownIntervalName, $hardGracePeriod);
                    // onlyMonthlyDashboardItems
                    $hardConstructionLoanInterestAmounts = $hardConstructionLoanCalculations['interestAmount'] ?? [];

                    // $operationDurationPerYear = $hospitalitySector->getOperationDurationPerYear($datesAsStringAndIndex,$datesIndexWithYearIndex,$yearIndexWithYear,$dateIndexWithDate,$dateWithMonthNumber);

                    // $dates = $hospitalitySector->getOnlyDatesOfActiveOperation($operationDurationPerYear,$dateIndexWithDate);
                    $additionalHardWithdrawalInterest = getDiffKeysFrom($hardLoanWithdrawal['withdrawal_interest_amounts'] ?? [], $hardConstructionLoanInterestAmounts);
                    foreach ($additionalHardWithdrawalInterest as $index => $value) {
                        $hardConstructionLoanInterestAmounts[$index] = $value;
                    }
                    /**
                     * ! loooll
                     */
                    $hardConstructionLoanEndBalance = $hardConstructionLoanCalculations['endBalance'];
                    $hardConstructionLoanEndBalanceAtStudyEndDate = $this->balanceAtOrBefore($hardConstructionLoanCalculations['endBalance'] ?? [], (int) $hospitalitySector->getStudyEndDateAsIndex());
                    $hardConstructionLoanInstallment['Hard Construction Loan Installment'] = $hardConstructionLoanCalculations['schedulePayment'] ?? [];
                }
            }

            // soft construction equity injection

            $softConstructionCost = $acquisition->getSoftConstructionCost();
            $softContingencyRate = $acquisition->getSoftConstructionContingencyRate();
            $softConstructionStartDateAsString = $acquisition->getSoftConstructionStartDateFormatted($hospitalitySector);
            $softConstructionStartDateAsIndex = $dateWithDateIndex[$softConstructionStartDateAsString];
            $duration = $acquisition->getSoftConstructionDuration();
            $softExecutionMethod = $acquisition->getSoftExecutionMethod();
            $downPaymentRateOne = $acquisition->getSoftDownPaymentPercentage();
            $softConstructionCollectionPolicyValue = $acquisition->getSoftCollectionPolicyValue();
            $downPaymentRateOne = $acquisition->getSoftDownPaymentPercentage();
            $softEquityFundingRate = $acquisition->getSoftEquityFundingRate();
            $softConstructionExecutionAndPayment = $softConstructionExecutionAndPaymentService->__calculate($softConstructionCost, $softContingencyRate, $softConstructionStartDateAsIndex, $duration, $softExecutionMethod, $dateWithDateIndex, $hospitalitySector);
            $contractPayments['Soft Construction Payment'] = $contractPaymentService->__calculate($softConstructionCost, $softContingencyRate, $softConstructionExecutionAndPayment, $softConstructionStartDateAsIndex, $downPaymentRateOne, $softConstructionCollectionPolicyValue, $dateIndexWithDate, $dateWithDateIndex);
            $softConstructionEquityPayment['Soft Construction Equity Injection'] = $softConstructionExecutionAndPaymentService->calculateSoftConstructionEquityPayment($contractPayments['Soft Construction Payment'], $softConstructionCost, $softContingencyRate, $softEquityFundingRate);
        }
        $ffe = $hospitalitySector->ffe;

        if ($ffe) {
            $totalFFECost = $ffe->getTotalItemsCost();
            $ffeStartDateAsString = $ffe->getStartDateFormatted($hospitalitySector);
            $ffeStartDateAsIndex = $dateWithDateIndex[$ffeStartDateAsString];
            $duration = $ffe->getDuration();
            $ffeExecutionMethod = $ffe->getExecutionMethod();
            $downPaymentRateOne = $ffe->getDownPaymentPercentage();
            $ffeCollectionPolicyValue = $ffe->getCollectionPolicyValue();
            $downPaymentRateOne = $ffe->getDownPaymentPercentage();
            $ffeEquityFundingRate = $ffe->getEquityFunding();

            $executionAndPayment = $ffeExecutionAndPaymentService->__calculate($totalFFECost, $ffeStartDateAsIndex, $duration, $ffeExecutionMethod, $dateWithDateIndex, $hospitalitySector);
            $contractPayments['FFE Payment'] = $contractPaymentService->__calculate($totalFFECost, 0, $executionAndPayment, $ffeStartDateAsIndex, $downPaymentRateOne, $ffeCollectionPolicyValue, $dateIndexWithDate, $dateWithDateIndex);
            $ffeEquityPayment['FFE Equity Injection'] = $ffeExecutionAndPaymentService->calculateFFEEquityPayment($contractPayments['FFE Payment'], $totalFFECost, 0, $ffeEquityFundingRate);
            $ffeLoanWithdrawal['FFE Loan Withdrawal'] = $ffeExecutionAndPaymentService->calculateFFELoanWithdrawal($contractPayments['FFE Payment'], $totalFFECost, 0, $ffeEquityFundingRate);

            $loanForFFECost = $ffe->getLoanForSection(FFE_COST);

            if ($loanForFFECost) {
                $ffeLoanType = $loanForFFECost->getLoanType();
                $ffeBaseRate = $loanForFFECost->getBaseRate();
                $ffeMarginRate = $loanForFFECost->getMarginRate();
                $ffeTenor = $loanForFFECost->getTenor();
                $ffeInstallmentIntervalName = $loanForFFECost->getInstallmentInterval();
                $ffeStepUpRate = $loanForFFECost->getStepUpRate();
                $ffeStepUpIntervalName = $loanForFFECost->getStepUpIntervalName();
                $ffeStepDownRate = $loanForFFECost->getStepDownRate();
                $ffeStepDownIntervalName = $loanForFFECost->getStepDownIntervalName();
                $ffeGracePeriod = $loanForFFECost->getGracePeriod();
                $ffeLoanPricing = $loanForFFECost->getPricing();
                $loanWithdrawalService = new CalculateLoanWithdrawal;
                $ffeLoanWithdrawalInterest = $loanWithdrawalService->__calculate($hospitalitySector->replaceIndexWithItsStringDate($ffeLoanWithdrawal['FFE Loan Withdrawal'], $dateIndexWithDate), $ffeBaseRate, $ffeMarginRate, $dateWithDateIndex);
                $ffeLoanStartDate = array_key_last($ffeLoanWithdrawalInterest);
                $ffeLoanAmount = $ffeLoanWithdrawalInterest[$ffeLoanStartDate];
                $ffeLoanWithdrawalInterestAmounts = $ffeLoanWithdrawalInterest['withdrawal_interest_amounts'] ?? [];
                $ffeLoanWithdrawalEndBalance = $ffeLoanWithdrawalInterest['withdrawalEndBalance'] ?? [];
                $ffeLoanWithdrawalAmounts = $ffeLoanWithdrawalInterest['loanWithdrawal'] ?? [];

                if ($ffeLoanStartDate) {
                    $ffeLoanStartDateAsIndex = $hospitalitySector->convertDateStringToDateIndex($ffeLoanStartDate);

                    $ffeLoanCalculations = $fixedLoanAtEndService->__calculate($ffeLoanType, $ffeLoanStartDate, $ffeLoanAmount, $ffeBaseRate, $ffeMarginRate, $ffeTenor, $ffeInstallmentIntervalName, $ffeStepUpRate, $ffeStepUpIntervalName, $ffeStepDownRate, $ffeStepDownIntervalName, $ffeGracePeriod, $ffeLoanStartDateAsIndex);

                    $ffeLoanInterestAmounts = $ffeLoanCalculations['interestAmount'] ?? [];
                    $ffeLoanEndBalanceAtStudyEndDate = $this->balanceAtOrBefore($ffeLoanCalculations['endBalance'] ?? [], (int) $hospitalitySector->getStudyEndDateAsIndex());
                    $ffeLoanEndBalance = $ffeLoanCalculations['endBalance'];
                    $ffeLoanInstallment['FFE Loan Installment'] = $ffeLoanCalculations['schedulePayment'] ?? [];
                }
            }
        }

        return [
            'totalPropertyPurchaseCost' => $totalPropertyPurchaseCost ?? 0,
            'propertyEquityPayment' => $propertyEquityPayment,
            'propertyLoanWithdrawal' => $propertyLoanWithdrawal,
            'propertyPayments' => $propertyPayments,
            'propertyLoanInstallment' => $propertyLoanInstallment,
            'propertyLoanInterestAmounts' => $propertyLoanInterestAmounts,
            'propertyLoanWithdrawalInterest' => $propertyLoanWithdrawalInterestAmounts ?? [],

            'totalLandPurchaseCost' => $totalLandPurchaseCost ?? 0,
            'landEquityPayment' => $landEquityPayment,
            'landLoanWithdrawal' => $landLoanWithdrawal,
            'landPayments' => $landPayments,
            'landLoanInstallment' => $landLoanInstallment,
            'landLoanInterestAmounts' => $landLoanInterestAmounts ?? [],
            'landLoanEndBalanceAtStudyEndDate' => $landLoanEndBalanceAtStudyEndDate,

            'contractPayments' => $contractPayments,

            'hardConstructionEquityPayment' => $hardConstructionEquityPayment,
            'hardConstructionLoanWithdrawal' => $hardConstructionLoanWithdrawal,
            'hardConstructionLoanInstallment' => $hardConstructionLoanInstallment,
            'hardConstructionLoanInterestAmounts' => $hardConstructionLoanInterestAmounts,
            'hardConstructionExecutionAndPayment' => $constructionExecutionAndPayment ?? [],
            'hardWithdrawalInterestAmounts' => $hardWithdrawalInterestAmounts ?? [],

            'softConstructionEquityPayment' => $softConstructionEquityPayment ?? [],
            'softConstructionExecutionAndPayment' => $softConstructionExecutionAndPayment ?? [],

            'ffeEquityPayment' => $ffeEquityPayment,
            'ffeLoanWithdrawal' => $ffeLoanWithdrawal,
            'ffeLoanInstallment' => $ffeLoanInstallment,
            'ffeLoanInterestAmounts' => $ffeLoanInterestAmounts,
            'ffeExecutionAndPayment' => $executionAndPayment ?? [],
            'ffeLoanWithdrawalInterest' => $ffeLoanWithdrawalInterestAmounts ?? [],
            'propertyLoanStartDate' => $propertyLoanStartDate,
            'propertyLoanAmount' => $propertyLoanAmount,
            'propertyLoanEndBalanceAtStudyEndBalance' => $propertyLoanEndBalanceAtStudyEndBalance,
            'landLoanStartDate' => $landLoanStartDate,
            'landLoanAmount' => $landLoanAmount,
            'hardLoanStartDate' => $hardLoanStartDate,
            'hardLoanAmount' => $hardLoanAmount,
            'hardConstructionLoanEndBalanceAtStudyEndDate' => $hardConstructionLoanEndBalanceAtStudyEndDate,
            'ffeLoanStartDate' => $ffeLoanStartDate,
            'ffeLoanAmount' => $ffeLoanAmount,
            'ffeLoanEndBalanceAtStudyEndDate' => $ffeLoanEndBalanceAtStudyEndDate,
            'propertyLoanPricing' => $propertyLoanPricing,
            'landLoanPricing' => $landLoanPricing,
            'hardLoanPricing' => $hardLoanPricing,
            'ffeLoanPricing' => $ffeLoanPricing,

            'propertyLoanEndBalance' => $propertyLoanEndBalance,
            'hardConstructionLoanEndBalance' => $hardConstructionLoanEndBalance,
            'landLoanEndBalance' => $landLoanEndBalance,
            'ffeLoanEndBalance' => $ffeLoanEndBalance,

            'hardLoanWithdrawalEndBalance' => $hardLoanWithdrawalEndBalance,
            // 'hardLoanWithdrawalCapitalizedInterest'=>$hardLoanWithdrawalCapitalizedInterest,
            'ffeLoanWithdrawalEndBalance' => $ffeLoanWithdrawalEndBalance,
            'propertyLoanWithdrawalEndBalance' => $propertyLoanWithdrawalEndBalance,
            'landLoanWithdrawalEndBalance' => $landLoanWithdrawalEndBalance,
            'hardLoanWithdrawalAmounts' => $hardLoanWithdrawalAmounts,
            'ffeLoanWithdrawalAmounts' => $ffeLoanWithdrawalAmounts,
            'propertyLoanWithdrawalAmounts' => $propertyLoanWithdrawalAmounts,
            'landLoanWithdrawalAmounts' => $landLoanWithdrawalAmounts,

            'propertyLandCapitalizedInterest' => $propertyLandCapitalizedInterest,
            'propertyBuildingCapitalizedInterest' => $propertyBuildingCapitalizedInterest,
            'propertyFFECapitalizedInterest' => $propertyFFECapitalizedInterest,

            'landLoanCapitalizedInterest' => $landLoanCapitalizedInterest,

            'propertyLoanCalculations' => $propertyLoanCalculations ?? [],
            'landLoanCalculations' => $landLoanCalculations ?? [],
            'hardConstructionLoanCalculations' => $hardConstructionLoanCalculations ?? [],
            'ffeLoanCalculations' => $ffeLoanCalculations ?? [],

        ];

        // period == tenor
        // duration == interval == $appliedStepValue
    }
}
