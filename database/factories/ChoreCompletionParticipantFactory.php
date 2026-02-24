<?php

namespace Database\Factories;

use App\Models\ChoreCompletion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChoreCompletionParticipant>
 */
class ChoreCompletionParticipantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chore_completion_id' => ChoreCompletion::factory(),
            'user_id' => User::factory(),
            'is_primary' => false,
            'points_awarded' => fake()->numberBetween(0, 20),
        ];
    }
}
