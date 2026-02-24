<?php

namespace Database\Factories;

use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PointsLedger>
 */
class PointsLedgerFactory extends Factory
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
            'user_id' => User::factory(),
            'chore_completion_id' => null,
            'awarded_by_user_id' => null,
            'points' => fake()->numberBetween(1, 25),
            'awarded_at' => now(),
            'entry_type' => 'approval',
            'description' => fake()->optional()->sentence(),
        ];
    }
}
