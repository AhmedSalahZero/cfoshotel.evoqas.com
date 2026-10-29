<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Currencies
//  Location: database/seeders/CurrenciesSeeder.php
//
//  The 6 currencies of the previous CFO Hotels: EGP, USD, EURO, AED, SAR and QAR.
//  Safe to run again: rows are matched by their number (id), so nothing is doubled
//  and nothing else in the table is touched.
//  (Names were taken from the previous CFO Hotels; stray spaces and invisible
//  direction marks were removed.)
// ══════════════════════════════════════════════════════════════════

class CurrenciesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('currencies')->upsert([
            ['id' => 1, 'name' => 'EGP'],
            ['id' => 2, 'name' => 'USD'],
            ['id' => 3, 'name' => 'EURO'],
            ['id' => 4, 'name' => 'AED'],
            ['id' => 5, 'name' => 'SAR'],
            ['id' => 6, 'name' => 'QAR'],
        ], ['id'], ['name']);
    }
}
