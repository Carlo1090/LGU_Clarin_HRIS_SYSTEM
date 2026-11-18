<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_each_approved_role_has_an_initial_dashboard(): void
    {
        $this->withoutVite();
        $this->seed();

        foreach (Role::query()->get() as $role) {
            $this->actingAs(User::factory()->for($role)->create())
                ->get(route('dashboard'))->assertOk()
                ->assertInertia(fn ($page) => $page->component('dashboard')
                    ->where('role', $role->name)
                    ->has('cards', match ($role->name) {
                        'Treasurer', 'DPO' => 3,
                        'MIS Administrator' => 5,
                        default => 4,
                    })
                );
        }
    }
}
