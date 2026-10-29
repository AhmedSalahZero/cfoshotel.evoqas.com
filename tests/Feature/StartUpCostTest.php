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

// The start-up cost page. Small, hand-checkable cases. Run:  php artisan test --filter=StartUpCostTest
// The study used: starts March 2026, 5 years, operation from month 12 (March 2027), one facility.
class StartUpCostTest extends TestCase
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

    private function url(): string
    {
        return "/studies/{$this->study->id}/start-up-cost";
    }

    private function props(?User $by = null): array
    {
        return json_decode(json_encode($this->actingAs($by ?? $this->owner)->get($this->url())->assertOk()->original->getData()['page']['props']), true);
    }

    private function form(?User $by = null): array
    {
        return $this->props($by)['form'];
    }

    private function savePage(array $form, ?User $by = null)
    {
        return $this->actingAs($by ?? $this->owner)->put($this->url(), $form);
    }

    private function rows(string $section)
    {
        return DepartmentExpense::query()->where('hospitality_sector_id', $this->study->id)->where('model_name', 'start')->where('section_name', $section)->orderBy('id')->get();
    }

    private function line(array $props, array $over = []): array
    {
        return array_replace(['id' => null, 'name' => 'Licences', 'currency' => (int) $this->study->main_functional_currency, 'cost' => 12000, 'date' => $props['months'][2]['index'], 'cash' => 40, 'dueDays' => 30], $over);
    }

    public function test_the_page_has_three_lines_in_each_table_and_the_months_before_the_opening(): void
    {
        $props = $this->props();
        $this->assertCount(3, $props['form']['sections'][ExpensePages::START_UP]);
        $this->assertCount(3, $props['form']['sections'][ExpensePages::PRE_OPERATING]);
        // March 2026 to February 2027, twelve months before the opening in March 2027
        $this->assertCount(12, $props['dates']);
        $this->assertSame('Mar 2026', $props['dates'][0]['label']);
        $this->assertSame('Feb 2027', $props['dates'][11]['label']);
        // a start-up cost may also be paid in the opening month
        $this->assertCount(13, $props['months']);
        $this->assertSame($props['months'][0]['index'], $props['form']['sections'][ExpensePages::START_UP][0]['date']);
        $this->assertSame(100, (int) $props['form']['sections'][ExpensePages::START_UP][0]['cash']);
    }

    public function test_a_start_up_cost_is_saved_as_the_old_application_kept_it(): void
    {
        $props = $this->props();
        $form = $props['form'];
        $form['sections'][ExpensePages::START_UP][0] = $this->line($props);
        $this->savePage($form)->assertRedirect($this->url())->assertSessionHasNoErrors();

        $row = $this->rows(ExpensePages::START_UP)->firstOrFail();
        $this->assertSame('Licences', $row->name);
        $this->assertEquals(12000, (float) $row->start_up_cost);
        $this->assertEquals(40, (float) $row->cash_payment_percentage);
        $this->assertEquals(60, (float) $row->deferred_payment_percentage);
        $this->assertSame('30', (string) $row->due_days);
        $this->assertSame($props['months'][2]['index'], (int) $row->date);
        $this->assertCount(1, $this->rows(ExpensePages::START_UP));
    }

    public function test_saving_works_in_a_fresh_request_that_did_not_open_the_page_first(): void
    {
        $props = $this->props();
        $form = $props['form'];
        $form['sections'][ExpensePages::START_UP][0] = $this->line($props);

        // A browser sends the Save as a new request: the date tables of the study are not in memory (the page visit above leaves them there).
        foreach (['datesIndexWithYearIndex', 'yearIndexWithYear', 'dateIndexWithDate', 'dateIndexWithMonthNumber', 'dateWithMonthNumber', 'dateWithDateIndex'] as $key) {
            app()->forgetInstance($key);
        }

        $this->savePage($form)->assertRedirect($this->url())->assertSessionHasNoErrors();
        $this->assertCount(1, $this->rows(ExpensePages::START_UP));
    }

    public function test_pre_operating_amounts_are_kept_by_date_index(): void
    {
        $props = $this->props();
        $form = $props['form'];
        $amounts = [];
        foreach ($props['dates'] as $k => $d) {
            $amounts[$d['index']] = 1000 * ($k + 1);
        }
        $form['sections'][ExpensePages::PRE_OPERATING][1] = ['id' => null, 'name' => 'Recruitment', 'currency' => (int) $this->study->main_functional_currency, 'amounts' => $amounts];
        $this->savePage($form)->assertSessionHasNoErrors();

        $row = $this->rows(ExpensePages::PRE_OPERATING)->firstOrFail();
        $this->assertEquals(1000, (float) $row->getManpowerPayloadAtDate((string) $props['dates'][0]['index']));
        $this->assertEquals(12000, (float) $row->getManpowerPayloadAtDate((string) $props['dates'][11]['index']));

        $again = $this->form();
        $this->assertSame($row->id, $again['sections'][ExpensePages::PRE_OPERATING][0]['id']);
        $this->assertEquals(3000, $again['sections'][ExpensePages::PRE_OPERATING][0]['amounts'][$props['dates'][2]['index']]);
    }

    public function test_a_line_is_updated_by_its_id_and_removed_when_its_name_is_cleared(): void
    {
        $props = $this->props();
        $form = $props['form'];
        $form['sections'][ExpensePages::START_UP][0] = $this->line($props);
        $form['sections'][ExpensePages::START_UP][1] = $this->line($props, ['name' => 'Training']);
        $this->savePage($form)->assertSessionHasNoErrors();
        $ids = $this->rows(ExpensePages::START_UP)->pluck('id')->all();

        $form = $this->form();
        $this->assertSame($ids, [$form['sections'][ExpensePages::START_UP][0]['id'], $form['sections'][ExpensePages::START_UP][1]['id']]);
        $form['sections'][ExpensePages::START_UP][0]['cost'] = 5000;
        $form['sections'][ExpensePages::START_UP][1]['name'] = '';
        $this->savePage($form)->assertSessionHasNoErrors();

        $rows = $this->rows(ExpensePages::START_UP);
        $this->assertCount(1, $rows);
        $this->assertSame($ids[0], $rows[0]->id);
        $this->assertEquals(5000, (float) $rows[0]->start_up_cost);
    }

    public function test_a_line_with_a_name_must_be_complete_and_inside_the_rules(): void
    {
        $props = $this->props();
        $form = $props['form'];
        $form['sections'][ExpensePages::START_UP][0] = $this->line($props, ['cost' => -5, 'cash' => 120, 'dueDays' => 31, 'date' => 99999, 'currency' => 999999]);
        $this->savePage($form)->assertSessionHasErrors([
            'sections.'.ExpensePages::START_UP.'.0.cost', 'sections.'.ExpensePages::START_UP.'.0.cash', 'sections.'.ExpensePages::START_UP.'.0.dueDays',
            'sections.'.ExpensePages::START_UP.'.0.date', 'sections.'.ExpensePages::START_UP.'.0.currency',
        ]);
        $this->assertCount(0, $this->rows(ExpensePages::START_UP));

        $form = $props['form'];
        $form['sections'][ExpensePages::START_UP][0] = $this->line($props);
        $form['sections'][ExpensePages::START_UP][1] = $this->line($props, ['name' => 'licences']);
        $this->savePage($form)->assertSessionHasErrors(['sections.'.ExpensePages::START_UP]);
    }

    public function test_the_menu_ends_with_the_start_up_cost_and_ticks_it_once_saved(): void
    {
        $find = fn ($menu) => collect($menu)->firstWhere('key', 'expenses.startup');
        $this->assertTrue($find($this->props()['menu'])['done'] === false);
        $keys = collect($this->props()['menu'])->pluck('key')->reject(fn ($key) => preg_match('/^(statements|financial)\.|^dashboard$/', $key))->values();
        $this->assertSame($keys->count() - 2, $keys->search('expenses.startup'));

        $props = $this->props();
        $form = $props['form'];
        $form['sections'][ExpensePages::START_UP][0] = $this->line($props);
        $this->savePage($form)->assertSessionHasNoErrors();
        $this->assertTrue($find($this->props()['menu'])['done']);
    }

    public function test_a_colleague_who_may_only_view_cannot_save_and_another_company_cannot_see_it(): void
    {
        $this->study->sharedWith()->attach($this->colleague->id, ['can_edit' => false]);

        $this->actingAs($this->colleague)->get($this->url())->assertOk();
        $this->savePage($this->form(), $this->colleague)->assertForbidden();

        $stranger = User::factory()->forCompany(Company::factory()->create())->create();
        $this->actingAs($stranger)->get($this->url())->assertNotFound();
    }
}
