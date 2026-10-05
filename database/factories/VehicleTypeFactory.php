<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VehicleType>
 */
class VehicleTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Tipper', 'Cooler', 'Superlink', 'Support']),
            'description' => fake()->sentence(),
            'base_rate_daily' => fake()->randomFloat(2, 1000, 5000),
            'requires_mdc' => true,
        ];
    }
}
