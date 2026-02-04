<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\Feeding;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class FeedingService
{
    public function create(User $user, Animal $animal, array $data): Feeding
    {
        $this->ensureOwnership($user, $animal);

        $feeding = Feeding::query()->create([
            'user_id' => $user->id,
            'animal_id' => $animal->id,
            'fed_at' => $data['fed_at'],
            'prey' => $data['prey'],
            'prey_weight_grams' => $data['prey_weight_grams'] ?? null,
            'quantity' => (int) ($data['quantity'] ?? 1),
            'notes' => $data['notes'] ?? null,
        ]);

        $animal->forceFill([
            'last_fed_at' => $feeding->fed_at,
        ])->save();

        return $feeding;
    }

    public function delete(User $user, Feeding $feeding): void
    {
        if ((int) $feeding->user_id !== (int) $user->id) {
            throw new AuthorizationException();
        }

        $feeding->delete();
    }

    protected function ensureOwnership(User $user, Animal $animal): void
    {
        if ((int) $animal->user_id !== (int) $user->id) {
            throw new AuthorizationException();
        }
    }
}

