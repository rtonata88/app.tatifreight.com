<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 500, 20000);
        $tax = round($subtotal * 0.15, 2);
        $total = $subtotal + $tax;

        return [
            'invoice_number' => 'INV-'.fake()->unique()->numerify('######'),
            // Client's factory belongs to another module; create the row directly.
            'client_id' => fn () => Client::create([
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'is_active' => true,
            ])->id,
            'created_by' => User::factory(),
            'status' => 'draft',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'description' => fake()->sentence(),
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'total' => $total,
            'amount_paid' => 0,
            'amount_due' => $total,
        ];
    }
}
