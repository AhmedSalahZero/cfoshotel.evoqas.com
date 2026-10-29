<?php

namespace Tests\Unit;

use App\Calculations\CalculateIrrService;
use App\Calculations\CalculatePaybackPeriodService;
use App\Calculations\CalculateProfitsEquationsService;
use App\Calculations\FixedAssetsPayableEndBalance;
use App\Calculations\PropertyInsurancePayableEndBalance;
use App\Calculations\PropertyTaxesPayableEndBalance;
use App\Calculations\RatioAnalysisService;
use App\Models\HospitalitySector;
use Tests\TestCase;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Step 06, last calculation classes
//  Location: tests/Unit/CalculationsStep06Test.php
//
//  IRR and NPV, payback period, profit lines, ratio analysis, and the payables of fixed assets,
//  property insurance and property taxes.
//  Part 1: 71 inputs whose answers were produced by the OLD application code (tests/Fixtures/hotel/step06_cases.json).
//  (Two deliberate differences from the old application: the IRR comes from the MathPHP library, and the
//  "Gross Profit Margin" ratio is the real one. Both are tested below.)
//  Part 2: small cases with answers that can be checked by hand.
//  Run:  php artisan test --filter=CalculationsStep06Test
// ══════════════════════════════════════════════════════════════════
class CalculationsStep06Test extends TestCase
{
    private function study(): HospitalitySector
    {
        return (new HospitalitySector)->forceFill(['financial_year_start_month' => 'january']);
    }

    // ── Part 1: the same answers as the previous application ──

    public function test_every_class_gives_the_same_answer_as_the_previous_application(): void
    {
        $fixture = json_decode((string) file_get_contents(dirname(__DIR__).'/Fixtures/hotel/step06_cases.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertGreaterThan(65, count($fixture['cases']));

        $failures = [];
        foreach ($fixture['cases'] as $i => $case) {
            $class = 'App\\Calculations\\'.$case['service'];
            $args = $case['args'];
            if ($case['study']) {
                // the study goes after the date list (4th place) or after the date list and its reverse (insurance: 5th place)
                array_splice($args, $case['service'] === 'PropertyInsurancePayableEndBalance' ? 4 : 3, 0, [$this->study()]);
            }
            $actual = json_encode((new $class)->{$case['method']}(...$args), JSON_PRESERVE_ZERO_FRACTION);

            if ($actual !== $case['expected']) {
                $failures[] = "#{$i} {$case['service']}::{$case['method']}: expected ".substr($case['expected'], 0, 120).', got '.substr((string) $actual, 0, 120);
            }
        }

        $this->assertSame([], $failures);
    }

    // ── Part 2: answers that can be checked by hand ──

    public function test_net_present_value_discounts_the_first_year_by_one_year(): void
    {
        $npv = (new CalculateIrrService)->calculateNetPresentValue([2026 => -100, 2027 => 60, 2028 => 60], 10);
        // -100/1.1 + 60/1.21 + 60/1.331
        $this->assertEqualsWithDelta(3.7566, $npv, 0.0001);
        $this->assertEqualsWithDelta(-40.0, (new CalculateIrrService)->calculateNetPresentValue([-100, 30, 30], 0), 0.0000001);
    }

    public function test_irr_comes_from_the_mathphp_library_and_makes_the_present_value_zero(): void
    {
        $service = new CalculateIrrService;

        // an investment of 100 with returns of 50, 40 and 30 earns about 10.65% a year
        $irr = $service->calculateIrr([-100, 50, 40, 30]);
        $this->assertEqualsWithDelta(0.1065168, $irr, 0.000001);

        foreach ([[-100, 60, 60], [-500, 300, 300, -200, 400], [-1000000, 150000, 250000, 300000, 350000, 400000, 2500000], [-1000.55, 0.1, 0.2, 1500.333]] as $flows) {
            $irr = $service->calculateIrr($flows);
            // at the IRR the net present value is zero
            $this->assertEqualsWithDelta(0.0, $service->calculateNetPresentValue($flows, $irr * 100), 0.0001);
        }

        $this->assertEqualsWithDelta(0.1306624, $service->calculateIrr([-100, 60, 60]), 0.000001);
        $this->assertEqualsWithDelta(0.3427966, $service->calculateIrr([-1000000, 150000, 250000, 300000, 350000, 400000, 2500000]), 0.000001);
    }

    public function test_the_years_of_the_cash_flow_are_taken_in_order_whatever_their_keys(): void
    {
        $service = new CalculateIrrService;

        $this->assertEqualsWithDelta($service->calculateIrr([-100, 60, 60]), $service->calculateIrr([2026 => -100, 2027 => 60, 2028 => 60]), 0.0000001);
    }

    public function test_there_is_no_irr_when_the_cash_flow_never_changes_sign(): void
    {
        $service = new CalculateIrrService;

        $this->assertNan($service->calculateIrr([10, 20, 30]));
        $this->assertNan($service->calculateIrr([-10, -20, -30]));
        $this->assertNan($service->calculateIrr([5])); // one value is not a cash flow
    }

    public function test_payback_period_is_the_month_the_money_is_back(): void
    {
        $service = new CalculatePaybackPeriodService;

        // equity 5,000: the money is back in the 9th month (accumulated cash reaches 5,000)
        $result = $service->__calculate(7, [-5000, -4000, -2500, -100, 0, 50, 2000, 4999, 5000, 7000], '01-01-2026', 5000.0);
        $this->assertSame(['Sep 2026' => '0.8 Years'], $result);

        // never reaches the equity
        $this->assertSame(['' => 'More Than 5 Years'], $service->__calculate(5, [1, 2, 3], '01-01-2026', 10.0));
        $this->assertSame(['' => 'More Than 5 Years'], $service->__calculate(5, [], '01-01-2026', 0.0));
    }

    public function test_profit_lines_follow_the_income_statement(): void
    {
        $service = new CalculateProfitsEquationsService;
        $revenues = ['01-01-2026' => 1000, '01-02-2026' => 0];
        $cogs = ['01-01-2026' => 400, '01-02-2026' => 10];

        $gross = $service->__calculateGrossProfit($revenues, $cogs);
        $this->assertSame(600, $gross['values']['01-01-2026']);
        $this->assertSame(-10, $gross['values']['01-02-2026']);
        $this->assertEquals(60, $gross['percentages']['01-01-2026']);
        $this->assertSame(0, $gross['percentages']['01-02-2026']); // no revenue: the percentage is 0

        $ebitda = $service->__calculateEBITDA($gross['values'], ['01-01-2026' => 100], ['01-01-2026' => 50], $revenues);
        $this->assertSame(450, $ebitda['values']['01-01-2026']);
        $this->assertEquals(45, $ebitda['percentages']['01-01-2026']);

        $ebit = $service->__calculateEBIT($ebitda['values'], ['01-01-2026' => 40], ['01-01-2026' => 10], $revenues);
        $this->assertSame(400, $ebit['values']['01-01-2026']);

        $ebt = $service->__calculateEBT($ebit['values'], ['01-01-2026' => 100], $revenues);
        $this->assertSame(300, $ebt['values']['01-01-2026']);

        $net = $service->__calculateNetProfit($ebt['values'], ['01-01-2026' => 75], $revenues);
        $this->assertSame(225, $net['values']['01-01-2026']);
        $this->assertEquals(22.5, $net['percentages']['01-01-2026']);
    }

    public function test_profit_lines_with_nothing_in_them_are_empty_and_do_not_fail(): void
    {
        $service = new CalculateProfitsEquationsService;
        $empty = ['values' => [], 'percentages' => []];

        $this->assertSame($empty, $service->__calculateGrossProfit([], []));
        $this->assertSame($empty, $service->__calculateEBITDA([], [], [], []));
        $this->assertSame($empty, $service->__calculateEBIT([], [], [], []));
        $this->assertSame($empty, $service->__calculateEBT([], [], []));
        $this->assertSame($empty, $service->__calculateNetProfit([], [], []));
    }

    public function test_ratio_operations_never_divide_by_zero(): void
    {
        $service = new RatioAnalysisService;

        $this->assertEquals(['a' => 5, 'b' => 0], $service->operationAmongTwoArrays(['a' => 10, 'b' => 4], ['a' => 2, 'b' => 0]));
        $this->assertEquals(['a' => 20], $service->operationAmongTwoArrays(['a' => 10], ['a' => 2], 'multiply'));
        $this->assertEquals(['a' => 8], $service->operationAmongTwoArrays(['a' => 10], ['a' => 2], 'subtraction'));
        $this->assertEquals(['a' => 12], $service->operationAmongTwoArrays(['a' => 10], ['a' => 2], 'sum'));
    }

    public function test_ratio_report_has_the_four_groups_and_the_formulas(): void
    {
        $dates = ['01-01-2026' => 0, '01-02-2026' => 1];
        $s = fn (float $a, float $b) => [0 => $a, 1 => $b];
        $report = (new RatioAnalysisService)->__calculate(
            $dates, $s(1000, 2000), $s(100, 200), $s(150, 250), $s(400, 800), $s(5000, 5000), $s(3000, 3000), $s(9000, 9000), $s(6000, 6000),
            $s(500, 500), $s(200, 200), $s(1500, 1500), $s(800, 800), $s(80, 160), $s(700, 700), $s(100, 100), $s(50, 50), $s(300, 300)
        );

        $this->assertSame(['Profitability Ratios', 'Liquidity Ratios', 'Efficiency Ratios', 'Leverage Ratios'], array_keys($report));
        $this->assertEquals([0 => 0.1, 1 => 0.1], $report['Profitability Ratios']['Return on Sales "ROS"']['data']);          // EBIT / sales
        $this->assertEquals([0 => 0.0089, 1 => 0.0178], array_map(fn ($v) => round($v, 4), $report['Profitability Ratios']['Return on Assets "ROA"']['data'])); // net profit / assets
        $this->assertEquals([0 => 1.875, 1 => 1.875], $report['Liquidity Ratios']['Current Ratio']['data']);                  // current assets / current liabilities
        $this->assertSame(' : 1', $report['Liquidity Ratios']['Current Ratio']['mark']);
        $this->assertEquals([0 => 0.3333, 1 => 0.3333], array_map(fn ($v) => round($v, 4), $report['Leverage Ratios']['Debt to Asset Ratio']['data']));
        $this->assertSame(0, $report['Efficiency Ratios']['Days Sales Outstanding "DSO"']['decimals']);
    }

    /**
     * The previous application showed the EBITDA margin on the "Gross Profit Margin" line. Corrected (owner's decision):
     * gross profit margin = gross profit / net sales, EBITDA margin = EBITDA / net sales.
     */
    public function test_gross_profit_margin_and_ebitda_margin_are_each_their_own_ratio(): void
    {
        $dates = ['01-01-2026' => 0];
        $s = fn (float $v) => [0 => $v];
        $report = (new RatioAnalysisService)->__calculate(
            $dates, $s(1000), $s(0), $s(150), $s(400), $s(0), $s(0), $s(0), $s(0), $s(0), $s(0), $s(0), $s(0), $s(0), $s(0), $s(0), $s(0), $s(0)
        );

        $this->assertEquals([0 => 0.15], $report['Profitability Ratios']['EBITDA Margin']['data']);       // EBITDA 150 / sales 1000
        $this->assertEquals([0 => 0.40], $report['Profitability Ratios']['Gross Profit Margin']['data']); // gross profit 400 / sales 1000
    }

    public function test_payables_roll_the_balance_forward_every_period(): void
    {
        $indexes = [];
        for ($i = 0; $i < 12; $i++) {
            $indexes[$i] = sprintf('01-%02d-2026', $i + 1);
        }
        $purchases = [0 => 1000, 1 => 500, 2 => 0, 3 => 200];
        $payments = [0 => 400, 1 => 100, 2 => 300, 3 => 0];

        $monthly = (new FixedAssetsPayableEndBalance)->calculateEndBalance($purchases, $payments, $indexes, $this->study())['monthly'];

        $this->assertSame([0 => 0, 1 => 600, 2 => 1000, 3 => 700], $monthly['beginning_balance']);
        $this->assertSame([0 => 1000, 1 => 1100, 2 => 1000, 3 => 900], $monthly['due_amount']);
        $this->assertSame([0 => 600, 1 => 1000, 2 => 700, 3 => 900], $monthly['end_balance']);

        // the three-month view: the first quarter is purchases 1500, payments 800
        $quarterly = (new FixedAssetsPayableEndBalance)->calculateEndBalance($purchases, $payments, $indexes, $this->study())['quarterly'];
        $this->assertSame(1500, $quarterly['asset_purchases'][2]);
        $this->assertSame(800, $quarterly['payment'][2]);
        $this->assertSame(700, $quarterly['end_balance'][2]);
    }

    public function test_the_three_payables_use_their_own_wording_for_the_charge(): void
    {
        $indexes = [];
        for ($i = 0; $i < 12; $i++) { // a full year, as every study has whole years
            $indexes[$i] = sprintf('01-%02d-2026', $i + 1);
        }
        $purchases = [0 => 100, 1 => 100];
        $payments = [1 => 50];

        $fixed = (new FixedAssetsPayableEndBalance)->calculateEndBalance($purchases, $payments, $indexes, $this->study());
        $insurance = (new PropertyInsurancePayableEndBalance)->getPropertyInsurancePayableEndBalance($purchases, $payments, $indexes, array_flip($indexes), $this->study());
        $taxes = (new PropertyTaxesPayableEndBalance)->getPropertyTaxesPayableEndBalance($purchases, $payments, $indexes, $this->study());

        $this->assertArrayHasKey('asset_purchases', $fixed['monthly']);
        $this->assertArrayHasKey('property_insurance', $insurance['monthly']);
        $this->assertArrayHasKey('property_taxes', $taxes['monthly']);
        foreach ([$fixed, $insurance, $taxes] as $result) {
            $this->assertSame([0 => 100, 1 => 150], $result['monthly']['end_balance']);
            $this->assertSame(['monthly', 'quarterly', 'semi-annually', 'annually'], array_keys($result));
        }
    }
}
