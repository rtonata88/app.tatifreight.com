<?php

namespace Database\Factories;

use App\Models\Logbook;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MdcCalculation>
 */
class MdcCalculationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $distance = fake()->randomFloat(2, 50, 1500);
        $gvm = fake()->randomFloat(2, 9, 56);

        return [
            'vehicle_id' => Vehicle::factory(),
            // A logbook without an end odometer, so its own MDC auto-calculation does not run.
            'logbook_id' => fn (array $attributes) => Logbook::create([
                'vehicle_id' => $attributes['vehicle_id'],
                'driver_id' => User::factory()->create()->id,
                'created_by' => User::factory()->create()->id,
                'date' => $attributes['calculation_date'] ?? now()->toDateString(),
                'start_odometer' => 1000,
                'origin_from' => fake()->city(),
                'origin_to' => fake()->city(),
            ])->id,
            'gvm_tonnes' => $gvm,
            'distance_km' => $distance,
            'mdc_amount' => round($distance / 100 * 250, 2),
            'calculation_date' => now()->toDateString(),
            'payment_status' => 'unpaid',
            'amount_paid' => 0,
        ];
    }

    public function partiallyPaid(float $amountPaid): static
    {
        return $this->state(fn () => ['payment_status' => 'partially_paid', 'amount_paid' => $amountPaid]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => ['payment_status' => 'paid', 'amount_paid' => $attributes['mdc_amount']]);
    }
}
