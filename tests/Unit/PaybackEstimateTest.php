<?php

namespace Tests\Unit;

use App\Calculations\CalculatePaybackPeriodService;
use Tests\TestCase;

// Payback when the money is NOT back inside the study. Run:  php artisan test --filter=PaybackEstimateTest
class PaybackEstimateTest extends TestCase
{
    /** 24 months: the cash stands at 1,300 after the first year and 1,400 after the second (the last year brought in 100); the money to repay is 2,000, so 600 is missing. */
    private function slowSeries(): array
    {
        $series = [];
        for ($i = 0; $i < 24; $i++) {
            $series[] = $i < 12 ? 1300.0 : 1300.0 + 100 * ($i - 11) / 12;
        }

        return $series;
    }

    public function test_inside_the_study_it_is_the_exact_month_as_before(): void
    {
        $result = (new CalculatePaybackPeriodService)->estimate(7, [-5000, -4000, -2500, -100, 0, 50, 2000, 4999, 5000, 7000], '01-01-2026', 5000.0);

        $this->assertSame(['status' => 'within', 'years' => 0.8, 'months' => 9, 'date' => 'Sep 2026', 'estimated' => false], $result);
    }

    public function test_beyond_the_study_the_last_year_is_carried_forward(): void
    {
        $service = new CalculatePaybackPeriodService;

        // 600 is missing and the last year brings 100: 6 more years, so 8 years in all
        $flat = $service->estimate(2, $this->slowSeries(), '01-01-2026', 2000.0);
        $this->assertSame('estimated', $flat['status']);
        $this->assertTrue($flat['estimated']);
        $this->assertSame(96, $flat['months']);
        $this->assertSame(8.0, $flat['years']);
        $this->assertSame('Dec 2033', $flat['date']);

        // with 10% growth the missing 600 is covered sooner: 2 + 4.57 years
        $growing = $service->estimate(2, $this->slowSeries(), '01-01-2026', 2000.0, 0.10);
        $this->assertSame(6.6, $growing['years']);
        $this->assertLessThan($flat['months'], $growing['months']);
    }

    public function test_a_last_year_with_no_cash_never_pays_back(): void
    {
        $this->assertSame('never', (new CalculatePaybackPeriodService)->estimate(2, array_fill(0, 24, -50.0), '01-01-2026', 100.0)['status']);
        $this->assertSame('never', (new CalculatePaybackPeriodService)->estimate(2, [], '01-01-2026', 100.0)['status']);
        $this->assertSame('never', (new CalculatePaybackPeriodService)->estimate(2, array_fill(0, 24, 10.0), '01-01-2026', 100.0)['status']);
    }

    public function test_a_shrinking_flow_that_can_never_add_up_is_never(): void
    {
        // discounted case: the flow shrinks 5% a year, 100 a year adds up to at most 2,000, but 3,000 is missing... never
        $result = (new CalculatePaybackPeriodService)->estimate(2, $this->slowSeries(), '01-01-2026', 4400.0, -0.05);
        $this->assertSame('never', $result['status']);

        // ... while 600 missing is covered in about 7.4 more years
        $covered = (new CalculatePaybackPeriodService)->estimate(2, $this->slowSeries(), '01-01-2026', 2000.0, -0.05);
        $this->assertSame('estimated', $covered['status']);
        $this->assertSame(9.4, $covered['years']);
    }

    public function test_more_than_fifty_years_is_capped(): void
    {
        $result = (new CalculatePaybackPeriodService)->estimate(2, $this->slowSeries(), '01-01-2026', 100000.0);

        $this->assertSame('capped', $result['status']);
        $this->assertNull($result['years']);
        $this->assertSame(50, CalculatePaybackPeriodService::LONGEST_ESTIMATE_YEARS);
    }
}
