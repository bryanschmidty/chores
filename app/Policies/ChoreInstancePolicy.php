<?php

namespace App\Policies;

use App\Enums\ChoreInstanceStatus;
use App\Models\ChoreInstance;
use App\Models\User;

class ChoreInstancePolicy
{
    public function assign(User $user, ChoreInstance $choreInstance): bool
    {
        if (! $user->hasRole('supervisor')) {
            return false;
        }

        return $choreInstance->household_id === $user->household_id;
    }

    public function claim(User $user, ChoreInstance $choreInstance): bool
    {
        if ($user->household_id !== $choreInstance->household_id) {
            return false;
        }

        if ($choreInstance->assigned_to_user_id !== null) {
            return false;
        }

        $status = $choreInstance->status;
        $statusValue = $status instanceof ChoreInstanceStatus ? $status->value : (string) $status;

        return in_array($statusValue, [ChoreInstanceStatus::Due->value, ChoreInstanceStatus::Overdue->value], true);
    }

    public function update(User $user, ChoreInstance $choreInstance): bool
    {
        return $this->assign($user, $choreInstance);
    }

    public function view(User $user, ChoreInstance $choreInstance): bool
    {
        return $choreInstance->household_id === $user->household_id;
    }
}
