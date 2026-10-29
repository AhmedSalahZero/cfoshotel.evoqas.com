<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Demo company and demo login
//  Location: database/seeders/DemoCompanySeeder.php
//  Run:      php artisan db:seed --class=DemoCompanySeeder
//
//  Creates "Sunrise Hospitality Investments (Demo)" with its standard room / food / gaming / meeting / other types,
//  and one company administrator:  demo@cfo-hotels.com  /  demo@cfo123
//  (The screens would refuse this password: it has no capital letter. The seeder writes it directly, as AdminUserSeeder does.)
//  Safe to run again: nothing is doubled and an existing password is not changed.
//  NOT YET RUN: written without access to PHP; first run it on a test database.
//  The demo study itself is typed through the screens, following CFO-Hotels-Demo-Case.xlsx.
// ══════════════════════════════════════════════════════════════════

class DemoCompanySeeder extends Seeder
{
    public const EMAIL = 'demo@cfo-hotels.com';

    public function run(): void
    {
        DB::transaction(function () {
            $company = Company::query()->where('name_en', 'Sunrise Hospitality Investments (Demo)')->first()
                ?? Company::create([
                    'name_en'                => 'Sunrise Hospitality Investments (Demo)',
                    'name_ar'                => 'صن رايز للاستثمار الفندقي (تجريبي)',
                    'status'                 => 'active',
                    'users_limit'            => 5,
                    'subscription_starts_at' => '2026-10-29',
                    'subscription_ends_at'   => null,
                    'contact_email'          => self::EMAIL,
                    'notes'                  => 'Demo company for testing every input form. Do not use for real studies.',
                ]);

            // The standard types of the hotel module (does nothing when the lists already have rows).
            $company->insertBasicSelects();

            if (User::query()->where('email', self::EMAIL)->exists()) {
                $this->command?->warn(self::EMAIL.' already exists - nothing changed.');

                return;
            }

            User::query()->forceCreate([
                'company_id'        => $company->id,
                'name'              => 'Demo Administrator',
                'email'             => self::EMAIL,
                'password'          => 'demo@cfo123',   // hashed by the model
                'role'              => User::ROLE_COMPANY_ADMIN,
                'email_verified_at' => now(),
                'language'          => 'en',
            ]);

            $this->command?->info('Demo company and login created: '.self::EMAIL);
        });
    }
}
