<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Currency;
use App\Models\DepartmentExpense;
use App\Models\HospitalitySector;
use App\Support\ExpensePages;
use App\Models\User;
use Database\Seeders\CountriesSeeder;
use Database\Seeders\CurrenciesSeeder;
use Database\Seeders\StatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

// The expense pages (rooms, property and the switched-off streams). Small, hand-checkable cases. Run:  php artisan test --filter=ExpensesTest
// The study used: starts March 2026, 5 years, operation from month 12 (March 2027), one facility.
class ExpensesTest extends TestCase
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

    private function url(string $page = 'rooms'): string
    {
        return "/studies/{$this->study->id}/expenses/{$page}";
    }

    /** The form the screen would show (the saved lines and the empty ones), ready to be changed and sent back. */
    private function form(string $page = 'rooms', ?User $by = null): array
    {
        $response = $this->actingAs($by ?? $this->owner)->get($this->url($page))->assertOk();

        return $response->original->getData()['page']['props']['form'];
    }

    private function savePage(array $form, string $page = 'rooms', ?User $by = null)
    {
        return $this->actingAs($by ?? $this->owner)->put($this->url($page), $form);
    }

    private function rows(string $model, string $section)
    {
        return DepartmentExpense::query()->where('hospitality_sector_id', $this->study->id)->where('model_name', $model)->where('section_name', $section)->orderBy('id')->get();
    }

    public function test_the_page_lists_the_tables_of_the_department_the_years_and_the_revenue_row(): void
    {
        $this->actingAs($this->owner)->get($this->url())->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Studies/Expenses')->where('page', 'rooms')->has('years', 5)->has('layout', 5)->missing('revenue')
            ->has('form.sections.'.ExpensePages::PERCENT_ROOMS, 5)->has('form.sections.'.ExpensePages::MANPOWER, 12)
            ->where('layout.0.kind', 'percent')->where('layout.4.kind', 'manpower'));
    }

    public function test_a_stream_that_is_switched_off_and_an_unknown_page_do_not_exist(): void
    {
        $this->actingAs($this->owner)->get($this->url('casinos'))->assertNotFound();
        $this->actingAs($this->owner)->get($this->url('nothing'))->assertNotFound();
    }

    public function test_the_menu_lists_the_expense_pages_of_the_streams_in_use(): void
    {
        $this->actingAs($this->owner)->get($this->url())->assertInertia(fn (Assert $p) => $p
            ->where('menu', fn ($menu) => collect($menu)->pluck('key')->filter(fn ($k) => str_starts_with($k, 'expenses.'))->values()->all()
                === ['expenses.rooms', 'expenses.foods', 'expenses.general', 'expenses.marketing', 'expenses.property', 'expenses.energy', 'expenses.management', 'expenses.startup', 'expenses.results']));
    }

    public function test_saving_keeps_the_lines_with_a_name_and_works_out_the_cost_at_opening(): void
    {
        $form = $this->form();
        $currency = (int) $this->study->main_functional_currency;
        $form['sections'][ExpensePages::PERCENT_ROOMS][0] = ['id' => null, 'name' => 'Commission', 'values' => $this->byYear(12.5)];
        $form['sections'][ExpensePages::PER_NIGHT][0] = ['id' => null, 'name' => 'Laundry', 'currency' => $currency, 'amount' => 100, 'escalation' => 10, 'atOperation' => 0, 'annual' => 3];

        $this->savePage($form)->assertRedirect($this->url())->assertSessionHasNoErrors();

        $percent = $this->rows('rooms', ExpensePages::PERCENT_ROOMS);
        $this->assertCount(1, $percent);
        $this->assertSame('Commission', $percent[0]->name);
        $this->assertEquals(12.5, (float) $percent[0]->payload[3]);

        $laundry = $this->rows('rooms', ExpensePages::PER_NIGHT)->firstOrFail();
        $this->assertEquals(100, (float) $laundry->expense_per_night_sold);
        // March 2026 to March 2027 is one year: 100 grown by 10% = 110
        $this->assertEqualsWithDelta(110.0, (float) $laundry->night_expense_at_operation_date, 0.01);
        $this->assertEquals(3, (float) $laundry->night_annual_escalation_rate);
    }

    public function test_the_per_guest_table_uses_its_own_columns(): void
    {
        $form = $this->form();
        $form['sections'][ExpensePages::PER_GUEST][0] = ['id' => null, 'name' => 'Welcome drink', 'currency' => (int) $this->study->main_functional_currency, 'amount' => 20, 'escalation' => 0, 'atOperation' => 0, 'annual' => 0];
        $this->savePage($form)->assertSessionHasNoErrors();

        $row = $this->rows('rooms', ExpensePages::PER_GUEST)->firstOrFail();
        $this->assertEquals(20, (float) $row->expense_per_guest_sold);
        $this->assertEqualsWithDelta(20.0, (float) $row->guest_expense_at_operation_date, 0.01);
    }

    public function test_a_line_is_updated_by_its_id_and_removed_when_its_name_is_cleared(): void
    {
        $form = $this->form();
        $form['sections'][ExpensePages::PERCENT_ROOMS][0] = ['id' => null, 'name' => 'Commission', 'values' => $this->byYear(10)];
        $form['sections'][ExpensePages::PERCENT_ROOMS][1] = ['id' => null, 'name' => 'Cleaning', 'values' => $this->byYear(5)];
        $this->savePage($form)->assertSessionHasNoErrors();
        $ids = $this->rows('rooms', ExpensePages::PERCENT_ROOMS)->pluck('id')->all();

        $form = $this->form();
        $this->assertSame($ids, [$form['sections'][ExpensePages::PERCENT_ROOMS][0]['id'], $form['sections'][ExpensePages::PERCENT_ROOMS][1]['id']]);
        $form['sections'][ExpensePages::PERCENT_ROOMS][0]['values'] = $this->byYear(20);
        $form['sections'][ExpensePages::PERCENT_ROOMS][1]['name'] = '';
        $this->savePage($form)->assertSessionHasNoErrors();

        $rows = $this->rows('rooms', ExpensePages::PERCENT_ROOMS);
        $this->assertCount(1, $rows);
        $this->assertSame($ids[0], $rows[0]->id);
        $this->assertEquals(20, (float) $rows[0]->payload[1]);
    }

    public function test_the_percentages_of_one_year_may_not_pass_100_and_a_name_may_not_repeat(): void
    {
        $form = $this->form();
        $form['sections'][ExpensePages::PERCENT_ROOMS][0] = ['id' => null, 'name' => 'A', 'values' => $this->byYear(60)];
        $form['sections'][ExpensePages::PERCENT_ROOMS][1] = ['id' => null, 'name' => 'B', 'values' => $this->byYear(41)];
        $this->savePage($form)->assertSessionHasErrors(['sections.'.ExpensePages::PERCENT_ROOMS.'.total.1']);
        $this->assertCount(0, $this->rows('rooms', ExpensePages::PERCENT_ROOMS));

        $form['sections'][ExpensePages::PERCENT_ROOMS][1] = ['id' => null, 'name' => 'a', 'values' => $this->byYear(10)];
        $this->savePage($form)->assertSessionHasErrors(['sections.'.ExpensePages::PERCENT_ROOMS]);
    }

    public function test_a_line_with_a_name_must_be_complete_and_use_a_currency_of_the_study(): void
    {
        $form = $this->form();
        $form['sections'][ExpensePages::PER_NIGHT][0] = ['id' => null, 'name' => 'Laundry', 'currency' => 999999, 'amount' => -1, 'escalation' => 101, 'atOperation' => 0, 'annual' => 0];
        $this->savePage($form)->assertSessionHasErrors([
            'sections.'.ExpensePages::PER_NIGHT.'.0.currency', 'sections.'.ExpensePages::PER_NIGHT.'.0.amount', 'sections.'.ExpensePages::PER_NIGHT.'.0.escalation',
        ]);
    }

    public function test_disposable_expenses_keep_the_cash_share_and_the_rest_is_deferred(): void
    {
        $form = $this->form();
        $form['sections'][ExpensePages::DISPOSABLE][0] = ['id' => null, 'name' => 'Soap', 'beginning' => 500, 'coverage' => 30, 'cash' => 40, 'dueDays' => 45, 'values' => $this->byYear(2)];
        $this->savePage($form)->assertSessionHasNoErrors();

        $row = $this->rows('rooms', ExpensePages::DISPOSABLE)->firstOrFail();
        $this->assertEquals(40, (float) $row->cash_payment_percentage);
        $this->assertEquals(60, (float) $row->deferred_payment_percentage);
        $this->assertSame('45', (string) $row->due_days);

        $form['sections'][ExpensePages::DISPOSABLE][0]['coverage'] = 31;
        $this->savePage($form)->assertSessionHasErrors(['sections.'.ExpensePages::DISPOSABLE.'.0.coverage']);
    }

    public function test_manpower_is_kept_when_switched_on_and_removed_when_switched_off(): void
    {
        $form = $this->form();
        $dates = array_keys((array) $form['sections'][ExpensePages::MANPOWER][0]['heads']);
        $this->assertNotEmpty($dates);
        $form['flag'] = true;
        $form['sections'][ExpensePages::MANPOWER][0] = [
            'id' => null, 'name' => 'Cleaner', 'salary' => 1000, 'currency' => (int) $this->study->main_functional_currency, 'escalation' => 10, 'atOperation' => 0,
            'annual' => 0, 'taxes' => 10, 'insurance' => 5, 'heads' => array_fill_keys($dates, 2),
        ];
        $this->savePage($form)->assertSessionHasNoErrors();

        $this->assertSame('1', (string) $this->study->fresh()->has_rooms_manpower);
        $row = $this->rows('rooms', ExpensePages::MANPOWER)->firstOrFail();
        $this->assertEqualsWithDelta(1100.0, (float) $row->net_salary_at_operation_date, 0.01);
        $this->assertEquals(2, (float) $row->manpower_payload[$dates[0]]);

        $form = $this->form();
        $this->assertTrue($form['flag']);
        $form['flag'] = false;
        $this->savePage($form)->assertSessionHasNoErrors();
        $this->assertSame('0', (string) $this->study->fresh()->has_rooms_manpower);
        $this->assertCount(0, $this->rows('rooms', ExpensePages::MANPOWER));
    }

    public function test_taxes_and_social_insurance_together_stay_under_100(): void
    {
        $form = $this->form();
        $form['flag'] = true;
        $form['sections'][ExpensePages::MANPOWER][0] = ['id' => null, 'name' => 'Cleaner', 'salary' => 1000, 'currency' => (int) $this->study->main_functional_currency, 'escalation' => 0, 'atOperation' => 0, 'annual' => 0, 'taxes' => 60, 'insurance' => 40, 'heads' => []];
        $this->savePage($form)->assertSessionHasErrors(['sections.'.ExpensePages::MANPOWER.'.0.taxes']);
    }

    public function test_the_property_page_always_has_the_taxes_and_insurance_rows_and_the_month_belongs_to_the_taxes(): void
    {
        $form = $this->form('property');
        $rows = $form['sections'][ExpensePages::PROPERTY];
        $this->assertSame(['Property Taxes', 'Property Insurance'], array_column($rows, 'name'));

        $currency = (int) $this->study->main_functional_currency;
        $form['sections'][ExpensePages::PROPERTY] = [
            ['id' => null, 'name' => 'x', 'currency' => $currency, 'percentage' => 1.5, 'annual' => 2, 'month' => '03'],
            ['id' => null, 'name' => 'y', 'currency' => $currency, 'percentage' => 0.5, 'annual' => 0, 'month' => null],
        ];
        $this->savePage($form, 'property')->assertSessionHasNoErrors();

        $saved = $this->rows('property', ExpensePages::PROPERTY);
        $this->assertSame(['Property Taxes', 'Property Insurance'], $saved->pluck('name')->all());
        $this->assertSame('03', (string) $saved[0]->payment_month);
        $this->assertEmpty($saved[1]->payment_month);
        $this->assertEquals(1.5, (float) $saved[0]->percentage_from_fixed_assets);
    }

    public function test_the_fixed_expenses_of_general_keep_their_payment_terms(): void
    {
        $form = $this->form('general');
        $fixed = ExpensePages::FIXED;
        $form['sections'][$fixed][0] = ['id' => null, 'name' => 'Rent', 'currency' => (int) $this->study->main_functional_currency, 'amount' => 5000, 'escalation' => 0, 'atOperation' => 0, 'annual' => 4, 'terms' => 'quarterly'];
        $this->savePage($form, 'general')->assertSessionHasNoErrors();

        $row = $this->rows('general', $fixed)->firstOrFail();
        $this->assertSame('quarterly', $row->opex_payment_terms);
        $this->assertEquals(5000, (float) $row->expense_per_night_sold);

        $form['sections'][$fixed][0]['terms'] = 'weekly';
        $this->savePage($form, 'general')->assertSessionHasErrors(['sections.'.$fixed.'.0.terms']);
    }

    public function test_the_menu_ticks_a_page_once_it_holds_a_line(): void
    {
        $form = $this->form();
        $form['sections'][ExpensePages::PERCENT_ROOMS][0] = ['id' => null, 'name' => 'Commission', 'values' => $this->byYear(10)];
        $this->savePage($form)->assertSessionHasNoErrors();

        $this->actingAs($this->owner)->get($this->url())->assertInertia(fn (Assert $p) => $p
            ->where('menu', fn ($menu) => collect($menu)->firstWhere('key', 'expenses.rooms')['done'] === true && collect($menu)->firstWhere('key', 'expenses.foods')['done'] === false));
    }

    public function test_a_colleague_who_may_only_view_cannot_save_and_another_company_cannot_see_it(): void
    {
        $this->study->sharedWith()->attach($this->colleague->id, ['can_edit' => false]);

        $this->actingAs($this->colleague)->get($this->url())->assertOk();
        $this->savePage($this->form(), 'rooms', $this->colleague)->assertForbidden();

        $stranger = User::factory()->forCompany(Company::factory()->create())->create();
        $this->actingAs($stranger)->get($this->url())->assertNotFound();
    }
}
