<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Database seeder
//  Location: database/seeders/DatabaseSeeder.php
//  Run:  php artisan db:seed
// ══════════════════════════════════════════════════════════════════

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            CountriesSeeder::class,
            StatesSeeder::class,
            CurrenciesSeeder::class,
            HotelCompanyTypesSeeder::class,
        ]);
    }
}
