<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'vehicle_id' => null,
            'booking_id' => null,
            'category' => fake()->randomElement(['fuel', 'maintenance', 'repairs', 'tolls', 'insurance', 'other']),
            'amount' => fake()->randomFloat(2, 50, 5000),
            'expense_date' => fake()->dateTimeBetween('-3 months')->format('Y-m-d'),
            'description' => fake()->sentence(),
            'receipt_path' => null,
            'status' => 'pending',
            'notes' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => 'rejected',
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }
}
