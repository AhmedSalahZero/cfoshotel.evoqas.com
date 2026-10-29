<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// The tables exist with exactly the agreed columns. Run:  php artisan test --filter=DatabaseStructureTest
// (The hotel tables are added, with their own checks, by the Financial Planning module.)
class DatabaseStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_company_tables_have_the_agreed_columns(): void
    {
        $agreed = [
            'companies' => 'id,name_en,name_ar,status,users_limit,subscription_starts_at,subscription_ends_at,contact_phone,contact_email,notes,created_by,created_at,updated_at',
            'users'     => 'id,company_id,name,email,email_verified_at,password,role,job_title,language,theme,is_active,created_by,last_login_at,remember_token,created_at,updated_at',
        ];

        foreach ($agreed as $table => $columns) {
            $want = explode(',', $columns);
            $have = Schema::getColumnListing($table);
            sort($want);
            sort($have);

            $this->assertSame($want, $have, "table '{$table}' does not have the agreed columns");
        }
    }

    public function test_the_accounts_and_system_tables_are_there(): void
    {
        foreach (['users', 'companies', 'password_reset_tokens', 'sessions', 'cache', 'jobs', 'failed_jobs'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "table '{$table}' is missing");
        }
    }

    public function test_a_company_that_has_people_cannot_be_deleted_by_accident(): void
    {
        $user = User::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('companies')->where('id', $user->company_id)->delete();
    }

    public function test_a_person_must_belong_to_a_real_company(): void
    {
        $this->expectException(QueryException::class);

        DB::table('users')->insert(['name' => 'Orphan', 'email' => 'orphan@x.test', 'password' => 'x', 'company_id' => 999999]);
    }
}
