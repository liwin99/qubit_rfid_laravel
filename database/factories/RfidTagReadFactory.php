<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RfidTagRead>
 */
class RfidTagReadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reader_name' => 'TestReader',
            'ip_address' => fake()->ipv4(),
            'epc' => 'TestEPC',
            'read_count' => fake()->numberBetween(0, 1000),
            'tag_read_datetime' => now(),
            'first_seen_timestamp' => time(),
            'last_seen_timestamp' => time(),
            'unique_hash' => hash('sha256', fake()->numberBetween(0, 2147483647)),
        ];
    }
}
