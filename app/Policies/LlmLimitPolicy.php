<?php

namespace App\Policies;

use App\Models\LlmLimit;
use App\Models\User;

class LlmLimitPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('llm.limit.manage');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LlmLimit $llmLimit): bool
    {
        return $user->can('llm.limit.manage');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('llm.limit.manage');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LlmLimit $llmLimit): bool
    {
        return $user->can('llm.limit.manage');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LlmLimit $llmLimit): bool
    {
        return $user->can('llm.limit.manage');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, LlmLimit $llmLimit): bool
    {
        return $user->can('llm.limit.manage');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, LlmLimit $llmLimit): bool
    {
        return false;
    }
}
