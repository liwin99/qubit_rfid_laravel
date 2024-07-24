<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkerTimeLog>
 */
class WorkerTimeLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reader_1_id' => 1,
            'reader_1_name' => null,
            'reader_2_id' => 1,
            'reader_2_name' => null,
            'epc' => fake()->regexify('[A-Z]{5}[0-9]{3}'),
            'project_id' => 1,
            'project_name' => null,
            'clock_in' => now(),
            'clock_out' => now(),
            'period' => now(),
            'last_tag_read' => null,
        ];
    }
}
