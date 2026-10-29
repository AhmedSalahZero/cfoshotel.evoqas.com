<?php

namespace Tests\Support;

use Database\Seeders\CountriesSeeder;
use Database\Seeders\CurrenciesSeeder;
use Database\Seeders\StatesSeeder;
use Illuminate\Support\Facades\DB;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Five real hotel studies for the tests
//  Location: tests/Support/HotelStudies.php   (data: tests/Fixtures/hotel/studies.json)
//
//  The studies are real ones of the previous CFO Hotels (anonymised), with every row that belongs to them:
//  rooms, food and beverage, gaming, meetings, other revenue, expenses, management fees, land, property,
//  furniture and equipment, loans. Rows of studies that no longer existed in the old database are left out.
//  Study numbers: 305, 306, 307, 313, 314. Companies: 40, 41, 42. People: 1 and 3.
// ══════════════════════════════════════════════════════════════════

final class HotelStudies
{
    public const STUDY_IDS = [305, 306, 307, 313, 314];

    /** @return array{companies: list<int>, users: list<int>, tables: array<string, list<array<string, mixed>>>} */
    public static function data(): array
    {
        static $data = null;

        return $data ??= json_decode((string) file_get_contents(dirname(__DIR__).'/Fixtures/hotel/studies.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    /** Puts the lists (countries, states, currencies), the companies, the people and the five studies in the database. */
    public static function load(): void
    {
        (new CountriesSeeder)->run();
        (new StatesSeeder)->run();
        (new CurrenciesSeeder)->run();

        $data = self::data();

        foreach ($data['companies'] as $id) {
            DB::table('companies')->insert(['id' => $id, 'name_en' => "Company {$id}", 'name_ar' => "شركة {$id}", 'status' => 'active']);
        }
        foreach ($data['users'] as $id) {
            DB::table('users')->insert(['id' => $id, 'name' => "Person {$id}", 'email' => "person{$id}@fixture.test", 'password' => 'not-a-real-password', 'company_id' => $data['companies'][0], 'role' => 'user']);
        }
        foreach ($data['tables'] as $table => $rows) {
            foreach (array_chunk($rows, 50) as $chunk) {
                DB::table($table)->insert($chunk);
            }
        }
    }

    /** @return list<array<string, mixed>> the rows of one table as they were saved */
    public static function rows(string $table): array
    {
        return self::data()['tables'][$table] ?? [];
    }
}
