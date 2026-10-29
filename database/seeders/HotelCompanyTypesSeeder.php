<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Standard type lists for the companies that already exist
//  Location: database/seeders/HotelCompanyTypesSeeder.php
//
//  New companies get their room, food and beverage, gaming, meeting and other types when they are created.
//  This gives the same to companies created before the hotel module existed. Safe to run again: a list that
//  already has rows is left alone.
// ══════════════════════════════════════════════════════════════════

class HotelCompanyTypesSeeder extends Seeder
{
    public function run(): void
    {
        Company::query()->orderBy('id')->each(fn (Company $company) => $company->insertBasicSelects());
    }
}
