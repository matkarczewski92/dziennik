<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Feed>
 */
class FeedFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Mysz 10-16g', 'Mysz 16-22g', 'Szczur 5-9g']),
            'feeding_interval' => fake()->numberBetween(5, 14),
            'amount' => fake()->numberBetween(0, 200),
            'last_price' => fake()->randomFloat(2, 0.50, 10.00),
        ];
    }
}

