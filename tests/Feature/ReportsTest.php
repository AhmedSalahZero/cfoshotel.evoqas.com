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

// The financial statements, the dashboard and the comparison (/studies/{id}/reports/{report}, /studies/{id}/dashboard, /studies/compare).
// Small on purpose: who may open them, what does not exist (404), the menu, and the shape of what the pages receive. The figures themselves were
// compared with the previous application (6 studies, about 91,000 cells of the four reports, the dashboard and the comparison), see docs/FINANCIAL-STATEMENTS.md.
// Run:  php artisan test --filter=ReportsTest
class ReportsTest extends TestCase
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

    private function props(string $url, ?User $as = null): array
    {
        return json_decode(json_encode($this->actingAs($as ?? $this->owner)->get($url)->assertOk()->original->getData()['page']['props']), true);
    }

    public function test_an_unknown_report_or_dashboard_choice_does_not_exist(): void
    {
        $id = $this->study->id;
        $this->actingAs($this->owner)->get("/studies/{$id}/reports/nothing")->assertNotFound();
        $this->actingAs($this->owner)->get("/studies/{$id}/dashboard?revenue_stream_type=nothing")->assertNotFound();
        $this->actingAs($this->owner)->get("/studies/{$id}/dashboard?gross_profit_type=Total+Revenues")->assertNotFound();
    }

    public function test_a_person_who_may_only_view_can_open_them_and_another_company_cannot(): void
    {
        $id = $this->study->id;
        $this->study->sharedWith()->attach($this->colleague->id, ['can_edit' => false]);
        foreach (['income', 'cash', 'balance', 'ratios'] as $report) {
            $this->actingAs($this->colleague)->get("/studies/{$id}/reports/{$report}")->assertOk();
        }
        $this->actingAs($this->colleague)->get("/studies/{$id}/dashboard")->assertOk();

        $stranger = User::factory()->forCompany(Company::factory()->create())->create();
        $this->actingAs($stranger)->get("/studies/{$id}/reports/income")->assertNotFound();
        $this->actingAs($stranger)->get("/studies/{$id}/dashboard")->assertNotFound();
    }

    public function test_the_menu_ends_with_the_financial_statements_and_the_dashboard(): void
    {
        $page = $this->props("/studies/{$this->study->id}/reports/income");

        $keys = array_column($page['menu'], 'key');
        $this->assertSame(['financial.income', 'financial.cash', 'financial.balance', 'financial.ratios', 'dashboard'], array_slice($keys, -5));
        $this->assertSame(['income', 'cash', 'balance', 'ratios'], array_keys($page['links']));
        $this->assertSame('income', $page['report']);
    }

    public function test_the_pages_receive_what_they_draw(): void
    {
        $id = $this->study->id;
        $income = $this->props("/studies/{$id}/reports/income");
        $this->assertSame('tree', $income['kind']);
        $this->assertIsBool($income['calculated']);
        if ($income['calculated']) {
            $this->assertSame(['monthly', 'quarterly', 'semi-annually', 'annually'], array_column($income['intervals'], 'name'));
            $this->assertNotEmpty($income['intervals'][0]['rows']);
            $this->assertSame(['key', 'label', 'level', 'parent', 'values', 'text'], array_slice(array_keys($income['intervals'][0]['rows'][0]), 0, 6));
        }

        $ratios = $this->props("/studies/{$id}/reports/ratios");
        $this->assertSame('ratios', $ratios['kind']);
        if ($ratios['calculated']) {
            $this->assertSame(['Profitability Ratios', 'Liquidity Ratios', 'Efficiency Ratios', 'Leverage Ratios'], array_column($ratios['groups'], 'key'));
        }

        $dashboard = $this->props("/studies/{$id}/dashboard");
        $this->assertSame('Total Revenues', $dashboard['selected']['revenue_stream_type']);
        $this->assertCount(6, $dashboard['streams']);
        $this->assertCount(6, $dashboard['profits']);
        $this->assertSame(url("/studies/compare?first={$id}"), $dashboard['compareUrl']);
    }

    public function test_the_comparison_wants_two_different_studies_the_person_may_see(): void
    {
        $id = $this->study->id;
        $page = $this->props('/studies/compare');
        $this->assertNull($page['result']);
        $this->assertNull($page['error']);
        $this->assertContains($id, array_column($page['studies'], 'id'));

        $this->assertSame('same', $this->props("/studies/compare?first={$id}&second={$id}")['error']);

        // a study that is not one of the person's choices (here one that does not exist; another company's is the same) is ignored: nothing is compared
        $page = $this->props("/studies/compare?first={$id}&second=999999");
        $this->assertNull($page['second']);
        $this->assertNull($page['result']);
    }
}
