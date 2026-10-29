<?php

namespace Tests\Unit;

use App\Support\StudyCalendar;
use PHPUnit\Framework\TestCase;

// The month arithmetic of a study. Run:  php artisan test --filter=StudyCalendarTest
class StudyCalendarTest extends TestCase
{
    public function test_it_stores_and_reads_the_legacy_month_format(): void
    {
        $this->assertSame('03/01/2026', StudyCalendar::toStored('2026-03'));
        $this->assertSame('2026-03', StudyCalendar::fromStored('03/01/2026'));
    }

    public function test_the_study_ends_in_the_last_month_of_its_length(): void
    {
        $this->assertSame('2031-02', StudyCalendar::endMonth('2026-03', 5));
        $this->assertSame('2030-12', StudyCalendar::endMonth('2026-01', 5));
    }

    public function test_months_can_be_added_across_years(): void
    {
        $this->assertSame('2027-02', StudyCalendar::addMonths('2026-11', 3));
        $this->assertSame('2026-11', StudyCalendar::addMonths('2026-11', 0));
    }

    public function test_the_month_list_runs_to_the_end_of_the_twentieth_calendar_year(): void
    {
        $list = StudyCalendar::monthList('2026-03');
        $this->assertCount(238, $list);
        $this->assertSame('01-03-2026', $list[0]);
    }
}
