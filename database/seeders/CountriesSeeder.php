<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Countries
//  Location: database/seeders/CountriesSeeder.php
//
//  The 7 countries a hotel study can be in.
//  Safe to run again: rows are matched by their number (id), so nothing is doubled
//  and nothing else in the table is touched.
//  (Names were taken from the previous CFO Hotels; stray spaces and invisible
//  direction marks were removed.)
// ══════════════════════════════════════════════════════════════════

class CountriesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('countries')->upsert([
            ['id' => 1, 'name_en' => 'Egypt', 'name_ar' => 'مصر'],
            ['id' => 2, 'name_en' => 'Saudi Arabia', 'name_ar' => 'المملكة العربية السعودية'],
            ['id' => 3, 'name_en' => 'United Arab Emirates', 'name_ar' => 'دولة الإمارات العربية المتحدة'],
            ['id' => 4, 'name_en' => 'United States', 'name_ar' => 'الولايات المتحده الأمريكيه'],
            ['id' => 5, 'name_en' => 'Kuwait', 'name_ar' => 'الكويت'],
            ['id' => 6, 'name_en' => 'Kenya', 'name_ar' => 'كينيا'],
            ['id' => 7, 'name_en' => 'Jordan', 'name_ar' => 'الأردن'],
        ], ['id'], ['name_en', 'name_ar']);
    }
}
