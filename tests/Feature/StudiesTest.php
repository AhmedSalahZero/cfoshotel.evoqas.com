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
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

// The study itself: list, create, edit, copy, delete, share and who may do what. Run:  php artisan test --filter=StudiesTest
class StudiesTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $admin;
    private User $owner;
    private User $colleague;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CountriesSeeder::class, StatesSeeder::class, CurrenciesSeeder::class]);

        $this->company = Company::factory()->create(['users_limit' => 10]);
        $this->company->insertBasicSelects();
        $this->admin = User::factory()->companyAdmin()->forCompany($this->company)->create();
        $this->owner = User::factory()->forCompany($this->company)->create();
        $this->colleague = User::factory()->forCompany($this->company)->create();
    }

    /** First active type of a stream that is not the "Total ..." row. */
    private function typeId(string $table, ?Company $company = null): int
    {
        return (int) DB::table($table)->where('company_id', ($company ?? $this->company)->id)->where('is_active', 1)->orderBy('id')->skip(1)->value('id');
    }

    private function form(array $override = []): array
    {
        $row = fn (string $table, int $count, $value) => ['type_id' => $this->typeId($table), 'count' => $count, 'value' => $value];
        $off = ['on' => false, 'mode' => 'types', 'rows' => [['type_id' => null, 'count' => null, 'value' => null]]];

        return array_merge([
            'study_name' => 'Nile Corniche', 'property_name' => 'Nile', 'property_status' => 'existing', 'star_rating' => 5,
            'country_id' => null, 'state_id' => null, 'region' => 'Zamalek',
            'study_start' => '2026-03', 'duration_in_years' => 5,
            'development_start_month' => 0, 'development_duration' => 0, 'operation_start_month' => 0,
            'financial_year_start_month' => 'january', 'main_functional_currency' => (int) Currency::query()->orderBy('id')->value('id'),
            'additional_currency' => null, 'exchange_rate' => 1, 'corporate_taxes_rate' => 20, 'investment_return_rate' => 10, 'perpetual_growth_rate' => 2,
            'streams' => [
                'rooms' => ['on' => true, 'mode' => 'types', 'rows' => [$row('room_names', 100, 2)]],
                'foods' => ['on' => true, 'mode' => 'types', 'rows' => [$row('food_names', 80, 3)]],
                'casinos' => $off, 'meetings' => $off, 'others' => $off,
            ],
            'channels' => ['on' => true, 'names' => ['direct-reservations', 'agency']],
        ], $override);
    }

    private function makeStudy(?User $by = null, array $override = []): HospitalitySector
    {
        $this->actingAs($by ?? $this->owner)->post('/studies', $this->form($override))->assertSessionHasNoErrors();

        return HospitalitySector::query()->withoutGlobalScopes()->latest('id')->firstOrFail();
    }

    // ── create ──────────────────────────────────────────────

    public function test_a_person_opens_the_new_study_form_with_the_company_lists(): void
    {
        $this->actingAs($this->owner)->get('/studies/create')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Studies/Form')->where('study.id', null)->where('study.duration_in_years', 5)
            ->has('options.currencies')->has('options.types.rooms')->where('access', 'owner'));
    }

    public function test_creating_a_study_works_out_the_dates_and_saves_the_streams(): void
    {
        $study = $this->makeStudy();

        $this->assertSame($this->company->id, (int) $study->company_id);
        $this->assertSame($this->owner->id, (int) $study->creator_id);
        $this->assertSame('03/01/2026', $study->study_start_date);
        $this->assertSame('02/01/2031', $study->study_end_date);
        $this->assertSame(1, $study->rooms()->count());
        $this->assertSame(1, $study->foods()->count());
        $this->assertSame(0, $study->casinos()->count());
        $this->assertSame(2, $study->salesChannels()->count());
    }

    public function test_the_form_is_checked(): void
    {
        $this->actingAs($this->owner);
        $bad = $this->form(['study_name' => '', 'operation_start_month' => 61]);
        $bad['streams']['rooms']['rows'][0]['type_id'] = null;

        $this->post('/studies', $bad)->assertSessionHasErrors(['study_name', 'operation_start_month', 'streams.rooms.rows.0.type_id']);
        $this->assertSame(0, HospitalitySector::query()->withoutGlobalScopes()->count());
    }

    public function test_a_type_can_be_used_once_and_must_belong_to_the_company(): void
    {
        $this->actingAs($this->owner);
        $twice = $this->form();
        $twice['streams']['rooms']['rows'][] = $twice['streams']['rooms']['rows'][0];
        $this->post('/studies', $twice)->assertSessionHasErrors();

        $other = Company::factory()->create();
        $other->insertBasicSelects();
        $foreign = $this->form();
        $foreign['streams']['rooms']['rows'][0]['type_id'] = $this->typeId('room_names', $other);
        $this->post('/studies', $foreign)->assertSessionHasErrors();
    }

    public function test_an_additional_currency_needs_its_rate_and_none_means_rate_one(): void
    {
        $this->actingAs($this->owner);
        $currencies = Currency::query()->orderBy('id')->pluck('id');

        $study = $this->makeStudy(null, ['additional_currency' => null, 'exchange_rate' => 55]);
        $this->assertEquals(1, $study->exchange_rate);

        $this->post('/studies', $this->form(['additional_currency' => $currencies[1], 'exchange_rate' => 0]))->assertSessionHasErrors('exchange_rate');
        $this->post('/studies', $this->form(['additional_currency' => $currencies[0]]))->assertSessionHasErrors('additional_currency');
    }

    // ── list and who sees what ──────────────────────────────

    public function test_who_sees_which_study(): void
    {
        $mine = $this->makeStudy($this->owner, ['study_name' => 'Mine']);
        $shared = $this->makeStudy($this->admin, ['study_name' => 'Shared']);
        $this->makeStudy($this->admin, ['study_name' => 'Private']);
        $shared->sharedWith()->attach($this->owner->id, ['can_edit' => false]);

        $this->actingAs($this->owner)->get('/studies')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Studies/Index')->has('studies', 2)->where('isAdmin', false));

        $this->actingAs($this->admin)->get('/studies')->assertInertia(fn (Assert $p) => $p->has('studies', 3)->where('isAdmin', true));
        $this->actingAs($this->colleague)->get('/studies')->assertInertia(fn (Assert $p) => $p->has('studies', 0));
        $this->assertNotNull($mine);
    }

    public function test_another_company_never_sees_or_reaches_a_study(): void
    {
        $study = $this->makeStudy();
        $stranger = User::factory()->companyAdmin()->forCompany(Company::factory()->create())->create();

        $this->actingAs($stranger)->get('/studies')->assertInertia(fn (Assert $p) => $p->has('studies', 0));
        $this->actingAs($stranger)->get("/studies/{$study->id}/edit")->assertNotFound();
        $this->actingAs($stranger)->put("/studies/{$study->id}", $this->form())->assertNotFound();
        $this->actingAs($stranger)->delete("/studies/{$study->id}")->assertNotFound();
    }

    // ── edit ────────────────────────────────────────────────

    public function test_the_owner_changes_a_study_and_a_switched_off_stream_loses_its_rows(): void
    {
        $withCasino = $this->form(['study_name' => 'Old']);
        $withCasino['streams']['casinos'] = ['on' => true, 'mode' => 'types', 'rows' => [['type_id' => $this->typeId('casino_names'), 'count' => 4, 'value' => 6]]];
        $study = $this->makeStudy($this->owner, $withCasino);
        $this->assertSame(1, $study->casinos()->count());

        $payload = $this->form(['study_name' => 'New name', 'duration_in_years' => 10]);
        $this->actingAs($this->owner)->put("/studies/{$study->id}", $payload)->assertRedirect('/studies');

        $study->refresh();
        $this->assertSame('New name', $study->study_name);
        $this->assertSame(10, (int) $study->duration_in_years);
        $this->assertSame('02/01/2036', $study->study_end_date);
        $this->assertSame(0, $study->casinos()->count());
        $this->assertSame(1, $study->rooms()->count());
    }

    public function test_the_edit_screen_is_filled_with_the_saved_study(): void
    {
        $study = $this->makeStudy();

        $this->actingAs($this->owner)->get("/studies/{$study->id}/edit")->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Studies/Form')->where('study.study_name', 'Nile Corniche')->where('study.study_start', '2026-03')
            ->where('study.streams.rooms.rows.0.count', 100)->where('study.channels.names', ['direct-reservations', 'agency']));
    }

    public function test_view_only_can_open_but_not_change_and_edit_access_can_change(): void
    {
        $study = $this->makeStudy();
        $study->sharedWith()->attach($this->colleague->id, ['can_edit' => false]);

        $this->actingAs($this->colleague)->get("/studies/{$study->id}/edit")->assertOk()->assertInertia(fn (Assert $p) => $p->where('access', 'view'));
        $this->actingAs($this->colleague)->put("/studies/{$study->id}", $this->form(['study_name' => 'Hacked']))->assertForbidden();
        $this->assertSame('Nile Corniche', $study->fresh()->study_name);

        $study->sharedWith()->updateExistingPivot($this->colleague->id, ['can_edit' => true]);
        $this->actingAs($this->colleague)->put("/studies/{$study->id}", $this->form(['study_name' => 'By colleague']))->assertRedirect('/studies');
        $this->assertSame('By colleague', $study->fresh()->study_name);
    }

    // ── copy and delete ─────────────────────────────────────

    public function test_copy_makes_a_new_study_owned_by_the_copier_and_not_shared(): void
    {
        $study = $this->makeStudy();
        $study->sharedWith()->attach($this->colleague->id, ['can_edit' => true]);

        $this->actingAs($this->colleague)->post("/studies/{$study->id}/copy", ['name' => 'Copy of Nile'])->assertRedirect('/studies');

        $copy = HospitalitySector::query()->withoutGlobalScopes()->where('study_name', 'Copy of Nile')->firstOrFail();
        $this->assertNotSame($study->id, $copy->id);
        $this->assertSame($this->colleague->id, (int) $copy->creator_id);
        $this->assertSame(1, $copy->rooms()->count());
        $this->assertSame(1, $copy->foods()->count());
        $this->assertSame(2, $copy->salesChannels()->count());
        $this->assertSame(0, $copy->sharedWith()->count());
        $this->assertSame(1, $study->rooms()->count());
    }

    public function test_copy_needs_a_name(): void
    {
        $study = $this->makeStudy();
        $this->actingAs($this->owner)->post("/studies/{$study->id}/copy", ['name' => ''])->assertSessionHasErrors('name');
    }

    public function test_every_table_hanging_on_a_study_is_known_to_the_copy(): void
    {
        $known = array_merge(\App\Services\StudyService::PLAIN_TABLES, \App\Services\StudyService::LINKED_TABLES, ['hospitality_sector_shares']);
        $found = collect(Schema::getTables())->pluck('name')
            ->filter(fn ($table) => Schema::hasColumn($table, 'hospitality_sector_id'))->values()->all();

        $this->assertSame([], array_values(array_diff($found, $known)), 'A table with hospitality_sector_id is not handled by copy / delete: add it to StudyService.');
    }

    public function test_only_the_owner_or_admin_deletes(): void
    {
        $study = $this->makeStudy();
        $study->sharedWith()->attach($this->colleague->id, ['can_edit' => true]);

        $this->actingAs($this->colleague)->delete("/studies/{$study->id}")->assertForbidden();
        $this->assertNotNull(HospitalitySector::query()->withoutGlobalScopes()->find($study->id));

        $this->actingAs($this->owner)->delete("/studies/{$study->id}")->assertRedirect('/studies');
        $this->assertNull(HospitalitySector::query()->withoutGlobalScopes()->find($study->id));
        $this->assertSame(0, DB::table('rooms')->where('hospitality_sector_id', $study->id)->count());
    }

    // ── share ───────────────────────────────────────────────

    public function test_the_owner_shares_with_colleagues(): void
    {
        $study = $this->makeStudy();

        $this->actingAs($this->owner)->getJson("/studies/{$study->id}/share")->assertOk()
            ->assertJsonPath('name', 'Nile Corniche')->assertJsonFragment(['id' => $this->colleague->id, 'access' => 'none']);

        $this->actingAs($this->owner)->put("/studies/{$study->id}/share", ['access' => [$this->colleague->id => 'edit']])->assertRedirect('/studies');
        $this->assertTrue((bool) $study->sharedWith()->find($this->colleague->id)->pivot->can_edit);

        $this->actingAs($this->owner)->put("/studies/{$study->id}/share", ['access' => [$this->colleague->id => 'none']]);
        $this->assertSame(0, $study->sharedWith()->count());
    }

    public function test_a_colleague_cannot_share_and_cannot_share_with_another_company(): void
    {
        $study = $this->makeStudy();
        $study->sharedWith()->attach($this->colleague->id, ['can_edit' => true]);
        $this->actingAs($this->colleague)->put("/studies/{$study->id}/share", ['access' => [$this->admin->id => 'view']])->assertForbidden();

        $stranger = User::factory()->forCompany(Company::factory()->create())->create();
        $this->actingAs($this->owner)->put("/studies/{$study->id}/share", ['access' => [$stranger->id => 'view']]);
        $this->assertSame(0, $study->sharedWith()->where('users.id', $stranger->id)->count());
    }

    // ── the rest ────────────────────────────────────────────

    public function test_the_states_of_a_country_come_back_as_json(): void
    {
        $countryId = DB::table('states')->value('country_id');
        $this->assertNotNull($countryId, 'The states seeder gave no states.');
        $this->actingAs($this->owner)->getJson("/studies/countries/{$countryId}/states")->assertOk()->assertJsonIsArray()->assertJsonStructure([['id', 'name']]);
    }

    public function test_a_read_only_company_can_look_but_not_change(): void
    {
        $study = $this->makeStudy();
        $this->company->update(['subscription_ends_at' => today()->subDay()]);
        $this->owner = $this->owner->fresh();   // reload the person so the company is read again

        $this->actingAs($this->owner)->get('/studies')->assertOk()->assertInertia(fn (Assert $p) => $p->where('readOnly', true));
        $this->actingAs($this->owner)->post('/studies', $this->form(['study_name' => 'Nope']));
        $this->actingAs($this->owner)->put("/studies/{$study->id}", $this->form(['study_name' => 'Nope']));
        $this->actingAs($this->owner)->delete("/studies/{$study->id}");

        $this->assertSame(1, HospitalitySector::query()->withoutGlobalScopes()->count());
        $this->assertSame('Nile Corniche', $study->fresh()->study_name);
    }

    public function test_a_signed_out_visitor_is_sent_to_sign_in(): void
    {
        $this->get('/studies')->assertRedirect('/login');
    }

    public function test_after_saving_the_person_goes_where_the_button_says(): void
    {
        $this->actingAs($this->owner);

        $created = $this->post('/studies', $this->form() + ['after' => 'next']);
        $study = HospitalitySector::query()->withoutGlobalScopes()->latest('id')->firstOrFail();
        $created->assertRedirect(route('studies.rooms.edit', $study->id));

        $url = "/studies/{$study->id}";
        $this->put($url, $this->form(['region' => 'Zamalek 1']) + ['after' => 'stay'])->assertRedirect(route('studies.edit', $study->id));
        $this->put($url, $this->form(['region' => 'Zamalek 2']) + ['after' => 'next'])->assertRedirect(route('studies.rooms.edit', $study->id));
        $this->put($url, $this->form(['region' => 'Zamalek 3']) + ['after' => 'close'])->assertRedirect(route('studies.index'));
        $this->put($url, $this->form(['region' => 'Zamalek 4']) + ['after' => 'somewhere else'])->assertRedirect(route('studies.index'));
        $this->put($url, $this->form(['region' => 'Zamalek 5']))->assertRedirect(route('studies.index'));
    }
}
