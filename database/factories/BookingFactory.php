<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 month', '+1 month');

        return [
            'booking_number' => 'BKG-'.fake()->unique()->numerify('######'),
            // ClientFactory belongs to another module, so build the client directly.
            'client_id' => Client::factory(),
            'vehicle_id' => Vehicle::factory(),
            'status' => 'pending',
            'start_date' => $start,
            'end_date' => (clone $start)->modify('+2 days'),
            'pickup_location' => fake()->city(),
            'delivery_location' => fake()->city(),
            'is_recurring' => false,
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
