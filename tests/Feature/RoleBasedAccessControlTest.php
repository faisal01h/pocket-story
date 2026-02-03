<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\LlmLimit;
use App\Models\LlmProvider;
use App\Models\RemoteLlmRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleBasedAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create initial permissions - match RoleSeeder exactly
        $permissions = collect([
            'game.view',
            'game.edit',
            'game.create',
            'game.delete',
            'game.llm',
            'game.llm-mode',
            'user.view',
            'user.edit',
            'user.delete',
            'user.analyze',
            'privilege.manage',
            'llm.provider.manage',
            'llm.limit.manage',
            'llm.request.view',
        ]);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // Create roles and assign created permissions - match RoleSeeder
        $superAdmin = Role::findOrCreate('Super Admin');
        // Super Admin gets everything via manual policy checks, not explicit permissions

        $admin = Role::findOrCreate('Administrator');
        $admin->syncPermissions($permissions);

        $userRole = Role::findOrCreate('User');
        $userRole->syncPermissions($permissions->only([
            'game.view',
            'game.edit',
            'game.create',
            'game.delete',
        ]));

        $plusUserRole = Role::findOrCreate('PlusUser');
        $plusUserRole->syncPermissions($permissions->only([
            'game.llm',
            'game.llm-mode',
        ]));
    }

    public function test_user_can_access_game_index_with_game_view_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');

        $response = $this->actingAs($user)->get(route('games.index'));

        $response->assertStatus(200);
    }

    public function test_user_without_game_view_permission_cannot_access_game_index(): void
    {
        $user = User::factory()->create();
        // No role assigned, so no permissions

        $response = $this->actingAs($user)->get(route('games.index'));

        $response->assertStatus(403);
    }

    public function test_user_can_create_game_with_game_create_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');

        $response = $this->actingAs($user)->get(route('games.create'));

        $response->assertStatus(200);
    }

    public function test_administrator_can_view_users(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertStatus(200);
    }

    public function test_regular_user_cannot_view_users(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');

        $response = $this->actingAs($user)->get(route('admin.users.index'));

        $response->assertStatus(403);
    }

    public function test_administrator_can_view_llm_providers(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $response = $this->actingAs($admin)->get(route('admin.llm-providers.index'));

        $response->assertStatus(200);
    }

    public function test_regular_user_cannot_view_llm_providers(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');

        $response = $this->actingAs($user)->get(route('admin.llm-providers.index'));

        $response->assertStatus(403);
    }

    public function test_user_with_game_llm_permission_can_switch_to_llm_mode(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');
        $user->assignRole('PlusUser'); // Has both game operations and LLM permissions

        $game = Game::factory()->create(['user_id' => $user->id]);
        $session = $game->gameSessions()->create([
            'user_id' => $user->id,
            'mode' => 'standard',
        ]);

        $response = $this->actingAs($user)->post(route('games.play.switch-mode', [$game, $session]), [
            'mode' => 'llm',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('game_sessions', [
            'id' => $session->id,
            'mode' => 'llm',
        ]);
    }

    public function test_user_without_game_llm_permission_cannot_switch_to_llm_mode(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User'); // Only has basic game permissions, not LLM

        $game = Game::factory()->create(['user_id' => $user->id]);
        $session = $game->gameSessions()->create([
            'user_id' => $user->id,
            'mode' => 'standard',
        ]);

        $response = $this->actingAs($user)->post(route('games.play.switch-mode', [$game, $session]), [
            'mode' => 'llm',
        ]);

        $response->assertStatus(403);
    }

    public function test_administrator_has_all_defined_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        // Test access to all major features
        $gameResponse = $this->actingAs($admin)->get(route('games.index'));
        $userResponse = $this->actingAs($admin)->get(route('admin.users.index'));
        $llmProviderResponse = $this->actingAs($admin)->get(route('admin.llm-providers.index'));

        $gameResponse->assertStatus(200);
        $userResponse->assertStatus(200);
        $llmProviderResponse->assertStatus(200);
    }

    public function test_user_can_only_edit_own_game(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');

        $ownGame = Game::factory()->create(['user_id' => $user->id]);
        $otherGame = Game::factory()->create(['user_id' => User::factory()->create()->id]);

        // Can edit own game
        $ownResponse = $this->actingAs($user)->get(route ('games.edit', $ownGame));
        $ownResponse->assertStatus(200);

        // Cannot edit other user's game
        $otherResponse = $this->actingAs($user)->get(route('games.edit', $otherGame));
        $otherResponse->assertStatus(403);
    }

    public function test_administrator_can_analyze_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $targetUser = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.users.summarize', $targetUser));

        // Should redirect back (might fail AI generation, but permission should allow it)
        $response->assertRedirect();
    }

    public function test_regular_user_cannot_analyze_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');

        $targetUser = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.users.summarize', $targetUser));

        $response->assertStatus(403);
    }
}
