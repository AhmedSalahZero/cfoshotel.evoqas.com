<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\CompanyService;
use Database\Seeders\HotelCompanyTypesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// Every company starts with the standard room, food and beverage, gaming, meeting and other types.
// Run:  php artisan test --filter=HotelCompanyTypesTest
class HotelCompanyTypesTest extends TestCase
{
    use RefreshDatabase;

    /** relation => [table, number of standard rows, first title] */
    private const LISTS = [
        'roomNames'    => ['room_names', 14, 'Total Rooms'],
        'foodNames'    => ['food_names', 4, 'Total F&B Facility'],
        'casinoNames'  => ['casino_names', 3, 'Total Gaming Facilities'],
        'meetingNames' => ['meeting_names', 3, 'Total Meeting Spaces Facilities'],
        'otherNames'   => ['other_names', 4, 'Total Other Revenues Facilities'],
    ];

    private function plainCompany(string $name = 'Nile Hotels'): Company
    {
        return Company::create(['name_en' => $name, 'name_ar' => $name, 'status' => 'active', 'users_limit' => 5]);
    }

    public function test_a_new_company_starts_with_the_standard_lists(): void
    {
        $creator = User::query()->forceCreate(['name' => 'Platform', 'email' => 'root@platform.test', 'password' => 'x', 'role' => User::ROLE_SUPER_ADMIN]);

        $company = app(CompanyService::class)->createWithAdmin([
            'name_en' => 'Delta Hotels', 'name_ar' => 'دلتا للفنادق', 'status' => 'trial', 'users_limit' => 5,
            'admin_name' => 'Sara Admin', 'admin_email' => 'sara@delta.test', 'admin_password' => 'Strong-Pass-2026',
        ], $creator);

        foreach (self::LISTS as $relation => [$table, $count, $first]) {
            $this->assertSame($count, DB::table($table)->where('company_id', $company->id)->count(), "{$table}: number of rows");
            $this->assertSame($first, $company->{$relation}()->orderBy('id')->first()->title, "{$table}: first row");
            $this->assertSame(1, (int) DB::table($table)->where('company_id', $company->id)->where('is_active', 1)->where('title', $first)->count());
        }
    }

    public function test_a_company_only_gets_its_own_lists(): void
    {
        $one = $this->plainCompany('One');
        $two = $this->plainCompany('Two');

        $one->insertBasicSelects();

        $this->assertSame(14, $one->roomNames()->count());
        $this->assertSame(0, $two->roomNames()->count());
    }

    public function test_running_the_defaults_again_adds_nothing(): void
    {
        $company = $this->plainCompany();

        $company->insertBasicSelects();
        $company->insertBasicSelects();

        foreach (self::LISTS as [$table, $count]) {
            $this->assertSame($count, DB::table($table)->where('company_id', $company->id)->count(), $table);
        }
    }

    public function test_a_list_that_already_has_rows_is_left_alone(): void
    {
        $company = $this->plainCompany();
        DB::table('room_names')->insert(['title' => 'Total Rooms', 'company_id' => $company->id, 'is_active' => 1]);
        DB::table('room_names')->insert(['title' => 'Villa', 'company_id' => $company->id, 'is_active' => 1]);

        $company->insertBasicSelects();

        $this->assertSame(2, $company->roomNames()->count(), 'the rooms the company already had are kept as they are');
        $this->assertSame(4, $company->foodNames()->count(), 'the empty lists are filled');
    }

    public function test_the_seeder_gives_existing_companies_their_lists(): void
    {
        $first = $this->plainCompany('First');
        $second = $this->plainCompany('Second');

        $this->seed(HotelCompanyTypesSeeder::class);
        $this->seed(HotelCompanyTypesSeeder::class);

        foreach ([$first, $second] as $company) {
            $this->assertSame(14, $company->roomNames()->count());
            $this->assertSame(4, $company->otherNames()->count());
        }
    }

    public function test_the_total_row_is_the_first_row_and_is_left_out_of_the_choices(): void
    {
        $company = $this->plainCompany();
        $company->insertBasicSelects();

        foreach (['Room', 'Food', 'Casino', 'Meeting', 'Other'] as $kind) {
            $total = $company->{"getTotal{$kind}Id"}();
            $all = $company->{lcfirst($kind).'Names'}()->orderBy('id')->get();

            $this->assertSame((int) $all->first()->id, (int) $total, "{$kind}: the Total row comes first");
            $this->assertFalse($company->{"get{$kind}ExceptTotal"}()->contains('id', $total));
            $this->assertSame($all->count() - 1, $company->{"get{$kind}ExceptTotal"}()->count());
        }
    }

    public function test_the_total_row_is_found_even_when_the_titles_are_not_in_english(): void
    {
        $company = $this->plainCompany();
        DB::table('room_names')->insert(['title' => 'إجمالي الغرف', 'company_id' => $company->id, 'is_active' => 1]);
        DB::table('room_names')->insert(['title' => 'غرفة عادية', 'company_id' => $company->id, 'is_active' => 1]);

        $firstId = (int) $company->roomNames()->orderBy('id')->value('id');

        $this->assertSame($firstId, (int) $company->getTotalRoomId());
        $this->assertSame(1, $company->getRoomExceptTotal()->count());
    }
}
