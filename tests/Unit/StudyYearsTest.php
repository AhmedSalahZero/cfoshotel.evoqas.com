<?php

namespace Tests\Unit;

use App\Support\StudyYears;
use PHPUnit\Framework\TestCase;

// Operating years of a study: a study starting 2026-03, 5 years, operation from month 12.
// Hand-checked: the study runs Mar 2026 - Feb 2031; operation starts Mar 2027, so 2027 has 10 months (Mar-Dec) and 2031 has 2 (Jan-Feb).
class StudyYearsTest extends TestCase
{
    public function test_operating_years_start_at_the_operation_month(): void
    {
        $years = StudyYears::list('03/01/2026', 12, 5);

        $this->assertSame([1, 2, 3, 4, 5], array_column($years, 'index'));
        $this->assertSame([2027, 2028, 2029, 2030, 2031], array_column($years, 'year'));
        $this->assertSame([10, 12, 12, 12, 2], array_column($years, 'months'));
    }

    public function test_a_study_that_opens_on_day_one_has_every_year(): void
    {
        $years = StudyYears::list('01/01/2026', 0, 3);

        $this->assertCount(3, $years);
        $this->assertSame([12, 12, 12], array_column($years, 'months'));
    }
}
