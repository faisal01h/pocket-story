<?php

namespace App\Policies;

use App\Models\RemoteLlmRequest;
use App\Models\User;

class LlmRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('llm.request.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RemoteLlmRequest $remoteLlmRequest): bool
    {
        return $user->can('llm.request.view');
    }
}
