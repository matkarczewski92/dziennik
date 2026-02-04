<?php

namespace Database\Factories;

use App\Models\Animal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Photo>
 */
class PhotoFactory extends Factory
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
            'path' => 'animals/'.$this->faker->numberBetween(1, 100).'/'.$this->faker->uuid().'.webp',
            'mime_type' => 'image/webp',
            'size_kb' => fake()->numberBetween(100, 3500),
            'taken_at' => fake()->optional()->date(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
