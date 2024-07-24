<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RfidReaderManagement>
 */
class RfidReaderManagementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'=> fake()->regexify('[A-Z]{5}[0-9]{3}'),
            'project_id' => 1,
            'location_1_id' => 1,
            'location_2_id' => 1,
            'location_3_id' => null,
            'location_4_id' => null,
            'used_for_attendance' => true,
        ];
    }
}
