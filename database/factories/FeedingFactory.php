<?php

namespace Database\Factories;

use App\Models\Animal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Feeding>
 */
class FeedingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'animal_id' => Animal::factory(),
            'fed_at' => fake()->date(),
            'prey' => fake()->randomElement(['mysz', 'szczur', 'przepiorka']),
            'prey_weight_grams' => fake()->randomFloat(2, 5, 300),
            'quantity' => fake()->numberBetween(1, 3),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
