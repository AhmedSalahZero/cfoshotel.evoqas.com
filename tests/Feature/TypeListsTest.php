<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\CountriesSeeder;
use Database\Seeders\CurrenciesSeeder;
use Database\Seeders\StatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

// The company's lists of types (room types, outlets, ...). Run:  php artisan test --filter=TypeListsTest
class TypeListsTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CountriesSeeder::class, StatesSeeder::class, CurrenciesSeeder::class]);
        $this->company = Company::factory()->create(['users_limit' => 10]);
        $this->company->insertBasicSelects();
        $this->admin = User::factory()->companyAdmin()->forCompany($this->company)->create();
        $this->user = User::factory()->forCompany($this->company)->create();
    }

    private function rooms(): array
    {
        return DB::table('room_names')->where('company_id', $this->company->id)->where('is_active', 1)->orderBy('id')->get(['id', 'title'])->all();
    }

    public function test_only_a_company_admin_opens_the_lists(): void
    {
        $this->actingAs($this->admin)->get('/types')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Company/Types')->has('lists.rooms'));
        $this->actingAs($this->user)->get('/types')->assertForbidden();
    }

    public function test_the_total_row_is_not_in_the_list(): void
    {
        $this->actingAs($this->admin)->get('/types')->assertInertia(fn (Assert $p) => $p->where('lists.rooms', fn ($rows) => collect($rows)->every(fn ($r) => stripos($r['title'], 'total') !== 0)));
    }

    public function test_a_type_can_be_added_and_renamed(): void
    {
        $existing = collect($this->rooms())->skip(1)->map(fn ($r) => ['id' => $r->id, 'title' => $r->title])->values()->all();
        $existing[0]['title'] = 'Renamed Room';
        $existing[] = ['id' => null, 'title' => 'Brand New Suite'];

        $this->actingAs($this->admin)->put('/types/rooms', ['rows' => $existing])->assertRedirect('/types')->assertSessionHasNoErrors();

        $titles = collect($this->rooms())->pluck('title');
        $this->assertTrue($titles->contains('Renamed Room'));
        $this->assertTrue($titles->contains('Brand New Suite'));
    }

    public function test_the_same_name_twice_is_refused(): void
    {
        $existing = collect($this->rooms())->skip(1)->map(fn ($r) => ['id' => $r->id, 'title' => $r->title])->values()->all();
        $existing[] = ['id' => null, 'title' => strtoupper($existing[0]['title'])];

        $this->actingAs($this->admin)->put('/types/rooms', ['rows' => $existing])->assertSessionHasErrors(['rows.'.(count($existing) - 1).'.title']);
    }
}
