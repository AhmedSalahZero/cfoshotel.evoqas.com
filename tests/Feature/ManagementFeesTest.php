<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Currency;
use App\Models\ManagementFee;
use App\Models\HospitalitySector;
use App\Services\Expenses\ManagementFeesService;
use App\Models\User;
use Database\Seeders\CountriesSeeder;
use Database\Seeders\CurrenciesSeeder;
use Database\Seeders\StatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

// The management fees page. Small, hand-checkable cases. Run:  php artisan test --filter=ManagementFeesTest
// The study used: starts March 2026, 5 years, operation from month 12 (March 2027), one facility.
class ManagementFeesTest extends TestCase
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

    private const BASE = 'base_management_fees_as_percentage_from_revenues';
    private const INCENTIVE = 'incentive_management_fees_as_percentage_from_revenues';

    private function url(): string
    {
        return "/studies/{$this->study->id}/management-fees";
    }

    private function form(?User $by = null): array
    {
        $form = $this->actingAs($by ?? $this->owner)->get($this->url())->assertOk()->original->getData()['page']['props']['form'];

        return json_decode(json_encode($form), true);
    }

    private function savePage(array $form, ?User $by = null)
    {
        return $this->actingAs($by ?? $this->owner)->put($this->url(), $form);
    }

    private function row(string $section): ?ManagementFee
    {
        return ManagementFee::query()->where('hospitality_sector_id', $this->study->id)->where('model_name', 'management')->where('section_name', $section)->first();
    }

    public function test_the_page_has_the_two_fees_with_fixed_names_and_one_box_for_each_year(): void
    {
        $this->actingAs($this->owner)->get($this->url())->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Studies/ManagementFees')->has('years', 5)->missing('revenue')->has('layout', 2)
            ->where('layout.0.name', self::BASE)->where('layout.0.hidesRevenue', false)->where('layout.1.name', self::INCENTIVE)->where('layout.1.hidesRevenue', true)
            ->where('form.sections.'.self::BASE.'.0.name', 'Base Management Fees')->where('form.sections.'.self::INCENTIVE.'.0.name', 'Incentive Management Fees')
            ->where('form.sections.'.self::BASE.'.0.values.3', 0));
    }

    public function test_saving_keeps_both_fees_as_the_old_application_kept_them(): void
    {
        $form = $this->form();
        $form['sections'][self::BASE][0]['values'] = [1 => 3, 2 => 3.5, 3 => 4, 4 => 4, 5 => 4];
        $form['sections'][self::INCENTIVE][0]['values'] = array_fill_keys([1, 2, 3, 4, 5], 10);
        $this->savePage($form)->assertRedirect($this->url())->assertSessionHasNoErrors();

        $base = $this->row(self::BASE);
        $this->assertSame('Base Management Fees', $base->name);
        $this->assertEquals(3.5, (float) $base->getPayloadAtYear(2));
        $this->assertEquals(10, (float) $this->row(self::INCENTIVE)->getPayloadAtYear(5));
        $this->assertSame(2, ManagementFee::query()->where('hospitality_sector_id', $this->study->id)->count());
    }

    public function test_saving_again_changes_the_same_lines(): void
    {
        $form = $this->form();
        $form['sections'][self::BASE][0]['values'] = array_fill_keys([1, 2, 3, 4, 5], 2);
        $this->savePage($form)->assertSessionHasNoErrors();
        $id = $this->row(self::BASE)->id;

        $form = $this->form();
        $this->assertSame($id, $form['sections'][self::BASE][0]['id']);
        $form['sections'][self::BASE][0]['values'] = array_fill_keys([1, 2, 3, 4, 5], 6);
        $this->savePage($form)->assertSessionHasNoErrors();

        $this->assertSame($id, $this->row(self::BASE)->id);
        $this->assertEquals(6, (float) $this->row(self::BASE)->getPayloadAtYear(1));
        $this->assertSame(2, ManagementFee::query()->where('hospitality_sector_id', $this->study->id)->count());
    }

    public function test_a_percentage_is_needed_for_every_year_and_stays_between_0_and_100(): void
    {
        $form = $this->form();
        $form['sections'][self::BASE][0]['values'][2] = 101;
        $form['sections'][self::INCENTIVE][0]['values'][3] = -1;
        $form['sections'][self::INCENTIVE][0]['values'][4] = null;
        $this->savePage($form)->assertSessionHasErrors([
            'sections.'.self::BASE.'.0.values.2', 'sections.'.self::INCENTIVE.'.0.values.3', 'sections.'.self::INCENTIVE.'.0.values.4',
        ]);
        $this->assertNull($this->row(self::BASE));
    }

    public function test_the_menu_ends_the_expenses_with_the_fees_and_ticks_them_once_saved(): void
    {
        $find = fn ($menu) => collect($menu)->firstWhere('key', 'expenses.management');
        $own = fn ($menu) => collect($menu)->reject(fn ($item) => preg_match('/^(statements|financial)\.|^dashboard$/', $item['key']))->values();
        $this->actingAs($this->owner)->get($this->url())->assertInertia(fn (Assert $p) => $p->where('menu', fn ($menu) => $find($menu)['done'] === false && $own($menu)->pluck('key')->search('expenses.management') === $own($menu)->count() - 3));

        $this->savePage($this->form())->assertSessionHasNoErrors();
        $this->actingAs($this->owner)->get($this->url())->assertInertia(fn (Assert $p) => $p->where('menu', fn ($menu) => $find($menu)['done'] === true));
    }

    public function test_the_fees_are_read_by_the_calculation_as_before(): void
    {
        $form = $this->form();
        $form['sections'][self::BASE][0]['values'] = array_fill_keys([1, 2, 3, 4, 5], 3);
        $this->savePage($form)->assertSessionHasNoErrors();

        $fee = $this->study->fresh()->getBaseManagementFee();
        $this->assertNotNull($fee);
        $this->assertSame('management', $fee->model_name);
        $this->assertEquals(3, (float) $fee->getPayloadAtYear(1));
        $this->assertSame('Base Management Fees', ManagementFeesService::SECTIONS[self::BASE]['name']);
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
