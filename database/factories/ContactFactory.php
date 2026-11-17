<?php

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title'       => fake()->words(2, true),
            'description' => fake()->sentence(),
            'icon'        => fake()->randomElement(['phone', 'email', 'location', 'clock']),
            'category'    => 'info',
            'sort_order'  => 0,
        ];
    }
}
