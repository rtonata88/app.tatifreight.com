<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MdcPayment>
 */
class MdcPaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_reference' => 'MDC-'.fake()->unique()->numerify('######'),
            'payment_date' => now()->toDateString(),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'payment_method' => 'bank_transfer',
            'bank_reference' => fake()->bothify('TXN-######'),
            'receipt_path' => null,
            'notes' => null,
            'expense_id' => null,
            'created_by' => User::factory(),
        ];
    }
}
