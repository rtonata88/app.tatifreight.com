<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'company_name' => fake()->optional()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('+264 81 ### ####'),
            'address' => fake()->streetAddress(),
            'city' => fake()->randomElement(['Windhoek', 'Walvis Bay', 'Swakopmund', 'Oshakati']),
            'province' => fake()->randomElement(['Khomas', 'Erongo', 'Oshana']),
            'postal_code' => fake()->numerify('9###'),
            'classification' => 'adhoc',
            'credit_limit' => 0,
            'payment_terms_days' => 30,
            'is_active' => true,
        ];
    }

    public function contract(): static
    {
        return $this->state(fn () => ['classification' => 'contract']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
