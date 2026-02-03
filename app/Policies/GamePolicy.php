<?php

namespace App\Policies;

use App\Models\Game;
use App\Models\User;

class GamePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('game.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Game $game): bool
    {
        return $user->can('game.view') && $user->id === $game->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('game.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Game $game): bool
    {
        return $user->can('game.edit') && $user->id === $game->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Game $game): bool
    {
        return $user->can('game.delete') && $user->id === $game->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Game $game): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Game $game): bool
    {
        return false;
    }

    /**
     * Determine whether the user can enable/disable LLM mode.
     */
    public function enableLlm(User $user, Game $game): bool
    {
        return $user->can('game.llm') && $user->id === $game->user_id;
    }

    /**
     * Determine whether the user can play games with LLM mode.
     */
    public function useLlmMode(User $user): bool
    {
        return $user->can('game.llm-mode');
    }
}
