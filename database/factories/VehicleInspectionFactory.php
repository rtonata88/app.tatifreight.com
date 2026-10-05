<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VehicleInspection>
 */
class VehicleInspectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'inspector_id' => User::factory(),
            'inspection_date' => fake()->dateTimeBetween('-6 months')->format('Y-m-d'),
            'inspection_type' => fake()->randomElement(['pre_trip', 'post_trip', 'maintenance', 'annual']),
            'mileage' => fake()->numberBetween(1000, 500000),
            'checklist' => null,
            'passed' => true,
            'issues_found' => null,
            'recommendations' => null,
            'photos' => null,
        ];
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'passed' => false,
            'issues_found' => fake()->sentence(),
        ]);
    }
}
