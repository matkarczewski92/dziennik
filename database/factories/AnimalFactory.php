<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Animal>
 */
class AnimalFactory extends Factory
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
            'name' => fake()->firstName(),
            'species' => fake()->randomElement(['Python regius', 'Boa constrictor', 'Morelia spilota']),
            'morph' => fake()->randomElement(['Normal', 'Albino', 'Piebald', 'Mojave']),
            'sex' => fake()->randomElement(['male', 'female', 'unknown']),
            'hatch_date' => fake()->date(),
            'acquired_at' => fake()->date(),
            'current_weight_grams' => fake()->randomFloat(2, 50, 5000),
            'feeding_interval_days' => fake()->numberBetween(5, 21),
            'last_fed_at' => fake()->date(),
            'secret_tag' => strtoupper(fake()->bothify('TAG###??')),
            'remote_id' => (string) fake()->numberBetween(1000, 9999),
            'imported_from_api' => false,
            'api_snapshot' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
