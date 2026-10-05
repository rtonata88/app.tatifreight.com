<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Logbook>
 */
class LogbookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->numberBetween(1000, 400000);

        return [
            'vehicle_id' => Vehicle::factory(),
            'driver_id' => User::factory(),
            'booking_id' => null,
            'date' => now()->format('Y-m-d'),
            'start_odometer' => $start,
            'end_odometer' => $start + fake()->numberBetween(10, 900),
            'origin_from' => fake()->randomElement(['Windhoek', 'Walvis Bay', 'Swakopmund', 'Oshakati']),
            'origin_to' => fake()->randomElement(['Keetmanshoop', 'Rundu', 'Lüderitz', 'Otjiwarongo']),
            'purpose' => fake()->optional()->sentence(),
            'notes' => null,
            'created_by' => User::factory(),
        ];
    }
}
