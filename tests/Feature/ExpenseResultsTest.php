<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Currency;
use App\Models\HospitalitySector;
use App\Support\ExpensePages;
use App\Models\User;
use App\Services\Revenue\RevenueFiguresService;
use Database\Seeders\CountriesSeeder;
use Database\Seeders\CurrenciesSeeder;
use Database\Seeders\StatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

// The expense results page. One hand-checkable case. Run:  php artisan test --filter=ExpenseResultsTest
// The study: starts March 2026, 5 years, operation from month 12 (March 2027), 100 rooms with 2 guests each.
// The first operating year is 2027 with 10 months (March to December = 306 days). Rooms: ADR 1,000 grown 10% for one year = 1,100;
// occupancy 60%, every month fully open. Two sales channels, the first with the whole share and no discount, so the figures stay as above.
//   room nights available   100 x 306            = 30,600
//   room nights sold        30,600 x 60%         = 18,360
//   rooms revenue           18,360 x 1,100       = 20,196,000
//   guests                  18,360 x 2           = 36,720
// Expenses entered on top of it (percentages of the revenue of 2027):
//   rooms: 10% of rooms revenue                 = 2,019,600
//   general: 5% of total revenue                = 1,009,800   and  5,000 a month for the 10 months of 2027 = 50,000 (60,000 in 2028)
//   base management fee: 3% of total revenue    =   605,880
//   start-up cost 12,000 paid in May 2026 (a year before the opening)
//   total operating expenses 2027               = 3,685,280
// The calculation was compared with the old application on 21 studies (docs/EXPENSES.md); this checks that the page adds the months up right.
class ExpenseResultsTest extends TestCase
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

    private function props(string $url): array
    {
        return json_decode(json_encode($this->actingAs($this->owner)->get($url)->assertOk()->original->getData()['page']['props']), true);
    }

    private function saveExpenses(): void
    {
        $id = $this->study->id;
        $currency = (int) $this->study->main_functional_currency;

        $form = $this->props("/studies/{$id}/expenses/rooms")['form'];
        $form['sections'][ExpensePages::PERCENT_ROOMS][0] = ['id' => null, 'name' => 'Commission', 'values' => $this->byYear(10)];
        $this->actingAs($this->owner)->put("/studies/{$id}/expenses/rooms", $form)->assertSessionHasNoErrors();

        $form = $this->props("/studies/{$id}/expenses/general")['form'];
        $form['sections'][ExpensePages::PERCENT_TOTAL][0] = ['id' => null, 'name' => 'Office', 'values' => $this->byYear(5)];
        $form['sections'][ExpensePages::FIXED][0] = ['id' => null, 'name' => 'Rent', 'currency' => $currency, 'amount' => 5000, 'escalation' => 0, 'atOperation' => 0, 'annual' => 0, 'terms' => 'monthly'];
        $this->actingAs($this->owner)->put("/studies/{$id}/expenses/general", $form)->assertSessionHasNoErrors();

        $form = $this->props("/studies/{$id}/management-fees")['form'];
        $form['sections']['base_management_fees_as_percentage_from_revenues'][0]['values'] = $this->byYear(3);
        $this->actingAs($this->owner)->put("/studies/{$id}/management-fees", $form)->assertSessionHasNoErrors();

        $page = $this->props("/studies/{$id}/start-up-cost");
        $form = $page['form'];
        $form['sections'][ExpensePages::START_UP][0] = ['id' => null, 'name' => 'Licences', 'currency' => $currency, 'cost' => 12000, 'date' => $page['months'][2]['index'], 'cash' => 100, 'dueDays' => 0];
        $this->actingAs($this->owner)->put("/studies/{$id}/start-up-cost", $form)->assertSessionHasNoErrors();
    }

    /** @return array<string, float> the figure of each line of a section for one year, keyed "group.section.line" */
    private function lines(array $page, int $year): array
    {
        $out = [];
        foreach ($page['groups'] as $group) {
            $out[$group['key'].'.total'] = (float) $group['total'][$year];
            foreach ($group['sections'] as $section) {
                foreach ($section['lines'] as $line) {
                    $out[$group['key'].'.'.$section['key'].'.'.$line['key']] = (float) $line['values'][$year];
                }
            }
        }

        return $out;
    }

    public function test_the_page_adds_the_months_of_each_calendar_year_including_the_years_before_the_opening(): void
    {
        $this->saveRooms();
        $this->saveExpenses();

        $page = $this->props("/studies/{$this->study->id}/expenses-results");
        $this->assertTrue($page['calculated']);
        // 2026 (10 months of the study before the opening), 2027 ... 2031 (the last 2 months of the study)
        $this->assertCount(6, $page['years']);
        $this->assertSame(['index' => 0, 'year' => 2026, 'months' => 10], $page['years'][0]);
        $this->assertSame(['index' => 5, 'year' => 2031, 'months' => 2], $page['years'][5]);

        $y2027 = $this->lines($page, 1);
        $this->assertEqualsWithDelta(2019600.0, $y2027['direct.rooms.percent'], 0.01);
        $this->assertEqualsWithDelta(1009800.0, $y2027['undistributed.general.percent'], 0.01);
        $this->assertEqualsWithDelta(50000.0, $y2027['undistributed.general.fixed'], 0.01);
        $this->assertEqualsWithDelta(605880.0, $y2027['management.management.base'], 0.01);
        $this->assertEqualsWithDelta(0.0, $y2027['startup.startup.payments'], 0.01);
        $this->assertEqualsWithDelta(3685280.0, (float) $page['totals']['operating'][1], 0.01);

        $this->assertEqualsWithDelta(60000.0, $this->lines($page, 2)['undistributed.general.fixed'], 0.01);
        // the start-up cost is paid before the opening and is not an operating expense
        $this->assertEqualsWithDelta(12000.0, $this->lines($page, 0)['startup.startup.payments'], 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $page['totals']['operating'][0], 0.01);
    }

    public function test_a_stream_that_is_switched_off_has_no_table_and_a_study_without_expenses_shows_zeros(): void
    {
        $this->saveRooms();

        $page = $this->props("/studies/{$this->study->id}/expenses-results");
        $this->assertTrue($page['calculated']);
        $sections = collect($page['groups'][0]['sections'])->pluck('key')->all();
        $this->assertSame(['rooms', 'foods'], $sections);
        $this->assertEqualsWithDelta(0.0, (float) $page['totals']['operating'][1], 0.01);
    }

    public function test_the_page_opens_for_a_study_that_cannot_be_calculated_yet(): void
    {
        $this->actingAs($this->owner)->get("/studies/{$this->study->id}/expenses-results")->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Studies/ExpenseResults')->has('years')->has('menu'));
    }

    public function test_the_menu_ends_with_the_expense_results(): void
    {
        $this->actingAs($this->owner)->get("/studies/{$this->study->id}/expenses-results")->assertInertia(fn (Assert $p) => $p
            ->where('menu', fn ($menu) => collect($menu)->reject(fn ($m) => preg_match('/^(statements|financial)\.|^dashboard$/', $m['key']))->last()['key'] === 'expenses.results'));
    }

    public function test_a_person_who_may_only_view_can_open_it_and_another_company_cannot(): void
    {
        $this->study->sharedWith()->attach($this->colleague->id, ['can_edit' => false]);
        $this->actingAs($this->colleague)->get("/studies/{$this->study->id}/expenses-results")->assertOk();

        $stranger = User::factory()->forCompany(Company::factory()->create())->create();
        $this->actingAs($stranger)->get("/studies/{$this->study->id}/expenses-results")->assertNotFound();
    }
}
