<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\VehicleType;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
class VehicleFactory extends Factory
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
            'reg_number' => 'N '.fake()->unique()->numerify('#####').' W',
            'make' => fake()->randomElement(['Scania', 'Volvo', 'Mercedes-Benz', 'MAN']),
            'model' => fake()->bothify('??###'),
            'year' => fake()->numberBetween(2010, 2025),
            'status' => 'available',
            'current_mileage' => fake()->numberBetween(1000, 500000),
        ];
    }
}
