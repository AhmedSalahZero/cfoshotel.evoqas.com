<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Currency;
use App\Models\HospitalitySector;
use App\Models\User;
use App\Support\StudyFlow;
use Database\Seeders\CountriesSeeder;
use Database\Seeders\CurrenciesSeeder;
use Database\Seeders\StatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// ══════════════════════════════════════════════════════════════════
//  "Save & next": the page that follows when a page of a study is saved
//  Location: tests/Feature/StudyFlowTest.php
//  Run:      php artisan test --filter=StudyFlowTest
//
//  The order is the one of the previous application (revenue pages, expense pages, management fees, start-up cost, investment pages, income
//  statement), and a stream the study switched off (casino, meetings, other revenue) is left out of it.
// ══════════════════════════════════════════════════════════════════

class StudyFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private HospitalitySector $study;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CountriesSeeder::class, StatesSeeder::class, CurrenciesSeeder::class]);
        $company = Company::factory()->create(['users_limit' => 10]);
        $company->insertBasicSelects();
        $this->owner = User::factory()->forCompany($company)->create();
        $this->study = $this->createStudy(false);
    }

    /** A study of the owner's company; with every stream on, the casino, meeting and other revenue pages have a facility to save. */
    private function createStudy(bool $everyStream): HospitalitySector
    {
        $companyId = $this->owner->company_id;
        $type = fn (string $table) => (int) DB::table($table)->where('company_id', $companyId)->where('is_active', 1)->orderBy('id')->skip(1)->value('id');
        $stream = fn (bool $on, string $table, int $count, int $value) => $on
            ? ['on' => true, 'mode' => 'types', 'rows' => [['type_id' => $type($table), 'count' => $count, 'value' => $value]]]
            : ['on' => false, 'mode' => 'types', 'rows' => [['type_id' => null, 'count' => null, 'value' => null]]];

        $this->actingAs($this->owner)->post('/studies', [
            'study_name' => 'Nile', 'property_name' => 'Nile', 'property_status' => 'existing', 'star_rating' => 5,
            'country_id' => null, 'state_id' => null, 'region' => 'Zamalek',
            'study_start' => '2026-03', 'duration_in_years' => 5,
            'development_start_month' => 0, 'development_duration' => 0, 'operation_start_month' => 12,
            'financial_year_start_month' => 'january', 'main_functional_currency' => (int) Currency::query()->orderBy('id')->value('id'),
            'additional_currency' => null, 'exchange_rate' => 1, 'corporate_taxes_rate' => 20, 'investment_return_rate' => 10, 'perpetual_growth_rate' => 2,
            'streams' => [
                'rooms' => $stream(true, 'room_names', 100, 2),
                'foods' => $stream(true, 'food_names', 2, 50),
                'casinos' => $stream($everyStream, 'casino_names', 2, 50),
                'meetings' => $stream($everyStream, 'meeting_names', 2, 50),
                'others' => $stream($everyStream, 'other_names', 2, 50),
            ],
            'channels' => ['on' => false, 'names' => []],
        ])->assertSessionHasNoErrors();

        return HospitalitySector::query()->withoutGlobalScopes()->latest('id')->firstOrFail();
    }

    private function url(string $path): string
    {
        return url("/studies/{$this->study->id}/{$path}");
    }

    /** The pages that follow $from, one after the other, until the list of studies. */
    private function walkFrom(string $from): array
    {
        $this->study->refresh();
        $keys = [];
        $current = $from;
        while (($next = StudyFlow::next($this->study, $current)) !== null) {
            $key = collect(\App\Support\StudyMenu::for($this->study))->firstWhere('href', $next)['key'];
            $keys[] = $key;
            $current = $key;
        }

        return $keys;
    }

    public function test_the_order_is_the_one_of_the_previous_application_without_the_streams_that_are_off(): void
    {
        $this->assertSame([
            'rooms', 'foods', 'expenses.rooms', 'expenses.foods', 'expenses.energy', 'expenses.general', 'expenses.marketing', 'expenses.property',
            'expenses.management', 'expenses.startup', 'investment.property', 'investment.land', 'investment.ffe', 'financial.income', 'financial.cash',
        ], $this->walkFrom('study'));
    }

    public function test_a_stream_that_is_switched_on_comes_in_at_its_place(): void
    {
        $this->study->update(['has_casino_section' => 1, 'has_meeting_section' => 1, 'has_other_section' => 1]);

        $keys = $this->walkFrom('study');

        $this->assertSame(['rooms', 'foods', 'casinos', 'meetings', 'others', 'expenses.rooms', 'expenses.foods', 'expenses.casinos', 'expenses.meetings', 'expenses.others', 'expenses.energy'], array_slice($keys, 0, 11));
        $this->assertSame('foods', $this->walkFrom('rooms')[0]);
        $this->assertSame('casinos', $this->walkFrom('foods')[0]);
        $this->assertSame('expenses.rooms', $this->walkFrom('others')[0]);
    }

    public function test_the_last_page_and_an_unknown_page_have_no_next(): void
    {
        $this->assertNull(StudyFlow::next($this->study, 'financial.cash'));
        $this->assertNull(StudyFlow::next($this->study, 'nothing'));
    }

    public function test_the_study_form_still_goes_on_to_the_rooms(): void
    {
        $form = $this->actingAs($this->owner)->get("/studies/{$this->study->id}/edit")->assertOk()->original->getData()['page']['props']['study'];

        $this->actingAs($this->owner)->put("/studies/{$this->study->id}", json_decode(json_encode($form), true) + ['after' => 'next'])
            ->assertRedirect($this->url('rooms'));
    }

    public function test_save_and_next_on_the_management_fees_goes_to_the_start_up_cost_and_a_plain_save_stays(): void
    {
        $form = json_decode(json_encode($this->actingAs($this->owner)->get($this->url('management-fees'))->assertOk()->original->getData()['page']['props']['form']), true);

        $this->actingAs($this->owner)->put($this->url('management-fees'), $form)->assertRedirect($this->url('management-fees'));
        $this->actingAs($this->owner)->put($this->url('management-fees'), $form + ['after' => 'stay'])->assertRedirect($this->url('management-fees'));
        $this->actingAs($this->owner)->put($this->url('management-fees'), $form + ['after' => 'next'])->assertRedirect($this->url('start-up-cost'));
    }

    public function test_save_and_next_on_the_start_up_cost_goes_to_the_property_page(): void
    {
        $form = json_decode(json_encode($this->actingAs($this->owner)->get($this->url('start-up-cost'))->assertOk()->original->getData()['page']['props']['form']), true);

        $this->actingAs($this->owner)->put($this->url('start-up-cost'), $form + ['after' => 'next'])->assertRedirect($this->url('property-acquisition-costs'));
    }

    public function test_save_and_next_on_an_expense_page_follows_the_expense_order(): void
    {
        $form = $this->actingAs($this->owner)->get($this->url('expenses/energy'))->assertOk()->original->getData()['page']['props']['form'];

        $this->actingAs($this->owner)->put($this->url('expenses/energy'), json_decode(json_encode($form), true) + ['after' => 'next'])
            ->assertRedirect($this->url('expenses/general'));
    }

    public function test_save_and_next_on_the_last_input_page_goes_to_the_income_statement(): void
    {
        $this->assertSame(url("/studies/{$this->study->id}/reports/income"), StudyFlow::next($this->study, 'investment.ffe'));
    }

    public function test_every_input_page_goes_on_to_the_page_that_follows_it(): void
    {
        $this->study = $this->createStudy(true);
        $follows = [
            'rooms' => 'foods', 'foods' => 'casinos', 'casinos' => 'meetings', 'meetings' => 'others', 'others' => 'expenses/rooms',
            'expenses/rooms' => 'expenses/foods', 'expenses/foods' => 'expenses/casinos', 'expenses/casinos' => 'expenses/meetings',
            'expenses/meetings' => 'expenses/others', 'expenses/others' => 'expenses/energy', 'expenses/energy' => 'expenses/general',
            'expenses/general' => 'expenses/marketing', 'expenses/marketing' => 'expenses/property', 'expenses/property' => 'management-fees',
            'management-fees' => 'start-up-cost', 'start-up-cost' => 'property-acquisition-costs', 'property-acquisition-costs' => 'land-acquisition-costs',
            'land-acquisition-costs' => 'ffe-cost', 'ffe-cost' => 'reports/income',
        ];

        foreach ($follows as $page => $next) {
            $form = json_decode(json_encode($this->actingAs($this->owner)->get($this->url($page))->assertOk()->original->getData()['page']['props']['form']), true);
            if ($page === 'property-acquisition-costs') {
                $form['breakdown'][1]['percentage'] = 100;   // the three shares must add up to 100
            }

            $this->actingAs($this->owner)->put($this->url($page), $form + ['after' => 'next'])->assertSessionHasNoErrors()->assertRedirect($this->url($next));
        }
    }
}
