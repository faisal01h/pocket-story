<?php

namespace Tests\Feature\Admin;

use App\Models\Game;
use App\Models\GameSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'Administrator']);
    }

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        User::factory()->count(5)->create();

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Users/Index'));
    }

    public function test_admin_can_view_user_details(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $user = User::factory()->create();
        $game = Game::factory()->create(['user_id' => $user->id]);
        GameSession::create(['user_id' => $user->id, 'game_id' => $game->id, 'mode' => 'llm']);

        $response = $this->actingAs($admin)->get("/admin/users/{$user->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Users/Show'));
    }

    public function test_admin_can_update_user_roles(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $user = User::factory()->create();
        $role = Role::create(['name' => 'Test Role']);

        $response = $this->actingAs($admin)->put("/admin/users/{$user->id}", [
            'roles' => ['Test Role'],
        ]);

        $response->assertRedirect();
        $this->assertTrue($user->fresh()->hasRole('Test Role'));
    }
}
