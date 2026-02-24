<?php

namespace App\Policies;

use App\Models\ChoreCompletion;
use App\Models\User;

class ChoreCompletionPolicy
{
    public function reject(User $user, ChoreCompletion $choreCompletion): bool
    {
        return $this->approve($user, $choreCompletion);
    }

    public function approve(User $user, ChoreCompletion $choreCompletion): bool
    {
        if (! $user->hasRole('supervisor')) {
            return false;
        }

        if ($user->household_id !== $choreCompletion->household_id) {
            return false;
        }

        return $user->id !== $choreCompletion->completed_by_user_id;
    }

    public function view(User $user, ChoreCompletion $choreCompletion): bool
    {
        return $choreCompletion->household_id === $user->household_id;
    }
}
