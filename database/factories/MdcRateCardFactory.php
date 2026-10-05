<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MdcRateCard>
 */
class MdcRateCardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $min = fake()->randomElement([3.5, 9, 16, 24, 32]);

        return [
            'category_name' => "Category ({$min}t+)",
            'min_gvm_tonnes' => $min,
            'max_gvm_tonnes' => $min + 7,
            'rate_per_100km' => fake()->randomFloat(2, 50, 600),
            'effective_from' => now()->subYear()->toDateString(),
            'effective_to' => null,
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
