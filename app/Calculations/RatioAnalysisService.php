<?php

namespace App\Calculations;

use App\Support\HArr;

/**
 * The ratio analysis report: profitability, liquidity, efficiency and leverage ratios, month by month.
 * Ported from the previous CFO Hotels (App\ReadyFunctions\RatioAnalysisService), with ONE deliberate correction:
 * the previous application showed the EBITDA margin on the "Gross Profit Margin" line (a variable was overwritten),
 * so the real gross profit margin was never shown. Here "Gross Profit Margin" = Gross Profit / Net Sales and
 * "EBITDA Margin" = EBITDA / Net Sales. Every other ratio is the same as before. (Decision of the owner, Step 06.)
 */
class RatioAnalysisService
{
    public function __calculate(
        array $dates,
        array $salesRevenues,
        array $EBIT,
        array $EBITDA,
        array $grossProfit,
        array $totalInvestment,
        array $totalLiabilities,
        array $totalAssets,
        array $shareholdersEquity,
        array $cashAndBanks,
        array $customersReceivablesAndChecks,
        array $totalCurrentAssets,
        array $totalCurrentLiabilities,
        array $netProfit,
        array $working_capital,
        array $supplierPayablesAndChecks,
        array $inventory,
        array $disposableCost
    ): array {
        $ratio_analysis = [
            'Profitability Ratios' => [
                'Return on Sales "ROS"' => [],
                'Return on Assets "ROA"' => [],
                'Return On Capital Employed "ROCE"' => [],
                'Return on Equity "ROE"' => [],
                'Gross Profit Margin' => [],
                'EBITDA Margin' => [],
                'Net Profit Margin' => [],

            ],
            'Liquidity Ratios' => [
                'Current Ratio' => [],
                'Quick Ratio' => [],
                'Cash Ratio' => [],
                'Working Capital' => [],

            ],
            'Efficiency Ratios' => [
                'Days Sales Outstanding "DSO"' => [],
                'Days Inventory Outstanding "DIO"' => [],
                'Days Payables Outstanding "DPO"' => [],
                'Cash Conversion Cycle "CCC"' => [],

            ],
            'Leverage Ratios' => [
                'Debt to Asset Ratio' => [],
                'Debt to Equity Ratio' => [],
            ],

        ];

        // Return on Sales "ROS"
        $ros = $this->operationAmongTwoArrays($EBIT, $salesRevenues);
        // Return on Assets "ROA"

        $roa = $this->operationAmongTwoArrays($netProfit, $totalAssets);
        // Return On Capital Employed "ROCE"
        $roce = $this->operationAmongTwoArrays($EBIT, $totalInvestment);
        // Return on Equity "ROE"
        $roe = $this->operationAmongTwoArrays($netProfit, $shareholdersEquity);
        // Gross Profit Margin
        $gross_profit_margin = $this->operationAmongTwoArrays($grossProfit, $salesRevenues);
        // EBITDA Margin
        $ebitda_margin = $this->operationAmongTwoArrays($EBITDA, $salesRevenues);
        // Net Profit Margin
        $net_profit_margin = $this->operationAmongTwoArrays(($netProfit), ($salesRevenues));

        // Current Ratio
        $current_ratio = $this->operationAmongTwoArrays(($totalCurrentAssets), ($totalCurrentLiabilities));
        // Quick Ratio
        $cash_and_ar = HArr::sumAtDates([$cashAndBanks, $customersReceivablesAndChecks], getDateFromTwoArrays($cashAndBanks, $customersReceivablesAndChecks));
        $quick_ratio = $this->operationAmongTwoArrays($cash_and_ar, $totalCurrentLiabilities);
        // Cash Ratio
        $cash_ratio = $this->operationAmongTwoArrays($cashAndBanks, $totalCurrentLiabilities);
        // Working Capital

        $intervals_number_of_days = [];
        foreach ($dates as $dateAsString => $dateAsIndex) {
            $month = date('m', strtotime($dateAsString));
            $intervals_number_of_days[$dateAsIndex] = $month * 30;
        }

        // Days Sales Outstanding "DSO"
        $checks_sales = $this->operationAmongTwoArrays($customersReceivablesAndChecks, $salesRevenues);

        $dso = $this->operationAmongTwoArrays($checks_sales, $intervals_number_of_days, 'multiply');

        // Days Inventory Outstanding "DIO"
        $inventory_trading = $this->operationAmongTwoArrays($inventory, $disposableCost);
        $dio = $this->operationAmongTwoArrays($inventory_trading, $intervals_number_of_days, 'multiply');
        // Days Payables Outstanding "DPO"
        // $supplierPayablesAndChecks = $balance_sheet['Suppliers Payables And Checks']??[]
        $suppliers_trading = $this->operationAmongTwoArrays($supplierPayablesAndChecks, $disposableCost);
        $dpo = $this->operationAmongTwoArrays($suppliers_trading, $intervals_number_of_days, 'multiply');
        // Cash Conversion Cycle "CCC"
        $dso_dio = HArr::sumAtDates([$dso, $dio], getDateFromTwoArrays($dso, $dio));
        // $dso_dio =  $this->finalTotal([$dso, $dio]);
        $ccc = $this->operationAmongTwoArrays($dso_dio, $dpo, 'subtraction');

        // Debt to Asset Ratio
        // $balance_sheet['Total Liabilities']??[]
        $debt_asset_ratio = $this->operationAmongTwoArrays($totalLiabilities, $totalAssets);
        // Debt to Equity Ratio
        $debt_equity_ratio = $this->operationAmongTwoArrays($totalLiabilities, $shareholdersEquity);

        $ratio_analysis_report = [
            'Profitability Ratios' => [
                'Return on Sales "ROS"' => [
                    'description' => 'ROS = EBIT ÷ Net Sales',
                    'data' => $ros,
                    'mark' => '%',
                    'decimals' => 2,
                ],
                'Return on Assets "ROA"' => [
                    'description' => 'ROA = Net Profit ÷  Total Assets',
                    'data' => $roa,
                    'mark' => '%',
                    'decimals' => 2,
                ],
                'Return On Capital Employed "ROCE"' => [
                    'description' => 'ROCE = EBIT ÷ Capital Employed (T.Assets - C. Liabs)',
                    'data' => $roce,
                    'mark' => '%',
                    'decimals' => 2,
                ],
                'Return on Equity "ROE"' => [
                    'description' => "ROE = Net Profit ÷  Owners' Equity",
                    'data' => $roe,
                    'mark' => '%',
                    'decimals' => 2,
                ],
                'Gross Profit Margin' => [
                    'description' => 'GP Rate = Gross Profit ÷ Net Sales',
                    'data' => $gross_profit_margin,
                    'mark' => '%',
                    'decimals' => 2,
                ],
                'EBITDA Margin' => [
                    'description' => 'EBITDA Rate = EBITDA ÷ Net Sales',
                    'data' => $ebitda_margin,
                    'mark' => '%',
                    'decimals' => 2,
                ],
                'Net Profit Margin' => [
                    'description' => 'NP Rate = Net Profit ÷ Net Sales',
                    'data' => $net_profit_margin,
                    'mark' => '%',
                    'decimals' => 2,
                ],

            ],
            'Liquidity Ratios' => [

                'Current Ratio' => [
                    'description' => 'CR = Current Assets ÷ Current Liabilities',
                    'data' => $current_ratio,
                    'mark' => ' : 1',
                    'decimals' => 2,
                ],
                'Quick Ratio' => [
                    'description' => 'QR = (Cash & Equivalent + AR + NR) ÷ Current Liabilities',
                    'data' => $quick_ratio,
                    'mark' => ' : 1',
                    'decimals' => 2,
                ],
                'Cash Ratio' => [
                    'description' => 'CaR = (Cash & Equivalent) ÷ Current Liabilities',
                    'data' => $cash_ratio,
                    'mark' => ' : 1',
                    'decimals' => 2,
                ],
                'Working Capital' => [
                    'description' => 'Working Capital = Current Assets - Current Liabilities',
                    'data' => $working_capital,
                    'mark' => ' EGP',
                    'decimals' => 2,
                ],

            ],
            'Efficiency Ratios' => [
                'Days Sales Outstanding "DSO"' => [
                    'description' => 'DSO = Av. Receivables ÷ Net Sales × 360',
                    'data' => $dso,
                    'mark' => ' Days',
                    'decimals' => 0,
                ],
                'Days Inventory Outstanding "DIO"' => [
                    'description' => 'DIO = Av. Inventory ÷ COGS × 360',
                    'data' => $dio,
                    'mark' => ' Days',
                    'decimals' => 0,
                ],
                'Days Payables Outstanding "DPO"' => [
                    'description' => 'DPO = Av. Payables ÷ COGS × 360',
                    'data' => $dpo,
                    'mark' => ' Days',
                    'decimals' => 0,
                ],
                'Cash Conversion Cycle "CCC"' => [
                    'description' => 'CCC = DSO + DIO - DPO',
                    'data' => $ccc,
                    'mark' => ' Days',
                    'decimals' => 0,
                ],

            ],
            'Leverage Ratios' => [

                'Debt to Asset Ratio' => [
                    'description' => 'D-A = Total Liabilities ÷ Total Assets',
                    'data' => $debt_asset_ratio,
                    'mark' => ' : 1',
                    'decimals' => 2,
                ],
                'Debt to Equity Ratio' => [
                    'description' => "D-E = Total Liabilities ÷ Owners' Equity",
                    'data' => $debt_equity_ratio,
                    'mark' => ' : 1',
                    'decimals' => 2,
                ],
            ],

        ];

        return $ratio_analysis_report;
    }

    public function operationAmongTwoArrays($array_one, $array_two, $operation = 'divide')
    {
        $dates = array_merge(array_keys($array_one) ?? [], array_keys($array_two) ?? []);
        $result = [];
        array_walk($dates, function ($date) use (&$result, $array_one, $array_two, $operation) {
            $value1 = $array_one[$date] ?? 0;
            $value2 = $array_two[$date] ?? 0;

            if ($operation == 'divide') {
                $result[$date] = $value2 != 0 ? $value1 / $value2 : 0;
            } elseif ($operation == 'multiply') {
                $result[$date] = $value1 * $value2;
            } elseif ($operation == 'subtraction') {
                $result[$date] = $value1 - $value2;
            } elseif ($operation == 'sum') {
                $result[$date] = $value1 + $value2;
            }
        });

        return $result;
    }
}
