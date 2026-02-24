<?php

namespace App\Policies;

use App\Models\ChoreTemplate;
use App\Models\User;

class ChoreTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('parent');
    }

    public function view(User $user, ChoreTemplate $choreTemplate): bool
    {
        if ($user->household_id !== $choreTemplate->household_id) {
            return false;
        }

        return $user->hasRole('parent');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('parent');
    }

    public function update(User $user, ChoreTemplate $choreTemplate): bool
    {
        if ($user->household_id !== $choreTemplate->household_id) {
            return false;
        }

        return $user->hasRole('parent');
    }

    public function delete(User $user, ChoreTemplate $choreTemplate): bool
    {
        return $this->update($user, $choreTemplate);
    }
}
