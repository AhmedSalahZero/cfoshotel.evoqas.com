<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Currency;
use App\Models\HospitalitySector;
use App\Models\User;
use App\Support\StudyNotices;
use Database\Seeders\CountriesSeeder;
use Database\Seeders\CurrenciesSeeder;
use Database\Seeders\StatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// The notice on the financial pages when the Property Acquisition Costs page was never saved but the hotel is built (the building is then not carried to the fixed assets).
// Run:  php artisan test --filter=StudyNoticesTest
class StudyNoticesTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private HospitalitySector $study;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CountriesSeeder::class, StatesSeeder::class, CurrenciesSeeder::class]);
        $company = Company::factory()->create(['users_limit' => 10]);
        $company->insertBasicSelects();
        $this->owner = User::factory()->forCompany($company)->create();

        $type = fn (string $table) => (int) DB::table($table)->where('company_id', $company->id)->where('is_active', 1)->orderBy('id')->skip(1)->value('id');
        $off = ['on' => false, 'mode' => 'types', 'rows' => [['type_id' => null, 'count' => null, 'value' => null]]];

        $this->actingAs($this->owner)->post('/studies', [
            'study_name' => 'Nile', 'property_name' => 'Nile', 'property_status' => 'under_development', 'star_rating' => 5,
            'country_id' => null, 'state_id' => null, 'region' => 'Zamalek',
            'study_start' => '2026-03', 'duration_in_years' => 5,
            'development_start_month' => 0, 'development_duration' => 12, 'operation_start_month' => 12,
            'financial_year_start_month' => 'january', 'main_functional_currency' => (int) Currency::query()->orderBy('id')->value('id'),
            'additional_currency' => null, 'exchange_rate' => 1, 'corporate_taxes_rate' => 20, 'investment_return_rate' => 10, 'perpetual_growth_rate' => 2,
            'streams' => [
                'rooms' => ['on' => true, 'mode' => 'types', 'rows' => [['type_id' => $type('room_names'), 'count' => 100, 'value' => 2]]],
                'foods' => ['on' => true, 'mode' => 'types', 'rows' => [['type_id' => $type('food_names'), 'count' => 2, 'value' => 50]]],
                'casinos' => $off, 'meetings' => $off, 'others' => $off,
            ],
            'channels' => ['on' => false, 'names' => []],
        ])->assertSessionHasNoErrors();

        $this->study = HospitalitySector::query()->withoutGlobalScopes()->latest('id')->firstOrFail();
    }

    private function construction(float $hard, float $soft): void
    {
        DB::table('acquisitions')->insert(['hospitality_sector_id' => $this->study->id, 'company_id' => $this->study->company_id, 'hard_construction_cost' => (string) $hard, 'soft_construction_cost' => (string) $soft]);
    }

    public function test_no_construction_means_nothing_to_say(): void
    {
        $this->assertFalse(StudyNotices::propertyMissing($this->study));

        $this->construction(0, 0);
        $this->assertFalse(StudyNotices::propertyMissing($this->study));
    }

    public function test_a_built_hotel_without_the_property_page_gets_the_notice(): void
    {
        $this->construction(1000000, 0);

        $this->assertTrue(StudyNotices::propertyMissing($this->study));
        $this->assertTrue(StudyNotices::for($this->study)['propertyMissing']);
        $this->assertSame(route('studies.investment.property.edit', $this->study->id), StudyNotices::for($this->study)['propertyUrl']);
    }

    public function test_the_soft_cost_alone_is_enough(): void
    {
        $this->construction(0, 500000);

        $this->assertTrue(StudyNotices::propertyMissing($this->study));
    }

    public function test_saving_the_property_page_removes_the_notice(): void
    {
        $this->construction(1000000, 0);
        DB::table('property_acquisitions')->insert(['hospitality_sector_id' => $this->study->id, 'company_id' => $this->study->company_id, 'property_payment_method' => 'cash']);

        $this->assertFalse(StudyNotices::propertyMissing($this->study));
    }

    public function test_the_financial_pages_and_the_dashboard_receive_it(): void
    {
        $this->construction(1000000, 0);

        foreach (["/studies/{$this->study->id}/reports/balance", "/studies/{$this->study->id}/reports/income", "/studies/{$this->study->id}/reports/ratios", "/studies/{$this->study->id}/dashboard"] as $url) {
            $props = $this->actingAs($this->owner)->get($url)->assertOk()->original->getData()['page']['props'];
            $this->assertTrue($props['notices']['propertyMissing'], $url);
        }
    }
}
