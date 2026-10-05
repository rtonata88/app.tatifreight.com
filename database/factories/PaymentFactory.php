<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_reference' => 'PAY-'.fake()->unique()->numerify('######'),
            'invoice_id' => Invoice::factory(),
            'client_id' => fn (array $attributes) => Invoice::find($attributes['invoice_id'])->client_id,
            'amount' => fake()->randomFloat(2, 100, 5000),
            'payment_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
            'transaction_reference' => fake()->bothify('TX-#####'),
        ];
    }
}
