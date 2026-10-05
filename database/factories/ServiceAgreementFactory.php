<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceAgreement>
 */
class ServiceAgreementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // ClientFactory is owned by another module; pass client_id explicitly if it is still empty.
            'client_id' => Client::factory(),
            'agreement_number' => 'SA-'.fake()->unique()->numerify('######'),
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addYear()->format('Y-m-d'),
            'terms' => fake()->paragraph(),
            'document_path' => null,
            'status' => 'draft',
        ];
    }
}
