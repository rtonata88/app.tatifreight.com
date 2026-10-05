<?php

namespace Database\Factories;

use App\Models\VehicleType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RateCard>
 */
class RateCardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehicle_type_id' => VehicleType::factory(),
            'client_id' => null,
            'name' => fake()->words(3, true).' Rate',
            'rate_type' => fake()->randomElement(['hourly', 'daily', 'per_km', 'tonnage', 'load_specific']),
            'rate' => fake()->randomFloat(2, 10, 10000),
            'includes_mdc' => false,
            'effective_from' => now()->subMonth()->format('Y-m-d'),
            'effective_to' => null,
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
