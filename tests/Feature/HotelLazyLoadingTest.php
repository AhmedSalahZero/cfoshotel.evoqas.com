<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\HotelStudies;
use Tests\TestCase;

// The application stops "lazy loading" of relations while building (a slow-query guard), but the hotel models
// read relations on demand by design, so only they are exempt.
// Run:  php artisan test --filter=HotelLazyLoadingTest
class HotelLazyLoadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_guard_is_on_while_building(): void
    {
        $this->assertTrue(Model::preventsLazyLoading());
    }

    public function test_hotel_models_may_read_a_relation_on_demand_even_in_a_list(): void
    {
        HotelStudies::load();

        $rooms = Room::query()->get();
        $this->assertGreaterThan(1, $rooms->count(), 'the guard only reacts when several models are loaded together');

        foreach ($rooms as $room) {
            $this->assertNotNull($room->hospitalitySector->id);
        }
    }

    public function test_every_other_model_still_refuses(): void
    {
        foreach ([1, 2] as $i) {
            Company::create(['name_en' => "C{$i}", 'name_ar' => "C{$i}", 'status' => 'active', 'users_limit' => 5]);
        }
        $company = Company::query()->first();
        foreach ([1, 2] as $i) {
            DB::table('users')->insert(['name' => "U{$i}", 'email' => "u{$i}@x.test", 'password' => 'x', 'company_id' => $company->id, 'role' => 'user']);
        }

        $this->expectException(LazyLoadingViolationException::class);

        foreach (User::query()->get() as $user) {
            $user->company;
        }
    }
}
