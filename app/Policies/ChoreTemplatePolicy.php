<?php

namespace App\Policies;

use App\Models\ChoreTemplate;
use App\Models\User;

class ChoreTemplatePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->canManageFamily();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ChoreTemplate $choreTemplate): bool
    {
        return $user->family_id === $choreTemplate->family_id && $user->canManageFamily();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->canManageFamily();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ChoreTemplate $choreTemplate): bool
    {
        return $user->family_id === $choreTemplate->family_id && $user->canManageFamily();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ChoreTemplate $choreTemplate): bool
    {
        return $user->family_id === $choreTemplate->family_id && $user->canManageFamily();
    }
}