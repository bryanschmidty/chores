<?php

namespace Database\Factories;

use App\Enums\ChoreInstanceStatus;
use App\Enums\ChoreSourceType;
use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChoreInstance>
 */
class ChoreInstanceFactory extends Factory
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
            'chore_template_id' => null,
            'created_by_user_id' => User::factory(),
            'assigned_to_user_id' => null,
            'claimed_by_user_id' => null,
            'source_type' => ChoreSourceType::AdHoc->value,
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'due_at' => now()->addDay(),
            'deadline_at' => now()->addDays(2),
            'claimed_at' => null,
            'base_points' => fake()->numberBetween(1, 20),
            'adjusted_points' => null,
            'status' => ChoreInstanceStatus::Due->value,
        ];
    }
}
