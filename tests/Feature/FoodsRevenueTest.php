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

// The food and beverage page. Small, hand-checkable cases. Run:  php artisan test --filter=FoodsRevenueTest
// The study used: starts March 2026, 5 years, operation from month 12 (March 2027), one facility.
class FoodsRevenueTest extends TestCase
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
                'foods' => ['on' => true, 'mode' => 'types', 'rows' => [['type_id' => $type('food_names'), 'count' => 2, 'value' => 50]]],
                'casinos' => $off, 'meetings' => $off, 'others' => $off,
            ],
            'channels' => ['on' => false, 'names' => []],
        ])->assertSessionHasNoErrors();

        $this->study = HospitalitySector::query()->withoutGlobalScopes()->latest('id')->firstOrFail();
    }

    private function byYear($value): array
    {
        return array_fill_keys([1, 2, 3, 4, 5], $value);
    }

    private function payload(array $facility = [], array $collection = []): array
    {
        $food = $this->study->foods()->firstOrFail();
        $terms = ['type' => 'system_default', 'interval' => 'monthly', 'rates' => [0, 0, 0, 0], 'due' => [0, 0, 0, 0]];

        return [
            'facilities' => [array_replace_recursive([
                'key' => (int) $food->food_type_id, 'method' => 'guest_capture_x_cover_value_per_guest_method',
                'currency' => (int) $this->study->main_functional_currency, 'value' => 50, 'escalation' => 10, 'annual' => 3,
                'capture' => $this->byYear(20), 'meals' => $this->byYear(0), 'target' => $this->byYear(0), 'rooms' => $this->byYear(0), 'terms' => $terms,
            ], $facility)],
            'collection' => array_replace_recursive(['mode' => 'general', 'general' => $terms], $collection),
        ];
    }

    private function savePage(array $payload, ?User $by = null)
    {
        return $this->actingAs($by ?? $this->owner)->put("/studies/{$this->study->id}/foods", $payload);
    }

    public function test_the_page_lists_the_facility_and_the_operating_years(): void
    {
        $this->actingAs($this->owner)->get("/studies/{$this->study->id}/foods")->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Studies/Foods')->has('years', 5)->has('methods', 4)->where('menu', fn ($menu) => collect($menu)->reject(fn ($m) => preg_match('/^((expenses|investment|statements|financial)\.|dashboard$)/', $m['key']))->count() === 4)
            ->has('form.facilities', 1)->where('form.facilities.0.count', 2)->where('form.facilities.0.cover', 50)->where('form.facilities.0.capacity', 100));
    }

    public function test_saving_keeps_only_the_figures_of_the_chosen_method_and_works_out_the_value_at_opening(): void
    {
        $this->savePage($this->payload())->assertRedirect("/studies/{$this->study->id}/foods")->assertSessionHasNoErrors();

        $food = $this->study->foods()->firstOrFail()->fresh();
        $this->assertSame('guest_capture_x_cover_value_per_guest_method', $food->getFAndBFacilities());
        // March 2026 to March 2027 is one year: 50 grown by 10% = 55
        $this->assertEqualsWithDelta(55.0, (float) $food->cover_value_at_operation_date, 0.01);
        $this->assertSame('20', (string) $food->guest_capture_cover_percentage[1]);
        $this->assertEmpty($food->cover_per_day);
        $this->assertEmpty($food->meal_per_guest);
        $this->assertTrue((bool) $this->study->fresh()->has_visit_food_section);
    }

    public function test_the_meals_method_keeps_both_series(): void
    {
        $this->savePage($this->payload(['method' => 'guest_capture_x_meals_per_guest_x_cover_value_per_meal_method', 'meals' => $this->byYear(2)]))->assertSessionHasNoErrors();

        $food = $this->study->foods()->firstOrFail()->fresh();
        $this->assertSame('2', (string) $food->meal_per_guest[3]);
        $this->assertSame('20', (string) $food->guest_capture_cover_percentage[3]);
    }

    public function test_the_figures_of_the_chosen_method_are_required_and_limited(): void
    {
        $missing = $this->payload(['method' => 'cover_count_target_per_day_x_cover_value_method']);
        unset($missing['facilities'][0]['target'][2]);
        $this->savePage($missing)->assertSessionHasErrors(['facilities.0.target.2']);

        $over = $this->payload();
        $over['facilities'][0]['capture'][4] = 101;
        $this->savePage($over)->assertSessionHasErrors(['facilities.0.capture.4']);
    }

    public function test_a_percentage_of_rooms_revenue_needs_no_cover_value(): void
    {
        $this->savePage($this->payload(['method' => 'percentage_from_rooms_revenue', 'rooms' => $this->byYear(30), 'value' => null, 'escalation' => null, 'annual' => null]))->assertSessionHasNoErrors();

        $food = $this->study->foods()->firstOrFail()->fresh();
        $this->assertEqualsWithDelta(0.0, (float) $food->cover_value, 0.0001);
        $this->assertSame('30', (string) $food->percentage_from_rooms_revenues[1]);
        $this->assertEmpty($food->guest_capture_cover_percentage);
    }

    public function test_a_method_that_is_not_one_of_the_four_is_refused(): void
    {
        $this->savePage($this->payload(['method' => 'made_up']))->assertSessionHasErrors(['facilities.0.method']);
    }

    public function test_the_policy_of_a_facility_must_total_100_only_when_it_is_set_per_facility(): void
    {
        $short = ['terms' => ['type' => 'customize', 'rates' => [40, 30, 20, 5], 'due' => [0, 30, 60, 90]]];

        $this->savePage($this->payload($short, ['mode' => 'per_facility']))->assertSessionHasErrors(['facilities.0.terms.rates']);

        $this->savePage($this->payload($short, ['mode' => 'general']))->assertSessionHasNoErrors();
        $this->assertSame('general_collection_terms', $this->study->fresh()->food_collection_policy_type);
    }

    public function test_a_policy_per_facility_is_saved_as_the_old_application_kept_it(): void
    {
        $good = ['terms' => ['type' => 'customize', 'rates' => [40, 30, 20, 10], 'due' => [0, 30, 60, 90]]];

        $this->savePage($this->payload($good, ['mode' => 'per_facility']))->assertSessionHasNoErrors();

        $this->assertSame('terms_per_sales_channel', $this->study->fresh()->food_collection_policy_type);
        $saved = json_decode($this->study->foods()->firstOrFail()->fresh()->collection_policy_value, true);
        $this->assertSame(['40', '30', '20', '10'], $saved['rate']);
        $this->assertSame(['0', '30', '60', '90'], $saved['due_in_days']);
    }

    public function test_a_colleague_who_may_only_view_cannot_save_and_another_company_cannot_see_it(): void
    {
        $this->study->sharedWith()->attach($this->colleague->id, ['can_edit' => false]);

        $this->actingAs($this->colleague)->get("/studies/{$this->study->id}/foods")->assertOk();
        $this->savePage($this->payload(), $this->colleague)->assertForbidden();

        $stranger = User::factory()->forCompany(Company::factory()->create())->create();
        $this->actingAs($stranger)->get("/studies/{$this->study->id}/foods")->assertNotFound();
    }
}
