<?php

namespace Database\Factories;

use App\Enums\ApprovalStatus;
use App\Models\ChoreInstance;
use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChoreCompletion>
 */
class ChoreCompletionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'chore_instance_id' => ChoreInstance::factory(),
            'completed_by_user_id' => User::factory(),
            'completed_at' => now(),
            'approval_status' => ApprovalStatus::Pending->value,
            'approved_by_user_id' => null,
            'approved_at' => null,
            'supervisor_adjusted_points' => null,
            'approval_comment' => null,
            'rejection_reason' => null,
        ];
    }
}
