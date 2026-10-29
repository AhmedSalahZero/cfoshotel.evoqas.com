<?php

namespace Tests\Feature;

use App\Models\Acquisition;
use App\Models\DepartmentExpense;
use App\Models\HospitalitySector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\HotelStudies;
use Tests\TestCase;

// A study hands its date tables to the application, and the accessors read them from there.
// Run:  php artisan test --filter=HotelStudyDatesTest
class HotelStudyDatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_study_fills_the_six_date_tables(): void
    {
        HotelStudies::load();

        $study = HospitalitySector::findOrFail(306);
        $dates = $study->getStudyDates();

        $this->assertCount(240, $dates, 'twenty years of months');
        $this->assertSame('01-01-2026', $dates[0]);
        $this->assertSame('01-12-2045', $dates[239]);

        $this->assertSame($study, $study->bindDateTables());

        $this->assertSame($dates, app('dateIndexWithDate'));
        $this->assertSame(array_flip($dates), app('dateWithDateIndex'));
        $this->assertSame(0, app('datesIndexWithYearIndex')[0]);
        $this->assertSame(0, app('datesIndexWithYearIndex')[11]);
        $this->assertSame(1, app('datesIndexWithYearIndex')[12]);
        $this->assertSame(19, app('datesIndexWithYearIndex')[239]);
        $this->assertSame('2026', (string) app('yearIndexWithYear')[0]);
        $this->assertSame('2045', (string) app('yearIndexWithYear')[19]);
        $this->assertSame('01', (string) app('dateIndexWithMonthNumber')[0]);
        $this->assertSame('12', (string) app('dateIndexWithMonthNumber')[239]);
        $this->assertSame('06', (string) app('dateWithMonthNumber')['01-06-2026']);
    }

    public function test_opening_another_study_replaces_the_tables(): void
    {
        HotelStudies::load();

        HospitalitySector::findOrFail(306)->bindDateTables();
        $this->assertSame('01-01-2026', app('dateIndexWithDate')[0]);
        $this->assertCount(240, app('dateIndexWithDate'));

        HospitalitySector::findOrFail(307)->bindDateTables();
        $this->assertSame('01-07-2025', app('dateIndexWithDate')[0]);
        $this->assertCount(234, app('dateIndexWithDate'));
        $this->assertSame(0, app('datesIndexWithYearIndex')[0]);
        $this->assertSame(0, app('datesIndexWithYearIndex')[5]);
        $this->assertSame(1, app('datesIndexWithYearIndex')[6], 'January 2026 is the second year of a study that starts in July 2025');
    }

    public function test_the_accessors_turn_a_date_number_into_a_date_and_back(): void
    {
        HotelStudies::load();
        $study = HospitalitySector::findOrFail(306)->bindDateTables();

        $this->assertSame('01-03-2026', $study->getFullDateStringFromDateIndex(2));
        $this->assertSame(2, $study->getDateIndexFromDate('01-03-2026'));
        $this->assertSame(1, $study->getYearIndexFromDateIndex(12));
        $this->assertSame(2027, (int) $study->getYearFromYearIndex(1));
    }

    public function test_the_land_and_expense_dates_are_read_from_the_study_tables(): void
    {
        HotelStudies::load();

        foreach (Acquisition::query()->whereNotNull('hospitality_sector_id')->get() as $acquisition) {
            $study = $acquisition->hospitalitySector->bindDateTables();
            $dates = $study->getStudyDates();

            $this->assertSame($dates[$acquisition->purchase_date] ?? null, $acquisition->getLandPurchaseDateAsString());
        }

        $checked = 0;
        foreach (DepartmentExpense::query()->whereNotNull('hospitality_sector_id')->whereNotNull('date')->get() as $expense) {
            $dates = $expense->hospitalitySector->bindDateTables()->getStudyDates();

            $this->assertSame($dates[$expense->date] ?? null, $expense->getDateAsString());
            $checked++;
        }

        $this->assertGreaterThan(0, $checked, 'the real studies have expenses with a start date');
    }
}
