<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// The models match the tables and each other. Run:  php artisan test --filter=ModelsTest
// (The hotel models are added, with their own checks, by the Financial Planning module.)
class ModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_model_points_to_a_real_table_and_only_casts_real_columns(): void
    {
        foreach ([User::class, Company::class] as $class) {
            $model = new $class();
            $this->assertTrue(Schema::hasTable($model->getTable()), "{$class}: table {$model->getTable()} is missing");

            $columns = Schema::getColumnListing($model->getTable());
            foreach (array_keys($model->getCasts()) as $cast) {
                $this->assertContains($cast, $columns, "{$class}: cast '{$cast}' is not a column");
            }
            foreach ($model->getFillable() as $fillable) {
                $this->assertContains($fillable, $columns, "{$class}: fillable '{$fillable}' is not a column");
            }
        }
    }

    public function test_a_company_and_its_people_see_each_other(): void
    {
        $company = Company::factory()->create();
        $person = User::factory()->forCompany($company)->create();

        $this->assertInstanceOf(HasMany::class, $company->users());
        $this->assertInstanceOf(BelongsTo::class, $person->company());
        $this->assertTrue($company->users->contains($person));
        $this->assertTrue($person->company->is($company));
    }

    public function test_a_company_counts_only_its_active_people_against_its_limit(): void
    {
        $company = Company::factory()->create(['users_limit' => 2]);
        User::factory()->forCompany($company)->create();
        User::factory()->forCompany($company)->suspended()->create();

        $this->assertSame(1, $company->activeUsersCount());
        $this->assertTrue($company->hasRoomForAnotherUser());

        User::factory()->forCompany($company)->create();

        $this->assertFalse($company->fresh()->hasRoomForAnotherUser());
    }

    public function test_the_roles_are_told_apart(): void
    {
        $this->assertTrue(User::factory()->superAdmin()->make()->isSuperAdmin());
        $this->assertTrue(User::factory()->companyAdmin()->make()->isCompanyAdmin());
        $this->assertFalse(User::factory()->make()->isCompanyAdmin());
    }
}
