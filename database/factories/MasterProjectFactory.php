<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MasterProject>
 */
class MasterProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'daily_period_from' => now()->startOfHour()->toTimeString(),
            'daily_period_to' => now()->startOfHour()->subMinute()->toTimeString(),
        ];
    }
}
