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
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

// The rooms revenue page. Small, hand-checkable cases. Run:  php artisan test --filter=RoomsRevenueTest
// The study used: starts March 2026, 5 years, operation from month 12 (March 2027), two reservation channels.
class RoomsRevenueTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $owner;
    private User $colleague;
    private HospitalitySector $study;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CountriesSeeder::class, StatesSeeder::class, CurrenciesSeeder::class]);
        $this->company = Company::factory()->create(['users_limit' => 10]);
        $this->company->insertBasicSelects();
        $this->owner = User::factory()->forCompany($this->company)->create();
        $this->colleague = User::factory()->forCompany($this->company)->create();

        $type = fn (string $table) => (int) DB::table($table)->where('company_id', $this->company->id)->where('is_active', 1)->orderBy('id')->skip(1)->value('id');
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

    private function years(): array
    {
        return [1, 2, 3, 4, 5];
    }

    private function byYear($value): array
    {
        return array_fill_keys($this->years(), $value);
    }

    private function months($value): array
    {
        return array_fill_keys(['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'], $value);
    }

    private function payload(array $override = []): array
    {
        $room = $this->study->rooms()->firstOrFail();
        $terms = ['type' => 'system_default', 'interval' => 'monthly', 'rates' => [0, 0, 0, 0], 'due' => [0, 0, 0, 0]];
        $channels = $this->study->salesChannels()->orderBy('id')->get()->values()->map(fn ($channel, $i) => [
            'name' => $channel->name, 'share' => $this->byYear($i === 0 ? 70 : 30), 'discount' => $this->byYear($i === 0 ? 0 : 15), 'terms' => $terms,
        ])->all();

        return array_replace_recursive([
            'currency' => (int) $this->study->main_functional_currency,
            'rooms' => [['key' => (int) $room->room_type_id, 'adr' => 1000, 'escalation' => 10, 'annual' => 3]],
            'exchange' => $this->byYear(1),
            'occupancy' => ['mode' => 'general', 'general' => $this->byYear(60), 'perRoom' => [(int) $room->room_type_id => $this->byYear(0)]],
            'seasonality' => ['type' => 'general', 'interval' => 'flat', 'general' => $this->months(100), 'perRoom' => [(int) $room->room_type_id => $this->months(100)]],
            'channels' => $channels,
            'collection' => ['mode' => 'general', 'general' => $terms],
        ], $override);
    }

    public function test_the_page_lists_the_operating_years_with_part_years(): void
    {
        $this->actingAs($this->owner)->get("/studies/{$this->study->id}/rooms")->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Studies/Rooms')
            ->where('years.0.year', 2027)->where('years.0.months', 10)
            ->where('years.4.year', 2031)->where('years.4.months', 2)
            ->has('years', 5)->where('menu', fn ($menu) => collect($menu)->reject(fn ($m) => preg_match('/^((expenses|investment|statements|financial)\.|dashboard$)/', $m['key']))->count() === 4)->has('form.channels', 2));
    }

    public function test_saving_stores_the_figures_and_works_out_the_adr_at_opening(): void
    {
        $this->actingAs($this->owner)->put("/studies/{$this->study->id}/rooms", $this->payload())
            ->assertRedirect("/studies/{$this->study->id}/rooms")->assertSessionHasNoErrors();

        $room = $this->study->rooms()->firstOrFail()->fresh();
        // March 2026 to March 2027 is one year: 1000 grown by 10% = 1100
        $this->assertEqualsWithDelta(1100.0, (float) $room->average_daily_rate_at_operation_date, 0.01);
        $this->assertEqualsWithDelta(60.0, (float) json_decode($this->study->fresh()->general_occupancy_rate, true)[1], 0.001);
        $this->assertTrue((bool) $this->study->fresh()->has_visit_room_section);
    }

    public function test_the_channel_shares_must_add_up_to_100_in_every_year(): void
    {
        $payload = $this->payload();
        $payload['channels'][0]['share'][3] = 50; // 50 + 30 = 80 in year 3

        $this->actingAs($this->owner)->put("/studies/{$this->study->id}/rooms", $payload)->assertSessionHasErrors(['channels.share.3']);
        $this->assertFalse((bool) $this->study->fresh()->has_visit_room_section);
    }

    public function test_occupancy_cannot_be_over_100(): void
    {
        $payload = $this->payload();
        $payload['occupancy']['general'][2] = 101;

        $this->actingAs($this->owner)->put("/studies/{$this->study->id}/rooms", $payload)->assertSessionHasErrors(['occupancy.general.2']);
    }

    public function test_a_customised_policy_must_total_100(): void
    {
        $payload = $this->payload(['collection' => ['general' => ['type' => 'customize', 'rates' => [40, 30, 20, 5], 'due' => [0, 30, 60, 90]]]]);

        $this->actingAs($this->owner)->put("/studies/{$this->study->id}/rooms", $payload)->assertSessionHasErrors(['collection.general.rates']);
    }

    public function test_a_customised_policy_that_totals_100_is_saved_as_the_old_application_kept_it(): void
    {
        $payload = $this->payload(['collection' => ['general' => ['type' => 'customize', 'rates' => [40, 30, 20, 10], 'due' => [0, 30, 60, 90]]]]);

        $this->actingAs($this->owner)->put("/studies/{$this->study->id}/rooms", $payload)->assertSessionHasNoErrors();

        $saved = json_decode($this->study->fresh()->rooms_general_collection_policy_value, true);
        $this->assertSame(['40', '30', '20', '10'], $saved['rate']);
        $this->assertSame(['0', '30', '60', '90'], $saved['due_in_days']);
    }

    public function test_a_colleague_who_may_only_view_cannot_save_and_another_company_cannot_see_it(): void
    {
        $this->study->sharedWith()->attach($this->colleague->id, ['can_edit' => false]);

        $this->actingAs($this->colleague)->get("/studies/{$this->study->id}/rooms")->assertOk();
        $this->actingAs($this->colleague)->put("/studies/{$this->study->id}/rooms", $this->payload())->assertForbidden();

        $other = Company::factory()->create();
        $stranger = User::factory()->forCompany($other)->create();
        $this->actingAs($stranger)->get("/studies/{$this->study->id}/rooms")->assertNotFound();
    }

    public function test_a_read_only_company_can_look_but_nothing_is_saved(): void
    {
        $this->company->update(['subscription_ends_at' => today()->subDay()]);
        $owner = $this->owner->fresh();   // reload the person so the company is read again

        $this->actingAs($owner)->get("/studies/{$this->study->id}/rooms")->assertOk()->assertInertia(fn (Assert $p) => $p->where('readOnly', true));
        $this->actingAs($owner)->put("/studies/{$this->study->id}/rooms", $this->payload());

        $this->assertFalse((bool) $this->study->fresh()->has_visit_room_section);
    }
}
