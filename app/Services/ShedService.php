<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\Shed;
use App\Models\User;
use App\Services\Animal\AnimalEventProjector;
use Illuminate\Auth\Access\AuthorizationException;

class ShedService
{
    public function __construct(
        protected AnimalEventProjector $eventProjector,
    ) {
    }

    public function create(User $user, Animal $animal, array $data): Shed
    {
        $this->ensureOwnership($user, $animal);

        $shed = Shed::query()->create([
            'user_id' => $user->id,
            'animal_id' => $animal->id,
            'shed_at' => $data['shed_at'],
            'quality' => $data['quality'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->eventProjector->projectShed($shed);

        return $shed;
    }

    public function delete(User $user, Shed $shed): void
    {
        if ((int) $shed->user_id !== (int) $user->id) {
            throw new AuthorizationException();
        }

        $shed->delete();
        $this->eventProjector->removeShed($shed);
    }

    public function update(User $user, Shed $shed, array $data): Shed
    {
        if ((int) $shed->user_id !== (int) $user->id) {
            throw new AuthorizationException();
        }

        $shed->update([
            'shed_at' => $data['shed_at'],
            'quality' => $data['quality'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->eventProjector->projectShed($shed);

        return $shed->refresh();
    }

    protected function ensureOwnership(User $user, Animal $animal): void
    {
        if ((int) $animal->user_id !== (int) $user->id) {
            throw new AuthorizationException();
        }
    }
}
