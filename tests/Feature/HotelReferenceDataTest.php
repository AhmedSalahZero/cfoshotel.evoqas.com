<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// The starting lists of the hotel module: countries, states and currencies.
// Run:  php artisan test --filter=HotelReferenceDataTest
class HotelReferenceDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_countries_states_and_currencies_are_seeded(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(7, DB::table('countries')->count());
        $this->assertSame(131, DB::table('states')->count());
        $this->assertSame(['EGP', 'USD', 'EURO', 'AED', 'SAR', 'QAR'], DB::table('currencies')->orderBy('id')->pluck('name')->all());
    }

    public function test_every_state_belongs_to_a_real_country(): void
    {
        $this->seed(DatabaseSeeder::class);

        $orphans = DB::table('states')->whereNotIn('country_id', DB::table('countries')->select('id'))->count();

        $this->assertSame(0, $orphans);
    }

    public function test_egypt_has_cairo_and_both_languages_are_filled_in(): void
    {
        $this->seed(DatabaseSeeder::class);

        $egypt = DB::table('countries')->where('name_en', 'Egypt')->first();

        $this->assertSame('مصر', $egypt->name_ar);
        $this->assertSame(1, DB::table('states')->where('country_id', $egypt->id)->where('name_en', 'Cairo')->count());
        $this->assertSame(0, DB::table('states')->where('name_en', '')->orWhere('name_ar', '')->count());
        $this->assertSame(0, DB::table('countries')->where('name_en', '')->orWhere('name_ar', '')->count());
    }

    public function test_no_name_has_stray_spaces_or_hidden_direction_marks(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (['countries', 'states'] as $table) {
            $this->assertSame(0, DB::table($table)->whereRaw('name_en <> TRIM(name_en)')->orWhereRaw('name_ar <> TRIM(name_ar)')->count(), "{$table}: stray spaces");

            foreach (DB::table($table)->pluck('name_ar') as $name) {
                $this->assertStringNotContainsString("\u{200E}", (string) $name);
                $this->assertStringNotContainsString("\u{200F}", (string) $name);
            }
        }
    }

    public function test_running_the_seeders_again_changes_nothing(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(7, DB::table('countries')->count());
        $this->assertSame(131, DB::table('states')->count());
        $this->assertSame(6, DB::table('currencies')->count());
    }

    public function test_the_main_seeder_still_makes_the_administrator(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, DB::table('users')->where('role', 'super_admin')->count());
    }
}
