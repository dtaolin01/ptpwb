<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title'       => fake()->words(3, true),
            'description' => fake()->sentence(),
            'category'    => 'service',
        ];
    }

    public function fleet(): static
    {
        return $this->state(fn () => [
            'category'     => 'fleet',
            'suitable_for' => fake()->sentence(),
            'capacity'     => 'Hingga '.fake()->numberBetween(4, 25).' ton',
            'advantage'    => fake()->sentence(),
        ]);
    }
}
