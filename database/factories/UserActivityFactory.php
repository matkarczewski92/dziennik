<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserActivity>
 */
class UserActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'causer_id' => User::factory(),
            'acted_as_id' => User::factory(),
            'action' => fake()->randomElement([
                'auth.login',
                'auth.logout',
                'admin.user.block',
                'admin.user.unblock',
                'admin.impersonation.start',
                'admin.impersonation.stop',
            ]),
            'subject_type' => null,
            'subject_id' => null,
            'meta' => null,
            'created_at' => now(),
        ];
    }
}
