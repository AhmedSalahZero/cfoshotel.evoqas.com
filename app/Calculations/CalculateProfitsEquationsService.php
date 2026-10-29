<?php

namespace App\Calculations;

/**
 * The profit lines of the income statement: gross profit, EBITDA, EBIT, EBT and net profit, each with its
 * percentage of revenue. Ported from the previous CFO Hotels (App\ReadyFunctions\CalculateProfitsEquationsService).
 *
 * Every method returns ['values' => [date => amount], 'percentages' => [date => percent of revenue]].
 * A month with no revenue has a percentage of 0.
 * (When nothing is passed in, the previous code stopped with an error; here the answer is simply empty.)
 */
class CalculateProfitsEquationsService
{
    protected function getDatesFromTwoArrays(array $firstArray, array $secondArray): array
    {
        return array_values(array_unique(array_merge(array_keys($firstArray), array_keys($secondArray))));
    }

    protected function getDatesFromThreeArrays(array $firstArray, array $secondArray, array $thirdArray): array
    {
        return array_values(array_unique(array_merge(array_keys($firstArray), array_keys($secondArray), array_keys($thirdArray))));
    }

    /**
     * Gross profit = revenues - cost of goods sold.
     *
     * @param  array  $revenues  [01-01-2025 => 5, 01-02-2025 => 5, ...]
     * @param  array  $cogs  [01-01-2025 => 5, 01-02-2025 => 5, ...]
     */
    public function __calculateGrossProfit(array $revenues, array $cogs): array
    {
        $grossProfit = [];
        $grossProfitPercentage = [];
        $dates = $this->getDatesFromTwoArrays($revenues, $cogs);
        foreach ($dates as $date) {
            $revenueAtDate = $revenues[$date] ?? 0;
            $cogsAtDate = $cogs[$date] ?? 0;
            $grossProfit[$date] = $revenueAtDate - $cogsAtDate;
            $grossProfitPercentage[$date] = $revenueAtDate ? $grossProfit[$date] / $revenueAtDate * 100 : 0;
        }

        return [
            'values' => $grossProfit,
            'percentages' => $grossProfitPercentage,
        ];
    }

    /**
     * EBITDA = gross profit - selling, general and administrative expenses - other deductions.
     */
    public function __calculateEBITDA(array $grossProfit, array $sga, array $otherDeductions, array $revenues): array
    {
        $ebitda = [];
        $ebitdaPercentages = [];
        $dates = $this->getDatesFromThreeArrays($grossProfit, $sga, $otherDeductions);
        foreach ($dates as $date) {
            $grossProfitAtDate = $grossProfit[$date] ?? 0;
            $sgaAtDate = $sga[$date] ?? 0;
            $otherDeductionsAtDate = $otherDeductions[$date] ?? 0;
            $revenueAtDate = $revenues[$date] ?? 0;
            $ebitda[$date] = $grossProfitAtDate - $sgaAtDate - $otherDeductionsAtDate;
            $ebitdaPercentages[$date] = $revenueAtDate ? $ebitda[$date] / $revenueAtDate * 100 : 0;
        }

        return [
            'values' => $ebitda,
            'percentages' => $ebitdaPercentages,
        ];
    }

    /**
     * EBIT = EBITDA - depreciation - incentive management fee.
     * The months are those of EBITDA and depreciation (the previous code did not look at the months of the incentive fee).
     */
    public function __calculateEBIT(array $ebitda, array $depreciation, array $incentiveManagementAmounts, array $revenues): array
    {
        $ebit = [];
        $ebitPercentages = [];
        $dates = $this->getDatesFromTwoArrays($ebitda, $depreciation);
        foreach ($dates as $date) {
            $ebitdaAtDate = $ebitda[$date] ?? 0;
            $depreciationAtDate = $depreciation[$date] ?? 0;
            $incentiveManagementAmountAtDate = $incentiveManagementAmounts[$date] ?? 0;
            $ebit[$date] = $ebitdaAtDate - $depreciationAtDate - $incentiveManagementAmountAtDate;
            $revenueAtDate = $revenues[$date] ?? 0;
            $ebitPercentages[$date] = $revenueAtDate ? $ebit[$date] / $revenueAtDate * 100 : 0;
        }

        return [
            'values' => $ebit,
            'percentages' => $ebitPercentages,
        ];
    }

    /**
     * EBT (profit before tax) = EBIT - loan interest. The months are those of EBIT.
     */
    public function __calculateEBT(array $ebit, array $loanInterest, array $revenues): array
    {
        $ebt = [];
        $ebtPercentages = [];
        $dates = array_keys($ebit);
        foreach ($dates as $date) {
            $ebitAtDate = $ebit[$date] ?? 0;
            $loanInterestAtDate = $loanInterest[$date] ?? 0;
            $ebt[$date] = $ebitAtDate - $loanInterestAtDate;
            $revenueAtDate = $revenues[$date] ?? 0;
            $ebtPercentages[$date] = $revenueAtDate ? $ebt[$date] / $revenueAtDate * 100 : 0;
        }

        return [
            'values' => $ebt,
            'percentages' => $ebtPercentages,
        ];
    }

    /**
     * Net profit = EBT - corporate taxes.
     */
    public function __calculateNetProfit(array $ebt, array $corporateTaxes, array $revenues): array
    {
        $netProfit = [];
        $netProfitPercentages = [];
        $dates = $this->getDatesFromTwoArrays($ebt, $corporateTaxes);
        foreach ($dates as $date) {
            $ebtAtDate = $ebt[$date] ?? 0;
            $corporateAtDate = $corporateTaxes[$date] ?? 0;
            $netProfit[$date] = $ebtAtDate - $corporateAtDate;
            $revenueAtDate = $revenues[$date] ?? 0;
            $netProfitPercentages[$date] = $revenueAtDate ? $netProfit[$date] / $revenueAtDate * 100 : 0;
        }

        return [
            'values' => $netProfit,
            'percentages' => $netProfitPercentages,
        ];
    }
}
