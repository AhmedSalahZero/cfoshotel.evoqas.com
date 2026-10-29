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

// The other revenue page. Small, hand-checkable cases. Run:  php artisan test --filter=OthersRevenueTest
// The study used: starts March 2026, 5 years, operation from month 12 (March 2027), one other-revenue facility (3 units).
class OthersRevenueTest extends TestCase
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
        $row = fn (string $table, int $count, ?int $value) => ['on' => true, 'mode' => 'types', 'rows' => [['type_id' => $type($table), 'count' => $count, 'value' => $value]]];
        $off = ['on' => false, 'mode' => 'types', 'rows' => [['type_id' => null, 'count' => null, 'value' => null]]];

        $this->actingAs($this->owner)->post('/studies', [
            'study_name' => 'Nile', 'property_name' => 'Nile', 'property_status' => 'existing', 'star_rating' => 5,
            'country_id' => null, 'state_id' => null, 'region' => 'Zamalek',
            'study_start' => '2026-03', 'duration_in_years' => 5,
            'development_start_month' => 0, 'development_duration' => 0, 'operation_start_month' => 12,
            'financial_year_start_month' => 'january', 'main_functional_currency' => (int) Currency::query()->orderBy('id')->value('id'),
            'additional_currency' => null, 'exchange_rate' => 1, 'corporate_taxes_rate' => 20, 'investment_return_rate' => 10, 'perpetual_growth_rate' => 2,
            'streams' => [
                'rooms' => $row('room_names', 100, 2), 'foods' => $row('food_names', 2, 50),
                'casinos' => $off, 'meetings' => $off, 'others' => $row('other_names', 3, null),
            ],
            'channels' => ['on' => false, 'names' => []],
        ])->assertSessionHasNoErrors();

        $this->study = HospitalitySector::query()->withoutGlobalScopes()->latest('id')->firstOrFail();
    }

    private function byYear($value): array
    {
        return array_fill_keys([1, 2, 3, 4, 5], $value);
    }

    private function terms(): array
    {
        return ['type' => 'system_default', 'interval' => 'monthly', 'rates' => [0, 0, 0, 0], 'due' => [0, 0, 0, 0]];
    }

    private function payload(array $facility = []): array
    {
        $other = $this->study->others()->firstOrFail();

        return [
            'facilities' => [$facility + [
                'key' => (int) $other->other_type_id, 'method' => 'guest_capture_x_charges_per_guest_method',
                'currency' => (int) $this->study->main_functional_currency, 'value' => 100, 'escalation' => 10, 'annual' => 3,
                'capture' => $this->byYear(15), 'rooms' => $this->byYear(0), 'terms' => $this->terms(),
            ]],
            'collection' => ['mode' => 'general', 'general' => $this->terms()],
        ];
    }

    private function save(array $payload, ?User $by = null)
    {
        return $this->actingAs($by ?? $this->owner)->put("/studies/{$this->study->id}/others", $payload);
    }

    public function test_the_page_lists_the_facility_the_years_and_the_study_menu(): void
    {
        $this->actingAs($this->owner)->get("/studies/{$this->study->id}/others")->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Studies/Others')->has('years', 5)->has('methods', 2)->where('menu', fn ($menu) => collect($menu)->reject(fn ($m) => preg_match('/^((expenses|investment|statements|financial)\.|dashboard$)/', $m['key']))->count() === 5)
            ->has('form.facilities', 1)->where('form.facilities.0.count', 3));
    }

    public function test_it_saves_the_charges_and_the_guest_capture_and_works_out_the_charges_at_opening(): void
    {
        $this->save($this->payload())->assertRedirect("/studies/{$this->study->id}/others")->assertSessionHasNoErrors();

        $other = $this->study->others()->firstOrFail()->fresh();
        $this->assertSame('guest_capture_x_charges_per_guest_method', $other->getFAndBFacilities());
        // March 2026 to March 2027 is one year: 100 grown by 10% = 110
        $this->assertEqualsWithDelta(110.0, (float) $other->charges_per_guest_at_operation_date, 0.01);
        $this->assertSame('15', (string) $other->guest_capture_cover_percentage[1]);
        $this->assertEmpty($other->percentage_from_rooms_revenues);
        $this->assertTrue((bool) $this->study->fresh()->has_visit_other_section);
    }

    public function test_a_percentage_of_rooms_revenue_needs_no_charges_per_guest(): void
    {
        $this->save($this->payload(['method' => 'percentage_from_rooms_revenue', 'rooms' => $this->byYear(12), 'value' => null, 'escalation' => null, 'annual' => null]))->assertSessionHasNoErrors();

        $other = $this->study->others()->firstOrFail()->fresh();
        $this->assertEqualsWithDelta(0.0, (float) $other->charges_per_guest, 0.0001);
        $this->assertSame('12', (string) $other->percentage_from_rooms_revenues[1]);
        $this->assertEmpty($other->guest_capture_cover_percentage);
    }

    public function test_the_figures_of_the_chosen_method_are_required_and_limited(): void
    {
        $missing = $this->payload();
        $missing['facilities'][0]['capture'] = [1 => 5, 2 => 5, 3 => 5, 5 => 5]; // year 4 is missing
        $this->save($missing)->assertSessionHasErrors(['facilities.0.capture.4']);

        $over = $this->payload();
        $over['facilities'][0]['capture'][3] = 101;
        $this->save($over)->assertSessionHasErrors(['facilities.0.capture.3']);
    }

    public function test_a_colleague_who_may_only_view_cannot_save_and_another_company_cannot_see_it(): void
    {
        $this->study->sharedWith()->attach($this->colleague->id, ['can_edit' => false]);

        $this->actingAs($this->colleague)->get("/studies/{$this->study->id}/others")->assertOk();
        $this->save($this->payload(), $this->colleague)->assertForbidden();

        $stranger = User::factory()->forCompany(Company::factory()->create())->create();
        $this->actingAs($stranger)->get("/studies/{$this->study->id}/others")->assertNotFound();
    }
}
