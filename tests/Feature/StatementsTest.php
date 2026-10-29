<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Currency;
use App\Models\HospitalitySector;
use App\Models\User;
use Database\Seeders\CountriesSeeder;
use Database\Seeders\CurrenciesSeeder;
use Database\Seeders\StatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// The statements screens (/studies/{id}/statements/{family}/{type}). Small on purpose: who may open them, what does not exist (404),
// the menu, and the shape of what the page receives. The figures themselves were compared with the previous application
// on 6 studies (about 55,000 cells, no difference), see docs/STATEMENTS.md.  Run:  php artisan test --filter=StatementsTest
class StatementsTest extends TestCase
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

    public function test_an_unknown_statement_or_one_of_a_stream_the_study_does_not_use_does_not_exist(): void
    {
        $id = $this->study->id;
        $this->actingAs($this->owner)->get("/studies/{$id}/statements/nothing")->assertNotFound();
        $this->actingAs($this->owner)->get("/studies/{$id}/statements/collection/nothing")->assertNotFound();
        $this->actingAs($this->owner)->get("/studies/{$id}/statements/collection/gaming")->assertNotFound();
        $this->actingAs($this->owner)->get("/studies/{$id}/statements/collection/meetings")->assertNotFound();
    }

    public function test_a_person_who_may_only_view_can_open_them_and_another_company_cannot(): void
    {
        $this->study->sharedWith()->attach($this->colleague->id, ['can_edit' => false]);
        $this->actingAs($this->colleague)->get("/studies/{$this->study->id}/statements/taxes/property")->assertOk();

        $stranger = User::factory()->forCompany(Company::factory()->create())->create();
        $this->actingAs($stranger)->get("/studies/{$this->study->id}/statements/taxes/property")->assertNotFound();
    }

    public function test_the_menu_and_the_links_follow_the_streams_of_the_study(): void
    {
        $page = $this->props("/studies/{$this->study->id}/statements/collection");

        $keys = array_column($page['menu'], 'key');
        foreach (['collection', 'inventory', 'disposables', 'prepaid', 'fees', 'taxes', 'assets', 'loans'] as $family) {
            $this->assertContains("statements.{$family}", $keys);
        }
        $this->assertSame(['rooms', 'foods', 'total'], $page['types']);
        $this->assertSame(['rooms', 'foods', 'total'], array_keys($page['links']));
        $this->assertSame('rooms', $page['type']);
    }

    public function test_a_calculated_study_gives_the_four_intervals_and_a_loan_gives_a_schedule(): void
    {
        $id = $this->study->id;
        $page = $this->props("/studies/{$id}/statements/collection/rooms");
        $this->assertIsBool($page['calculated']);
        $this->assertSame('periods', $page['kind']);

        $loan = $this->props("/studies/{$id}/statements/loans/property");
        $this->assertSame('schedule', $loan['kind']);
        $this->assertIsArray($loan['rows']);
    }
}
