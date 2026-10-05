<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CompanyBankAccount>
 */
class CompanyBankAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bank_name' => fake()->randomElement(['Bank Windhoek', 'FNB Namibia', 'Standard Bank Namibia', 'Nedbank Namibia']),
            'account_name' => fake()->company(),
            'account_number' => fake()->numerify('##########'),
            'branch_name' => fake()->city(),
            'branch_code' => fake()->numerify('######'),
            'swift_code' => null,
            'currency' => 'NAD',
            'is_primary' => false,
            'is_active' => true,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
