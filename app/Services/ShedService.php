<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\Shed;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class ShedService
{
    public function create(User $user, Animal $animal, array $data): Shed
    {
        $this->ensureOwnership($user, $animal);

        return Shed::query()->create([
            'user_id' => $user->id,
            'animal_id' => $animal->id,
            'shed_at' => $data['shed_at'],
            'quality' => $data['quality'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function delete(User $user, Shed $shed): void
    {
        if ((int) $shed->user_id !== (int) $user->id) {
            throw new AuthorizationException();
        }

        $shed->delete();
    }

    protected function ensureOwnership(User $user, Animal $animal): void
    {
        if ((int) $animal->user_id !== (int) $user->id) {
            throw new AuthorizationException();
        }
    }
}

