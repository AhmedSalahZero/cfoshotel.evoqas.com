<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

// The first screen after signing in. Run:  php artisan test --filter=HomeScreenTest
class HomeScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_the_signed_in_person(): void
    {
        $user = User::factory()->create(['name' => 'Mona Ali']);

        $this->actingAs($user)->get('/home')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('auth.user.name', 'Mona Ali')
            ->where('auth.user.initials', 'MA')
            ->where('auth.user.email', $user->email)
            ->where('app.name', 'CFO Hotels')
            ->has('flash')
            ->has('timezone'));
    }

    public function test_the_password_never_reaches_the_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/home');

        $response->assertDontSee($user->password, false);
        $response->assertInertia(fn (Assert $page) => $page->missing('auth.user.password'));
    }

    public function test_the_page_title_is_the_application_name(): void
    {
        $this->actingAs(User::factory()->create())->get('/home')->assertSee('CFO Hotels', false);
    }

    public function test_the_super_admin_goes_straight_to_the_companies(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())->get('/home')->assertRedirect(route('admin.companies.index'));
    }

    public function test_home_gets_the_count_and_the_recent_studies(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/home')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Home')->where('studyCount', 0)->has('recent', 0)->has('canCreate'));
    }
}
