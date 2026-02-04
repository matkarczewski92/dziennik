<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\User;
use App\Models\Weight;
use Illuminate\Auth\Access\AuthorizationException;

class WeightService
{
    public function create(User $user, Animal $animal, array $data): Weight
    {
        $this->ensureOwnership($user, $animal);

        $weight = Weight::query()->create([
            'user_id' => $user->id,
            'animal_id' => $animal->id,
            'measured_at' => $data['measured_at'],
            'weight_grams' => $data['weight_grams'],
            'notes' => $data['notes'] ?? null,
        ]);

        $animal->forceFill([
            'current_weight_grams' => $weight->weight_grams,
        ])->save();

        return $weight;
    }

    public function delete(User $user, Weight $weight): void
    {
        if ((int) $weight->user_id !== (int) $user->id) {
            throw new AuthorizationException();
        }

        $weight->delete();
    }

    protected function ensureOwnership(User $user, Animal $animal): void
    {
        if ((int) $animal->user_id !== (int) $user->id) {
            throw new AuthorizationException();
        }
    }
}

