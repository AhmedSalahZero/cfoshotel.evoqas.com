<?php

namespace Tests\Feature;

use App\Models\HospitalitySector;
use App\Services\Reports\FinancialReports;
use App\Services\Statements\ReportItems;
use App\Support\StudyCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Support\HotelStudies;
use Tests\TestCase;

// The cash in / out data behind the income statement, the cash in / out, the balance sheet, the ratios and the dashboard is worked out once and kept
// (without its context, which is kept on its own) until study data is written. Run:  php artisan test --filter=ReportCacheTest
class ReportCacheTest extends TestCase
{
    use RefreshDatabase;

    private const STUDY = 313;

    protected function setUp(): void
    {
        parent::setUp();
        HotelStudies::load();
        StudyCache::enable(true);
        StudyCache::bump();
    }

    protected function tearDown(): void
    {
        foreach (['result', 'report-context', 'cash-in-out'] as $name) {
            Cache::store('file')->forget('study-'.self::STUDY.'-'.$name);
        }
        StudyCache::enable(null);
        parent::tearDown();
    }

    /** A fresh calculator, as a new page request would have. */
    private function cashInOut(): array
    {
        $study = HospitalitySector::query()->withoutGlobalScopes()->findOrFail(self::STUDY);
        $study->bindDateTables();

        return (new FinancialReports(app(ReportItems::class)))->cashInOut($study);
    }

    private function kept(): ?array
    {
        return Cache::store('file')->get('study-'.self::STUDY.'-cash-in-out');
    }

    public function test_it_is_kept_without_the_context_and_given_back_with_it(): void
    {
        $first = $this->cashInOut();

        $kept = $this->kept();
        $this->assertNotNull($kept, 'the cash in / out data was not kept');
        $this->assertSame(StudyCache::version(), $kept['version']);
        $this->assertArrayNotHasKey('c', $kept['data']);

        $second = $this->cashInOut();
        $this->assertArrayHasKey('c', $second);
        $this->assertSame(array_keys($first), array_keys($second));
        foreach (array_keys($first) as $key) {
            if ($key !== 'c') {
                $this->assertSame($first[$key], $second[$key], $key);
            }
        }
    }

    public function test_a_write_to_study_data_makes_it_work_it_out_again(): void
    {
        $this->cashInOut();
        $before = $this->kept()['version'];

        StudyCache::noteQuery('update `acquisitions` set `land_purchase_cost` = ? where `id` = ?');
        $this->cashInOut();

        $this->assertNotSame($before, $this->kept()['version']);
        $this->assertSame(StudyCache::version(), $this->kept()['version']);
    }
}
