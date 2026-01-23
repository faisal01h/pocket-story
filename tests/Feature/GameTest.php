<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_displays_games()
    {
        $user = User::factory()->create();
        $games = Game::factory()->count(3)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('games.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Games/Index')
                ->has('games', 3)
            );
    }

    public function test_create_displays_form()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('games.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Games/Create')
            );
    }

    public function test_store_validates_and_creates_game()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('games.store'), [
                'title' => 'New Game',
                'description' => 'Description',
                'settings' => ['default_mode' => 'standard'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('games', [
            'title' => 'New Game',
            'user_id' => $user->id,
        ]);
    }

    public function test_edit_displays_form()
    {
        $user = User::factory()->create();
        $game = Game::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('games.edit', $game))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Games/Edit')
                ->has('game')
            );
    }

    public function test_update_validates_and_updates_game()
    {
        $user = User::factory()->create();
        $game = Game::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->put(route('games.update', $game), [
                'title' => 'Updated Title',
                'description' => 'Updated Description',
                'settings' => ['default_mode' => 'llm', 'llm_enabled' => true],
            ])
            ->assertRedirect(route('games.show', $game));

        $this->assertDatabaseHas('games', [
            'id' => $game->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_destroy_deletes_game()
    {
        $user = User::factory()->create();
        $game = Game::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete(route('games.destroy', $game))
            ->assertRedirect(route('games.index'));

        $this->assertDatabaseMissing('games', ['id' => $game->id]);
    }

    public function test_cannot_access_other_users_games()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $game = Game::factory()->create(['user_id' => $user1->id]);

        $this->actingAs($user2)
            ->get(route('games.edit', $game))
            ->assertForbidden();

        $this->actingAs($user2)
            ->put(route('games.update', $game), ['title' => 'Hacked'])
            ->assertForbidden();

        $this->actingAs($user2)
            ->delete(route('games.destroy', $game))
            ->assertForbidden();
    }
}
