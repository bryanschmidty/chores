<?php

namespace Database\Factories;

use App\Models\ChoreTemplate;
use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WeeklyClaim>
 */
class WeeklyClaimFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $weekStart = now()->startOfWeek()->toDateString();
        $weekEnd = now()->endOfWeek()->toDateString();

        return [
            'household_id' => Household::factory(),
            'chore_template_id' => ChoreTemplate::factory(),
            'assigned_to_user_id' => User::factory(),
            'claimed_by_user_id' => User::factory(),
            'overridden_by_user_id' => null,
            'week_start_at' => $weekStart,
            'week_end_at' => $weekEnd,
        ];
    }
}
