<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quote>
 */
class QuoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 1000, 50000);
        $tax = round($subtotal * 0.15, 2);

        return [
            'quote_number' => 'QT-'.fake()->unique()->numerify('######'),
            'client_id' => fn () => Client::create([
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'is_active' => true,
            ])->id,
            'created_by' => User::factory(),
            'version' => 1,
            'status' => 'draft',
            'valid_until' => now()->addDays(30)->toDateString(),
            'description' => fake()->sentence(),
            'terms_conditions' => null,
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'total' => $subtotal + $tax,
            'notes' => null,
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
