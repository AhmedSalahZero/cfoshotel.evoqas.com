<?php

namespace Tests\Unit;

use App\Models\DepartmentExpense;
use Tests\TestCase;

// The head counts of all months at once give exactly the figures of the month-by-month lookup. Run:  php artisan test --filter=ManpowerPayloadTest
class ManpowerPayloadTest extends TestCase
{
    public function test_all_dates_at_once_equal_the_one_by_one_lookup(): void
    {
        $dates = range(3, 40);

        foreach ([[5 => 2, 9 => 4, 14 => 3], [3 => 1], [], [38 => 7, 6 => 1]] as $payload) {
            $expense = new DepartmentExpense;
            $expense->manpower_payload = $payload;

            $all = $expense->getManpowerPayloadForDates($dates);

            $this->assertCount(count($dates), $all);
            foreach ($dates as $date) {
                $this->assertSame($expense->getManpowerPayloadAtDate((string) $date, $dates), $all[$date], 'date '.$date);
            }
        }
    }

    public function test_the_last_head_count_carries_on_after_the_last_saved_month(): void
    {
        $expense = new DepartmentExpense;
        $expense->manpower_payload = [5 => 2, 9 => 4];

        $all = $expense->getManpowerPayloadForDates(range(3, 20));

        $this->assertSame(0, $all[3]);
        $this->assertEquals(2, $all[5]);
        $this->assertEquals(4, $all[9]);
        $this->assertEquals(4, $all[20]);
    }
}
