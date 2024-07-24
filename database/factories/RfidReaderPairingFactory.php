<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RfidReaderPairing>
 */
class RfidReaderPairingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pair_id' => 1,
            'reader_id' => null,
            'reader_position' => 1,
        ];
    }
}
