<?php

namespace Database\Factories;

use App\Enums\RecurrenceType;
use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChoreTemplate>
 */
class ChoreTemplateFactory extends Factory
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
            'created_by_user_id' => User::factory(),
            'default_assignee_user_id' => null,
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'points' => fake()->numberBetween(1, 20),
            'recurrence_type' => fake()->randomElement([
                RecurrenceType::Daily,
                RecurrenceType::Weekly,
                RecurrenceType::EveryNDays,
                RecurrenceType::Weekdays,
            ])->value,
            'recurrence_interval' => fake()->numberBetween(1, 7),
            'recurrence_weekdays' => null,
            'is_active' => true,
            'last_completed_at' => null,
        ];
    }
}
