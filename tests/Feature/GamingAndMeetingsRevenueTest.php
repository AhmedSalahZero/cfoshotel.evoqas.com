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

// The gaming page and the meeting spaces page. Small, hand-checkable cases. Run:  php artisan test --filter=GamingAndMeetingsRevenueTest
// The study used: starts March 2026, 5 years, operation from month 12 (March 2027), one gaming facility and one meeting facility.
class GamingAndMeetingsRevenueTest extends TestCase
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
        $row = fn (string $table, int $count, int $value) => ['on' => true, 'mode' => 'types', 'rows' => [['type_id' => $type($table), 'count' => $count, 'value' => $value]]];
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
                'casinos' => $row('casino_names', 2, 30), 'meetings' => $row('meeting_names', 3, 200), 'others' => $off,
            ],
            'channels' => ['on' => false, 'names' => []],
        ])->assertSessionHasNoErrors();

        $this->study = HospitalitySector::query()->withoutGlobalScopes()->latest('id')->firstOrFail();
    }

    private function byYear($value): array
    {
        return array_fill_keys([1, 2, 3, 4, 5], $value);
    }

    private function terms(array $override = []): array
    {
        return $override + ['type' => 'system_default', 'interval' => 'monthly', 'rates' => [0, 0, 0, 0], 'due' => [0, 0, 0, 0]];
    }

    private function months(array $values): array
    {
        return array_combine(['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'], $values);
    }

    private function send(string $page, array $payload, ?User $by = null)
    {
        return $this->actingAs($by ?? $this->owner)->put("/studies/{$this->study->id}/{$page}", $payload);
    }

    // ── gaming ──────────────────────────────────────────────

    private function casinoPayload(array $facility = [], array $collection = []): array
    {
        $casino = $this->study->casinos()->firstOrFail();

        return [
            'facilities' => [$facility + [
                'key' => (int) $casino->casino_type_id, 'method' => 'guest_capture_casino_charges_per_guest_method',
                'currency' => (int) $this->study->main_functional_currency, 'value' => 100, 'escalation' => 10, 'annual' => 3,
                'capture' => $this->byYear(15), 'target' => $this->byYear(0), 'rooms' => $this->byYear(0), 'terms' => $this->terms(),
            ]],
            'collection' => $collection + ['mode' => 'general', 'general' => $this->terms()],
        ];
    }

    public function test_the_gaming_page_lists_the_facility_the_years_and_the_study_menu(): void
    {
        $this->actingAs($this->owner)->get("/studies/{$this->study->id}/casinos")->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Studies/Casinos')->has('years', 5)->has('methods', 3)->where('menu', fn ($menu) => collect($menu)->reject(fn ($m) => preg_match('/^((expenses|investment|statements|financial)\.|dashboard$)/', $m['key']))->count() === 6)
            ->has('form.facilities', 1)->where('form.facilities.0.count', 2)->where('form.facilities.0.cover', 30)->where('form.facilities.0.capacity', 60));
    }

    public function test_gaming_saves_the_charges_and_the_guest_capture_and_works_out_the_charges_at_opening(): void
    {
        $this->send('casinos', $this->casinoPayload())->assertRedirect("/studies/{$this->study->id}/casinos")->assertSessionHasNoErrors();

        $casino = $this->study->casinos()->firstOrFail()->fresh();
        $this->assertSame('guest_capture_casino_charges_per_guest_method', $casino->getFAndBFacilities());
        // March 2026 to March 2027 is one year: 100 grown by 10% = 110
        $this->assertEqualsWithDelta(110.0, (float) $casino->charges_value_at_operation_date, 0.01);
        $this->assertSame('15', (string) $casino->guest_capture_cover_percentage[1]);
        $this->assertEmpty($casino->percentage_from_rooms_revenues);
        $this->assertTrue((bool) $this->study->fresh()->has_visit_casino_section);
    }

    public function test_the_guest_target_method_keeps_its_figure_in_the_same_column_as_the_old_application(): void
    {
        $this->send('casinos', $this->casinoPayload(['method' => 'guest_count_target_per_day_charges_per_guest_method', 'target' => $this->byYear(40)]))->assertSessionHasNoErrors();

        $this->assertSame('40', (string) $this->study->casinos()->firstOrFail()->fresh()->guest_capture_cover_percentage[2]);
    }

    public function test_a_percentage_of_rooms_revenue_needs_no_charges_per_guest(): void
    {
        $this->send('casinos', $this->casinoPayload(['method' => 'percentage_from_rooms_revenue', 'rooms' => $this->byYear(12), 'value' => null, 'escalation' => null, 'annual' => null]))->assertSessionHasNoErrors();

        $casino = $this->study->casinos()->firstOrFail()->fresh();
        $this->assertEqualsWithDelta(0.0, (float) $casino->cover_value, 0.0001);
        $this->assertSame('12', (string) $casino->percentage_from_rooms_revenues[1]);
        $this->assertEmpty($casino->guest_capture_cover_percentage);
    }

    public function test_gaming_figures_of_the_chosen_method_are_required_and_limited(): void
    {
        $missing = $this->casinoPayload(['method' => 'guest_count_target_per_day_charges_per_guest_method']);
        $missing['facilities'][0]['target'] = [1 => 5, 2 => 5, 3 => 5, 5 => 5]; // year 4 is missing
        $this->send('casinos', $missing)->assertSessionHasErrors(['facilities.0.target.4']);

        $over = $this->casinoPayload();
        $over['facilities'][0]['capture'][3] = 101;
        $this->send('casinos', $over)->assertSessionHasErrors(['facilities.0.capture.3']);
    }

    public function test_a_colleague_who_may_only_view_cannot_save_gaming_and_another_company_cannot_see_it(): void
    {
        $this->study->sharedWith()->attach($this->colleague->id, ['can_edit' => false]);

        $this->actingAs($this->colleague)->get("/studies/{$this->study->id}/casinos")->assertOk();
        $this->send('casinos', $this->casinoPayload(), $this->colleague)->assertForbidden();

        $stranger = User::factory()->forCompany(Company::factory()->create())->create();
        $this->actingAs($stranger)->get("/studies/{$this->study->id}/casinos")->assertNotFound();
    }

    // ── meeting spaces ──────────────────────────────────────

    private function plan(array $override = []): array
    {
        $key = (int) $this->study->meetings()->firstOrFail()->meeting_type_id;
        $months = $this->months([8, 8, 8, 8, 8, 8, 8, 8, 9, 9, 9, 9]); // adds up to 100
        $quarters = ['quarter-one' => 25, 'quarter-two' => 25, 'quarter-three' => 25, 'quarter-four' => 25];

        return $override + ['type' => 'general', 'interval' => 'flat', 'months' => $months, 'quarters' => $quarters, 'perMonths' => [$key => $months], 'perQuarters' => [$key => $quarters]];
    }

    private function meetingPayload(array $facility = [], array $guestPlan = [], array $rentPlan = [], array $collection = []): array
    {
        $meeting = $this->study->meetings()->firstOrFail();

        return [
            'facilities' => [$facility + [
                'key' => (int) $meeting->meeting_type_id, 'method' => 'guest_count_charges_per_guest_occupancy_rate_method',
                'currency' => (int) $this->study->main_functional_currency, 'value' => 200, 'escalation' => 10, 'annual' => 3,
                'occupancy' => $this->byYear(40), 'fb' => $this->byYear(0), 'terms' => $this->terms(),
            ]],
            'guestPlan' => $this->plan($guestPlan),
            'rentPlan' => $this->plan($rentPlan),
            'collection' => $collection + ['mode' => 'general', 'general' => $this->terms()],
        ];
    }

    public function test_the_meetings_page_lists_the_facility_and_starts_with_plans_that_add_up_to_100(): void
    {
        $this->actingAs($this->owner)->get("/studies/{$this->study->id}/meetings")->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Studies/Meetings')->has('years', 5)->has('form.facilities', 1)->where('form.facilities.0.capacity', 600)
            ->where('form.guestPlan.type', 'general')->where('form.guestPlan.interval', 'flat')
            ->where('form.guestPlan.quarters', fn ($q) => abs(array_sum(collect($q)->all()) - 100) < 0.01)
            ->where('form.guestPlan.months', fn ($m) => abs(array_sum(collect($m)->all()) - 100) < 0.01));
    }

    public function test_the_per_guest_method_saves_charges_occupancy_and_its_plan_only(): void
    {
        $this->send('meetings', $this->meetingPayload())->assertRedirect("/studies/{$this->study->id}/meetings")->assertSessionHasNoErrors();

        $meeting = $this->study->meetings()->firstOrFail()->fresh();
        $study = $this->study->fresh();
        $this->assertSame('guest_count_charges_per_guest_occupancy_rate_method', $meeting->getFAndBFacilities());
        $this->assertEqualsWithDelta(200.0, (float) $meeting->charges_value_per_guest, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $meeting->cover_value, 0.01);
        $this->assertEqualsWithDelta(220.0, (float) $meeting->charges_value_at_operation_date, 0.01); // one year at 10%
        $this->assertSame('40', (string) $meeting->guest_capture_cover_percentage[1]);
        // the guest plan is saved (flat); the rent plan is not used by any facility, so it is empty
        $this->assertSame('general-seasonality', $study->guest_meeting_seasonality_type);
        $this->assertSame('flat-seasonality', $study->guest_meeting_seasonality_interval);
        $this->assertEqualsWithDelta(100 / 12, (float) json_decode($study->guest_meeting_general_seasonality, true)['01'], 0.0001);
        $this->assertNull($study->rent_meeting_seasonality_type);
        $this->assertTrue((bool) $study->has_visit_meeting_section);
    }

    public function test_the_daily_rent_method_keeps_the_rent_in_cover_value_and_uses_the_rent_plan(): void
    {
        $this->send('meetings', $this->meetingPayload(['method' => 'facility_count_daily_rent_occupancy_rate_method', 'value' => 5000]))->assertSessionHasNoErrors();

        $meeting = $this->study->meetings()->firstOrFail()->fresh();
        $this->assertEqualsWithDelta(5000.0, (float) $meeting->cover_value, 0.01);
        $this->assertNull($meeting->charges_value_per_guest);
        $this->assertSame('flat-seasonality', $this->study->fresh()->rent_meeting_seasonality_interval);
        $this->assertNull($this->study->fresh()->guest_meeting_seasonality_type);
    }

    public function test_the_percentage_of_food_and_beverage_needs_no_charges_and_no_plan(): void
    {
        $this->send('meetings', $this->meetingPayload(['method' => 'percentage_from_f_b_revenue', 'fb' => $this->byYear(18), 'value' => null, 'escalation' => null, 'annual' => null]))->assertSessionHasNoErrors();

        $meeting = $this->study->meetings()->firstOrFail()->fresh();
        $this->assertSame('18', (string) $meeting->percentage_from_f_and_b_revenues[1]);
        $this->assertEmpty($meeting->guest_capture_cover_percentage);
        $this->assertNull($this->study->fresh()->guest_meeting_seasonality_type);
    }

    public function test_a_quarterly_or_monthly_plan_must_add_up_to_100(): void
    {
        $short = ['interval' => 'quarterly', 'quarters' => ['quarter-one' => 30, 'quarter-two' => 30, 'quarter-three' => 30, 'quarter-four' => 5]];
        $this->send('meetings', $this->meetingPayload([], $short))->assertSessionHasErrors(['guestPlan.quarters']);

        $good = ['interval' => 'quarterly', 'quarters' => ['quarter-one' => 10, 'quarter-two' => 20, 'quarter-three' => 30, 'quarter-four' => 40]];
        $this->send('meetings', $this->meetingPayload([], $good))->assertSessionHasNoErrors();
        $this->assertSame('40', (string) json_decode($this->study->fresh()->guest_meeting_general_seasonality, true)['quarter-four']);

        $monthly = $this->plan(['interval' => 'monthly']);
        $monthly['months']['12'] = 50;
        $this->send('meetings', $this->meetingPayload([], $monthly))->assertSessionHasErrors(['guestPlan.months']);
    }

    public function test_a_plan_per_facility_is_saved_on_the_facility_and_not_on_the_study(): void
    {
        $this->send('meetings', $this->meetingPayload([], ['type' => 'per_facility', 'interval' => 'monthly']))->assertSessionHasNoErrors();

        $meeting = $this->study->meetings()->firstOrFail()->fresh();
        $this->assertSame('per-meeting-type-seasonality', $this->study->fresh()->guest_meeting_seasonality_type);
        $this->assertNull($this->study->fresh()->guest_meeting_general_seasonality);
        $this->assertSame('9', (string) $meeting->guest_seasonality['12']);
        $this->assertNull($meeting->rent_seasonality);
    }

    public function test_meeting_figures_of_the_chosen_method_are_required_and_limited(): void
    {
        $over = $this->meetingPayload();
        $over['facilities'][0]['occupancy'][2] = 101;
        $this->send('meetings', $over)->assertSessionHasErrors(['facilities.0.occupancy.2']);

        $this->send('meetings', $this->meetingPayload(['method' => 'made_up']))->assertSessionHasErrors(['facilities.0.method']);
    }

    public function test_a_colleague_who_may_only_view_cannot_save_meetings(): void
    {
        $this->study->sharedWith()->attach($this->colleague->id, ['can_edit' => false]);

        $this->actingAs($this->colleague)->get("/studies/{$this->study->id}/meetings")->assertOk();
        $this->send('meetings', $this->meetingPayload(), $this->colleague)->assertForbidden();
    }
}
