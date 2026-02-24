<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WeeklyClaim;

class WeeklyClaimPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['parent', 'kid', 'supervisor']);
    }

    public function view(User $user, WeeklyClaim $weeklyClaim): bool
    {
        return $user->household_id === $weeklyClaim->household_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['kid', 'supervisor']);
    }

    public function update(User $user, WeeklyClaim $weeklyClaim): bool
    {
        if ($user->household_id !== $weeklyClaim->household_id) {
            return false;
        }

        return $user->hasRole('supervisor');
    }
}
