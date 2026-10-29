<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Currency;
use App\Models\HospitalitySector;
use App\Models\User;
use App\Services\Revenue\RevenueFiguresService;
use Database\Seeders\CountriesSeeder;
use Database\Seeders\CurrenciesSeeder;
use Database\Seeders\StatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

// The revenue results page and the reading aids. One hand-checkable case. Run:  php artisan test --filter=RevenueResultsTest
// The study: starts March 2026, 5 years, operation from month 12 (March 2027), 100 rooms with 2 guests each.
// The first operating year is 2027 with 10 months (March to December = 306 days). Rooms: ADR 1,000 grown 10% for one year = 1,100;
// occupancy 60%, every month fully open. Two sales channels, the first with the whole share and no discount, so the figures stay as above.
//   room nights available   100 x 306            = 30,600
//   room nights sold        30,600 x 60%         = 18,360
//   rooms revenue           18,360 x 1,100       = 20,196,000
//   guests                  18,360 x 2           = 36,720
// The calculation itself was compared with the old application on 21 studies (docs/REVENUE.md); this checks that the page shows it.
class RevenueResultsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $colleague;
    private HospitalitySector $study;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CountriesSeeder::class, StatesSeeder::class, CurrenciesSeeder::class]);
        $company = Company::factory()->create(['users_limit' => 10]);
        $company->insertBasicSelects();
        $this->owner = User::factory()->forCompany($company)->create();
        $this->colleague = User::factory()->forCompany($company)->create();

        $type = fn (string $table) => (int) DB::table($table)->where('company_id', $company->id)->where('is_active', 1)->orderBy('id')->skip(1)->value('id');
        $off = ['on' => false, 'mode' => 'types', 'rows' => [['type_id' => null, 'count' => null, 'value' => null]]];

        $this->actingAs($this->owner)->post('/studies', [
            'study_name' => 'Nile', 'property_name' => 'Nile', 'property_status' => 'existing', 'star_rating' => 5,
            'country_id' => null, 'state_id' => null, 'region' => 'Zamalek',
            'study_start' => '2026-03', 'duration_in_years' => 5,
            'development_start_month' => 0, 'development_duration' => 0, 'operation_start_month' => 12,
            'financial_year_start_month' => 'january', 'main_functional_currency' => (int) Currency::query()->orderBy('id')->value('id'),
            'additional_currency' => null, 'exchange_rate' => 1, 'corporate_taxes_rate' => 20, 'investment_return_rate' => 10, 'perpetual_growth_rate' => 2,
            'streams' => [
                'rooms' => ['on' => true, 'mode' => 'types', 'rows' => [['type_id' => $type('room_names'), 'count' => 100, 'value' => 2]]],
                'foods' => ['on' => true, 'mode' => 'types', 'rows' => [['type_id' => $type('food_names'), 'count' => 80, 'value' => 3]]],
                'casinos' => $off, 'meetings' => $off, 'others' => $off,
            ],
            'channels' => ['on' => true, 'names' => ['direct-reservations', 'agency']],
        ])->assertSessionHasNoErrors();

        $this->study = HospitalitySector::query()->withoutGlobalScopes()->latest('id')->firstOrFail();
    }

    private function byYear($value): array
    {
        return array_fill_keys([1, 2, 3, 4, 5], $value);
    }

    private function saveRooms(): void
    {
        $room = $this->study->rooms()->firstOrFail();
        $terms = ['type' => 'system_default', 'interval' => 'monthly', 'rates' => [0, 0, 0, 0], 'due' => [0, 0, 0, 0]];
        $channels = $this->study->salesChannels()->orderBy('id')->get()->values()->map(fn ($channel, $i) => [
            'name' => $channel->name, 'share' => $this->byYear($i === 0 ? 100 : 0), 'discount' => $this->byYear(0), 'terms' => $terms,
        ])->all();
        $months = array_fill_keys(['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'], 100);

        $this->actingAs($this->owner)->put("/studies/{$this->study->id}/rooms", [
            'currency' => (int) $this->study->main_functional_currency,
            'rooms' => [['key' => (int) $room->room_type_id, 'adr' => 1000, 'escalation' => 10, 'annual' => 3]],
            'exchange' => $this->byYear(1),
            'occupancy' => ['mode' => 'general', 'general' => $this->byYear(60), 'perRoom' => [(int) $room->room_type_id => $this->byYear(0)]],
            'seasonality' => ['type' => 'general', 'interval' => 'flat', 'general' => $months, 'perRoom' => [(int) $room->room_type_id => $months]],
            'channels' => $channels,
            'collection' => ['mode' => 'general', 'general' => $terms],
        ])->assertRedirect("/studies/{$this->study->id}/rooms")->assertSessionHasNoErrors();

        $figures = app(RevenueFiguresService::class);
        $this->assertNotNull($figures->figures($this->study->fresh()), 'The revenue could not be calculated: '.$figures->error);
    }

    public function test_the_results_page_shows_the_rooms_revenue_the_nights_and_the_guests_of_the_first_year(): void
    {
        $this->saveRooms();

        $this->actingAs($this->owner)->get("/studies/{$this->study->id}/revenue")->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Studies/Revenue')->where('calculated', true)->has('years', 5)->where('streams', ['rooms', 'foods'])
            ->where('revenue.rooms.1', fn ($v) => abs($v - 20196000) < 0.01)
            ->where('revenue.total.1', fn ($v) => abs($v - 20196000) < 0.01)
            ->where('operating.nightsAvailable.1', fn ($v) => abs($v - 30600) < 0.01)
            ->where('operating.nightsSold.1', fn ($v) => abs($v - 18360) < 0.01)
            ->where('operating.guests.1', fn ($v) => abs($v - 36720) < 0.01));
    }

    public function test_the_revenue_pages_get_the_reading_aids(): void
    {
        $this->saveRooms();

        // The aids are a deferred prop: the page comes first, the figures a moment later (loadDeferredProps asks for them, as the browser does).
        $this->actingAs($this->owner)->get("/studies/{$this->study->id}/foods")->assertOk()->assertInertia(fn (Assert $p) => $p
            ->missing('aids')
            ->loadDeferredProps(fn (Assert $later) => $later
                ->where('aids.guests.1', fn ($v) => abs($v - 36720) < 0.01)
                ->where('aids.roomsRevenue.1', fn ($v) => abs($v - 20196000) < 0.01)));
        $this->actingAs($this->owner)->get("/studies/{$this->study->id}/rooms")->assertOk()->assertInertia(fn (Assert $p) => $p
            ->missing('aids')
            ->loadDeferredProps(fn (Assert $later) => $later
                ->where('aids.nightsAvailable.1', fn ($v) => abs($v - 30600) < 0.01)));
    }

    public function test_a_study_without_rates_has_no_revenue_and_the_page_still_opens(): void
    {
        $this->actingAs($this->owner)->get("/studies/{$this->study->id}/revenue")->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Studies/Revenue')->has('years', 5));
        $this->actingAs($this->owner)->get("/studies/{$this->study->id}/rooms")->assertOk();
    }

    public function test_a_person_who_may_only_view_can_open_the_results_and_another_company_cannot(): void
    {
        $this->saveRooms();
        $this->study->sharedWith()->attach($this->colleague->id, ['can_edit' => false]);

        $this->actingAs($this->colleague)->get("/studies/{$this->study->id}/revenue")->assertOk();

        $stranger = User::factory()->forCompany(Company::factory()->create())->create();
        $this->actingAs($stranger)->get("/studies/{$this->study->id}/revenue")->assertNotFound();
    }

    public function test_the_study_menu_lists_the_results_last(): void
    {
        $this->actingAs($this->owner)->get("/studies/{$this->study->id}/rooms")->assertInertia(fn (Assert $p) => $p
            ->where('menu', fn ($menu) => collect($menu)->pluck('key')->reject(fn ($k) => preg_match('/^((expenses|investment|statements|financial)\.|dashboard$)/', $k))->values()->all() === ['study', 'rooms', 'foods', 'revenue']));
    }

    public function test_the_revenue_only_calculation_matches_the_whole_calculation(): void
    {
        $study = HospitalitySector::query()->withoutGlobalScopes()->findOrFail($this->study->id);
        $study->bindDateTables();
        $whole = $study->calculateRoomRevenueAndGuestCount();
        $part = $study->calculateRevenueOnly();

        $this->assertNotEmpty($part['annuallyTotalHotelRevenue']);
        foreach ($part as $key => $value) {
            $this->assertEquals($whole[$key], $value, "The revenue-only calculation differs from the whole one at: {$key}");
        }
    }

    public function test_a_kept_result_is_not_reused_after_the_data_changes(): void
    {
        \App\Support\StudyCache::enable(true);

        try {
            $this->saveRooms();
            $load = fn () => HospitalitySector::query()->withoutGlobalScopes()->findOrFail($this->study->id);

            $first = (new RevenueFiguresService)->result($load());
            $again = (new RevenueFiguresService)->result($load());   // from the kept copy
            $this->assertEquals($first, $again);

            // the price of every room at the opening date changes (a write to the rooms table; the calculation reads this column): the kept copy must not be used
            $this->study->rooms()->update(['average_daily_rate_at_operation_date' => '5000']);

            $fresh = $load();
            $after = (new RevenueFiguresService)->result($fresh);
            $this->assertNotEquals($first['annuallyTotalHotelRevenue'], $after['annuallyTotalHotelRevenue']);

            $fresh->bindDateTables();
            $this->assertEquals($fresh->calculateRoomRevenueAndGuestCount()['annuallyTotalHotelRevenue'], $after['annuallyTotalHotelRevenue']);
        } finally {
            \App\Support\StudyCache::enable(null);
        }
    }
}
