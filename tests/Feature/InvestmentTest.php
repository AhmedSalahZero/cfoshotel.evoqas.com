<?php

namespace Tests\Feature;

use App\Models\Acquisition;
use App\Models\Company;
use App\Models\Currency;
use App\Models\FFE;
use App\Models\FFEItem;
use App\Models\HospitalitySector;
use App\Models\Loan;
use App\Models\PropertyAcquisition;
use App\Models\PropertyAcquisitionBreakDown;
use App\Models\User;
use Database\Seeders\CountriesSeeder;
use Database\Seeders\CurrenciesSeeder;
use Database\Seeders\StatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// The investment pages: land & construction, property acquisition, FF&E (with the loan form). Small, hand-checkable cases.
// Run:  php artisan test --filter=InvestmentTest
// The study used: starts March 2026, 5 years, operation from month 12 (March 2027), development 0 months, one facility.
class InvestmentTest extends TestCase
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

    private function url(string $page): string
    {
        return "/studies/{$this->study->id}/{$page}";
    }

    private function props(string $page, ?User $by = null): array
    {
        return json_decode(json_encode($this->actingAs($by ?? $this->owner)->get($this->url($page))->assertOk()->original->getData()['page']['props']), true);
    }

    private function save(string $page, array $form, ?User $by = null)
    {
        return $this->actingAs($by ?? $this->owner)->put($this->url($page), $form);
    }

    private function loan(array $over = []): array
    {
        return array_replace(['loan_type' => 'normal', 'base_rate' => 0, 'margin_rate' => 0, 'period' => 0, 'grace_period' => 0, 'installment_interval' => 'quartly',
            'step_up_rate' => 0, 'step_up_interval' => 'annually', 'step_down_rate' => 0, 'step_down_interval' => 'annually'], $over);
    }

    // ---------------------------------------------------------------- land & construction

    private function landForm(array $over = []): array
    {
        $form = $this->props('land-acquisition-costs')['form'];

        return array_replace_recursive($form, $over);
    }

    public function test_the_land_page_starts_blank_with_the_months_and_the_old_defaults(): void
    {
        $props = $this->props('land-acquisition-costs');
        // March 2026 to March 2027, the opening month included
        $this->assertCount(13, $props['months']);
        $this->assertSame('Mar 2026', $props['months'][0]['label']);
        $this->assertSame(12, $props['opening']);
        $form = $props['form'];
        $this->assertSame('cash', $form['land']['method']);
        $this->assertEquals(100, $form['land']['equity']);
        $this->assertEquals(100, $form['hard']['equity']);
        $this->assertSame('straight-line', $form['hard']['method']);
        $this->assertSame(0, $form['hard']['start']);
        $this->assertSame(['land', 'hard_cost_construction'], array_keys($form['loans']));
        $this->assertSame('normal', $form['loans']['land']['loan_type']);
    }

    public function test_the_land_and_the_constructions_are_saved_as_the_old_application_kept_them(): void
    {
        $form = $this->landForm([
            'land' => ['date' => 1, 'cost' => 1000000, 'contingency' => 5, 'method' => 'cash', 'equity' => 100],
            'hard' => ['cost' => 8000000, 'contingency' => 10, 'start' => 2, 'duration' => 6, 'method' => 's-curve', 'down' => 20, 'one' => 30, 'dueOne' => 30, 'dueTwo' => 60, 'equity' => 100],
            'soft' => ['cost' => 500000, 'contingency' => 0, 'start' => 0, 'duration' => 4, 'method' => 'steady-growth', 'down' => 100, 'one' => 0, 'dueOne' => 0, 'dueTwo' => 0],
        ]);
        $this->save('land-acquisition-costs', $form)->assertRedirect($this->url('land-acquisition-costs'))->assertSessionHasNoErrors();

        $a = Acquisition::query()->where('hospitality_sector_id', $this->study->id)->firstOrFail();
        $this->assertSame('1', (string) $a->purchase_date);
        $this->assertEquals(1000000, (float) $a->land_purchase_cost);
        $this->assertEquals(1050000, $a->getTotalPurchaseCost());
        $this->assertSame('cash', $a->land_payment_method);
        $this->assertEquals(100, (float) $a->land_equity_funding_rate);
        $this->assertSame('1', (string) $a->land_installment_count);
        $this->assertSame('0', (string) $a->first_land_down_payment_percentage);
        $this->assertSame('0', (string) $a->installment_interval);
        $this->assertSame('customize', $a->collection_policy_type);
        $this->assertSame(1, (int) $a->has_land_section);

        $this->assertSame('2', (string) $a->hard_construction_start_date);
        $this->assertSame('6', (string) $a->hard_construction_duration);
        $this->assertSame('8', (string) $a->hard_construction_end_date);
        $this->assertSame('s-curve', $a->hard_execution_method);
        $this->assertEquals(50, (float) $a->hard_balance_rate_two);
        $this->assertEquals(8800000, $a->getTotalHardConstructionCost());
        $this->assertSame('4', (string) $a->soft_construction_end_date);
        $this->assertEquals(0, (float) $a->soft_balance_rate_two);
        $this->assertSame('steady-growth', $a->soft_execution_method);
    }

    public function test_saving_again_changes_the_same_record_and_loans(): void
    {
        $form = $this->landForm(['land' => ['cost' => 100, 'equity' => 60], 'loans' => ['land' => $this->loan(['period' => 60, 'base_rate' => 3, 'margin_rate' => 1.5])]]);
        $this->save('land-acquisition-costs', $form)->assertSessionHasNoErrors();
        $ids = [Acquisition::query()->pluck('id')->all(), Loan::query()->pluck('id')->all()];

        $form['land']['cost'] = 250;
        $this->save('land-acquisition-costs', $form)->assertSessionHasNoErrors();

        $this->assertSame($ids, [Acquisition::query()->pluck('id')->all(), Loan::query()->pluck('id')->all()]);
        $this->assertEquals(250, (float) Acquisition::query()->firstOrFail()->land_purchase_cost);
        $this->assertSame(['hard_cost_construction', 'land'], Loan::query()->orderBy('section_name')->pluck('section_name')->all());
    }

    public function test_an_installment_purchase_keeps_its_terms_and_a_cash_purchase_clears_them(): void
    {
        $form = $this->landForm(['land' => ['cost' => 1000, 'method' => 'installment', 'first' => 20, 'second' => 30, 'after' => 6, 'count' => 4, 'interval' => 'quarterly']]);
        $this->save('land-acquisition-costs', $form)->assertSessionHasNoErrors();
        $a = Acquisition::query()->firstOrFail();
        $this->assertEquals(20, (float) $a->first_land_down_payment_percentage);
        $this->assertEquals(30, (float) $a->second_land_down_payment_percentage);
        $this->assertEquals(50, $a->getLandBalanceRate());
        $this->assertSame('quarterly', $a->installment_interval);
        $this->assertSame('4', (string) $a->land_installment_count);

        $form['land']['method'] = 'cash';
        $this->save('land-acquisition-costs', $form)->assertSessionHasNoErrors();
        $a = Acquisition::query()->firstOrFail();
        $this->assertSame('0', (string) $a->first_land_down_payment_percentage);
        $this->assertSame('0', (string) $a->installment_interval);
    }

    public function test_a_customised_collection_is_kept_as_json_and_must_add_up_to_100(): void
    {
        $form = $this->landForm(['land' => ['cost' => 1000, 'method' => 'customize', 'terms' => ['rates' => [40, 30, 20, 5], 'due' => [0, 30, 60, 90]]]]);
        $this->save('land-acquisition-costs', $form)->assertSessionHasErrors(['land.terms.rates']);
        $this->assertSame(0, Acquisition::query()->count());

        $form['land']['terms']['rates'] = [40, 30, 20, 10];
        $this->save('land-acquisition-costs', $form)->assertSessionHasNoErrors();
        $a = Acquisition::query()->firstOrFail();
        $this->assertSame(['40', '30', '20', '10'], (array) json_decode($a->collection_policy_value, true)['rate']);
        $this->assertSame(['0', '30', '60', '90'], (array) json_decode($a->collection_policy_value, true)['due_in_days']);
        $this->assertEquals(30, (float) $a->getSalesChannelRateAndDueInDays(1, 'rate'));
    }

    public function test_the_checks_of_the_land_page(): void
    {
        $bad = $this->landForm([
            'land' => ['date' => 99, 'cost' => -1, 'contingency' => 150, 'method' => 'barter', 'equity' => 120],
            'hard' => ['start' => 11, 'duration' => 5, 'down' => 80, 'one' => 40, 'dueOne' => 31],
        ]);
        $this->save('land-acquisition-costs', $bad)->assertSessionHasErrors(['land.date', 'land.cost', 'land.contingency', 'land.method', 'land.equity', 'hard.dueOne']);

        // the construction ends 4 months after the opening; the two balances use more than 100 %
        $late = $this->landForm(['hard' => ['start' => 11, 'duration' => 5, 'down' => 80, 'one' => 40]]);
        $this->save('land-acquisition-costs', $late)->assertSessionHasErrors(['hard.one', 'hard.duration']);
        $this->assertSame(0, Acquisition::query()->count());
    }

    public function test_the_two_down_payments_may_not_be_more_than_100(): void
    {
        $form = $this->landForm(['land' => ['method' => 'installment', 'first' => 70, 'second' => 40, 'count' => 2, 'interval' => 'monthly']]);
        $this->save('land-acquisition-costs', $form)->assertSessionHasErrors(['land.second']);
    }

    // ---------------------------------------------------------------- loan form

    public function test_a_loan_is_asked_for_only_when_there_is_debt(): void
    {
        // no debt: an empty loan is fine
        $this->save('land-acquisition-costs', $this->landForm())->assertSessionHasNoErrors();
        // debt: the tenor and the installment interval are needed
        $form = $this->landForm(['land' => ['equity' => 70]]);
        $this->save('land-acquisition-costs', $form)->assertSessionHasErrors(['loans.land.period']);
        $form['loans']['land'] = $this->loan(['period' => 60]);
        $this->save('land-acquisition-costs', $form)->assertSessionHasNoErrors();
    }

    public function test_the_loan_keeps_pricing_and_clears_what_its_type_does_not_use(): void
    {
        $form = $this->landForm(['land' => ['equity' => 50], 'loans' => ['land' => $this->loan([
            'loan_type' => 'step-up', 'base_rate' => 3.5, 'margin_rate' => 1.25, 'period' => 84, 'grace_period' => 12, 'step_up_rate' => 1, 'step_up_interval' => 'semi annually', 'step_down_rate' => -2,
        ])]]);
        $this->save('land-acquisition-costs', $form)->assertSessionHasNoErrors();

        $loan = Acquisition::query()->firstOrFail()->getLoanForSection(LAND_LOAN);
        $this->assertSame('step-up', $loan->loan_type);
        $this->assertEquals(4.75, (float) $loan->pricing);
        $this->assertSame('84', (string) $loan->period);
        // a step-up loan has no grace period and no step-down
        $this->assertSame('0', (string) $loan->grace_period);
        $this->assertEquals(0, (float) $loan->step_down_rate);
        $this->assertEquals(1, (float) $loan->step_up_rate);
        $this->assertSame('semi annually', $loan->step_up_interval);
        $this->assertNull($loan->capitalization_type);
    }

    public function test_a_grace_loan_knows_its_capitalization_and_leaves_two_months_of_the_tenor(): void
    {
        $loan = $this->loan(['loan_type' => 'grace_step-down_with_capitalization', 'period' => 24, 'grace_period' => 6, 'step_down_rate' => -2, 'step_down_interval' => 'annually']);
        $form = $this->landForm(['land' => ['equity' => 50], 'loans' => ['land' => $loan]]);
        $this->save('land-acquisition-costs', $form)->assertSessionHasNoErrors();
        $saved = Acquisition::query()->firstOrFail()->getLoanForSection(LAND_LOAN);
        $this->assertSame('with_capitalization', $saved->capitalization_type);
        $this->assertSame('6', (string) $saved->grace_period);
        $this->assertEquals(-2, (float) $saved->step_down_rate);

        $form['loans']['land']['grace_period'] = 23;
        $this->save('land-acquisition-costs', $form)->assertSessionHasErrors(['loans.land.grace_period']);
        $form['loans']['land']['loan_type'] = 'grace_period_without_capitalization';
        $form['loans']['land']['grace_period'] = 6;
        $this->save('land-acquisition-costs', $form)->assertSessionHasNoErrors();
        $this->assertSame('without_capitalization', Acquisition::query()->firstOrFail()->getLoanForSection(LAND_LOAN)->capitalization_type);
    }

    public function test_the_step_down_rate_is_negative_and_the_intervals_are_the_old_ones(): void
    {
        $form = $this->landForm(['land' => ['equity' => 50], 'loans' => ['land' => $this->loan(['loan_type' => 'step-down', 'period' => 24, 'step_down_rate' => 3])]]);
        $this->save('land-acquisition-costs', $form)->assertSessionHasErrors(['loans.land.step_down_rate']);
        $form['loans']['land']['step_down_rate'] = -3;
        $form['loans']['land']['installment_interval'] = 'quarterly';
        $this->save('land-acquisition-costs', $form)->assertSessionHasErrors(['loans.land.installment_interval']);
    }

    // ---------------------------------------------------------------- property

    private function propertyForm(array $over = []): array
    {
        return array_replace_recursive($this->props('property-acquisition-costs')['form'], $over);
    }

    public function test_the_property_page_starts_with_three_breakdown_lines(): void
    {
        $props = $this->props('property-acquisition-costs');
        $this->assertSame(['Land Cost', 'Building Cost', 'Furniture, Fixture & Equipment Cost'], array_column($props['form']['breakdown'], 'name'));
        $this->assertSame([0, 3, 3], array_column($props['form']['breakdown'], 'depreciation'));
        $this->assertSame('cash', $props['form']['property']['method']);
        $this->assertSame(['property_loan'], array_keys($props['form']['loans']));
    }

    public function test_the_property_and_its_breakdown_are_saved_as_the_old_application_kept_them(): void
    {
        $form = $this->propertyForm([
            'property' => ['date' => 0, 'cost' => 330000000, 'contingency' => 0, 'method' => 'cash', 'equity' => 100],
            'breakdown' => [['percentage' => 20], ['percentage' => 70, 'depreciation' => 25], ['percentage' => 10, 'depreciation' => 10]],
            'replacement' => ['rate' => 2, 'interval' => 3, 'ffeRate' => 4, 'ffeInterval' => 5],
        ]);
        $this->save('property-acquisition-costs', $form)->assertRedirect($this->url('property-acquisition-costs'))->assertSessionHasNoErrors();

        $p = PropertyAcquisition::query()->where('hospitality_sector_id', $this->study->id)->firstOrFail();
        $this->assertEquals(330000000, (float) $p->property_purchase_cost);
        $this->assertSame('3', (string) $p->replacement_interval);
        $this->assertEquals(4, (float) $p->ffe_replacement_cost_rate);
        $this->assertSame('Refurbishment & Renovation', $p->replacement_cost_name);

        $rows = PropertyAcquisitionBreakDown::query()->where('hospitality_sector_id', $this->study->id)->orderBy('id')->get();
        $this->assertSame(['Land Cost', 'Building Cost', 'Furniture, Fixture & Equipment Cost'], $rows->pluck('name')->all());
        $this->assertSame(['property', 'property', 'property'], $rows->pluck('model_name')->all());
        $this->assertSame(['property_acquisition'], $rows->pluck('section_name')->unique()->values()->all());
        $this->assertEquals([66000000, 231000000, 33000000], $rows->map(fn ($r) => (float) $r->item_amount)->all());
        $this->assertSame([null, '25', '10'], $rows->pluck('depreciation_duration')->all());
        // what the calculation reads
        $this->assertEquals(70, $p->getBuildingPropertyPercentage());
        $this->assertEquals(10, $p->getFFEPropertyAmountPercentage());
        $this->assertEquals(20, $p->getLandPropertyAmountPercentage());
    }

    public function test_the_breakdown_is_written_again_on_every_save_and_must_add_up_to_100(): void
    {
        $form = $this->propertyForm(['property' => ['cost' => 1000], 'breakdown' => [['percentage' => 20], ['percentage' => 70], ['percentage' => 5]]]);
        $this->save('property-acquisition-costs', $form)->assertSessionHasErrors(['breakdown']);
        $this->assertSame(0, PropertyAcquisitionBreakDown::query()->count());

        $form['breakdown'][2]['percentage'] = 10;
        $this->save('property-acquisition-costs', $form)->assertSessionHasNoErrors();
        $this->save('property-acquisition-costs', $form)->assertSessionHasNoErrors();
        $this->assertSame(3, PropertyAcquisitionBreakDown::query()->count());
        $this->assertSame(1, PropertyAcquisition::query()->count());
    }

    public function test_the_building_may_be_depreciated_over_50_years_but_the_furniture_not(): void
    {
        $form = $this->propertyForm(['property' => ['cost' => 1000], 'breakdown' => [['percentage' => 20], ['percentage' => 70, 'depreciation' => 50], ['percentage' => 10, 'depreciation' => 50]]]);
        $this->save('property-acquisition-costs', $form)->assertSessionHasErrors(['breakdown.2.depreciation']);
        $form['breakdown'][2]['depreciation'] = 10;
        $this->save('property-acquisition-costs', $form)->assertSessionHasNoErrors();
        $this->assertSame('50', (string) PropertyAcquisitionBreakDown::query()->where('name', 'Building Cost')->value('depreciation_duration'));
    }

    public function test_the_property_loan_is_kept_in_its_own_section(): void
    {
        $form = $this->propertyForm([
            'property' => ['cost' => 1000, 'equity' => 40],
            'breakdown' => [['percentage' => 20], ['percentage' => 70], ['percentage' => 10]],
            'loans' => ['property_loan' => $this->loan(['period' => 120, 'base_rate' => 4, 'margin_rate' => 2])],
        ]);
        $this->save('property-acquisition-costs', $form)->assertSessionHasNoErrors();
        $loan = PropertyAcquisition::query()->firstOrFail()->getLoanForSection(PROPERTY_LOAN);
        $this->assertSame('property_loan', $loan->section_name);
        $this->assertEquals(6, (float) $loan->pricing);
        $this->assertSame(1, Loan::query()->count());
    }

    // ---------------------------------------------------------------- FF&E

    private function ffeForm(array $over = []): array
    {
        return array_replace_recursive($this->props('ffe-cost')['form'], $over);
    }

    private function item(array $over = []): array
    {
        return array_replace(['id' => null, 'name' => 'Beds', 'currency' => (int) $this->study->main_functional_currency, 'cost' => 1000, 'contingency' => 20, 'depreciation' => 7, 'replacementRate' => 5, 'replacementInterval' => 2], $over);
    }

    public function test_the_ffe_page_has_ten_blank_items(): void
    {
        $props = $this->props('ffe-cost');
        $this->assertCount(10, $props['form']['rows']);
        $this->assertSame('', $props['form']['rows'][0]['name']);
        $this->assertSame(3, $props['form']['rows'][0]['depreciation']);
        $this->assertSame(1, $props['form']['rows'][0]['replacementInterval']);
        $this->assertCount(1, $props['currencies']);
        $this->assertEquals(100, $props['form']['equity']);
    }

    public function test_ffe_items_and_dates_are_saved_as_the_old_application_kept_them(): void
    {
        $form = $this->ffeForm(['dates' => ['start' => 1, 'duration' => 6, 'method' => 'steady-decline'], 'terms' => ['down' => 25, 'one' => 25, 'dueOne' => 15, 'dueTwo' => 45]]);
        $form['rows'][0] = $this->item();
        $form['rows'][1] = $this->item(['name' => 'TVs', 'cost' => 500, 'contingency' => 0]);
        $this->save('ffe-cost', $form)->assertRedirect($this->url('ffe-cost'))->assertSessionHasNoErrors();

        $ffe = FFE::query()->where('hospitality_sector_id', $this->study->id)->firstOrFail();
        $this->assertSame('1', (string) $ffe->start_date);
        $this->assertSame('6', (string) $ffe->duration);
        $this->assertSame('7', (string) $ffe->end_date);
        $this->assertSame('steady-decline', $ffe->execution_method);
        $this->assertEquals(50, (float) $ffe->balance_rate_two);
        $this->assertEquals(100, (float) $ffe->ffe_equity_funding);

        $items = FFEItem::query()->where('hospitality_sector_id', $this->study->id)->orderBy('id')->get();
        $this->assertSame(['Beds', 'TVs'], $items->pluck('name')->all());
        $this->assertSame(['ffe_cost'], $items->pluck('section_name')->unique()->values()->all());
        $this->assertSame(['ffe'], $items->pluck('model_name')->unique()->values()->all());
        $this->assertSame([(string) $ffe->id], $items->pluck('ffe_id')->map(fn ($v) => (string) $v)->unique()->values()->all());
        $this->assertEquals(1200, $items[0]->getTotalCost());
        $this->assertSame(7, (int) $items[0]->depreciation_duration);
        $this->assertEquals(1700, $ffe->getTotalItemsCost());
    }

    public function test_an_item_is_updated_by_its_id_and_removed_when_its_name_is_cleared(): void
    {
        $form = $this->ffeForm();
        $form['rows'][0] = $this->item();
        $form['rows'][1] = $this->item(['name' => 'TVs']);
        $this->save('ffe-cost', $form)->assertSessionHasNoErrors();
        $ids = FFEItem::query()->orderBy('id')->pluck('id')->all();

        $form = $this->ffeForm();
        $this->assertSame($ids, [$form['rows'][0]['id'], $form['rows'][1]['id']]);
        $form['rows'][0]['cost'] = 2500;
        $form['rows'][1]['name'] = '';
        $this->save('ffe-cost', $form)->assertSessionHasNoErrors();

        $items = FFEItem::query()->get();
        $this->assertCount(1, $items);
        $this->assertSame($ids[0], $items[0]->id);
        $this->assertEquals(2500, (float) $items[0]->item_cost);
    }

    public function test_the_checks_of_the_ffe_page(): void
    {
        $form = $this->ffeForm(['dates' => ['start' => 10, 'duration' => 6]]);
        $form['rows'][0] = $this->item(['currency' => 999999, 'cost' => -5, 'contingency' => 120, 'depreciation' => 2, 'replacementInterval' => 9]);
        $this->save('ffe-cost', $form)->assertSessionHasErrors(['rows.0.currency', 'rows.0.cost', 'rows.0.contingency', 'rows.0.depreciation', 'rows.0.replacementInterval']);

        $form['rows'][0] = $this->item();
        $form['rows'][1] = $this->item(['name' => 'beds']);
        $this->save('ffe-cost', $form)->assertSessionHasErrors(['dates.duration', 'rows']);
        $this->assertSame(0, FFE::query()->count());
    }

    public function test_the_ffe_loan_is_found_by_the_ffe_and_the_legacy_loan_comes_first(): void
    {
        $form = $this->ffeForm(['equity' => 50, 'loans' => ['ffe_cost' => $this->loan(['period' => 36, 'base_rate' => 2, 'margin_rate' => 1])]]);
        $form['rows'][0] = $this->item();
        $this->save('ffe-cost', $form)->assertSessionHasNoErrors();
        $ffe = FFE::query()->firstOrFail();
        $loan = $ffe->getLoanForSection(FFE_COST);
        $this->assertSame((string) $ffe->id, (string) $loan->ffe_id);
        $this->assertEquals(3, (float) $loan->pricing);

        // saving again changes the same loan
        $form['loans']['ffe_cost']['period'] = 48;
        $this->save('ffe-cost', $form)->assertSessionHasNoErrors();
        $this->assertSame(1, Loan::query()->where('section_name', FFE_COST)->count());
        $this->assertSame('48', (string) FFE::query()->firstOrFail()->getLoanForSection(FFE_COST)->period);

        // a loan of the old application is stored under the FF&E's own id; it is the one that is read
        Loan::query()->delete();
        $old = new Loan(['section_name' => FFE_COST, 'ffe_id' => $ffe->id, 'company_id' => $this->company->id, 'loan_type' => 'normal', 'period' => '99']);
        $old->id = $ffe->id + 1000;
        $old->save();
        $legacy = new Loan(['section_name' => FFE_COST, 'ffe_id' => $ffe->id, 'company_id' => $this->company->id, 'loan_type' => 'normal', 'period' => '77']);
        $legacy->id = $ffe->id;
        $legacy->save();
        $this->assertSame('77', (string) FFE::query()->firstOrFail()->getLoanForSection(FFE_COST)->period);
    }

    // ---------------------------------------------------------------- the whole

    public function test_the_menu_lists_the_investment_pages_and_ticks_them_once_saved(): void
    {
        $find = fn (array $menu, string $key) => collect($menu)->firstWhere('key', $key);
        $menu = $this->props('land-acquisition-costs')['menu'];
        foreach (['investment.property', 'investment.land', 'investment.ffe'] as $key) {
            $this->assertFalse($find($menu, $key)['done']);
        }
        $keys = collect($menu)->pluck('key');
        $this->assertLessThan($keys->search('expenses.rooms'), $keys->search('investment.property'));

        $this->save('land-acquisition-costs', $this->landForm())->assertSessionHasNoErrors();
        $menu = $this->props('property-acquisition-costs')['menu'];
        $this->assertTrue($find($menu, 'investment.land')['done']);
        $this->assertFalse($find($menu, 'investment.property')['done']);
    }

    public function test_a_colleague_who_may_only_view_cannot_save_and_another_company_cannot_see_the_pages(): void
    {
        $this->study->sharedWith()->attach($this->colleague->id, ['can_edit' => false]);
        $stranger = User::factory()->forCompany(Company::factory()->create())->create();

        foreach (['land-acquisition-costs', 'property-acquisition-costs', 'ffe-cost'] as $page) {
            $this->actingAs($this->colleague)->get($this->url($page))->assertOk();
            $this->save($page, $this->props($page)['form'], $this->colleague)->assertForbidden();
            $this->actingAs($stranger)->get($this->url($page))->assertNotFound();
        }
    }
}
