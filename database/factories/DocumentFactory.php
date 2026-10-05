<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->lexify('document-????').'.pdf';

        return [
            'title' => fake()->sentence(3),
            'category' => fake()->randomElement(['contract', 'license', 'insurance', 'receipt', 'invoice', 'quote', 'other']),
            'file_path' => 'documents/'.$name,
            'file_name' => $name,
            'file_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(10_000, 2_000_000),
            'uploaded_by' => User::factory(),
            'version' => 1,
        ];
    }
}
